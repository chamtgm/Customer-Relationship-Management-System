<?php
session_start(); // Start session at the top
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "comp1044_database";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header("Location: LoginPage.php");
    exit();
}

// Retrieve session variables
$staffID = $_SESSION["Staff_ID"];
$roleTitle = $_SESSION["Role_Title"];

// Determine if we're managing 'customer', 'lead', or 'interaction'
$type = isset($_GET['type']) ? $_GET['type'] : 'customer';

// Handle reminder buttons
if ($type === 'reminder') {
    $action = $_GET['action'] ?? '';
    if ($action === 'delete' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "DELETE FROM reminder_record WHERE Reminder_Record_ID = $id";
        if ($conn->query($sql) === TRUE) {
            header("Location: ReminderPage.php?success=Deleted");
        } else {
            header("Location: ReminderPage.php?error=CannotDelete");
        }
        exit();
    }

    if ($action === 'markread' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "UPDATE reminder_record SET Reminder_ID = 1 WHERE Reminder_Record_ID = $id";
        if ($conn->query($sql) === TRUE) {
            header("Location: ReminderPage.php");
        } else {
            echo "Error marking as read: " . $conn->error;
        }
        exit();
    }
    
    if ($action === 'markunread' && isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $sql = "UPDATE reminder_record SET Reminder_ID = 2 WHERE Reminder_Record_ID = $id";
        if ($conn->query($sql) === TRUE) {
            header("Location: ReminderPage.php");
        } else {
            echo "Error marking as unread: " . $conn->error;
        }
        exit();
    }
}

// Map table/ID/columns based on $type
if ($type === 'lead') {
    $tableName = 'lead';
    $primaryKey = 'Lead_ID';
    $columns = [
        'Last_Name',
        'First_Name',
        'Email',
        'Address',
        'Company',
        'Notes',
        'Status',
        'Phone_Number',
        'Staff_ID'
    ];
} elseif ($type === 'interaction') {
    $tableName = 'interaction';
    $primaryKey = 'Interaction_ID';
    $columns = [
        'Lead_ID',       // Add Lead_ID
        'Interaction_Date',
        'Interaction_Type',
        'Description',
        'Staff_ID' // Add Staff_ID for interactions
    ];
    // Add Customer_ID back separately for data handling, but not direct form generation
    $interactionColumns = array_merge(['Customer_ID'], $columns);
} else {
    $tableName = 'customer';
    $primaryKey = 'Customer_ID';
    $columns = [
        'Last_Name',
        'First_Name',
        'Email',
        'Address',
        'Company',
        'Phone_Number',
        'Staff_ID'
    ];
}

// Handle CRUD Actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

/**
 * DELETE a record
 */
