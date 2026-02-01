<?php
session_start();
if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit();
}

include("../php/db.php");

// Fetch user's tickets with technician name and status
$user_id = isset($_GET['id']) && is_numeric($_GET['id']) ? $_GET['id'] : $_SESSION['id'];
$tickets_query = "SELECT t.*,
                         CASE
                           WHEN t.status = 'complete' THEN 'Resolved'
                           WHEN t.status = 'closed' THEN 'Closed'
                           WHEN t.status = 'pending' THEN 'In Progress'
                           WHEN t.status = 'followup' THEN 'Follow Up'
                           WHEN t.status = 'assigning' THEN 'Open'
                           ELSE 'Open'
                         END as status_label,
                         CASE
                           WHEN t.status = 'complete' THEN 'bg-green-100 text-green-700'
                           WHEN t.status = 'closed' THEN 'bg-red-100 text-red-700'
                           WHEN t.status = 'pending' THEN 'bg-yellow-100 text-yellow-700'
                           WHEN t.status = 'followup' THEN 'bg-yellow-100 text-yellow-700'
                           WHEN t.status = 'assigning' THEN 'bg-blue-100 text-blue-700'
                           ELSE 'bg-blue-100 text-blue-700'
                         END as status_class,
                         tech.name as technician_name,
                         COUNT(r.reply_id) as reply_count
                  FROM tbl_ticket t
                  LEFT JOIN tbl_technician tech ON t.assigned_technician_id = tech.technician_id
                  LEFT JOIN tbl_ticket_reply r ON t.ticket_id = r.ticket_id
                  WHERE t.user_id = ?
                  GROUP BY t.ticket_id
                  ORDER BY t.created_at DESC";

