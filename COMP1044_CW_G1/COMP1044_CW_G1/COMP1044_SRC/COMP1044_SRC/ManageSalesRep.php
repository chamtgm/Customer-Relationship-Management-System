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

$roleTitle = $_SESSION["Role_Title"];
$staffID = $_SESSION["Staff_ID"];

// Ensure only Admins can access this page
if ($roleTitle !== "Admin") {
    echo "<p>Access denied. This page is only accessible to Admins.</p>";
    exit();
}

// Get the staff ID from the URL
if (isset($_GET['type'], $_GET['action'], $_GET['id']) && $_GET['type'] === 'staff' && $_GET['action'] === 'edit') {
    $selectedStaffID = intval($_GET['id']);

    // Fetch sales representative details
    $staffQuery = "SELECT First_Name, Last_Name, Email FROM staff WHERE Staff_ID = ?";
    $stmt = $conn->prepare($staffQuery);
    $stmt->bind_param("i", $selectedStaffID);
    $stmt->execute();
    $staffResult = $stmt->get_result();
    $staffDetails = $staffResult->fetch_assoc();
    $stmt->close();

    if (!$staffDetails) {
        echo "<p>Invalid sales representative ID.</p>";
        exit();
    }

    // Fetch customers registered by the sales representative
    $customerQuery = "SELECT * FROM customer WHERE Staff_ID = ?";
    $stmt = $conn->prepare($customerQuery);
    $stmt->bind_param("i", $selectedStaffID);
    $stmt->execute();
    $customerResult = $stmt->get_result();
    $stmt->close();

    // Fetch leads registered by the sales representative
    $leadQuery = "SELECT * FROM lead WHERE Staff_ID = ?";
    $stmt = $conn->prepare($leadQuery);
    $stmt->bind_param("i", $selectedStaffID);
    $stmt->execute();
    $leadResult = $stmt->get_result();
    $stmt->close();

    // Fetch interactions registered by the sales representative
    $interactionQuery = "
        SELECT
            i.Interaction_ID,
            i.Interaction_Date,
            i.Interaction_Type,
            i.Description,
            COALESCE(CONCAT(c.First_Name, ' ', c.Last_Name), CONCAT(l.First_Name, ' ', l.Last_Name)) AS EntityName,
            CASE
                WHEN i.Customer_ID IS NOT NULL THEN 'Customer'
                WHEN i.Lead_ID IS NOT NULL THEN 'Lead'
                ELSE 'Unknown'
            END AS EntityType,
            COALESCE(i.Customer_ID, i.Lead_ID) AS EntityID
        FROM interaction i
        LEFT JOIN customer c ON i.Customer_ID = c.Customer_ID
        LEFT JOIN lead l ON i.Lead_ID = l.Lead_ID
        WHERE i.Staff_ID = ?
        ORDER BY i.Interaction_Date DESC
    ";
    $stmt = $conn->prepare($interactionQuery);
    $stmt->bind_param("i", $selectedStaffID);
    $stmt->execute();
    $interactionResult = $stmt->get_result();
    $stmt->close();
} else {
    echo "<p>Invalid request.</p>";
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Sales Representative</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <!-- ===== MAIN CONTENT ===== -->
    <div class="main-content">
        <header>
            <h2>Manage Sales Representative: <?php echo htmlspecialchars($staffDetails['First_Name'] . ' ' . $staffDetails['Last_Name']); ?></h2>
        </header>

        <main>
            <!-- Customers Table -->
            <h3>Customers</h3>
            <table>
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Company</th>
                        <th>Address</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($customerResult && $customerResult->num_rows > 0): ?>
                        <?php while ($row = $customerResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Customer_ID']); ?></td>
                                <td><?php echo htmlspecialchars($row['First_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td><?php echo htmlspecialchars($row['Phone_Number']); ?></td>
                                <td><?php echo htmlspecialchars($row['Company']); ?></td>
                                <td><?php echo htmlspecialchars($row['Address']); ?></td>
                                <td>
                                    <a href='manageEntity.php?type=customer&action=edit&id=<?php echo $row['Customer_ID']; ?>' class='btn btn-edit'>Edit</a>
                                    <!-- Add Delete button if needed -->
                                    <!-- <a href='manageEntity.php?type=customer&action=delete&id=<?php echo $row['Customer_ID']; ?>' class='btn btn-delete' onclick='return confirm("Are you sure?");'>Delete</a> -->
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">No customers found for this representative.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <br>

            <!-- Leads Table -->
            <h3>Leads</h3>
            <table>
                <thead>
                    <tr>
                        <th>Lead ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Phone Number</th>
                        <th>Company</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($leadResult && $leadResult->num_rows > 0): ?>
                        <?php while ($row = $leadResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Lead_ID']); ?></td>
                                <td><?php echo htmlspecialchars($row['First_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Last_Name']); ?></td>
                                <td><?php echo htmlspecialchars($row['Email']); ?></td>
                                <td><?php echo htmlspecialchars($row['Phone_Number']); ?></td>
                                <td><?php echo htmlspecialchars($row['Company']); ?></td>
                                <td>
                                    <a href='manageEntity.php?type=lead&action=edit&id=<?php echo $row['Lead_ID']; ?>' class='btn btn-edit'>Edit</a>
                                    <!-- Add Delete button if needed -->
                                    <!-- <a href='manageEntity.php?type=lead&action=delete&id=<?php echo $row['Lead_ID']; ?>' class='btn btn-delete' onclick='return confirm("Are you sure?");'>Delete</a> -->
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No leads found for this representative.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <br>

            <!-- Interactions Table -->
            <h3>Interactions</h3>
            <table>
                <thead>
                    <tr>
                        <th>Interaction ID</th>
                        <th>Type</th> <!-- Changed Header -->
                        <th>Name</th> <!-- Changed Header -->
                        <th>Date</th>
                        <th>Interaction Type</th>
                        <th>Description</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($interactionResult && $interactionResult->num_rows > 0): ?>
                        <?php while ($row = $interactionResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['Interaction_ID']); ?></td>
                                <td><?php echo htmlspecialchars($row['EntityType']); ?></td> <!-- Changed Data -->
                                <td><?php echo htmlspecialchars($row['EntityName']); ?></td> <!-- Changed Data -->
                                <td><?php echo htmlspecialchars($row['Interaction_Date']); ?></td>
                                <td><?php echo htmlspecialchars($row['Interaction_Type']); ?></td>
                                <td><?php echo htmlspecialchars($row['Description']); ?></td>
                                <td>
                                    <a href='manageEntity.php?type=interaction&action=edit&id=<?php echo $row['Interaction_ID']; ?>' class='btn btn-edit'>Edit</a>
                                    <!-- Add Delete button if needed -->
                                    <!-- <a href='manageEntity.php?type=interaction&action=delete&id=<?php echo $row['Interaction_ID']; ?>' class='btn btn-delete' onclick='return confirm("Are you sure?");'>Delete</a> -->
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7">No interactions found for this representative.</td> <!-- Adjusted colspan -->
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>