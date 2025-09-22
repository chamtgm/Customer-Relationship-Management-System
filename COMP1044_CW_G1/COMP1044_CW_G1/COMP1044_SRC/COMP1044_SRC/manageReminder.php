<?php
// filepath: c:\xampp\htdocs\assignment\manageReminder.php
session_start();

// Set the default timezone to Malaysia
date_default_timezone_set('Asia/Kuala_Lumpur');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "comp1044_database";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header("Location: LoginPage.php");
    exit();
}

$userEmail = $_SESSION["Email"];
$staffID = $_SESSION["Staff_ID"]; // Current logged-in staff ID
$roleTitle = $_SESSION["Role_Title"]; // Retrieve role title from session

// --- Variables for the form ---
$reminder_id = null;
$description = '';
$event_date = ''; // Stored as DATE
$event_time = ''; // Stored as TIME
$staff_id_assigned = ''; // ID of staff assigned to the reminder
$customer_id_related = '';
$lead_id_related = '';
$reminder_date_db = ''; // The raw reminder_date from DB (DATETIME)
$reminder_interval_selected = ''; // To try and pre-select interval
$page_mode = 'add'; // Default to adding a new reminder
$reminder_type_edit = ''; // To store the type ('Customer' or 'Lead') in edit mode
$error = null; // Initialize error variable

// --- Fetch Sales Reps (Needed for both Add and Edit Admin view) ---
$salesReps = [];
if ($roleTitle === "Admin") {
    $salesRepQuery = "SELECT Staff_ID, First_Name, Last_Name, Email FROM staff WHERE Role_ID = 2"; // Assuming Role_ID 2 is Sales Rep
    $salesRepResult = $conn->query($salesRepQuery);
    if ($salesRepResult) {
        while ($rep = $salesRepResult->fetch_assoc()) {
            $salesReps[] = $rep;
        }
    } else {
        // Handle error fetching sales reps if necessary
        error_log("Error fetching sales reps: " . $conn->error);
    }
}

// --- Fetch Customers for Dropdown ---
$customers = [];
$customerSql = "SELECT Customer_ID, First_Name, Last_Name FROM customer ORDER BY Last_Name, First_Name";
$customerResult = $conn->query($customerSql);
if ($customerResult) {
    while ($cust = $customerResult->fetch_assoc()) {
        $customers[] = $cust;
    }
} else {
    error_log("Error fetching customers: " . $conn->error);
}

// --- Fetch Leads for Dropdown ---
$leads = [];
$leadSql = "SELECT Lead_ID, First_Name, Last_Name FROM lead ORDER BY Last_Name, First_Name";
$leadResult = $conn->query($leadSql);
if ($leadResult) {
    while ($lead = $leadResult->fetch_assoc()) {
        $leads[] = $lead;
    }
} else {
    error_log("Error fetching leads: " . $conn->error);
}

