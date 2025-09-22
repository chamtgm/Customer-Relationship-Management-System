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

// Check if the user is logged in
if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header("Location: LoginPage.php");
    exit();
}

// Retrieve session variables
$userEmail = $_SESSION["Email"];
$roleTitle = $_SESSION["Role_Title"];
$staffID   = $_SESSION["Staff_ID"];

// Restrict access to Admins only
if ($roleTitle !== "Admin") {
    echo "<p>Access denied. This page is only accessible to Admins.</p>";
    exit();
}

// Fetch all staff (Admin + Sales Rep) with role names
$sql = "SELECT s.Staff_ID, s.First_Name, s.Last_Name, s.Email, s.Role_ID, r.Role_Title
        FROM staff s
        JOIN role r ON s.Role_ID = r.Role_ID
        ORDER BY (s.Staff_ID = ?) DESC, s.Role_ID ASC, s.Staff_ID ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $staffID);
$stmt->execute();
$result = $stmt->get_result();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - Sales Representatives Dashboard</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <header>
            <div class="search-wrapper">
                <form method="get" action="Search_SalesRep.php">
                    <input type="search" name="q" placeholder="Search Sales Rep..." />
                    <input type="hidden" name="source" value="Settings">
                </form>
            </div>
            <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
        </header>

        <main>
            <!-- Success Messages -->
            <?php if (isset($_GET['success']) && $_GET['success'] === 'Deleted'): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Success:</strong> Staff member deleted successfully.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'Updated'): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Success:</strong> Staff member updated successfully.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] === 'Added'): ?>
            <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Success:</strong> New staff member added successfully.
            </div>
            <?php endif; ?>
            
            <!-- Error Messages -->
            <?php if (isset($_GET['error']) && $_GET['error'] === 'CannotDelete'): ?>
            <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Error:</strong> Cannot delete this staff member because they have related customers, leads, or interactions.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'HasRelationships'): ?>
            <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Error:</strong> Cannot delete this staff member because they have related records. Please reassign those records first.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'DeleteSelf'): ?>
            <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Error:</strong> You cannot delete your own admin account.
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error']) && $_GET['error'] === 'empty_search'): ?>
            <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
                <strong>Error:</strong> Please enter a search term before searching.
            </div>
            <?php endif; ?>

            <div class="dashboard-header">
                <h2>Staff Dashboard</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Staff ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()):
                            $rowStaffId = (int)$row['Staff_ID'];
                            $isAdmin    = strtolower(trim($row['Role_Title'])) === 'admin';
                            $isLoggedInAdmin = $isAdmin && $rowStaffId === (int)$staffID;
                            $rowClass = $isLoggedInAdmin ? 'logged-in-admin' : '';
                        ?>
                        <tr class="<?php echo $rowClass; ?>">
                            <td><?php echo htmlspecialchars($row['Staff_ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['First_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Last_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Role_Title']); ?></td>
                            <td>
                                <?php if ($isAdmin): ?>
                                    <button class="btn-view" disabled>View Only</button>
                                <?php else: ?>
                                    <button class="btn-edit"
                                        onclick="window.location.href='manageSalesRep.php?type=staff&action=edit&id=<?php echo $row['Staff_ID']; ?>'">Edit</button>
                                    <button class="btn-delete"
                                        onclick="if(confirm('Are you sure you want to delete this sales representative?')) {
                                            window.location.href='manageEntity.php?type=staff&action=delete&id=<?php echo $row['Staff_ID']; ?>';
                                        }">Delete</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6">No staff found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>