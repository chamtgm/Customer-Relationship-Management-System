<?php
session_start();

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
$loggedInStaffID = $_SESSION["Staff_ID"]; // Renamed to avoid confusion
$roleTitle = $_SESSION["Role_Title"]; // Retrieve role title from session

// --- Fetch Customers ---
$customerSql = "SELECT Customer_ID, First_Name, Last_Name FROM customer ORDER BY Last_Name, First_Name";
$customerResult = $conn->query($customerSql);
$customers = [];
if ($customerResult && $customerResult->num_rows > 0) {
    while($row = $customerResult->fetch_assoc()) {
        $customers[] = $row;
    }
}

// --- Fetch Leads ---
$leadSql = "SELECT Lead_ID, First_Name, Last_Name FROM lead ORDER BY Last_Name, First_Name";
$leadResult = $conn->query($leadSql);
$leads = [];
if ($leadResult && $leadResult->num_rows > 0) {
    while($row = $leadResult->fetch_assoc()) {
        $leads[] = $row;
    }
}

// --- Fetch Sales Representatives (Only if Admin) ---
$salesReps = [];
if ($roleTitle === "Admin") {
    // Use Role_ID = 2 to fetch Sales Representatives
    $salesRepSql = "SELECT Staff_ID, First_Name, Last_Name FROM staff WHERE Role_ID = 2 ORDER BY Last_Name, First_Name";
    $salesRepResult = $conn->query($salesRepSql);
    if ($salesRepResult && $salesRepResult->num_rows > 0) {
        while($row = $salesRepResult->fetch_assoc()) {
            $salesReps[] = $row;
        }
    }
}

