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

if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
  header("Location: LoginPage.php");
  exit();
}

$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Check user role
$userEmail = $_SESSION["Email"];
$roleTitle = $_SESSION["Role_Title"];
$staffID = $_SESSION["Staff_ID"]; // Retrieve Staff_ID from session

// Modify SQL query based on role
if ($roleTitle === "Admin") {
    // Admin can see all reminders with staff names
    $sql = "SELECT n.*, s.First_Name, s.Last_Name 
            FROM reminder_record n 
            LEFT JOIN staff s ON n.Staff_ID = s.Staff_ID 
            ORDER BY n.Reminder_Record_ID ASC";
    $result = $conn->query($sql); // Execute the query and assign the result
    if (!$result) {
        die("Query failed: " . $conn->error); // Handle query failure
    }
} else {
    // Sales representatives can only see their own reminders
    $sql = "SELECT * FROM reminder_record WHERE Staff_ID = ? ORDER BY Reminder_Record_ID ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staffID);
    $stmt->execute();
    $result = $stmt->get_result(); // Assign the result of the prepared statement
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
  <style>
    /* Add styles for visual feedback during AJAX operations if desired */
    .reminder-box.processing {
      opacity: 0.5;
      pointer-events: none; /* Prevent clicks while processing */
    }
    .hidden {
        display: none;
    }
  </style>
</head>
<body>

  <?php include 'sidebar.php'; ?>

  <!-- ===== MAIN CONTENT ===== -->
  <div class="main-content">
    <!-- TOP BAR -->
    <header>
        <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" fill="#888">
              <path d="M19.021 17.271 14.8 13.05q-.75.65-1.75 1.025-1 .375-2.1.375-2.625 0-4.462-1.837Q4.65 10.775 4.65 8.15q0-2.625 1.838-4.463Q8.325 1.85 10.95 1.85q2.625 0 4.462 1.837Q17.25 5.525 17.25 8.15q0 1.1-.388 2.125T15.863 12l4.2 4.2q.275.275.275.625 0 .35-.275.625-.275.275-.625.275-.35 0-.625-.275ZM10.95 9.95q.75 0 1.35-.6t.6-1.35q0-.75-.6-1.35t-1.35-.6q-.75 0-1.35.6t-.6 1.35q0 .75.6 1.35t1.35.6Z"/>
            </svg>
            <form method="get" action="SearchReminder.php">
                <input type="search" name="q" placeholder="Search" value="<?php echo htmlspecialchars($searchQuery); ?>" required />
                <input type="hidden" name="type" value="customer" /> <!-- Default to customer -->
            </form>
        </div>
        <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
    </header>

    <!-- DASHBOARD CONTENT -->
    <main>
      <div class="dashboard-header">
        <h2>Reminders</h2>
        <button onclick="window.location.href='ReminderPage.php'">Back</button>
      </div>

      <!-- Categories for reminders -->
      <div class="reminder-categories">
        <!-- New Reminders Section -->
        <section class="reminder-section" id="new-reminders-section">
          <h3><span class="status-indicator status-new"></span> New Reminders</h3>
          <div class="reminder-grid" id="new-reminders-grid">
          <?php
          // Track if we found any new reminders
          $hasNewReminders = false;

          // Display reminders in boxes, new first
          if ($result && $result->num_rows > 0) {
              // Reset the result pointer to the beginning
              if ($result->data_seek(0)) {
                  while ($row = $result->fetch_assoc()) {
                      // ... (fetch data as before) ...
                      $reminder_id = isset($row['Reminder_Record_ID']) ? $row['Reminder_Record_ID'] : 'General';
                      $event_date = isset($row['Event_Date']) ? $row['Event_Date'] : 'General';
                      $event_time = isset($row['Event_Time']) ? $row['Event_Time'] : 'N/A'; // Fetch Event_Time
                      $reminder_type = isset($row['Reminder_ID']) ? $row['Reminder_ID'] : 1; // Default to read if not set? Check logic. Assume 0 or other value means unread.
                      $description = isset($row['Description']) ? $row['Description'] : 'No details available';
                      $staff_id = isset($row['Staff_ID']) ? $row['Staff_ID'] : 'Unknown';
                      $reminder_date_time = $row['reminder_date'] ?? null;

                      $formatted_reminder = 'N/A';
                      if ($reminder_date_time) { /* ... format date ... */
                          try {
                              $reminder_dt = new DateTime($reminder_date_time);
                              $formatted_reminder = $reminder_dt->format('Y-m-d H:i');
                          } catch (Exception $e) { $formatted_reminder = 'Invalid Date'; }
                      }

                      $staff_name = 'Unknown';
                      if ($roleTitle === "Admin") { /* ... get staff name ... */
                          $first_name = isset($row['First_Name']) ? $row['First_Name'] : '';
                          $last_name = isset($row['Last_Name']) ? $row['Last_Name'] : '';
                          if (!empty($first_name) || !empty($last_name)) {
                              $staff_name = trim($first_name . ' ' . $last_name);
                          } else { $staff_name = 'Staff #' . $staff_id; }
                      }

                      $shortDesc = $description;
                      if (strlen($description) > 50) { $shortDesc = substr($description, 0, 50) . '...'; }

                      // Only display unread reminders (assuming type != 1 means unread)
                      if ($reminder_type != 1) {
                          $hasNewReminders = true;
                          // Add data attributes for JS
                          echo '<div id="reminder-'.$reminder_id.'" class="reminder-box" ';
                          echo ' data-id="'.$reminder_id.'" data-date="'.$event_date.'" data-time="'.htmlspecialchars($event_time, ENT_QUOTES).'" data-staff="'.htmlspecialchars($staff_name, ENT_QUOTES).'" '; // Added data-time
                          echo ' data-description="'.htmlspecialchars($description, ENT_QUOTES).'" data-type="'.$reminder_type.'" data-reminder="'.htmlspecialchars($formatted_reminder, ENT_QUOTES).'" ';
                          echo ' onclick="showReminderDetailsFromElement(this)">'; // Use new function

                          echo '<div class="reminder-header">';
                          echo '<span class="status-indicator status-new"></span>'; // Unread indicator
                          echo '<span class="reminder-id">ID: '.htmlspecialchars($reminder_id).'</span>';
                          echo '<span class="reminder-date">'.htmlspecialchars($event_date).'</span>';
                          echo '</div>';

                          if ($roleTitle === "Admin") {
                              echo '<div class="reminder-staff">Assigned to: '.htmlspecialchars($staff_name).'</div>';
                          }

                          echo '<div class="reminder-content">'.htmlspecialchars($shortDesc).'</div>';
                          echo '<div class="reminder-actions">';
                          // Use new JS functions for actions, pass ID
                          echo '<button class="btn-delete" onclick="deleteReminder(\''.$reminder_id.'\'); event.stopPropagation();">Delete</button>';
                          echo '<button class="btn-mark-read" onclick="markReminderRead(\''.$reminder_id.'\'); event.stopPropagation();">Read</button>';
                          echo '</div>';
                          echo '</div>'; // End reminder-box
                      }
                  }
              }

              // Display message if no new reminders
              if (!$hasNewReminders) {
                  echo '<div class="no-reminders-message" id="no-new-reminders">No new reminders</div>';
              } else {
                  // Add hidden message for when JS removes all items
                  echo '<div class="no-reminders-message hidden" id="no-new-reminders">No new reminders</div>';
              }
          } else {
               echo '<div class="no-reminders-message" id="no-new-reminders">No reminders found</div>';
          }
          ?>
          </div>
        </section>

        <!-- Read Reminders Section -->
        <section class="reminder-section" id="read-reminders-section">
          <h3><span class="status-indicator status-read">✓</span> Read Reminders</h3>
          <div class="reminder-grid" id="read-reminders-grid">
          <?php
          // Track if we found any read reminders
          $hasReadReminders = false;

          // Display read reminders
          if ($result && $result->num_rows > 0) {
              // Reset the result pointer to the beginning
              if ($result->data_seek(0)) {
                  while ($row = $result->fetch_assoc()) {
                      // ... (fetch data as before) ...
                      $reminder_id = isset($row['Reminder_Record_ID']) ? $row['Reminder_Record_ID'] : 'General';
                      $event_date = isset($row['Event_Date']) ? $row['Event_Date'] : 'General';
                      $event_time = isset($row['Event_Time']) ? $row['Event_Time'] : 'N/A'; // Fetch Event_Time
                      $reminder_type = isset($row['Reminder_ID']) ? $row['Reminder_ID'] : 1;
                      $description = isset($row['Description']) ? $row['Description'] : 'No details available';
                      $staff_id = isset($row['Staff_ID']) ? $row['Staff_ID'] : 'Unknown';
                      $reminder_date_time = $row['reminder_date'] ?? null;

                      $formatted_reminder = 'N/A';
                      if ($reminder_date_time) { /* ... format date ... */
                          try {
                              $reminder_dt = new DateTime($reminder_date_time);
                              $formatted_reminder = $reminder_dt->format('Y-m-d H:i');
                          } catch (Exception $e) { $formatted_reminder = 'Invalid Date'; }
                      }

                      $staff_name = 'Unknown';
                      if ($roleTitle === "Admin") { /* ... get staff name ... */
                          $first_name = isset($row['First_Name']) ? $row['First_Name'] : '';
                          $last_name = isset($row['Last_Name']) ? $row['Last_Name'] : '';
                          if (!empty($first_name) || !empty($last_name)) {
                              $staff_name = trim($first_name . ' ' . $last_name);
                          } else { $staff_name = 'Staff #' . $staff_id; }
                      }

                      $shortDesc = $description;
                      if (strlen($description) > 50) { $shortDesc = substr($description, 0, 50) . '...'; }

                      // Only display read reminders (assuming type == 1 means read)
                      if ($reminder_type == 1) {
                          $hasReadReminders = true;
                          // Add data attributes for JS
                          echo '<div id="reminder-'.$reminder_id.'" class="reminder-box" ';
                          echo ' data-id="'.$reminder_id.'" data-date="'.$event_date.'" data-time="'.htmlspecialchars($event_time, ENT_QUOTES).'" data-staff="'.htmlspecialchars($staff_name, ENT_QUOTES).'" '; // Added data-time
                          echo ' data-description="'.htmlspecialchars($description, ENT_QUOTES).'" data-type="'.$reminder_type.'" data-reminder="'.htmlspecialchars($formatted_reminder, ENT_QUOTES).'" ';
                          echo ' onclick="showReminderDetailsFromElement(this)">'; // Use new function

                          echo '<div class="reminder-header">';
                          echo '<span class="status-indicator status-read">✓</span>'; // Read indicator
                          echo '<span class="reminder-id">ID: '.htmlspecialchars($reminder_id).'</span>';
                          echo '<span class="reminder-date">'.htmlspecialchars($event_date).'</span>';
                          echo '</div>';

                          if ($roleTitle === "Admin") {
                              echo '<div class="reminder-staff">Assigned to: '.htmlspecialchars($staff_name).'</div>';
                          }

                          echo '<div class="reminder-content">'.htmlspecialchars($shortDesc).'</div>';
                          echo '<div class="reminder-actions">';
                          // Use new JS functions for actions, pass ID
                          echo '<button class="btn-delete" onclick="deleteReminder(\''.$reminder_id.'\'); event.stopPropagation();">Delete</button>';
                          echo '<button class="btn-mark-unread" onclick="markReminderUnread(\''.$reminder_id.'\'); event.stopPropagation();">Unread</button>';
                          echo '</div>';
                          echo '</div>'; // End reminder-box
                      }
                  }
              }

              // Display message if no read reminders
              if (!$hasReadReminders) {
                  echo '<div class="no-reminders-message" id="no-read-reminders">No read reminders</div>';
              } else {
                  // Add hidden message for when JS removes all items
                  echo '<div class="no-reminders-message hidden" id="no-read-reminders">No read reminders</div>';
              }
          } else {
              // If $result itself is empty, we already showed "No reminders found" in the 'new' section check.
              // If $result has rows but none are read, the logic above handles it.
              // We might need a message here if $result exists but has 0 rows initially.
              if (!$hasNewReminders && !$hasReadReminders && $result && $result->num_rows == 0) {
                 echo '<div class="no-reminders-message">No reminders found</div>';
              } else if (!$hasReadReminders && $result && $result->num_rows > 0) {
                 // This case is handled above by the $hasReadReminders check inside the loop
                 echo '<div class="no-reminders-message" id="no-read-reminders">No read reminders</div>';
              }
          }
          ?>
          </div>
        </section>
      </div>
    </main>
  </div>

  <!-- Reminder Detail Modal -->
  <div id="reminderModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeReminderModal()" style="cursor: pointer">&times;</span>
      <h3>Reminder Details</h3>
      <div class="reminder-detail">
        <p><strong>ID:</strong> <span id="modalReminderId"></span></p>
        <p><strong>Date:</strong> <span id="modalReminderDate"></span></p>
        <p><strong>Time:</strong> <span id="modalReminderTime"></span></p> <!-- Added Time element -->
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
          <!-- Modal buttons call the AJAX functions directly -->
          <button id="modalBtnRead" class="btn-mark-read" onclick="markReminderRead(currentReminderId); closeReminderModal();">Mark as Read</button>
          <button id="modalBtnUnread" class="btn-mark-unread" onclick="markReminderUnread(currentReminderId); closeReminderModal();">Mark as Unread</button>
          <button class="btn-delete" onclick="deleteReminder(currentReminderId); closeReminderModal();">Delete</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Global variables for current reminder in modal
    let currentReminderId = null;
    // No need for currentReminderType globally anymore if we fetch from element

    // --- Helper Functions ---

    // Function to insert an element into a grid while maintaining ascending order by data-id
    function insertSorted(grid, element) {
        const elementId = parseInt(element.dataset.id, 10);
        let inserted = false;
        const existingElements = grid.querySelectorAll('.reminder-box');

        for (let i = 0; i < existingElements.length; i++) {
            const existingElement = existingElements[i];
            const existingElementId = parseInt(existingElement.dataset.id, 10);

            if (elementId < existingElementId) {
                grid.insertBefore(element, existingElement);
                inserted = true;
                break;
            }
        }

        // If not inserted yet (either grid was empty or elementId is largest), append it
        if (!inserted) {
            grid.appendChild(element);
        }
    }

    function sendAjaxRequest(action, reminderId, callback) {
        const reminderElement = document.getElementById('reminder-' + reminderId);
        if (reminderElement) {
            reminderElement.classList.add('processing'); // Visual feedback
        }

        const xhr = new XMLHttpRequest();
        const url = `manageEntity.php?type=reminder&action=${action}&id=${reminderId}&ajax=1`; // Add ajax=1 flag
        xhr.open('GET', url, true); // Use GET for simplicity here, POST might be better RESTfully

        xhr.onload = function() {
            if (reminderElement) {
                reminderElement.classList.remove('processing');
            }
            if (xhr.status >= 200 && xhr.status < 300) {
                // Success
                console.log(`AJAX Success: Action=${action}, ID=${reminderId}`);
                if (callback) callback(true, reminderId);
            } else {
                // Failure
                console.error(`AJAX Error: Status ${xhr.status} for Action=${action}, ID=${reminderId}`);
                alert(`Failed to ${action} reminder. Please try again.`);
                if (callback) callback(false, reminderId);
            }
        };

        xhr.onerror = function() {
            // Network error
            if (reminderElement) {
                reminderElement.classList.remove('processing');
            }
            console.error(`AJAX Network Error: Action=${action}, ID=${reminderId}`);
            alert('Network error. Please check your connection and try again.');
            if (callback) callback(false, reminderId);
        };

        xhr.send();
    }

    function updateUIMessages() {
        // Show/Hide "No new/read reminders" messages
        const newReminderGrid = document.getElementById('new-reminders-grid');
        const readReminderGrid = document.getElementById('read-reminders-grid');
        const noNewMsg = document.getElementById('no-new-reminders');
        const noReadMsg = document.getElementById('no-read-reminders');

        if (noNewMsg) noNewMsg.classList.toggle('hidden', newReminderGrid.querySelectorAll('.reminder-box').length > 0);
        if (noReadMsg) noReadMsg.classList.toggle('hidden', readReminderGrid.querySelectorAll('.reminder-box').length > 0);
    }


    // --- Core Action Functions ---

    function markReminderRead(reminderId) {
        sendAjaxRequest('markread', reminderId, function(success) {
            if (success) {
                const element = document.getElementById('reminder-' + reminderId);
                if (!element) return;

                const readGrid = document.getElementById('read-reminders-grid');

                // 1. Update Status Indicator
                const statusIndicator = element.querySelector('.status-indicator');
                if (statusIndicator) {
                    statusIndicator.classList.remove('status-new');
                    statusIndicator.classList.add('status-read');
                    statusIndicator.textContent = '✓';
                }

                // 2. Update Action Buttons
                const actionsDiv = element.querySelector('.reminder-actions');
                if (actionsDiv) {
                    const readButton = actionsDiv.querySelector('.btn-mark-read');
                    if(readButton) {
                        readButton.textContent = 'Unread';
                        readButton.classList.remove('btn-mark-read');
                        readButton.classList.add('btn-mark-unread');
                        readButton.onclick = (event) => { markReminderUnread(reminderId); event.stopPropagation(); };
                    }
                }

                // 3. Update Data Attribute
                element.dataset.type = '1'; // Mark as read type

                // 4. Move Element to Read Grid (Sorted)
                insertSorted(readGrid, element);

                // 5. Update Messages
                updateUIMessages();
            }
        });
    }

    function markReminderUnread(reminderId) {
        sendAjaxRequest('markunread', reminderId, function(success) {
            if (success) {
                const element = document.getElementById('reminder-' + reminderId);
                if (!element) return;

                const newGrid = document.getElementById('new-reminders-grid');

                // 1. Update Status Indicator
                const statusIndicator = element.querySelector('.status-indicator');
                if (statusIndicator) {
                    statusIndicator.classList.remove('status-read');
                    statusIndicator.classList.add('status-new');
                    statusIndicator.textContent = ''; // Or use a dot/icon if preferred
                }

                // 2. Update Action Buttons
                const actionsDiv = element.querySelector('.reminder-actions');
                if (actionsDiv) {
                    const unreadButton = actionsDiv.querySelector('.btn-mark-unread');
                     if(unreadButton) {
                        unreadButton.textContent = 'Read';
                        unreadButton.classList.remove('btn-mark-unread');
                        unreadButton.classList.add('btn-mark-read');
                        unreadButton.onclick = (event) => { markReminderRead(reminderId); event.stopPropagation(); };
                    }
                }

                 // 3. Update Data Attribute
                element.dataset.type = '0'; // Mark as unread type (assuming 0 or non-1)

                // 4. Move Element to New Grid (Sorted)
                insertSorted(newGrid, element);

                // 5. Update Messages
                updateUIMessages();
            }
        });
    }

    function deleteReminder(reminderId) {
        if (!confirm('Are you sure you want to delete this reminder?')) {
            return;
        }
        sendAjaxRequest('delete', reminderId, function(success) {
            if (success) {
                const element = document.getElementById('reminder-' + reminderId);
                if (element) {
                    element.remove(); // Remove element from DOM
                    updateUIMessages(); // Update messages after deletion
                }
            }
        });
    }

    // --- Modal Functions ---

    // Updated function to populate modal from element's data attributes
    function showReminderDetailsFromElement(element) {
        currentReminderId = element.dataset.id;
        const type = parseInt(element.dataset.type, 10); // Get type from data attribute

        console.log(`Modal opened for ID: ${currentReminderId}, Type: ${type}`); // Debug log

        document.getElementById('modalReminderId').textContent = element.dataset.id;
        document.getElementById('modalReminderDate').textContent = element.dataset.date;
        document.getElementById('modalReminderTime').textContent = element.dataset.time; // Display event time
        document.getElementById('modalReminderReminderDate').textContent = element.dataset.reminder;
        document.getElementById('modalReminderStatus').textContent = (type === 1) ? 'Read' : 'Unread';

        const staffElement = document.getElementById('modalReminderStaff');
        if (staffElement) {
            staffElement.textContent = element.dataset.staff;
        }

        document.getElementById('modalReminderDescription').textContent = element.dataset.description;

        // Show/hide read/unread buttons based on current status (type)
        document.getElementById('modalBtnRead').style.display = (type === 1) ? 'none' : 'inline-block';
        document.getElementById('modalBtnUnread').style.display = (type === 1) ? 'inline-block' : 'none';

        document.getElementById('reminderModal').style.display = 'block';
    }


    function closeReminderModal() {
      document.getElementById('reminderModal').style.display = 'none';
      currentReminderId = null; // Clear current ID when closing
    }

    // Modal action buttons now directly call the AJAX functions and then close the modal
    // (No separate markAsRead, markAsUnread, deleteFromModal needed unless adding extra modal-specific logic)

    // --- Initial Setup & Other Functions ---

    document.addEventListener('DOMContentLoaded', function() {
      // Initial UI message update
      updateUIMessages();

      // Scroll to element if hash is present (keep this functionality)
      if (window.location.hash && window.location.hash.startsWith('#reminder-')) {
         // ... (scrolling logic remains the same) ...
         try {
          const elementId = window.location.hash.substring(1); // Remove the '#'
          const elementToScrollTo = document.getElementById(elementId);
          if (elementToScrollTo) {
            setTimeout(() => {
                elementToScrollTo.scrollIntoView({ behavior: 'auto', block: 'center' });
            }, 150);
          } else { console.warn('Element to scroll to not found:', elementId); }
        } catch (e) { console.error('Error scrolling to element:', e); }
      }

    });

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