// --- Check if we are in EDIT mode ---
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $reminder_id = filter_var($_GET['id'], FILTER_VALIDATE_INT);
    $page_mode = 'edit';

    if ($reminder_id) {
        // Fetch the existing reminder data
        // Ensure logged-in user has permission (Admin sees all, Staff sees own)
        if ($roleTitle === 'Admin') {
            $stmt = $conn->prepare("SELECT * FROM reminder_record WHERE Reminder_Record_ID = ?");
            $stmt->bind_param("i", $reminder_id);
        } else {
            // Non-admin can only edit their own reminders
            $stmt = $conn->prepare("SELECT * FROM reminder_record WHERE Reminder_Record_ID = ? AND Staff_ID = ?");
            $stmt->bind_param("ii", $reminder_id, $staffID);
        }

        if ($stmt) {
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $reminder_data = $result->fetch_assoc();
                // Populate variables with fetched data
                $description = $reminder_data['Description'] ?? '';
                $event_date = $reminder_data['Event_Date'] ?? ''; // Should be YYYY-MM-DD
                $event_time = $reminder_data['Event_Time'] ?? ''; // Should be HH:MM:SS or HH:MM
                // Format time for input type="time" which expects HH:MM
                if ($event_time) {
                    try {
                         $timeObj = new DateTime($event_time);
                         $event_time = $timeObj->format('H:i'); // Format as HH:MM
                    } catch (Exception $e) {
                        $event_time = ''; // Handle invalid time format
                    }
                }

                $staff_id_assigned = $reminder_data['Staff_ID'] ?? '';
                $customer_id_related = $reminder_data['Customer_ID'] ?? '';
                $lead_id_related = $reminder_data['Lead_ID'] ?? '';
                $reminder_date_db = $reminder_data['reminder_date'] ?? ''; // Raw DATETIME string

                // Determine the type for edit mode
                if (!empty($customer_id_related)) {
                    $reminder_type_edit = 'Customer';
                } elseif (!empty($lead_id_related)) {
                    $reminder_type_edit = 'Lead';
                }

                // --- Attempt to determine selected interval (optional complexity) ---
                if ($event_date && $event_time && $reminder_date_db) {
                    try {
                        $eventDateTime = new DateTime($event_date . ' ' . $event_time, new DateTimeZone('Asia/Kuala_Lumpur'));
                        $reminderDateTime = new DateTime($reminder_date_db, new DateTimeZone('Asia/Kuala_Lumpur'));
                        $diff = $eventDateTime->diff($reminderDateTime);

                        // Calculate total difference in hours/days/weeks to match options
                        $total_hours = $diff->h + ($diff->days * 24);
                        $total_days = $diff->days;

                        if ($diff->invert === 1) { // Ensure reminder is before event
                            if ($total_days >= 7 && $total_days % 7 === 0) {
                                $reminder_interval_selected = '+1 week'; // Simplification: only checks for exactly 1 week
                            } elseif ($total_days === 2) {
                                $reminder_interval_selected = '+2 days';
                            } elseif ($total_days === 1) {
                                $reminder_interval_selected = '+1 day';
                            } elseif ($total_hours === 3) {
                                $reminder_interval_selected = '+3 hours';
                            } elseif ($total_hours === 2) {
                                $reminder_interval_selected = '+2 hours';
                            } elseif ($total_hours === 1) {
                                $reminder_interval_selected = '+1 hour';
                            }
                        }
                    } catch (Exception $e) {
                        // Ignore errors in interval calculation, leave dropdown unselected
                    }
                }
                // --- End interval calculation attempt ---

            } else {
                // Reminder not found or user doesn't have permission
                $error = "Error: Reminder not found or you do not have permission to edit it.";
                // Optional: Redirect or display error prominently and exit
                // header("Location: ReminderPage.php?error=notfound"); exit();
            }
            $stmt->close();
        } else {
            // SQL error preparing statement
            $error = "Error preparing statement: " . $conn->error;
        }
    } else {
        // Invalid ID format
        $error = "Error: Invalid Reminder ID.";
    }
}

