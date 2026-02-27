<?php
require_once __DIR__ . '/../bootstrap.php';

// Try to load new AuthService if available, otherwise fallback to old method
$useNewAuth = false;
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Services\AuthService')) {
        $useNewAuth = true;
    }
}

// Fallback to old db.php if new system not available
if (!$useNewAuth) {
    require_once __DIR__ . '/../php/db.php';
}

session_start();

// Load CSRF middleware if available
$csrfAvailable = class_exists('Middleware\CsrfMiddleware');

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate CSRF token if available
    if ($csrfAvailable && !\Middleware\CsrfMiddleware::validateToken()) {
        $error = "Invalid security token. Please try again.";
    } else {
        // Capture input safely
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Email and password are required.";
        } else {
            if ($useNewAuth) {
                // Use new AuthService
                $authService = new \Services\AuthService();
                $userData = $authService->authenticate($email, $password);
                
                if ($userData !== false) {
                    $authService->createSession($userData);
                    
                    // Redirect based on user type
                    if (isset($userData['user_type']) && $userData['user_type'] === 'external') {
                        header("Location: create_ticket.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                // Fallback to old authentication method (with password hashing support)
                $sql = "SELECT * FROM tbl_technician WHERE email=? AND status='active'";
                $stmt = $conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stmt->close();

                    if ($row) {
                        // Support both hashed and plain text passwords (for migration)
                        $passwordValid = false;
                        if (strlen($row['password']) >= 60) {
                            // Hashed password
                            $passwordValid = password_verify($password, $row['password']);
                        } else {
                            // Plain text (for backward compatibility)
                            $passwordValid = hash_equals($row['password'], $password);
                        }

                        if ($passwordValid) {
                            $_SESSION['role'] = "technician";
                            $_SESSION['id'] = $row['technician_id'];
                            $_SESSION['technician_id'] = $row['technician_id'];
                            $_SESSION['name'] = $row['name'];
                            header("Location: dashboard.php");
                            exit();
                        }
                    }
                }

                // Check user table
                if (empty($error)) {
                    $sql = "SELECT * FROM tbl_user WHERE email=? AND status='active'";
                    $stmt = $conn->prepare($sql);
                    if ($stmt) {
                        $stmt->bind_param("s", $email);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $row = $result->fetch_assoc();
                        $stmt->close();

                        if ($row) {
                            // Support both hashed and plain text passwords (for migration)
                            $passwordValid = false;
                            if (strlen($row['password']) >= 60) {
                                // Hashed password
                                $passwordValid = password_verify($password, $row['password']);
                            } else {
                                // Plain text (for backward compatibility)
                                $passwordValid = hash_equals($row['password'], $password);
                            }

                            if ($passwordValid) {
                                // Check user role for access control
                                if ($row['user_role'] === 'department_head') {
                                    $_SESSION['role'] = "department_head";
                                } elseif ($row['user_role'] === 'admin') {
                                    $_SESSION['role'] = "admin";
                                } else {
                                    $_SESSION['role'] = "user";
                                }
                                $_SESSION['id'] = $row['user_id'];
                                $_SESSION['name'] = $row['name'];
                                $_SESSION['user_type'] = $row['user_type'];
                                $_SESSION['department_id'] = $row['department_id'];
                                
                                // Redirect based on user type
                                if ($row['user_type'] === 'external') {
                                    header("Location: create_ticket.php");
                                } else {
                                    header("Location: dashboard.php");
                                }
                                exit();
                            }
                        }
                    }
                }

                if (empty($error)) {
                    $error = "Invalid email or password.";
                }
            }
        }
    }
}

// Generate CSRF token for form
$csrfToken = $csrfAvailable ? \Middleware\CsrfMiddleware::generateToken() : '';
$csrfTokenName = $csrfAvailable ? \Middleware\CsrfMiddleware::getTokenName() : '';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/login.css">
  <script src="../js/ui-enhancements.js" defer></script>
  <script src="../js/animations.js" defer></script>