if ($action === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Handle deletion for each type
    if ($type === 'staff') {
        // If trying to delete self as admin, prevent it
        if ($id == $staffID && $roleTitle === "Admin") {
            header("Location: Settings.php?error=DeleteSelf");
            exit();
        }
        
        // Check for related records
        $customerCheck = $conn->query("SELECT COUNT(*) AS count FROM customer WHERE Staff_ID = $id");
        $leadCheck = $conn->query("SELECT COUNT(*) AS count FROM lead WHERE Staff_ID = $id");
        $interactionCheck = $conn->query("SELECT COUNT(*) AS count FROM interaction WHERE Staff_ID = $id");

        $customerCount = $customerCheck->fetch_assoc()['count'];
        $leadCount = $leadCheck->fetch_assoc()['count'];
        $interactionCount = $interactionCheck->fetch_assoc()['count'];

        if ($customerCount > 0 || $leadCount > 0 || $interactionCount > 0) {
            // Redirect back with an error message
            header("Location: Settings.php?error=CannotDelete");
            exit();
        }
        
        // If no related records, proceed with deletion
        $tableName = 'staff';
        $primaryKey = 'Staff_ID';
        
        $sql = "DELETE FROM $tableName WHERE $primaryKey = $id";
        if ($conn->query($sql) === TRUE) {
            header("Location: Settings.php?success=Deleted");
        } else {
            if ($conn->errno == 1451) {  // Foreign key constraint error
                header("Location: Settings.php?error=HasRelationships");
            } else {
                header("Location: Settings.php?error=CannotDelete");
            }
        }
        exit();
    } elseif ($type === 'customer') {
        $tableName = 'customer';
        $primaryKey = 'Customer_ID';
        
        // Check if the customer has interactions
        $interactionCheck = $conn->query("SELECT COUNT(*) AS count FROM interaction WHERE Customer_ID = $id");
        $interactionCount = $interactionCheck->fetch_assoc()['count'];
        
        // Also check for reminders referencing this customer
        $reminderCheck = $conn->query("SELECT COUNT(*) AS count FROM reminder_record WHERE Customer_ID = $id");
        $reminderCount = $reminderCheck->fetch_assoc()['count'];
        
        if ($interactionCount > 0 || $reminderCount > 0) {
            // Redirect back with an error message
            header("Location: CustomerPage.php?error=HasRelatedRecords");
            exit();
        }
    } elseif ($type === 'lead') {
        $tableName = 'lead';
        $primaryKey = 'Lead_ID';
        
        // Check if the lead has interactions
        $interactionCheck = $conn->query("SELECT COUNT(*) AS count FROM interaction WHERE Lead_ID = $id");
        $interactionCount = $interactionCheck->fetch_assoc()['count'];
        
        // Also check for reminders referencing this lead
        $reminderCheck = $conn->query("SELECT COUNT(*) AS count FROM reminder_record WHERE Lead_ID = $id");
        $reminderCount = $reminderCheck->fetch_assoc()['count'];
        
        if ($interactionCount > 0 || $reminderCount > 0) {
            // Redirect back with an error message
            header("Location: LeadPage.php?error=HasRelatedRecords");
            exit();
        }
    } elseif ($type === 'interaction') {
        $tableName = 'interaction';
        $primaryKey = 'Interaction_ID';
        
        $sql = "DELETE FROM $tableName WHERE $primaryKey = $id";
        if ($conn->query($sql) === TRUE) {
            header("Location: Interactions.php?success=Deleted");
        } else {
            if ($conn->errno == 1451) {  // Foreign key constraint error
                header("Location: Interactions.php?error=HasRelationships");
            } else {
                header("Location: Interactions.php?error=CannotDelete");
            }
        }
        exit();
    } else {
        // Invalid type
        header("Location: Settings.php?error=InvalidType");
        exit();
    }

    // Perform the DELETE operation
    $sql = "DELETE FROM $tableName WHERE $primaryKey = $id";
    if ($conn->query($sql) === TRUE) {
        // Redirect back with a success message
        if ($type === 'staff') {
            header("Location: Settings.php?success=Deleted");
        } elseif ($type === 'customer') {
            header("Location: CustomerPage.php?success=Deleted");
        } elseif ($type === 'lead') {
            header("Location: LeadPage.php?success=Deleted");
        } elseif ($type === 'interaction') {
            header("Location: Interactions.php?success=Deleted");
        }
        exit();
    } else {
        // Handle specific database errors
        if ($conn->errno == 1451) {  // MySQL error code for foreign key constraint violation
            if ($type === 'staff') {
                header("Location: Settings.php?error=HasRelationships");
            } elseif ($type === 'customer') {
                header("Location: CustomerPage.php?error=HasRelationships");
            } elseif ($type === 'lead') {
                header("Location: LeadPage.php?error=HasRelationships");
            } elseif ($type === 'interaction') {
                header("Location: Interactions.php?error=HasRelationships");
            }
        } else {
            // Generic error handling for other errors
            if ($type === 'staff') {
                header("Location: Settings.php?error=CannotDelete");
            } elseif ($type === 'customer') {
                header("Location: CustomerPage.php?error=CannotDelete");
            } elseif ($type === 'lead') {
                header("Location: LeadPage.php?error=CannotDelete");
            } elseif ($type === 'interaction') {
                header("Location: Interactions.php?error=CannotDelete");
            }
        }
        exit();
    }
}

