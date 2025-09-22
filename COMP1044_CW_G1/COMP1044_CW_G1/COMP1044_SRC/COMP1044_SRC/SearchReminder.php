<?php
session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "comp1044_database";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Authentication check
if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header("Location: LoginPage.php");
    exit();
}

$userEmail = $_SESSION["Email"];
$roleTitle = $_SESSION["Role_Title"];
$staffID   = $_SESSION["Staff_ID"];

// Get search query
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Check for blank search and redirect with error message
if (empty($searchQuery)) {
    header("Location: ReminderPage.php?error=empty_search");
    exit();
}

// Escape user input
$searchTerm = "%" . $conn->real_escape_string($searchQuery) . "%";

// Modify SQL query based on role
if ($roleTitle === "Admin") {
    // Admin can search all reminders with customer/lead names AND staff names
    $sql = "SELECT n.*, s.First_Name as Staff_First_Name, s.Last_Name as Staff_Last_Name, 
            c.Customer_ID, c.First_Name as Customer_First_Name, c.Last_Name as Customer_Last_Name,
            l.Lead_ID, l.First_Name as Lead_First_Name, l.Last_Name as Lead_Last_Name
            FROM reminder_record n
            LEFT JOIN staff s ON n.Staff_ID = s.Staff_ID
            LEFT JOIN customer c ON n.Customer_ID = c.Customer_ID
            LEFT JOIN lead l ON n.Lead_ID = l.Lead_ID
            WHERE n.Reminder_Record_ID LIKE ?
            OR n.Event_Date LIKE ?
            OR n.Event_Time LIKE ?       -- Added Event_Time search
            OR n.reminder_date LIKE ?    -- Added reminder_date search
            OR n.Description LIKE ?
            OR CONCAT(c.First_Name, ' ', c.Last_Name) LIKE ?
            OR CONCAT(l.First_Name, ' ', l.Last_Name) LIKE ?
            OR CONCAT(s.First_Name, ' ', s.Last_Name) LIKE ?
            ORDER BY n.Reminder_Record_ID DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
} else {
    // Sales representatives can only search their own reminders
    $sql = "SELECT n.*, 
            c.Customer_ID, c.First_Name as Customer_First_Name, c.Last_Name as Customer_Last_Name,
            l.Lead_ID, l.First_Name as Lead_First_Name, l.Last_Name as Lead_Last_Name
            FROM reminder_record n
            LEFT JOIN customer c ON n.Customer_ID = c.Customer_ID
            LEFT JOIN lead l ON n.Lead_ID = l.Lead_ID
            WHERE n.Staff_ID = ? 
            AND (n.Reminder_Record_ID LIKE ?
            OR n.Event_Date LIKE ?
            OR n.Event_Time LIKE ?       -- Added Event_Time search
            OR n.reminder_date LIKE ?    -- Added reminder_date search
            OR n.Description LIKE ?
            OR CONCAT(c.First_Name, ' ', c.Last_Name) LIKE ?
            OR CONCAT(l.First_Name, ' ', l.Last_Name) LIKE ?)
            ORDER BY n.Reminder_Record_ID DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isssssss', $staffID, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* Add styles for modal if not already present */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4); }
        .modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 60%; max-width: 600px; border-radius: 8px; position: relative; }
        .close { color: #aaa; float: right; font-size: 28px; font-weight: bold; position: absolute; top: 10px; right: 20px; }
        .close:hover, .close:focus { color: black; text-decoration: none; cursor: pointer; }
        .reminder-detail p { margin: 10px 0; }
        .reminder-description { margin-top: 15px; }
        .description-box { border: 1px solid #ccc; padding: 10px; min-height: 80px; background-color: #f9f9f9; border-radius: 4px; margin-top: 5px; }
        .modal-actions { margin-top: 20px; text-align: right; }
        .modal-actions button { margin-left: 10px; padding: 8px 15px; border-radius: 4px; cursor: pointer; }
        .btn-mark-read { background-color: #4CAF50; color: white; border: none; }
        .btn-mark-unread { background-color: #ff9800; color: white; border: none; }
        .btn-delete { background-color: #f44336; color: white; border: none; }
        .btn-back { padding: 8px 15px; background-color: #ccc; border: none; border-radius: 4px; cursor: pointer; }
        .dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

<div class="main-content">
    <header>
        <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" fill="#888">
              <path d="M19.021 17.271 14.8 13.05q-.75.65-1.75 1.025-1 .375-2.1.375-2.625 0-4.462-1.837Q4.65 10.775 4.65 8.15q0-2.625 1.838-4.463Q8.325 1.85 10.95 1.85q2.625 0 4.462 1.837Q17.25 5.525 17.25 8.15q0 1.1-.388 2.125T15.863 12l4.2 4.2q.275.275.275.625 0 .35-.275.625-.275.275-.625.275-.35 0-.625-.275ZM10.95 9.95q.75 0 1.35-.6t.6-1.35q0-.75-.6-1.35t-1.35-.6q-.75 0-1.35.6t-.6 1.35q0 .75.6 1.35t1.35.6Z"/>
            </svg>
            <form method="get" action="SearchReminder.php">
                <input type="search" name="q" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>" />
            </form>
        </div>
        <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
    </header>

    <main>
        <div class="dashboard-header">
            <h2>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
            <button class="btn-back" onclick="window.location.href='ReminderPage.php'">Back</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Status</th>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Reminder</th>
                    <?php if ($roleTitle === "Admin"): ?>
                    <th>Assigned To</th>
                    <?php endif; ?>
                    <th>Related To</th>
                    <th>Details</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): 
                        // Determine status indicator
                        $reminder_id = $row['Reminder_Record_ID'];
                        $reminder_type = isset($row['Reminder_ID']) ? $row['Reminder_ID'] : 1;
                        $status_indicator = ($reminder_type == 1) ? 
                            '<span class="status-indicator status-read">✓</span>' : 
                            '<span class="status-indicator status-new"></span>';
                            
                        // Get staff name for admin view
                        $staff_name = ''; // Initialize staff_name
                        if ($roleTitle === "Admin") {
                            $staff_first_name = isset($row['Staff_First_Name']) ? $row['Staff_First_Name'] : '';
                            $staff_last_name = isset($row['Staff_Last_Name']) ? $row['Staff_Last_Name'] : '';
                            $staff_name = trim($staff_first_name . ' ' . $staff_last_name);
                            if (empty($staff_name)) {
                                $staff_name = 'Staff #' . $row['Staff_ID'];
                            }
                        }
                        
                        // Determine related entity (customer or lead)
                        $related_to = 'N/A';
                        if (!empty($row['Customer_ID'])) {
                            $customer_name = trim(($row['Customer_First_Name'] ?? '') . ' ' . ($row['Customer_Last_Name'] ?? ''));
                            $related_to = 'Customer: ' . $customer_name;
                        } elseif (!empty($row['Lead_ID'])) {
                            $lead_name = trim(($row['Lead_First_Name'] ?? '') . ' ' . ($row['Lead_Last_Name'] ?? ''));
                            $related_to = 'Lead: ' . $lead_name;
                        }
                        
                        // Truncate description
                        $description = $row['Description'] ?? 'No details available';
                        $shortDesc = $description;
                        if (strlen($description) > 50) {
                            $shortDesc = substr($description, 0, 50) . '...';
                        }

                        // Format reminder date/time if it exists
                        $reminder_date_time = $row['reminder_date'] ?? null;
                        $formatted_reminder = 'N/A';
                        if ($reminder_date_time) {
                            try {
                                $reminder_dt = new DateTime($reminder_date_time);
                                $formatted_reminder = $reminder_dt->format('Y-m-d H:i');
                            } catch (Exception $e) {
                                $formatted_reminder = 'Invalid Date';
                            }
                        }

                        // Get Event Time
                        $event_time = isset($row['Event_Time']) ? date('H:i', strtotime($row['Event_Time'])) : 'N/A'; // Format time
                    ?>
                        <tr class="reminder-row" onclick="showReminderDetails(
                            '<?php echo $reminder_id; ?>', 
                            '<?php echo htmlspecialchars($row['Event_Date']); ?>', 
                            '<?php echo htmlspecialchars($event_time); ?>',
                            '<?php echo htmlspecialchars($formatted_reminder); ?>',
                            '<?php echo htmlspecialchars($staff_name); ?>', 
                            `<?php echo htmlspecialchars($description, ENT_QUOTES); ?>`, 
                            <?php echo $reminder_type; ?>)">
                            <td><?php echo $status_indicator; ?></td>
                            <td><?php echo htmlspecialchars($reminder_id); ?></td>
                            <td><?php echo htmlspecialchars($row['Event_Date']); ?></td>
                            <td><?php echo htmlspecialchars($event_time); ?></td>
                            <td><?php echo htmlspecialchars($formatted_reminder); ?></td>
                            <?php if ($roleTitle === "Admin"): ?>
                            <td><?php echo htmlspecialchars($staff_name); ?></td>
                            <?php endif; ?>
                            <td><?php echo htmlspecialchars($related_to); ?></td>
                            <td class="expandable-cell"><?php echo htmlspecialchars($shortDesc); ?></td>
                            <td class="action-buttons">
                                <button class='btn-delete'
                                    onclick="if(confirm('Are you sure you want to delete this record?')) {
                                        window.location.href='manageEntity.php?type=reminder&action=delete&id=<?php echo $reminder_id; ?>';
                                    }; event.stopPropagation();"
                                >Delete</button>
                                <?php if ($reminder_type != 1): // Show Read button only if unread ?>
                                <button class='btn-mark-read'
                                    onclick="window.location.href='manageEntity.php?type=reminder&action=markread&id=<?php echo $reminder_id; ?>'; event.stopPropagation();"
                                >Read</button>
                                <?php endif; ?>
                                <?php if ($reminder_type == 1): // Show Unread button only if read ?>
                                <button class='btn-mark-unread'
                                    onclick="window.location.href='manageEntity.php?type=reminder&action=markunread&id=<?php echo $reminder_id; ?>'; event.stopPropagation();"
                                >Unread</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo ($roleTitle === "Admin") ? 9 : 8; ?>">No reminders found matching your search.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<!-- Reminder Detail Modal (Same as ReminderPage.php) -->
<div id="reminderModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeReminderModal()" style="cursor: pointer; font-size: 24px;">&times;</span>
      <h3>Reminder Details</h3>
      <div class="reminder-detail">
        <p><strong>ID:</strong> <span id="modalReminderId"></span></p>
        <p><strong>Date:</strong> <span id="modalReminderDate"></span></p>
        <p><strong>Time:</strong> <span id="modalReminderTime"></span></p>
        <p><strong>Reminder:</strong> <span id="modalReminderReminderDate"></span></p>
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
          <button id="modalBtnUnread" class="btn-mark-unread" onclick="markAsUnread()">Mark as Unread</button>
          <button class="btn-delete" onclick="deleteFromModal()">Delete</button>
        </div>
      </div>
    </div>
</div>

<script>
    // Global variables for current reminder
    let currentReminderId = null;
    let currentReminderType = null;
    
    // Function to show reminder details in modal
    function showReminderDetails(id, date, time, reminderDate, staff, description, type) {
      // Store current reminder info
      currentReminderId = id;
      currentReminderType = type;
      
      // Populate modal with data
      document.getElementById('modalReminderId').textContent = id;
      document.getElementById('modalReminderDate').textContent = date;
      document.getElementById('modalReminderTime').textContent = time; // Populate time
      document.getElementById('modalReminderReminderDate').textContent = reminderDate; // Populate reminder date
      document.getElementById('modalReminderStatus').textContent = (type == 1) ? 'Read' : 'Unread';
      
      const staffElement = document.getElementById('modalReminderStaff');
      if (staffElement) {
        staffElement.textContent = staff || 'N/A'; // Handle case where staff might be empty for non-admin
      }
      
      document.getElementById('modalReminderDescription').textContent = description;
      
      // Show/hide read/unread buttons based on status
      document.getElementById('modalBtnRead').style.display = (type == 1) ? 'none' : 'inline-block';
      document.getElementById('modalBtnUnread').style.display = (type == 1) ? 'inline-block' : 'none';
      
      // Show the modal
      document.getElementById('reminderModal').style.display = 'block';
    }

    // Function to close the reminder modal
    function closeReminderModal() {
      document.getElementById('reminderModal').style.display = 'none';
    }

    // Functions for modal action buttons
    function markAsRead() {
      window.location.href = 'manageEntity.php?type=reminder&action=markread&id=' + currentReminderId;
    }

    function markAsUnread() {
      window.location.href = 'manageEntity.php?type=reminder&action=markunread&id=' + currentReminderId;
    }

    function deleteFromModal() {
      if (confirm('Are you sure you want to delete this reminder?')) {
        window.location.href = 'manageEntity.php?type=reminder&action=delete&id=' + currentReminderId;
      }
    }

    // Function to expand text and mark reminder as read
    function expandAndMarkRead(button, reminderId) {
      // Find the row and the expandable cell
      const row = button.closest('tr');
      const expandableCell = row.querySelector('.expandable-cell');
      
      // Update the status indicator to "read"
      const statusCell = row.querySelector('td:first-child');
      if (statusCell) {
        statusCell.innerHTML = '<span class="status-indicator status-read">✓</span>';
      }
      
      // After visual changes, mark as read in the database
      window.location.href = 'manageEntity.php?type=reminder&action=markread&id=' + reminderId;
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
      const modal = document.getElementById('reminderModal');
      if (event.target == modal) {
        closeReminderModal();
      }
    };
</script>
</body>
</html>