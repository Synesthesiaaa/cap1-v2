<?php
require_once __DIR__ . '/../bootstrap.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Logged-in users should not access public registration form.
if (isset($_SESSION['id'])) {
    $userType = $_SESSION['user_type'] ?? '';
    if ($userType === 'external') {
        header("Location: create_ticket.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$csrfAvailable = class_exists('Middleware\CsrfMiddleware');
$csrfToken = $csrfAvailable ? \Middleware\CsrfMiddleware::generateToken() : '';
$csrfTokenName = $csrfAvailable ? \Middleware\CsrfMiddleware::getTokenName() : '';
?>
<!DOCTYPE html>
<html>
<head>
  <title>Register</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/login.css">
  <script src="../js/ui-enhancements.js" defer></script>
  <script src="../js/animations.js" defer></script>
</head>
<body class="page-transition login-page" style="margin: 0; padding: 0; overflow-x: hidden;">
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<div class="flex min-h-screen w-full login-container">
  <div class="w-1/2 bg-gradient-to-br from-blue-50 to-blue-100 flex justify-center items-center login-logo-section">
    <div class="text-center">
      <img src="../assets/img/logowithname.png" alt="Company Logo" class="mb-4 mx-auto max-w-xs">
    </div>
  </div>

  <div class="w-1/2 bg-white p-10 flex flex-col justify-center items-center login-form-section">
    <div class="w-full max-w-md">
      <h2 class="text-3xl font-bold mb-2 text-gray-800">Create Account</h2>
      <p class="text-gray-500 text-sm mb-8">New accounts are created as external customers.</p>

      <div id="registerError" class="w-full p-3 bg-red-100 border border-red-400 text-red-700 rounded-md text-sm mb-4 hidden"></div>

      <form id="registerForm" class="space-y-6">
        <?php if ($csrfAvailable && !empty($csrfToken)): ?>
          <input type="hidden" name="<?php echo htmlspecialchars($csrfTokenName); ?>" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <?php endif; ?>

        <div>
          <label for="name" class="block text-sm font-semibold text-gray-700 mb-2">Full name</label>
          <input type="text" id="name" name="name" required class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>

        <div>
          <label for="email" class="block text-sm font-semibold text-gray-700 mb-2">Email address</label>
          <input type="email" id="email" name="email" required class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>

        <div>
          <label for="password" class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
          <input type="password" id="password" name="password" minlength="8" required class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>

        <div>
          <label for="password_confirm" class="block text-sm font-semibold text-gray-700 mb-2">Confirm password</label>
          <input type="password" id="password_confirm" name="password_confirm" minlength="8" required class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>

        <div>
          <label for="company" class="block text-sm font-semibold text-gray-700 mb-2">Company (optional)</label>
          <input type="text" id="company" name="company" class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>

        <div>
          <label for="phone" class="block text-sm font-semibold text-gray-700 mb-2">Phone (optional)</label>
          <input type="text" id="phone" name="phone" class="w-full px-4 py-3.5 border-2 border-gray-300 rounded-md focus:outline-none transition-all text-base login-input">
        </div>

        <button type="submit" id="registerSubmitBtn" class="w-full text-white font-semibold py-4 rounded-md text-base transition-all duration-200 shadow-md hover:shadow-lg login-submit-btn">
          Create account
        </button>
      </form>

      <p class="mt-4 text-sm text-gray-500 text-center">
        Already have an account?
        <a href="login.php" class="text-blue-600 hover:text-blue-700 font-medium">Login here</a>
      </p>
    </div>
  </div>
</div>

<script>
(function() {
  const form = document.getElementById('registerForm');
  const errBox = document.getElementById('registerError');
  const submitBtn = document.getElementById('registerSubmitBtn');

  function showError(message) {
    errBox.textContent = message;
    errBox.classList.remove('hidden');
  }

  form?.addEventListener('submit', async (e) => {
    e.preventDefault();
    errBox.classList.add('hidden');
    errBox.textContent = '';

    const password = form.password.value || '';
    const passwordConfirm = form.password_confirm.value || '';
    if (password !== passwordConfirm) {
      showError('Passwords do not match.');
      return;
    }

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
        const msg = (data && (data.error || data.message))
          ? (data.error || data.message)
          : ('Registration failed (' + res.status + ')');
        showError(msg);
        return;
      }

      window.location.href = 'login.php?registered=1';
    } catch (err) {
      showError('Registration failed. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Create account';
    }
  });
})();
</script>
</body>
</html>