/**
 * ADD / UPDATE a record
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [];
    $colsToProcess = ($type === 'interaction') ? $interactionColumns : $columns; // Use specific columns for interaction

    // --- MODIFIED: Handle Interaction Related Entity (Separate Dropdowns) ---
    if ($type === 'interaction' && isset($_POST['entity_type'])) {
        $entityType = $_POST['entity_type'];

        if ($entityType === 'customer' && isset($_POST['customer_id']) && !empty($_POST['customer_id'])) {
            $data['Customer_ID'] = intval($_POST['customer_id']);
            $data['Lead_ID'] = null;
        } elseif ($entityType === 'lead' && isset($_POST['lead_id']) && !empty($_POST['lead_id'])) {
            $data['Lead_ID'] = intval($_POST['lead_id']);
            $data['Customer_ID'] = null;
        } else {
            // Handle case where type is selected but ID isn't, or type isn't selected
            // Depending on requirements, you might want to redirect with an error
            // or allow interactions not linked to either. For now, set both to null.
            $data['Customer_ID'] = null;
            $data['Lead_ID'] = null;
            // Consider adding an error redirect here if a selection is mandatory
            // header("Location: manageEntity.php?type=interaction&error=noEntitySelected");
            // exit();
        }
        // Remove Customer_ID and Lead_ID from $colsToProcess to avoid double processing
        $colsToProcess = array_diff($colsToProcess, ['Customer_ID', 'Lead_ID']);
    }
    // --- END MODIFIED SECTION ---


    foreach ($colsToProcess as $col) {
        // Skip IDs handled above for interactions
        if ($type === 'interaction' && ($col === 'Customer_ID' || $col === 'Lead_ID')) {
            continue;
        }
        // Skip Staff_ID if it's handled by the admin dropdown later
        if ($col === 'Staff_ID' && $roleTitle === 'Admin') {
             continue; // Will be handled by the specific Staff_ID dropdown for admins
        }
        $value = $_POST[$col] ?? '';
        // Special handling for potential NULL values if needed, e.g., empty strings
        $data[$col] = ($value === '' && in_array($col, ['Notes', 'Company', 'Description'])) ? null : $conn->real_escape_string($value);
    }

    // Validate First_Name and Last_Name for leads and customers
    if (($type === 'lead' || $type === 'customer') && isset($data['First_Name'], $data['Last_Name'])) {
        $nameRegex = "/^[A-Za-z\s]+$/";
    
        if (!preg_match($nameRegex, $data['First_Name'])) {
            $_SESSION['form_data'] = $_POST; // Store submitted data in session
            header("Location: manageEntity.php?type=$type&error=invalidFirstName");
            exit();
        }
    
        if (!preg_match($nameRegex, $data['Last_Name'])) {
            $_SESSION['form_data'] = $_POST; // Store submitted data in session
            header("Location: manageEntity.php?type=$type&error=invalidLastName");
            exit();
        }
    }
    
    // Validate Email for leads and customers only
    if (($type === 'lead' || $type === 'customer') && isset($data['Email']) && !filter_var($data['Email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['form_data'] = $_POST; // Store submitted data in session
        header("Location: manageEntity.php?type=$type&error=invalidEmail");
        exit();
    }

    // Handle Staff_ID for Admins
    if ($roleTitle === 'Admin') {
        // Ensure Staff_ID is set, especially for interactions if not handled above
        if (!isset($data['Staff_ID'])) {
             $data['Staff_ID'] = intval($_POST['Staff_ID']);
        }
    } else {
        $data['Staff_ID'] = $staffID; // For non-admins, use their own Staff_ID
    }

    // Check for duplicate email and phone number across both tables
    $email = isset($data['Email']) ? $data['Email'] : null;
    $phoneNumber = isset($data['Phone_Number']) ? $data['Phone_Number'] : null;
    
    if ($type === 'customer' || $type === 'lead') {
        if ($email || $phoneNumber) {
            $duplicateCheckSql = "
                SELECT Email, Phone_Number, 'customer' AS SourceTable FROM customer WHERE Email = '$email' OR Phone_Number = '$phoneNumber'
                UNION
                SELECT Email, Phone_Number, 'lead' AS SourceTable FROM lead WHERE Email = '$email' OR Phone_Number = '$phoneNumber'
            ";
        
            if (!empty($_POST['record_id'])) {
                // Exclude the current record when updating
                $id = intval($_POST['record_id']);
                if ($type === 'customer') {
                    $duplicateCheckSql = "
                        SELECT Email, Phone_Number FROM customer 
                        WHERE (Email = '$email' OR Phone_Number = '$phoneNumber') AND Customer_ID != $id
                        UNION
                        SELECT Email, Phone_Number FROM lead 
                        WHERE Email = '$email' OR Phone_Number = '$phoneNumber'
                    ";
                } elseif ($type === 'lead') {
                    $duplicateCheckSql = "
                        SELECT Email, Phone_Number FROM lead 
                        WHERE (Email = '$email' OR Phone_Number = '$phoneNumber') AND Lead_ID != $id
                        UNION
                        SELECT Email, Phone_Number FROM customer 
                        WHERE Email = '$email' OR Phone_Number = '$phoneNumber'
                    ";
                }
            }
        
            if (!empty($duplicateCheckSql)) {
                $duplicateCheckResult = $conn->query($duplicateCheckSql);
                if ($duplicateCheckResult && $duplicateCheckResult->num_rows > 0) {
                    // Duplicate found
                    $_SESSION['form_data'] = $_POST; // Store submitted data in session
                    header("Location: manageEntity.php?type=$type&error=duplicate");
                    exit();
                }
            }
        }
    }

    if (!empty($_POST['record_id'])) {
        // UPDATE existing record
        $id = intval($_POST['record_id']);
    
        $setParts = [];
        foreach ($data as $col => $val) {
            if ($val === null) {
                $setParts[] = "`$col`=NULL"; // Use backticks for column names
            } else {
                // Escape again just to be safe, though done before
                $escapedVal = $conn->real_escape_string($val);
                $setParts[] = "`$col`='$escapedVal'";
            }
        }
        $setClause = implode(", ", $setParts);
        $sql = "UPDATE `$tableName` SET $setClause WHERE `$primaryKey` = $id"; // Use backticks
    
        if ($conn->query($sql) === TRUE) {
            if ($type === 'customer') {
                header("Location: CustomerPage.php?success=Updated");
            } elseif ($type === 'lead') {
                header("Location: LeadPage.php?success=Updated");
            } elseif ($type === 'interaction') {
                header("Location: Interactions.php?success=Updated");
            }
            exit();
        } else {
            // Log the error instead of displaying it
            error_log("Error updating record: " . $conn->error . " | SQL: " . $sql);
            // Redirect to an error page with a generic message
            header("Location: manageEntity.php?type=$type&error=updateFailed");
            exit();
        }
    } else {
        // ADD new record
        $colNames = implode(", ", array_map(function($col){ return "`$col`"; }, array_keys($data))); // Use backticks
        $colValues = implode(", ", array_map(function ($val) use ($conn) {
             if ($val === null) {
                 return "NULL";
             }
             // Escape again just to be safe
             return "'" . $conn->real_escape_string($val) . "'";
        }, $data));

        $sql = "INSERT INTO `$tableName` ($colNames) VALUES ($colValues)"; // Use backticks

        if ($conn->query($sql) === TRUE) {
            if ($type === 'customer') {
                header("Location: CustomerPage.php?success=Added");
            } elseif ($type === 'lead') {
                header("Location: LeadPage.php?success=Added");
            } elseif ($type === 'interaction') { // Added redirect for interaction
                header("Location: Interactions.php?success=Added");
            }
            exit();
        } else {
            echo "Error adding record: " . $conn->error . "<br>SQL: " . $sql; // Added SQL output for debugging
        }
    }
    exit();
}

/**
 * EDIT form: fetch record if action=edit
 */