$stmt = $conn->prepare($tickets_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tickets_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>View History | Interconnect Solutions Company</title>
  
  <!-- Theme CSS -->
  <link rel="stylesheet" href="../css/theme.css">
  <link rel="stylesheet" href="../css/components.css">
  <link rel="stylesheet" href="../css/animations.css">
  <link rel="stylesheet" href="../css/basicTemp.css">
  
  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- Google Font -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  
  <!-- UI Enhancements -->
  <script src="../js/ui-enhancements.js" defer></script>
  <script src="../js/animations.js" defer></script>
  <script src="../js/popup.js"></script>
  
  <style>
    body { 
      font-family: var(--font-family, 'Inter'), sans-serif;
      background-color: var(--bg-primary, #f8fafc);
      color: var(--text-primary, #1f2937);
    }
    
    /* Enhanced ticket card hover effects */
    .ticket-card {
      transition: all var(--transition-base, 0.3s) ease;
    }
    .ticket-card:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg, 0 10px 25px -3px rgba(0, 0, 0, 0.1));
    }
    
    /* Smooth expand/collapse animation */
    .details {
      animation: slideDown var(--transition-base, 0.3s) ease-out;
    }
  </style>
</head>
<body class="bg-gray-50 text-gray-900 page-transition" style="background-color: var(--bg-primary, #f8fafc);">
<?php include("../includes/navbar.php"); ?>
  <!-- Main Content -->
  <main class="max-w-5xl mx-auto py-10 px-4">
    <?php
    $customer_name = '';
    if ($user_id !== $_SESSION['id']) {
        // Get customer name for display
        $customer_query = "SELECT name FROM tbl_user WHERE user_id = ?";
        $stmt_check = $conn->prepare($customer_query);
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $customer_result = $stmt_check->get_result();
        if ($customer_result->num_rows > 0) {
            $customer_name = ' - ' . $customer_result->fetch_assoc()['name'];
        }
        $stmt_check->close();
    }
    ?>
    <h1 class="text-3xl font-bold mb-8">View History<?php echo htmlspecialchars($customer_name); ?></h1>

    <!-- Ticket List -->
    <div id="ticket-list" class="space-y-4">
      <?php if ($tickets_result->num_rows > 0): ?>
        <?php while ($ticket = $tickets_result->fetch_assoc()): ?>
          <div class="ticket-card bg-white shadow-sm border border-gray-200 rounded-lg hover:shadow-md transition cursor-pointer p-5 flex justify-between items-center" onclick="toggleDetails(this, '<?php echo htmlspecialchars($ticket['reference_id']); ?>')" style="border-color: var(--border-color, #e5e7eb);">
            <div>
              <p class="text-full font-semibold text-blue-700"><?php echo htmlspecialchars($ticket['title']); ?> #<?php echo htmlspecialchars($ticket['reference_id']); ?></p>
              <p class="text-sm text-gray-500 mt-1 flex items-center space-x-2">
                <span><?php
                  $created_date = new DateTime($ticket['created_at']);
                  $now = new DateTime();
                  $interval = $created_date->diff($now);

                  if ($interval->days > 0) {
                    echo $interval->days . ' day' . ($interval->days > 1 ? 's' : '') . ' ago';
                  } elseif ($interval->h > 0) {
                    echo $interval->h . ' hour' . ($interval->h > 1 ? 's' : '') . ' ago';
                  } elseif ($interval->i > 0) {
                    echo $interval->i . ' minute' . ($interval->i > 1 ? 's' : '') . ' ago';
                  } else {
                    echo 'Just now';
                  }
                ?></span>
                <span>•</span>
                <span><?php echo ($ticket['reply_count'] ?? 0) . ' repl' . (($ticket['reply_count'] ?? 0) != 1 ? 'ies' : 'y'); ?></span>
                <span>•</span>
                <span><?php echo date('d F, Y, g:i A', strtotime($ticket['created_at'])); ?></span>
              </p>
            </div>
            <div class="text-center">
              <img src="https://i.pravatar.cc/50?img=<?php echo rand(1,10); ?>" alt="user" class="w-10 h-10 rounded-full mx-auto mb-1">
              <span class="px-3 py-1 text-xs rounded-full font-semibold <?php echo htmlspecialchars($ticket['status_class']); ?>"><?php echo htmlspecialchars($ticket['status_label']); ?></span>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="bg-white shadow-sm border border-gray-200 rounded-lg p-5 text-center text-gray-500">
          <p>No tickets found. <a href="create_ticket.php" class="text-blue-600 hover:underline">Create your first ticket</a></p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    // Expandable Ticket Interaction
    function toggleDetails(card, ref) {
      // if already expanded, collapse it
      if (card.nextElementSibling && card.nextElementSibling.classList.contains('details')) {
        card.nextElementSibling.remove();
        return;
      }

      // collapse other open details
      document.querySelectorAll('.details').forEach(el => el.remove());

      const details = document.createElement('div');
      details.className = 'details bg-gray-50 border-l-4 border-blue-600 p-4 mt-2 rounded text-sm text-gray-700';
      details.style.borderLeftColor = 'var(--primary-color, #2563eb)';
      details.style.backgroundColor = 'var(--bg-secondary, #f9fafb)';
      details.style.color = 'var(--text-primary, #374151)';

      // Fetch ticket details dynamically
      fetch(`../php/get_ticket.php?ref=${ref}`)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            details.innerHTML = `
              <p><strong>Description:</strong> ${data.ticket.description || 'No description available.'}</p>
              <p class="mt-2"><strong>Assigned To:</strong> ${data.ticket.technician_name || 'Unassigned'}</p>
              <p class="mt-2"><strong>Priority:</strong> ${data.ticket.priority && data.ticket.priority.toLowerCase() === 'critical' ? 'Urgent' : (data.ticket.priority && data.ticket.priority.toLowerCase() === 'regular' ? 'Medium' : (data.ticket.priority || 'N/A'))}</p>
              <p class="mt-2"><strong>Category:</strong> ${data.ticket.category || 'N/A'}</p>
              <p class="mt-2"><strong>Status:</strong> ${data.ticket.status || 'N/A'}</p>
              <p class="mt-2"><strong>Last Updated:</strong> ${new Date(data.ticket.updated_at || data.ticket.created_at).toLocaleString()}</p>
              <div class="mt-3 flex justify-end space-x-2">
                <button onclick="viewFullTicket('${ref}')" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-all duration-200 hover:translate-y-[-1px] hover:shadow-md" style="background-color: var(--primary-color, #2563eb);">View Full Ticket</button>
                <button onclick="closeDetails()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600 transition-all duration-200 hover:translate-y-[-1px] hover:shadow-md">Close</button>
              </div>
            `;
          } else {
            details.innerHTML = `
              <p class="text-red-700">Error loading ticket details.</p>
              <div class="mt-3 flex justify-end">
                <button onclick="closeDetails()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">Close</button>
              </div>
            `;
          }
        })
        .catch(error => {
          details.innerHTML = `
            <p class="text-red-700">Error loading ticket details.</p>
            <div class="mt-3 flex justify-end">
              <button onclick="closeDetails()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600">Close</button>
            </div>
          `;
        });

      card.insertAdjacentElement('afterend', details);
    }

    function viewFullTicket(ref) {
      window.location.href = `cust_ticket.php?ref=${ref}`;
    }

    function closeDetails() {
      document.querySelectorAll('.details').forEach(el => el.remove());
    }
  </script>
  
  <script>
    // Initialize page transitions
    document.addEventListener('DOMContentLoaded', function() {
      // Add smooth scroll behavior
      document.querySelectorAll('.ticket-card').forEach((card, index) => {
        card.style.animationDelay = `${index * 0.05}s`;
        card.classList.add('fade-in');
      });
    });
  </script>
</body>
</html>