// --- Handle FORM SUBMISSION (POST request) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- UPDATE Reminder ---
    if (isset($_POST['update_reminder']) && $page_mode === 'edit' && isset($_POST['reminder_id'])) {
        $reminder_id_update = filter_var($_POST['reminder_id'], FILTER_VALIDATE_INT);

        // Validate ID again
        if (!$reminder_id_update) {
            $error = "Invalid Reminder ID for update.";
        } else {
            // Get submitted data (use htmlspecialchars or filter as needed)
            $description_update = $_POST['description'];
            $event_date_update = $_POST['interaction_date']; // YYYY-MM-DD
            $event_time_update = $_POST['Event_Time']; // HH:MM
            $reminder_interval_update = $_POST['reminder_interval'];
            // Staff ID can only be changed by Admin
            $staff_id_update = ($roleTitle === 'Admin') ? $_POST['staff_id'] : $staffID; // Use logged-in staffID if not admin

            // --- Recalculate reminder_date ---
            $eventDateTimeStr_update = $event_date_update . ' ' . $event_time_update;
            $reminderDateTime_update = null;
            if (!empty($reminder_interval_update) && !empty($eventDateTimeStr_update)) {
                 try {
                    $eventDateTime = new DateTime($eventDateTimeStr_update, new DateTimeZone('Asia/Kuala_Lumpur'));
                    $intervalSpec = '';
                    switch ($reminder_interval_update) {
                        case '+1 hour': $intervalSpec = 'PT1H'; break;
                        case '+2 hours': $intervalSpec = 'PT2H'; break;
                        case '+3 hours': $intervalSpec = 'PT3H'; break;
                        case '+1 day': $intervalSpec = 'P1D'; break;
                        case '+2 days': $intervalSpec = 'P2D'; break;
                        case '+1 week': $intervalSpec = 'P1W'; break;
                    }
                    if (!empty($intervalSpec)) {
                        $interval = new DateInterval($intervalSpec);
                        $reminderDateTimeObj = clone $eventDateTime;
                        $reminderDateTimeObj->sub($interval);
                        $reminderDateTime_update = $reminderDateTimeObj->format('Y-m-d H:i:s');
                    }
                 } catch (Exception $e) {
                     $error = "Error calculating reminder date during update: " . $e->getMessage();
                     $reminderDateTime_update = null;
                 }
            }
            // --- End reminder date calculation ---

            // Proceed with update if no calculation error
            if (!isset($error)) {
                 // Prepare UPDATE statement
                 // DO NOT update Customer_ID or Lead_ID
                 // Reset Reminder_ID (status) to 2 (unread) on edit
                 $updateSql = "UPDATE reminder_record SET
                                    Description = ?,
                                    Event_Date = ?,
                                    Event_Time = ?,
                                    Staff_ID = ?,
                                    reminder_date = ?,
                                    Reminder_ID = 2
                               WHERE Reminder_Record_ID = ?";

                 // Add permission check for non-admins
                 if ($roleTitle !== 'Admin') {
                     $updateSql .= " AND Staff_ID = ?"; // Ensure non-admin only updates their own
                 }

                 $stmtUpdate = $conn->prepare($updateSql);
                 if ($stmtUpdate) {
                     if ($roleTitle === 'Admin') {
                         $stmtUpdate->bind_param("sssssi",
                             $description_update,
                             $event_date_update,
                             $event_time_update, // Use HH:MM format
                             $staff_id_update,
                             $reminderDateTime_update, // Calculated reminder datetime or null
                             $reminder_id_update
                         );
                     } else {
                         $stmtUpdate->bind_param("sssssii", // Add Staff_ID for WHERE clause
                             $description_update,
                             $event_date_update,
                             $event_time_update, // Use HH:MM format
                             $staff_id_update, // This will be the logged-in user's ID
                             $reminderDateTime_update, // Calculated reminder datetime or null
                             $reminder_id_update,
                             $staffID // For the WHERE clause check
                         );
                     }

                     if ($stmtUpdate->execute()) {
                         if ($stmtUpdate->affected_rows > 0) {
                            // Redirect back with success message and hash
                            header("Location: ReminderPage.php?success=updated#reminder-" . $reminder_id_update);
                            exit();
                         } else {
                             // Either no changes were made, or permission denied (for non-admin)
                             $error = "No changes were made, or permission denied.";
                         }
                     } else {
                         $error = "Failed to update reminder: " . $stmtUpdate->error;
                     }
                     $stmtUpdate->close();
                 } else {
                     $error = "Error preparing update statement: " . $conn->error;
                 }
            }
        }
    // --- ADD Reminder (Original Logic) ---
    } elseif (isset($_POST['add_reminder']) && $page_mode === 'add') {
        // --- Start of Add Logic ---
        $description = $_POST['description'];
        $interaction_date_str = $_POST['interaction_date']; // e.g., "2025-04-25"
        $event_time_str = $_POST['Event_Time'];         // e.g., "10:00"
        $reminder_type = $_POST['reminder_type'];
        // Staff ID assignment depends on role
        $assigned_staff_id = ($roleTitle === 'Admin') ? $_POST['staff_id'] : $staffID; // Use logged-in staffID if not admin
        $customer_id = ($reminder_type === 'Customer' && isset($_POST['customer_id'])) ? filter_var($_POST['customer_id'], FILTER_VALIDATE_INT) : null;
        $lead_id = ($reminder_type === 'Lead' && isset($_POST['lead_id'])) ? filter_var($_POST['lead_id'], FILTER_VALIDATE_INT) : null;
        $reminder_interval_str = $_POST['reminder_interval'];

        // Validate required fields for add mode
        if (empty($description) || empty($interaction_date_str) || empty($event_time_str) || empty($assigned_staff_id)) {
             $error = "Missing required fields for adding reminder.";
        }
        if ($reminder_type === 'Customer' && empty($customer_id)) {
             $error = "Customer ID is required when type is Customer.";
        }
        if ($reminder_type === 'Lead' && empty($lead_id)) {
             $error = "Lead ID is required when type is Lead.";
        }


        // Combine date and time for reminder calculation
        $eventDateTimeStr = $interaction_date_str . ' ' . $event_time_str; // e.g., "2025-04-25 10:00"

        // Calculate Reminder_DateTime based on the combined event date/time and interval
        $reminderDateTime = null; // Initialize as null
        if (!isset($error) && !empty($reminder_interval_str) && !empty($eventDateTimeStr)) {
            try {
                $eventDateTime = new DateTime($eventDateTimeStr, new DateTimeZone('Asia/Kuala_Lumpur'));
                $intervalSpec = '';
                switch ($reminder_interval_str) {
                    case '+1 hour': $intervalSpec = 'PT1H'; break;
                    case '+2 hours': $intervalSpec = 'PT2H'; break;
                    case '+3 hours': $intervalSpec = 'PT3H'; break;
                    case '+1 day': $intervalSpec = 'P1D'; break;
                    case '+2 days': $intervalSpec = 'P2D'; break;
                    case '+1 week': $intervalSpec = 'P1W'; break;
                }
                if (!empty($intervalSpec)) {
                    $interval = new DateInterval($intervalSpec);
                    $reminderDateTimeObj = clone $eventDateTime;
                    $reminderDateTimeObj->sub($interval);
                    $reminderDateTime = $reminderDateTimeObj->format('Y-m-d H:i:s');
                }
            } catch (Exception $e) {
                $error = "Error calculating reminder date: " . $e->getMessage();
                $reminderDateTime = null;
            }
        }

        // Use the separate date and time strings for their respective columns
        $eventDateForDB = $interaction_date_str; // Use the date string directly
        $eventTimeForDB = date('H:i:s', strtotime($event_time_str)); // Explicitly format the time

        // Proceed only if no error occurred
        if (!isset($error)) {
            if ($roleTitle === 'Admin' && $assigned_staff_id === 'all') {
                // Insert reminder for all sales representatives
                $allSuccess = true;
                foreach ($salesReps as $rep) {
                    $insertSql = "INSERT INTO reminder_record (Staff_ID, Customer_ID, Lead_ID, Description, Event_Date, Reminder_ID, reminder_date, Event_Time)
                                  VALUES (?, ?, ?, ?, ?, 2, ?, ?)";
                    $stmt = $conn->prepare($insertSql);
                    $stmt->bind_param("iiissss", $rep['Staff_ID'], $customer_id, $lead_id, $description, $eventDateForDB, $reminderDateTime, $eventTimeForDB);
                    if (!$stmt->execute()) {
                        $error = "Failed to add reminder for " . $rep['First_Name'] . " " . $rep['Last_Name'] . ": " . $stmt->error;
                        $allSuccess = false;
                        $stmt->close();
                        break; // Exit loop on first error
                    }
                    $stmt->close();
                }
                if ($allSuccess) {
                     header("Location: ReminderPage.php?success=added_all");
                     exit();
                }
            } else {
                // Insert reminder for a single sales representative (or non-admin's own)
                $insertSql = "INSERT INTO reminder_record (Staff_ID, Customer_ID, Lead_ID, Description, Event_Date, Reminder_ID, reminder_date, Event_Time)
                              VALUES (?, ?, ?, ?, ?, 2, ?, ?)";
                $stmt = $conn->prepare($insertSql);
                $stmt->bind_param("iiissss", $assigned_staff_id, $customer_id, $lead_id, $description, $eventDateForDB, $reminderDateTime, $eventTimeForDB);

                if ($stmt->execute()) {
                    header("Location: ReminderPage.php?success=added");
                    exit();
                } else {
                    $error = "Failed to add reminder: " . $stmt->error;
                }
                 $stmt->close();
            }
        }
        // --- End of Add Logic ---
    }
} // End of POST handling

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Title changes based on mode -->
    <title><?php echo ($page_mode === 'edit' ? 'Edit' : 'Add'); ?> Reminder - ABB CRM</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* Optional: Style disabled/readonly fields for clarity */
        select[disabled], input[readonly] {
            background-color: #eee; /* Light grey background */
            cursor: not-allowed;    /* Indicate non-interactive */
            opacity: 0.7;           /* Slightly faded */
        }
        .error-message {
             color: red;
             background-color: #f8d7da;
             padding: 10px;
             border: 1px solid #f5c6cb;
             border-radius: 5px;
             margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <header>
             <!-- Display Title based on Mode -->
            <h2><?php echo ($page_mode === 'edit' ? 'Edit Reminder (ID: ' . htmlspecialchars($reminder_id) . ')' : 'Add New Reminder'); ?></h2>
        </header>

        <main>
        <?php
        // Display error message if one occurred during page load (GET) or POST processing
        if (!empty($error)) {
            echo "<p class='error-message'>Error: " . htmlspecialchars($error) . "</p>";
        }
        ?>

        <!-- Form action includes edit parameters if applicable -->
        <form method="post" action="manageReminder.php<?php echo ($page_mode === 'edit' ? '?action=edit&id=' . $reminder_id : ''); ?>" class="form-container">
            <?php if ($page_mode === 'edit'): ?>
                <!-- Hidden field to identify the record being edited during POST -->
                <input type="hidden" name="reminder_id" value="<?php echo htmlspecialchars($reminder_id); ?>">
            <?php endif; ?>

            <div class="form-group">
                <label for="reminder_type" class="input-label">Type:</label>
                <!-- Disable dropdown in edit mode, keep onchange only for add mode -->
                <select name="reminder_type" id="reminder_type" class="input-field" required <?php echo ($page_mode === 'edit' ? 'disabled' : 'onchange="toggleIdField()"'); ?>>
                    <!-- Pre-select based on $reminder_type_edit in edit mode -->
                    <option value="Customer" <?php echo ($page_mode === 'edit' && $reminder_type_edit === 'Customer') ? 'selected' : ''; ?>>Customer</option>
                    <option value="Lead" <?php echo ($page_mode === 'edit' && $reminder_type_edit === 'Lead') ? 'selected' : ''; ?>>Lead</option>
                </select>
                <?php if ($page_mode === 'edit'): ?>
                    <!-- Add a hidden input to submit the type value if needed, though it's better not to update it -->
                    <!-- <input type="hidden" name="reminder_type_hidden" value="<?php echo htmlspecialchars($reminder_type_edit); ?>"> -->
                <?php endif; ?>
            </div>

            <!-- Customer ID Field: Changed to Select -->
            <div class="form-group" id="customer_id_group">
                <label for="customer_id" class="input-label">Customer:</label>
                <select name="customer_id" id="customer_id" class="input-field" <?php echo ($page_mode === 'edit' ? 'disabled' : ''); ?>>
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $customer): ?>
                        <?php
                            $selected = ($page_mode === 'edit' && $customer['Customer_ID'] == $customer_id_related) ? 'selected' : '';
                            $display_name = htmlspecialchars($customer['Customer_ID'] . ' - ' . $customer['Last_Name'] . ' ' . $customer['First_Name']);
                        ?>
                        <option value="<?php echo htmlspecialchars($customer['Customer_ID']); ?>" <?php echo $selected; ?>>
                            <?php echo $display_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                 <?php if ($page_mode === 'edit'): ?>
                    <!-- Hidden input to pass the value when disabled -->
                    <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customer_id_related); ?>">
                <?php endif; ?>
            </div>

            <!-- Lead ID Field: Changed to Select -->
            <div class="form-group" id="lead_id_group" style="display: none;">
                <label for="lead_id" class="input-label">Lead:</label>
                 <select name="lead_id" id="lead_id" class="input-field" <?php echo ($page_mode === 'edit' ? 'disabled' : ''); ?>>
                    <option value="">-- Select Lead --</option>
                     <?php foreach ($leads as $lead): ?>
                        <?php
                            $selected = ($page_mode === 'edit' && $lead['Lead_ID'] == $lead_id_related) ? 'selected' : '';
                            $display_name = htmlspecialchars($lead['Lead_ID'] . ' - ' . $lead['Last_Name'] . ' ' . $lead['First_Name']);
                        ?>
                        <option value="<?php echo htmlspecialchars($lead['Lead_ID']); ?>" <?php echo $selected; ?>>
                            <?php echo $display_name; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                 <?php if ($page_mode === 'edit'): ?>
                    <!-- Hidden input to pass the value when disabled -->
                     <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($lead_id_related); ?>">
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="description" class="input-label">Details:</label>
                <!-- Autofill description -->
                <textarea name="description" id="description" class="input-field" required><?php echo htmlspecialchars($description); ?></textarea>
            </div>

            <div class="form-group">
                <label for="interaction_date" class="input-label">Event Date:</label> <!-- Changed label back -->
                 <!-- Autofill event date -->
                <input type="date" name="interaction_date" id="interaction_date" class="input-field" required value="<?php echo htmlspecialchars($event_date); ?>" />
            </div>

            <div class="form-group">
                <label for="Event_Time" class="input-label">Event Time:</label>
                <select name="Event_Time" id="Event_Time" class="input-field" required>
                    <option value="" >-- Select Time --</option>
                    <?php
                    // Generate time options from 8:00 AM to 6:00 PM in 15-minute intervals
                    $start = strtotime('00:00');
                    $end = strtotime('23:45'); // 6:00 PM

                    // Determine the currently selected value (from session, edit record, or default)
                    // Assuming the stored value is in 'H:i:s' or 'H:i' format
                    $selectedTimeValue = '';
                    if (isset($_SESSION['form_data']['Event_Time'])) {
                        $selectedTimeValue = date('H:i', strtotime($_SESSION['form_data']['Event_Time']));
                    } elseif ($page_mode === 'edit' && isset($event_time)) {
                        $selectedTimeValue = date('H:i', strtotime($event_time));
                    }

                    for ($time = $start; $time <= $end; $time += 900) { // 900 seconds = 15 minutes
                        $timeValue = date('H:i', $time); // Value for the option (e.g., 16:00)
                        $timeDisplay = date('g:ia', $time); // Display format (e.g., 4:00pm)

                        // Check if this option should be selected
                        $isSelected = ($timeValue === $selectedTimeValue) ? 'selected' : '';

                        echo "<option value=\"{$timeValue}\" {$isSelected}>{$timeDisplay}</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label for="reminder_interval" class="input-label">Remind Me Before Event:</label>
                <select name="reminder_interval" id="reminder_interval" class="input-field">
                    <!-- Pre-select interval if calculated -->
                    <option value="" <?php echo ($reminder_interval_selected === '') ? 'selected' : ''; ?>>No Reminder</option>
                    <option value="+1 hour" <?php echo ($reminder_interval_selected === '+1 hour') ? 'selected' : ''; ?>>1 Hour Before</option>
                    <option value="+2 hours" <?php echo ($reminder_interval_selected === '+2 hours') ? 'selected' : ''; ?>>2 Hours Before</option>
                    <option value="+3 hours" <?php echo ($reminder_interval_selected === '+3 hours') ? 'selected' : ''; ?>>3 Hours Before</option>
                    <option value="+1 day" <?php echo ($reminder_interval_selected === '+1 day') ? 'selected' : ''; ?>>1 Day Before</option>
                    <option value="+2 days" <?php echo ($reminder_interval_selected === '+2 days') ? 'selected' : ''; ?>>2 Days Before</option>
                    <option value="+1 week" <?php echo ($reminder_interval_selected === '+1 week') ? 'selected' : ''; ?>>1 Week Before</option>
                </select>
            </div>

            <?php if ($roleTitle === "Admin"): ?>
                <div class="form-group">
                    <label for="staff_id" class="input-label">Assign to Sales Representative:</label>
                    <select name="staff_id" id="staff_id" class="input-field" required>
                        <option value="">Select Sales Representative</option>
                        <!-- "All" option only makes sense for adding, not editing -->
                        <?php if ($page_mode === 'add'): ?>
                            <option value="all">All Sales Representatives</option>
                        <?php endif; ?>
                        <?php foreach ($salesReps as $rep): ?>
                            <?php $selected = ($rep['Staff_ID'] == $staff_id_assigned) ? ' selected' : ''; ?>
                            <option value="<?php echo htmlspecialchars($rep['Staff_ID']); ?>"<?php echo $selected; ?>>
                                <?php echo htmlspecialchars($rep['First_Name'] . ' ' . $rep['Last_Name'] . ' (' . $rep['Email'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; // End of Admin-only assignment section ?>

            <!-- Change button text and name based on mode -->
            <button type="submit" name="<?php echo ($page_mode === 'edit' ? 'update_reminder' : 'add_reminder'); ?>" class="form-button">
                <?php echo ($page_mode === 'edit' ? 'Update Reminder' : 'Add Reminder'); ?>
            </button>
            <!-- Cancel button always goes back to the list -->
            <button type="button" onclick="window.location.href='ReminderPage.php'" class="form-button form-button-cancel">Cancel</button>
        </form>

        <script>
            function toggleIdField() {
                // console.log('Running toggleIdField...'); // Uncomment for debugging
                const reminderTypeSelect = document.getElementById('reminder_type');
                const customerGroup = document.getElementById('customer_id_group');
                const leadGroup = document.getElementById('lead_id_group');
                // Target the SELECT elements now
                const customerSelect = document.getElementById('customer_id');
                const leadSelect = document.getElementById('lead_id');

                // Check if all required elements were found
                if (!reminderTypeSelect || !customerGroup || !leadGroup || !customerSelect || !leadSelect) {
                    console.error('Error in toggleIdField: One or more form elements not found. Check HTML IDs.');
                    return; // Stop execution if elements are missing
                }

                const reminderType = reminderTypeSelect.value; // Read value even if disabled
                // console.log('Selected Type:', reminderType); // Uncomment for debugging

                // Determine if in edit mode by checking if selects are disabled
                const isEditMode = reminderTypeSelect.disabled; // Type select is disabled in edit mode

                // Default state: hide both groups and remove required attribute (if not disabled)
                customerGroup.style.display = 'none';
                leadGroup.style.display = 'none';
                if (!isEditMode) { // Only manage required attribute in add mode
                    customerSelect.required = false;
                    leadSelect.required = false;
                }


                // Show the correct group based on the selected type
                if (reminderType === 'Customer') {
                    // console.log('Setting display for Customer'); // Uncomment for debugging
                    customerGroup.style.display = 'block';
                    // Set required only if in add mode
                    if (!isEditMode) {
                        customerSelect.required = true;
                    }
                } else if (reminderType === 'Lead') {
                    // console.log('Setting display for Lead'); // Uncomment for debugging
                    leadGroup.style.display = 'block';
                    // Set required only if in add mode
                    if (!isEditMode) {
                        leadSelect.required = true;
                    }
                }
            }

             // Call on page load to set initial visibility based on pre-selected type
            document.addEventListener('DOMContentLoaded', function() {
                // console.log('DOM fully loaded.'); // Uncomment for debugging
                toggleIdField();
            });
        </script>
    </main>
</div>
</body>
</html>