$editRecord = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $sql = "SELECT * FROM $tableName WHERE $primaryKey = $id";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        $editRecord = $result->fetch_assoc();
    }
}

// --- ADDED: Fetch Customer List for Interaction Dropdown ---
$customerList = [];
if ($type === 'interaction') {
    $customerSql = "SELECT Customer_ID, First_Name, Last_Name FROM customer ORDER BY First_Name, Last_Name";
    $customerResult = $conn->query($customerSql);
    if ($customerResult && $customerResult->num_rows > 0) {
        while ($customerRow = $customerResult->fetch_assoc()) {
            $customerList[] = $customerRow;
        }
    }
}
// --- END ADDED SECTION ---

// --- ADDED: Fetch Lead List for Interaction Dropdown ---
$leadList = [];
if ($type === 'interaction') {
    $leadSql = "SELECT Lead_ID, First_Name, Last_Name FROM lead ORDER BY First_Name, Last_Name";
    $leadResult = $conn->query($leadSql);
    if ($leadResult && $leadResult->num_rows > 0) {
        while ($leadRow = $leadResult->fetch_assoc()) {
            $leadList[] = $leadRow;
        }
    }
}
// --- END ADDED SECTION ---


// --- MODIFIED: Fetch Staff List for Admin Dropdown (Only Sales Representatives) ---
$staffList = [];
if ($roleTitle === 'Admin') {
    // Modified query to only select staff with Sales Representative role
    $staffSql = "SELECT s.Staff_ID, s.First_Name, s.Last_Name 
                FROM staff s
                JOIN role r ON s.Role_ID = r.Role_ID
                WHERE r.Role_Title = 'Sales Representative'
                ORDER BY s.First_Name, s.Last_Name";
    $staffResult = $conn->query($staffSql);
    if ($staffResult && $staffResult->num_rows > 0) {
        while ($staffRow = $staffResult->fetch_assoc()) {
            $staffList[] = $staffRow;
        }
    }
}
// --- END MODIFIED SECTION ---

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage <?php echo ucfirst($type); ?></title>
    <link rel="stylesheet" href="home.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <!-- Add intl-tel-input CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css"/>
    <!-- Add intl-tel-input JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
