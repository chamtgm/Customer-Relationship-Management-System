<?php
session_start(); // Start session at the top
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "comp1044_database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- ADDED: Automatically mark past-due reminders as read ---
$updatePastDueSql = "UPDATE reminder_record
                     SET Reminder_ID = 1
                     WHERE Reminder_ID != 1 AND reminder_date <= NOW()";
if (!$conn->query($updatePastDueSql)) {
    error_log("Error updating past-due reminders: " . $conn->error);
}
// --- END ADDED SECTION ---

if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
  header("Location: LoginPage.php");
  exit();
}

// Check user role
$userEmail = $_SESSION["Email"];
$roleTitle = $_SESSION["Role_Title"];
$staffID   = $_SESSION["Staff_ID"]; // Retrieve Staff_ID from session

// --- Query for UNREAD reminders count (will now reflect the update above) ---
// This count is now primarily for the sidebar badge, not the removed popup
if ($roleTitle === "Admin") {
    $unreadSql = "SELECT COUNT(*) AS total_unread
                  FROM reminder_record
                  WHERE Reminder_ID != 1";
    $unreadRes = $conn->query($unreadSql);
    if (!$unreadRes) {
        // Log error, but don't necessarily die if only badge fails
        error_log("Unread count query failed (Admin): " . $conn->error);
        $unreadCount = 0; // Default to 0 on error
    } else {
        $rowUnread = $unreadRes->fetch_assoc();
        $unreadCount = $rowUnread['total_unread'] ?? 0;
    }
} else {
    $unreadSql = "SELECT COUNT(*) AS total_unread
                  FROM reminder_record
                  WHERE Reminder_ID != 1
                    AND Staff_ID = ?";
    $stmtUnread = $conn->prepare($unreadSql);
    if ($stmtUnread) {
        $stmtUnread->bind_param("i", $staffID);
        $stmtUnread->execute();
        $unreadRes = $stmtUnread->get_result();
        $rowUnread = $unreadRes->fetch_assoc();
        $stmtUnread->close();
        $unreadCount = $rowUnread['total_unread'] ?? 0;
    } else {
        error_log("Unread count query failed (Staff): " . $conn->error);
        $unreadCount = 0; // Default to 0 on error
    }
}

// If unreadCount > 9, show "9+", otherwise show the number
$unreadBadge = ($unreadCount > 9) ? '9+' : $unreadCount;