</head>
<body class="page-transition login-page" style="margin: 0; padding: 0; overflow-x: hidden;">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<div class="flex h-screen w-full login-container">
  <div class="w-1/2 bg-gradient-to-br from-blue-50 to-blue-100 flex justify-center items-center login-logo-section">
    <div class="text-center">
      <img src="../assets/img/logowithname.png" alt="Company Logo" class="mb-4 mx-auto max-w-xs">
    </div>
  </div>

  <div class="w-1/2 bg-white p-10 flex flex-col justify-center items-center login-form-section">
    <div class="w-full max-w-md">
      <!-- <a href="#" class="text-sm text-gray-500 mb-6 inline-flex items-center">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
        </svg>
        Back
      </a> -->
      <h2 class="text-3xl font-bold mb-2 text-gray-800">Account Login</h2>
      <p class="text-gray-500 text-sm mb-8">If you are already a member you can login with your email address and password.</p>
      <form action="" method="post" class="space-y-6">
        <?php if (!empty($error)): ?>
          <div class="w-full p-3 bg-red-100 border border-red-400 text-red-700 rounded-md text-sm">
            <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>
        <div id="signupSuccess" class="w-full p-3 bg-green-100 border border-green-400 text-green-800 rounded-md text-sm hidden"></div>
        <?php if ($csrfAvailable && !empty($csrfToken)): ?>
          <input type="hidden" name="<?php echo htmlspecialchars($csrfTokenName); ?>" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <?php endif; ?>
        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email address</label>
          <input type="email" name="email" id="email" required 
                 class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>
        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
          <input type="password" name="password" id="password" required 
                 class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>
        <div class="flex items-center remember-me-container">
          <input type="checkbox" id="remember" name="remember" class="remember-checkbox">
          <label for="remember" class="remember-label">Remember me</label>
        </div>
        <button type="submit" class="w-full text-white font-semibold py-4 rounded-md text-base transition-all duration-200 shadow-md hover:shadow-lg login-submit-btn">
          Login
        </button>
      </form>
      <p class="mt-4 text-sm text-gray-500 text-center">
        Don’t have an account?
        <button type="button" id="openSignupModal" class="text-blue-600 hover:text-blue-700 font-medium">Sign up here</button>
      </p>
    </div>
  </div>
</div>

<!-- Sign Up Modal -->
<div id="signupModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
  <div class="bg-white rounded-md shadow-md max-w-md w-full p-10 signup-modal-panel">
    <div class="flex items-start justify-between mb-4">
      <div>
        <h2>Create Account</h2>
        <p class="text-gray-500">Registers as Customer (External) only.</p>
      </div>
      <button type="button" id="closeSignupModal" class="text-gray-400 hover:text-gray-600">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
      </button>
    </div>

    <div id="signupError" class="w-full p-3 bg-red-100 border border-red-400 text-red-700 rounded-md text-sm mb-4 hidden"></div>

    <form id="signupForm" class="space-y-4">
      <?php if ($csrfAvailable && !empty($csrfToken)): ?>
        <input type="hidden" name="<?php echo htmlspecialchars($csrfTokenName); ?>" value="<?php echo htmlspecialchars($csrfToken); ?>">
      <?php endif; ?>

      <div>
        <label>Full name</label>
        <input name="name" type="text" required class="login-input">
      </div>
      <div>
        <label>Email</label>
        <input name="email" type="email" required class="login-input">
      </div>
      <div>
        <label>Password</label>
        <input name="password" type="password" minlength="8" required class="login-input">
      </div>
      <div>
        <label>Confirm password</label>
        <input name="password_confirm" type="password" minlength="8" required class="login-input">
      </div>
      <div>
        <label>Company (optional)</label>
        <input name="company" type="text" class="login-input">
      </div>
      <div>
        <label>Phone (optional)</label>
        <input name="phone" type="text" class="login-input">
      </div>

      <button type="submit" id="signupSubmitBtn" class="login-submit-btn">
        Create account
      </button>
    </form>
  </div>
</div>

<script>
(function() {
  const modal = document.getElementById('signupModal');
  const openBtn = document.getElementById('openSignupModal');
  const closeBtn = document.getElementById('closeSignupModal');
  const form = document.getElementById('signupForm');
  const errBox = document.getElementById('signupError');
  const successBox = document.getElementById('signupSuccess');
  const submitBtn = document.getElementById('signupSubmitBtn');

  function openModal() {
    errBox.classList.add('hidden');
    errBox.textContent = '';
    modal.classList.remove('hidden');
    modal.classList.add('flex');
  }

  function closeModal() {
    modal.classList.add('hidden');
    modal.classList.remove('flex');
  }

  openBtn?.addEventListener('click', openModal);
  closeBtn?.addEventListener('click', closeModal);
  modal?.addEventListener('click', (e) => {
    if (e.target === modal) closeModal();
  });

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    errBox.classList.add('hidden');
    errBox.textContent = '';
    successBox.classList.add('hidden');
    successBox.textContent = '';

    submitBtn.disabled = true;
    submitBtn.textContent = 'Creating...';

    try {
      const fd = new FormData(form);
      const res = await fetch('../php/register_user.php', {
        method: 'POST',
        body: fd
      });
      const data = await res.json().catch(() => null);

      if (!res.ok || !data || data.success !== true) {
        const msg = (data && (data.error || data.message)) ? (data.error || data.message) : ('Registration failed (' + res.status + ')');
        errBox.textContent = msg;
        errBox.classList.remove('hidden');
        return;
      }

      closeModal();
      form.reset();
      successBox.textContent = data.message || 'Account created. Please log in.';
      successBox.classList.remove('hidden');
    } catch (err) {
      errBox.textContent = 'Registration failed. Please try again.';
      errBox.classList.remove('hidden');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Create account';
    }
  });
})();
</script>
</body>
</html>
