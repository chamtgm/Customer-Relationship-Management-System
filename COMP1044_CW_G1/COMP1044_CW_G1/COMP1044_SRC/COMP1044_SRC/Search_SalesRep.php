<?php
session_start(); // Start session at the top
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "comp1044_database";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header("Location: LoginPage.php");
    exit();
}

$userEmail = $_SESSION["Email"]; // Retrieve email from session
$roleTitle = $_SESSION["Role_Title"]; // Retrieve role title from session
$staffID = $_SESSION["Staff_ID"]; // Retrieve Staff_ID from session

// Get source page for redirecting
$sourcePage = isset($_GET['source']) ? $_GET['source'] : 'Settings';

// Get search query
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Redirect if the search query is empty
if (empty($searchQuery)) {
    header("Location: {$sourcePage}.php?error=empty_search");
    exit();
}

// Escape user input
$searchQuery = "%" . $conn->real_escape_string($searchQuery) . "%";

// Define the SQL query based on the user's role
if ($roleTitle === "Admin") {
    $sql = "
        SELECT Staff_ID, First_Name, Last_Name, Email, Role_Title 
        FROM staff 
        JOIN role ON staff.Role_ID = role.Role_ID
        WHERE (First_Name LIKE ? OR Last_Name LIKE ? OR Email LIKE ?)
        AND Role_Title != 'Admin'
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $searchQuery, $searchQuery, $searchQuery);
} else {
    header("Location: HomePage.php");
    exit();
}

$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Search Results</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>

    <?php include 'sidebar.php'; ?>

<!-- ===== MAIN CONTENT ===== -->
<div class="main-content">
    <header>
        <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" fill="#888">
                <path d="M19.021 17.271 14.8 13.05q-.75.65-1.75 1.025-1 .375-2.1.375-2.625 0-4.462-1.837Q4.65 10.775 4.65 8.15q0-2.625 1.838-4.463Q8.325 1.85 10.95 1.85q2.625 0 4.462 1.837Q17.25 5.525 17.25 8.15q0 1.1-.388 2.125T15.863 12l4.2 4.2q.275.275.275.625 0 .35-.275.625-.275.275-.625.275-.35 0-.625-.275ZM10.95 9.95q.75 0 1.35-.6t.6-1.35q0-.75-.6-1.35t-1.35-.6q-.75 0-1.35.6t-.6 1.35q0 .75.6 1.35t1.35.6Z"/>
            </svg>
            <form method="get" action="Search_SalesRep.php">
                <input type="search" name="q" placeholder="Search Sales Rep..." />
                <input type="hidden" name="type" value="staff" />
            </form>
        </div>
        <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
    </header>

    <!-- SEARCH RESULTS -->
    <main>
        <div class="dashboard-header">
            <h2>Search Results for "<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"</h2>
            <button class="btn-back" onclick="window.location.href='Settings.php'">Back</button>
        </div>

        <table class="styled-table">
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