</head>
<body>
<?php include 'sidebar.php'; ?>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    <header>
        <h2>Manage <?php echo ucfirst($type); ?></h2>
    </header>

    <!-- ===== FORM for Add / Edit ===== -->
    <section style="margin-bottom: 20px; margin-top: 20px;">
    <?php if (($type === 'customer' || $type === 'lead') && isset($_GET['error'])): ?>
        <div class="error-message" style="color: red; margin-bottom: 10px;" id="errorMsg">
            <?php if ($_GET['error'] === 'invalidFirstName'): ?>
                Error: First name cannot contain numbers or special characters.
            <?php elseif ($_GET['error'] === 'invalidLastName'): ?>
                Error: Last name cannot contain numbers or special characters.
            <?php elseif ($_GET['error'] === 'invalidEmail'): ?>
                Error: Please enter a valid email address.
            <?php elseif ($_GET['error'] === 'duplicate'): ?>
                Error: Email or Phone Number already exists in another record. Please use unique values.
            <?php endif; ?>
        </div>
        <script>
            // Remove the error parameter from the URL without reloading
            if (history.replaceState) {
                const url = new URL(window.location);
                url.searchParams.delete('error');
                history.replaceState(null, '', url);
            }
        </script>
    <?php endif; ?>


        <form method="post" action="manageEntity.php?type=<?php echo $type; ?>" class="form-container">
            <?php if ($editRecord): ?>
                <input type="hidden" name="record_id" value="<?php echo $editRecord[$primaryKey]; ?>">
            <?php endif; ?>

            <!-- Generate form fields dynamically based on $columns -->
            <?php
                // --- MODIFIED: Special Handling for Interaction Related Entity (Separate Dropdowns) ---
                if ($type === 'interaction') {
                    // Determine initial entity type and ID for edit mode or from session
                    $selectedEntityType = '';
                    $selectedCustomerId = '';
                    $selectedLeadId = '';
                    $displayCustomerName = '';
                    $displayLeadName = '';

                    if (isset($_SESSION['form_data']['entity_type'])) {
                        // If form data exists (e.g., validation error on ADD), use it
                        $selectedEntityType = $_SESSION['form_data']['entity_type'];
                        $selectedCustomerId = $_SESSION['form_data']['customer_id'] ?? '';
                        $selectedLeadId = $_SESSION['form_data']['lead_id'] ?? '';
                        // Note: We might not have the name readily available here if only IDs were stored.
                        // This part primarily handles the ADD scenario after an error.
                    } elseif ($editRecord) {
                        // If editing, use the record's data
                        if (!empty($editRecord['Customer_ID'])) {
                            $selectedEntityType = 'customer';
                            $selectedCustomerId = $editRecord['Customer_ID'];
                            // Find the customer name from the list
                            foreach ($customerList as $customer) {
                                if ($customer['Customer_ID'] == $selectedCustomerId) {
                                    $displayCustomerName = htmlspecialchars(trim($customer['First_Name'] . ' ' . $customer['Last_Name']));
                                    break;
                                }
                            }
                        } elseif (!empty($editRecord['Lead_ID'])) {
                            $selectedEntityType = 'lead';
                            $selectedLeadId = $editRecord['Lead_ID'];
                             // Find the lead name from the list
                            foreach ($leadList as $lead) {
                                if ($lead['Lead_ID'] == $selectedLeadId) {
                                    $displayLeadName = htmlspecialchars(trim($lead['First_Name'] . ' ' . $lead['Last_Name']));
                                    break;
                                }
                            }
                        }
                    }

                    // --- Display Logic ---
                    if ($editRecord) { // Display as plain text when editing
                        ?>
                        <div class="form-group">
                            <label class="input-label">Type:</label>
                            <p class="form-static-text"><?php echo ucfirst(htmlspecialchars($selectedEntityType)); ?></p>
                            <?php // Hidden field to submit the value ?>
                            <input type="hidden" name="entity_type" value="<?php echo htmlspecialchars($selectedEntityType); ?>">
                        </div>

                        <?php if ($selectedEntityType === 'customer'): ?>
                            <div class="form-group">
                                <label class="input-label">Customer:</label>
                                <p class="form-static-text"><?php echo $selectedCustomerId . ' - ' . $displayCustomerName; ?></p>
                                <?php // Hidden field to submit the value ?>
                                <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($selectedCustomerId); ?>">
                            </div>
                        <?php elseif ($selectedEntityType === 'lead'): ?>
                             <div class="form-group">
                                <label class="input-label">Lead:</label>
                                <p class="form-static-text"><?php echo $selectedLeadId . ' - ' . $displayLeadName; ?></p>
                                <?php // Hidden field to submit the value ?>
                                <input type="hidden" name="lead_id" value="<?php echo htmlspecialchars($selectedLeadId); ?>">
                            </div>
                        <?php endif; ?>

                        <?php
                    } else { // Display dropdowns when adding
                        ?>
                        <div class="form-group">
                            <label for="entity_type" class="input-label">Type:</label>
                            <select name="entity_type" id="entity_type" class="input-field" required>
                                <option value="">-- Select Type --</option>
                                <option value="customer" <?php echo ($selectedEntityType === 'customer') ? 'selected' : ''; ?>>Customer</option>
                                <option value="lead" <?php echo ($selectedEntityType === 'lead') ? 'selected' : ''; ?>>Lead</option>
                            </select>
                        </div>

                        <div class="form-group" id="customer-select-group" style="display: <?php echo ($selectedEntityType === 'customer') ? 'block' : 'none'; ?>;">
                            <label for="customer_id" class="input-label">Customer:</label>
                            <select name="customer_id" id="customer_id" class="input-field" <?php echo ($selectedEntityType === 'customer') ? 'required' : ''; ?>>
                                <option value="">-- Select Customer --</option>
                                <?php foreach ($customerList as $customer):
                                    $customerName = htmlspecialchars(trim($customer['First_Name'] . ' ' . $customer['Last_Name']));
                                    $isSelected = ($customer['Customer_ID'] == $selectedCustomerId) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $customer['Customer_ID']; ?>" <?php echo $isSelected; ?>>
                                        <?php echo $customer['Customer_ID'] . ' - ' . $customerName; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group" id="lead-select-group" style="display: <?php echo ($selectedEntityType === 'lead') ? 'block' : 'none'; ?>;">
                            <label for="lead_id" class="input-label">Lead:</label>
                            <select name="lead_id" id="lead_id" class="input-field" <?php echo ($selectedEntityType === 'lead') ? 'required' : ''; ?>>
                                <option value="">-- Select Lead --</option>
                                <?php foreach ($leadList as $lead):
                                    $leadName = htmlspecialchars(trim($lead['First_Name'] . ' ' . $lead['Last_Name']));
                                    $isSelected = ($lead['Lead_ID'] == $selectedLeadId) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo $lead['Lead_ID']; ?>" <?php echo $isSelected; ?>>
                                        <?php echo $lead['Lead_ID'] . ' - ' . $leadName; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php
                    } // End if/else for editRecord
                }
                // --- END MODIFIED SECTION ---


                foreach ($columns as $col) {
                    // Skip IDs handled above for interactions
                    if ($type === 'interaction' && ($col === 'Customer_ID' || $col === 'Lead_ID')) {
                        continue;
                    }
                    // Skip Staff_ID here, it will be handled separately for Admins
                    if ($col === 'Staff_ID') {
                        continue;
                    }

                    // Use submitted data if available, otherwise use the existing record's data
                    $value = isset($_SESSION['form_data'][$col]) ? $_SESSION['form_data'][$col] : ($editRecord && isset($editRecord[$col]) ? $editRecord[$col] : '');
                    ?>
                    <div class="form-group">
                        <label for="<?php echo $col; ?>" class="input-label"><?php echo ucfirst(str_replace('_', ' ', $col)); ?>:</label>

                        <?php /* --- REMOVED Customer_ID specific dropdown --- */ ?>

                        <?php if ($col === 'Status' && $type === 'lead'): // Only show Status dropdown for leads ?>
                            <select name="<?php echo $col; ?>" id="<?php echo $col; ?>" class="input-field" required>
                                <?php
                                $statusOptions = ['New', 'Contacted', 'In Progress', 'Closed'];
                                foreach ($statusOptions as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php if ($value === $option) echo 'selected'; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($col === 'Interaction_Type' && $type === 'interaction'): // Dropdown for Interaction Type ?>
                             <select name="<?php echo $col; ?>" id="<?php echo $col; ?>" class="input-field" required>
                                <?php
                                $interactionOptions = ['Call', 'Email', 'Meeting', 'Note'];
                                foreach ($interactionOptions as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php if ($value === $option) echo 'selected'; ?>>
                                        <?php echo $option; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php elseif ($col === 'Interaction_Date' && $type === 'interaction'): // Date input for Interaction Date ?>
                            <input
                                type="date"
                                name="<?php echo $col; ?>"
                                id="<?php echo $col; ?>"
                                value="<?php echo htmlspecialchars($value); ?>"
                                class="input-field"
                                required
                            >
                        <?php elseif ($col === 'Phone_Number'): // Special handling for Phone Number ?>
                            <input
                                type="tel" /* Use type="tel" for phone numbers */
                                name="<?php echo $col; ?>"
                                id="phone" /* Add an ID for the JS */
                                value="<?php echo htmlspecialchars($value); ?>"
                                class="input-field"
                                required
                            >
                            <!-- Hidden field to store the formatted phone number with country code -->
                            <input type="hidden" name="full_phone_number" id="full_phone_number" value="">
                        <?php else: // Default text input for other fields ?>
                            <input
                                type="text"
                                name="<?php echo $col; ?>"
                                id="<?php echo $col; ?>"
                                value="<?php echo htmlspecialchars($value); ?>"
                                class="input-field"
                                <?php echo ($col !== 'Notes' && $col !== 'Company' && $col !== 'Description') ? 'required' : ''; // Make Notes, Company, Description optional ?>
                            >
                        <?php endif; ?>
                    </div>
            <?php } ?>


            <!-- MODIFIED: Add Staff selection dropdown for Admins -->
            <?php if ($roleTitle === 'Admin'): ?>
                <div class="form-group">
                    <label for="Staff_ID" class="input-label">Assign to Sales Rep:</label>
                    <select name="Staff_ID" id="Staff_ID" class="input-field" required>
                        <option value="">-- Select Sales Rep --</option>
                        <?php
                        // Determine the selected Staff_ID (from session, edit record, or default)
                        $selectedStaffId = isset($_SESSION['form_data']['Staff_ID'])
                                           ? $_SESSION['form_data']['Staff_ID']
                                           : ($editRecord && isset($editRecord['Staff_ID']) ? $editRecord['Staff_ID'] : '');

                        foreach ($staffList as $staff):
                            $staffName = htmlspecialchars(trim($staff['First_Name'] . ' ' . $staff['Last_Name']));
                            $isSelected = ($staff['Staff_ID'] == $selectedStaffId) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $staff['Staff_ID']; ?>" <?php echo $isSelected; ?>>
                                <?php echo $staff['Staff_ID'] . ' - ' . $staffName; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <!-- END MODIFIED SECTION -->

            <button type="submit" class="form-button">
                <?php echo $editRecord ? "Update" : "Add"; ?> <?php echo ucfirst($type); ?>
            </button>
        </form>

        <!--  Clear session form data after form is displayed -->
        <?php unset($_SESSION['form_data']); ?>
    </section>

    <script>
        // Wait for the DOM to be fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            const entityTypeSelect = document.getElementById('entity_type');
            const customerGroup = document.getElementById('customer-select-group');
            const customerSelect = document.getElementById('customer_id');
            const leadGroup = document.getElementById('lead-select-group');
            const leadSelect = document.getElementById('lead_id');

            // Function to update visibility and required attribute
            function toggleRelatedSelects() {
                // Ensure elements exist before proceeding
                if (!entityTypeSelect || !customerGroup || !leadGroup || !customerSelect || !leadSelect) {
                    return;
                }
                // *** Do not run toggle logic if the type select is disabled (i.e., editing) ***
                if (entityTypeSelect.disabled) {
                    return;
                }

                const selectedType = entityTypeSelect.value;

                if (selectedType === 'customer') {
                    customerGroup.style.display = 'block'; // Show Customer section
                    customerSelect.required = true;
                    leadGroup.style.display = 'none';      // Hide Lead section
                    leadSelect.required = false;
                    leadSelect.value = ''; // Clear lead selection
                } else if (selectedType === 'lead') {
                    customerGroup.style.display = 'none';      // Hide Customer section
                    customerSelect.required = false;
                    customerSelect.value = ''; // Clear customer selection
                    leadGroup.style.display = 'block';     // Show Lead section
                    leadSelect.required = true;
                } else { // Nothing selected
                    customerGroup.style.display = 'none';
                    customerSelect.required = false;
                    customerSelect.value = '';
                    leadGroup.style.display = 'none';
                    leadSelect.required = false;
                    leadSelect.value = '';
                }
            }

            // Add event listener only if the type select exists AND is not disabled
            if (entityTypeSelect && !entityTypeSelect.disabled) {
                entityTypeSelect.addEventListener('change', toggleRelatedSelects);
            }

            // --- Existing validation script ---
            const form = document.querySelector('form.form-container'); // Target the specific form

            if (form) { // Only proceed if the form exists
                form.addEventListener('submit', function(event) {
                    // Get elements, they might be null if not applicable to the entity type
                    const firstNameInput = form.querySelector('input[name="First_Name"]');
                    const lastNameInput = form.querySelector('input[name="Last_Name"]');
                    const emailInput = form.querySelector('input[name="Email"]');

                    // Regex (using a case-insensitive flag for email)
                    const nameRegex = /^[A-Za-z\s]+$/;
                    // A common robust email regex pattern
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

                    let isValid = true;
                    let errorMessages = []; // Collect errors

                    // Validate First Name only if the input exists and fails the regex test
                    if (firstNameInput && !nameRegex.test(firstNameInput.value.trim())) {
                        errorMessages.push("First name cannot contain numbers or special characters.");
                        isValid = false;
                    }

                    // Validate Last Name only if the input exists and fails the regex test
                    if (lastNameInput && !nameRegex.test(lastNameInput.value.trim())) {
                        errorMessages.push("Last name cannot contain numbers or special characters.");
                        isValid = false;
                    }

                    // Validate Email only if the input exists and fails the regex test
                    if (emailInput && !emailRegex.test(emailInput.value.trim())) {
                        errorMessages.push("Please enter a valid email address.");
                        isValid = false;
                    }

                    // If any validation failed, prevent submission and show errors
                    if (!isValid) {
                        // Join messages with newlines for the alert
                        alert("Please fix the following errors:\n\n" + errorMessages.join("\n"));
                        event.preventDefault(); // Prevent form submission
                    }
                }); // Closes addEventListener callback
            } else {
                // Optional: Log a warning if the form isn't found, might help debugging
                // console.warn("ManageEntity form not found for validation script.");
            }
        }); // Closes DOMContentLoaded callback
    </script>

    <!-- Add script to initialize intl-tel-input -->
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const phoneInputField = document.querySelector("#phone");
        
        if (phoneInputField) {
          // Initialize with Malaysia as preferred country
          const phoneInput = window.intlTelInput(phoneInputField, {
            initialCountry: "my", // Set Malaysia as the initial country
            preferredCountries: ["my", "sg", "cn"], // Preferred countries in dropdown
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"
          });
          
          // Format existing number if present
          if (phoneInputField.value) {
            const phoneNumber = phoneInputField.value.trim();
            
            // If number doesn't start with +, assume it's a Malaysian number without country code
            if (!phoneNumber.startsWith('+')) {
              // Remove leading zeros if present
              let formattedNumber = phoneNumber.replace(/^0+/, '');
              // Set as Malaysian number (+60)
              phoneInput.setNumber('+60' + formattedNumber);
            } else {
              // If it already has country code, just set it
              phoneInput.setNumber(phoneNumber);
            }
          }

          // Handle form submission
          const form = phoneInputField.closest('form');
          if (form) {
            form.addEventListener('submit', function(e) {
              // Get full international number
              if (phoneInput.isValidNumber()) {
                const fullNumber = phoneInput.getNumber();
                phoneInputField.value = fullNumber; // Update the input value
              } else {
                // Optionally handle invalid phone numbers
                e.preventDefault();
                alert("Please enter a valid phone number.");
              }
            });
          }
        }
      });
    </script>

    <style>
        /* Optional: Add some basic styling for the static text */
        .form-static-text {
            padding: 8px 12px; /* Match input padding */
            border: 1px solid #ccc; /* Match input border */
            background-color: #eee; /* Indicate it's read-only */
            border-radius: 4px; /* Match input border-radius */
            min-height: 38px; /* Match input height */
            display: flex;
            align-items: center;
            word-break: break-word; /* Prevent long names from overflowing */
        }
    </style>

</div> <!-- Closes main-content -->
</body>
</html>