// Handle form submission for adding a new interaction
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_interaction"])) {
    $entityType = $_POST["entity_type"]; // 'Customer' or 'Lead'
    $customerID = ($entityType === 'Customer' && !empty($_POST["customer_id"])) ? $_POST["customer_id"] : null;
    $leadID = ($entityType === 'Lead' && !empty($_POST["lead_id"])) ? $_POST["lead_id"] : null;
    $interactionDate = $_POST["interaction_date"];
    $interactionType = $_POST["interaction_type"];
    $description = $_POST["description"];

    // Determine the Staff ID to insert
    $staffIDToInsert = $loggedInStaffID; // Default to logged-in user
    if ($roleTitle === "Admin" && isset($_POST["assigned_staff_id"]) && !empty($_POST["assigned_staff_id"])) {
        $staffIDToInsert = $_POST["assigned_staff_id"];
    } elseif ($roleTitle === "Admin" && empty($_POST["assigned_staff_id"])) {
        // Admin must select a staff member if the dropdown is shown
        echo "<script>alert('Admin must assign a Sales Representative.');</script>";
        // Optionally, prevent further execution or handle differently
        // For now, we'll let the validation below catch it if needed, or proceed if not strictly required
    }

    // Validate that either Customer_ID or Lead_ID is provided based on type
    if (($entityType === 'Customer' && empty($customerID)) || ($entityType === 'Lead' && empty($leadID))) {
        echo "<script>alert('Please select a valid " . $entityType . ".');</script>";
    } else {
        // Assuming 'interaction' table has nullable Customer_ID and Lead_ID columns
        $sql = "INSERT INTO interaction (Customer_ID, Lead_ID, Interaction_Date, Interaction_Type, Description, Staff_ID)
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        // Bind parameters: customerID (i), leadID (i), interactionDate (s), interactionType (s), description (s), staffIDToInsert (i)
        $stmt->bind_param("iisssi", $customerID, $leadID, $interactionDate, $interactionType, $description, $staffIDToInsert);

        if ($stmt->execute()) {
            header("Location: Interactions.php?success=Updated");
            exit();
        } else {
            header("Location: Interactions.php?error=UpdateFailed");
            exit();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Interactions</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        /* Initially hide lead dropdown */
        #lead-group { display: none; }
    </style>
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <header>
            <h2>Manage Interactions</h2>
        </header>

        <main>
        <!-- Form to Add New Interaction -->
        <form method="post" action="ManageInteraction.php" class="form-container">
            <div class="form-group">
                <label for="interaction_type" class="input-label">Type:</label>
                <select name="interaction_type" id="interaction_type" class="input-field" required>
                    <option value="Call">Call</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Email">Email</option>
                </select>
            </div>

            <div class="form-group">
                <label for="description" class="input-label">Description:</label>
                <textarea name="description" id="description" class="input-field" required></textarea>
            </div>

            <div class="form-group">
                <label for="interaction_date" class="input-label">Date:</label>
                <input type="date" name="interaction_date" id="interaction_date" class="input-field" required value="<?php echo date('Y-m-d'); // Default to today's date ?>" />
            </div>

            <!-- Entity Type Selection -->
            <div class="form-group">
                <label for="entity_type" class="input-label">Associate With:</label>
                <select name="entity_type" id="entity_type" class="input-field" required onchange="toggleEntityDropdown()">
                    <option value="Customer">Customer</option>
                    <option value="Lead">Lead</option>
                </select>
            </div>

            <!-- Customer Dropdown -->
            <div class="form-group" id="customer-group">
                <label for="customer_id" class="input-label">Customer:</label>
                <select name="customer_id" id="customer_id" class="input-field">
                    <option value="">-- Select Customer --</option>
                    <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo htmlspecialchars($customer['Customer_ID']); ?>">
                            <?php echo htmlspecialchars($customer['Customer_ID']) . ' - ' . htmlspecialchars($customer['Last_Name']) . ' ' . htmlspecialchars($customer['First_Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Lead Dropdown (Initially Hidden) -->
            <div class="form-group" id="lead-group">
                <label for="lead_id" class="input-label">Lead:</label>
                <select name="lead_id" id="lead_id" class="input-field">
                    <option value="">-- Select Lead --</option>
                    <?php foreach ($leads as $lead): ?>
                        <option value="<?php echo htmlspecialchars($lead['Lead_ID']); ?>">
                            <?php echo htmlspecialchars($lead['Lead_ID']) . ' - ' . htmlspecialchars($lead['Last_Name']) . ' ' . htmlspecialchars($lead['First_Name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Assign Staff Dropdown (Admin Only) -->
            <?php if ($roleTitle === "Admin"): ?>
                <div class="form-group">
                    <label for="assigned_staff_id" class="input-label">Assign to Sales Rep:</label>
                    <select name="assigned_staff_id" id="assigned_staff_id" class="input-field" required>
                        <option value="">-- Select Sales Rep --</option>
                        <?php foreach ($salesReps as $rep): ?>
                            <option value="<?php echo htmlspecialchars($rep['Staff_ID']); ?>">
                                <?php echo htmlspecialchars($rep['Staff_ID']) . ' - ' . htmlspecialchars($rep['Last_Name']) . ' ' . htmlspecialchars($rep['First_Name']); ?>
                            </option>
                        <?php endforeach; ?>
                        <?php if (empty($salesReps)): ?>
                            <option value="" disabled>No Sales Representatives found</option>
                        <?php endif; ?>
                    </select>
                </div>
            <?php endif; ?>

            <button type="submit" name="add_interaction" class="form-button">Add Interaction</button>
        </form>
    </main>
</div>

<script>
    function toggleEntityDropdown() {
        const entityType = document.getElementById('entity_type').value;
        const customerGroup = document.getElementById('customer-group');
        const leadGroup = document.getElementById('lead-group');
        const customerSelect = document.getElementById('customer_id');
        const leadSelect = document.getElementById('lead_id');

        if (entityType === 'Customer') {
            customerGroup.style.display = 'block';
            leadGroup.style.display = 'none';
            customerSelect.required = true; // Make customer required
            leadSelect.required = false;    // Make lead not required
            leadSelect.value = '';          // Clear lead selection
        } else if (entityType === 'Lead') {
            customerGroup.style.display = 'none';
            leadGroup.style.display = 'block';
            customerSelect.required = false; // Make customer not required
            leadSelect.required = true;     // Make lead required
            customerSelect.value = '';       // Clear customer selection
        } else {
             // Should not happen with current options, but good practice
            customerGroup.style.display = 'block';
            leadGroup.style.display = 'none';
            customerSelect.required = true;
            leadSelect.required = false;
        }
    }

    // Initialize on page load in case of errors/reloads
    document.addEventListener('DOMContentLoaded', toggleEntityDropdown);
</script>

</body>
</html>