// Modify the main SQL query for listing reminders
if ($roleTitle === "Admin") {
    // Admin can see all reminders with staff names and related entities
    $sql = "SELECT n.*, s.First_Name, s.Last_Name,
            c.Customer_ID, c.First_Name as Customer_First_Name, c.Last_Name as Customer_Last_Name,
            l.Lead_ID, l.First_Name as Lead_First_Name, l.Last_Name as Lead_Last_Name
            FROM reminder_record n 
            LEFT JOIN staff s ON n.Staff_ID = s.Staff_ID
            LEFT JOIN customer c ON n.Customer_ID = c.Customer_ID
            LEFT JOIN lead l ON n.Lead_ID = l.Lead_ID
            ORDER BY n.Reminder_Record_ID ASC"; // Added Reminder_DateTime
    $result = $conn->query($sql);
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
} else {
    // Sales representatives can only see their own reminders
    $sql = "SELECT n.*,
            c.Customer_ID, c.First_Name as Customer_First_Name, c.Last_Name as Customer_Last_Name,
            l.Lead_ID, l.First_Name as Lead_First_Name, l.Last_Name as Lead_Last_Name
            FROM reminder_record n
            LEFT JOIN customer c ON n.Customer_ID = c.Customer_ID
            LEFT JOIN lead l ON n.Lead_ID = l.Lead_ID
            WHERE n.Staff_ID = ? 
            ORDER BY n.Reminder_Record_ID ASC"; // Added Reminder_DateTime
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staffID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>ABB Robotics CRM - Reminders</title>
  <link rel="stylesheet" href="home.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>

  <!-- Reminder Prompt Removed -->

  <?php include 'sidebar.php'; // Ensure sidebar.php uses $unreadBadge if needed ?>

  <!-- ===== MAIN CONTENT ===== -->
  <div class="main-content">
    <!-- TOP BAR -->
    <header>
      <div class="search-wrapper">
        <!-- Simple SVG icon for search -->
        <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" fill="#888">
          <path d="M19.021 17.271 14.8 13.05q-.75.65-1.75 1.025-1 .375-2.1.375-2.625 0-4.462-1.837Q4.65 10.775 4.65 8.15q0-2.625 1.838-4.463Q8.325 1.85 10.95 1.85q2.625 0 4.462 1.837Q17.25 5.525 17.25 8.15q0 1.1-.388 2.125T15.863 12l4.2 4.2q.275.275.275.625 0 .35-.275.625-.275.275-.625.275-.35 0-.625-.275ZM10.95 9.95q.75 0 1.35-.6t.6-1.35q0-.75-.6-1.35t-1.35-.6q-.75 0-1.35.6t-.6 1.35q0 .75.6 1.35t1.35.6Z"/>
        </svg>
        <form method="get" action="SearchReminder.php">
          <input type="search" name="q" placeholder="Search reminder..." />
        </form>
      </div>
      <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
    </header>

    <!-- DASHBOARD CONTENT -->
    <main>
      <?php if (isset($_GET['error']) && $_GET['error'] === 'empty_search'): ?>
      <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
        <strong>Error:</strong> Please enter a search term before searching.
      </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['success']) && $_GET['success'] === 'Deleted'): ?>
        <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          <strong>Success:</strong> Reminder deleted successfully.
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['success']) && $_GET['success'] === 'updated'): ?>
        <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          <strong>Success:</strong> Reminder updated successfully.
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['success']) && $_GET['success'] === 'added'): ?>
        <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          <strong>Success:</strong> New reminder added successfully.
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['success']) && $_GET['success'] === 'added_all'): ?>
        <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          <strong>Success:</strong> Reminders added for all sales representatives.
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['error']) && $_GET['error'] === 'CannotDelete'): ?>
        <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          <strong>Error:</strong> Failed to delete reminder. Please try again later.
        </div>
      <?php endif; ?>
      
      <?php if (isset($_GET['error']) && $_GET['error'] === 'notfound'): ?>
        <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
          <strong>Error:</strong> Reminder not found or you do not have permission to edit it.
        </div>
      <?php endif; ?>
      
      <div class="dashboard-header">
        <h2>Reminder Dashboard</h2>
        <div class="button-group">
          <button onclick="window.location.href='SortReminderPage.php'" style="background-color: #2578f4;">Sort</button>
          <button onclick="window.location.href='manageReminder.php'">Add New</button>
        </div>
      </div>

      <table>
        <thead>
          <tr>
            <th>Status</th>
            <th>ID</th>
            <th>DATE</th> <!-- This is the column in question -->
            <th>REMINDER</th>
            <?php if ($roleTitle === "Admin"): ?>
            <th>ASSIGNED TO</th>
            <?php endif; ?>
            <th>RELATED TO</th>
            <th>DETAILS</th>
            <th>ACTION</th>
          </tr>
        </thead>
        <tbody>
          <?php
          // Display reminders in table rows
          if ($result && $result->num_rows > 0) {
              while ($reminder = $result->fetch_assoc()) {
                  // Safely get values with fallbacks
                  $reminder_id = $reminder['Reminder_Record_ID'] ?? 'General';
                  $event_date = $reminder['Event_Date'] ?? 'General'; // Make sure you are using 'Event_Date' here
                  $event_time = $reminder['Event_Time'] ?? 'N/A'; // Get Event_Time
                  $reminder_type = $reminder['Reminder_ID'] ?? 1;
                  $description = $reminder['Description'] ?? 'No details available';
                  $staff_id = $reminder['Staff_ID'] ?? 'Unknown';
                  $reminder_date_time = $reminder['reminder_date'] ?? null; // Fetch reminder date

                  // Format reminder date/time if it exists
                  $formatted_reminder = 'N/A';
                  if ($reminder_date_time) {
                      try {
                          $reminder_dt = new DateTime($reminder_date_time);
                          $formatted_reminder = $reminder_dt->format('Y-m-d H:i'); // Format as desired
                      } catch (Exception $e) {
                          $formatted_reminder = 'Invalid Date'; // Handle potential errors
                      }
                  }
                  
                  // Get staff name if available (for admin view)
                  $staff_name = 'Unknown';
                  if ($roleTitle === "Admin") {
                      $first_name = $reminder['First_Name'] ?? '';
                      $last_name  = $reminder['Last_Name'] ?? '';
                      if (!empty($first_name) || !empty($last_name)) {
                          $staff_name = trim($first_name . ' ' . $last_name);
                      } else {
                          $staff_name = 'Staff #' . $staff_id;
                      }
                  }
                  
                  // Determine related entity (customer or lead)
                  $related_to = 'N/A';
                  if (!empty($reminder['Customer_ID'])) {
                      $customer_name = trim(($reminder['Customer_First_Name'] ?? '') . ' ' . ($reminder['Customer_Last_Name'] ?? ''));
                      $related_to = 'Customer: ' . htmlspecialchars($customer_name);
                  } elseif (!empty($reminder['Lead_ID'])) {
                      $lead_name = trim(($reminder['Lead_First_Name'] ?? '') . ' ' . ($reminder['Lead_Last_Name'] ?? ''));
                      $related_to = 'Lead: ' . htmlspecialchars($lead_name);
                  }
                  
                  // Status indicator based on read/unread
                  $status_indicator = ($reminder_type == 1)
                      ? '<span class="status-indicator status-read">✓</span>'
                      : '<span class="status-indicator status-new"></span>';
                  
                  // Make the row clickable to show details
                  // NOTE: For safety in injecting JS, wrap description in backticks or escape properly
                  $escapedDescription = htmlspecialchars($description, ENT_QUOTES);
                  // Pass the formatted reminder date and event time to the JS function
                  echo "<tr id='reminder-" . htmlspecialchars($reminder_id) . "' class='reminder-row' onclick=\"showReminderDetails('{$reminder_id}', '{$event_date}', '{$event_time}', '{$staff_name}', `{$escapedDescription}`, {$reminder_type}, '{$formatted_reminder}')\">"; // Added event_time
                  echo "<td>{$status_indicator}</td>";
                  echo "<td>" . htmlspecialchars($reminder_id) . "</td>";
                  echo "<td>" . htmlspecialchars($event_date) . "</td>";
                  echo "<td>" . htmlspecialchars($formatted_reminder) . "</td>"; // Display reminder date
                  
                  if ($roleTitle === "Admin") {
                      echo "<td>" . htmlspecialchars($staff_name) . "</td>";
                  }
                  
                  // Add the Related To column
                  echo "<td>" . $related_to . "</td>"; // Already escaped above
                  
                  // Truncate description for display if it's long
                  $shortDesc = $description;
                  if (strlen($description) > 50) {
                      $shortDesc = substr($description, 0, 50) . '...';
                  }
                  
                  echo "<td class='expandable-cell'>" . htmlspecialchars($shortDesc) . "</td>";
                  
                  // Action Buttons
                  echo "<td class='action-buttons'>";
                  echo "<button class='btn-delete'
                            onclick=\"if(confirm('Are you sure you want to delete this record?')) {
                              window.location.href='manageEntity.php?type=reminder&action=delete&id={$reminder_id}';
                            }; event.stopPropagation();\"
                          >Delete</button>";

                  // Show 'Read' button only if unread
                  if ($reminder_type != 1) {
                      echo "<button class='btn-mark-read'
                                onclick=\"window.location.href='manageEntity.php?type=reminder&action=markread&id={$reminder_id}'; event.stopPropagation();\"
                              >Read</button>";
                  }

                  // --- MODIFIED: Show 'Edit' button instead of 'Unread' ---
                  // This button will always be shown, regardless of read/unread status,
                  // assuming users can edit both. Adjust logic if needed.
                  echo "<button class='btn-edit'
                            onclick=\"window.location.href='manageReminder.php?action=edit&id={$reminder_id}'; event.stopPropagation();\"
                          >Edit</button>";
                  // --- END MODIFICATION ---

                  echo "</td>";
                  echo "</tr>";
              }
          } else {
              // Adjust colspan for admin vs non-admin
              $colspan = ($roleTitle === "Admin") ? 8 : 7; // Increased colspan
              echo "<tr><td colspan='" . $colspan . "'>No reminders found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </main>
  </div>
  
  <!-- Reminder Detail Modal -->
  <div id="reminderModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeReminderModal()" style="cursor: pointer; font-size: 24px;">&times;</span>
      <h3>Reminder Details</h3>
      <div class="reminder-detail">
        <p><strong>ID:</strong> <span id="modalReminderId"></span></p>
        <p><strong>Date:</strong> <span id="modalReminderDate"></span></p>
        <p><strong>Time:</strong> <span id="modalReminderTime"></span></p> <!-- Added Time element -->
        <p><strong>Reminder Date:</strong> <span id="modalReminderReminderDate"></span></p> <!-- Added Reminder Date -->
        <p><strong>Status:</strong> <span id="modalReminderStatus"></span></p>
        <?php if ($roleTitle === "Admin"): ?>
        <p><strong>Assigned To:</strong> <span id="modalReminderStaff"></span></p>
        <?php endif; ?>
        <div class="reminder-description">
          <h4>Description:</h4>
          <div class="description-box">
            <p id="modalReminderDescription"></p>
          </div>
        </div>
        <div class="modal-actions">
          <button id="modalBtnRead" class="btn-mark-read" onclick="markAsRead()">Mark as Read</button>
          <!-- --- MODIFIED: Replaced Unread button with Edit button --- -->
          <button id="modalBtnEdit" class="btn-edit" onclick="editFromModal()">Edit</button>
          <!-- --- END MODIFICATION --- -->
          <button class="btn-delete" onclick="deleteFromModal()">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Global variables for current reminder in modal
    let currentReminderId = null;
    let currentReminderType = null;

    // --- Popup Related JS Removed ---
    // Removed: DOMContentLoaded listener for showing prompt
    // Removed: dismissReminder() function
    // Removed: viewAllReminders() function (both versions)
    // Removed: updateBadge() function

    // --- Existing Modal and Action Functions ---

    // Removed: expandAndMarkRead function (replaced with simpler buttons)

    // Function to show reminder details in modal
    function showReminderDetails(id, date, time, staff, description, type, reminderDate) {
      currentReminderId   = id;
      currentReminderType = type;

      document.getElementById('modalReminderId').textContent = id;
      document.getElementById('modalReminderDate').textContent = date;
      document.getElementById('modalReminderTime').textContent = time;
      document.getElementById('modalReminderReminderDate').textContent = reminderDate;
      document.getElementById('modalReminderStatus').textContent = (type == 1) ? 'Read' : 'Unread';

      const staffElement = document.getElementById('modalReminderStaff');
      if (staffElement) {
        staffElement.textContent = staff;
      }
      document.getElementById('modalReminderDescription').textContent = description;

      // Show/hide read button based on status
      document.getElementById('modalBtnRead').style.display   = (type == 1) ? 'none' : 'inline-block';
      // --- MODIFIED: Edit button is always visible in modal ---
      document.getElementById('modalBtnEdit').style.display = 'inline-block';
      // --- END MODIFICATION ---

      // Show the modal
      document.getElementById('reminderModal').style.display = 'block';
    }

    // Function to close the reminder modal
    function closeReminderModal() {
      document.getElementById('reminderModal').style.display = 'none';
      currentReminderId = null; // Clear ID when closing
      currentReminderType = null;
    }

    // Functions for modal action buttons (redirect to manageEntity.php or manageReminder.php)
    function markAsRead() {
      if (currentReminderId) {
        window.location.href = 'manageEntity.php?type=reminder&action=markread&id=' + currentReminderId;
      }
    }

    // --- REMOVED: markAsUnread() function ---

    // --- ADDED: editFromModal() function ---
    function editFromModal() {
      if (currentReminderId) {
        window.location.href = 'manageReminder.php?action=edit&id=' + currentReminderId;
      }
    }
    // --- END ADDED FUNCTION ---

    function deleteFromModal() {
      if (currentReminderId && confirm('Are you sure you want to delete this reminder?')) {
        window.location.href = 'manageEntity.php?type=reminder&action=delete&id=' + currentReminderId;
      }
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('reminderModal');
      if (event.target == modal) {
        closeReminderModal();
      }
    };

    // --- ADDED: Scroll to hash on page load ---
    document.addEventListener('DOMContentLoaded', function() {
      if (window.location.hash && window.location.hash.startsWith('#reminder-')) {
        try {
          const elementId = window.location.hash.substring(1); // Remove the '#'
          const elementToScrollTo = document.getElementById(elementId);
          if (elementToScrollTo) {
            console.log("Scrolling to element:", elementId);
            // Use setTimeout to ensure layout is complete before scrolling
            setTimeout(() => {
                elementToScrollTo.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Optional: Add a temporary highlight
                elementToScrollTo.style.transition = 'background-color 0.5s ease-in-out';
                elementToScrollTo.style.backgroundColor = '#ffffcc'; // Light yellow highlight
                setTimeout(() => {
                    elementToScrollTo.style.backgroundColor = ''; // Remove highlight
                }, 2000); // Highlight duration: 2 seconds
            }, 150); // Small delay
          } else {
            console.warn('Element to scroll to not found:', elementId);
          }
        } catch (e) {
          console.error('Error scrolling to element:', e);
        }
      }
    });
    // --- END ADDED SCRIPT ---

  </script>
</body>
</html>
