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
$sourcePage = isset($_GET['source']) ? $_GET['source'] : 'HomePage';

// Get search query
$rawSearchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Redirect if the search query is empty
if (empty($rawSearchQuery)) {
    header("Location: {$sourcePage}.php?error=empty_search");
    exit();
}

// Prepare the search query for SQL
$searchQuery = "%" . $conn->real_escape_string($rawSearchQuery) . "%";

// Define the SQL query based on the user's role and search query
// --- MODIFIED SQL QUERIES TO INCLUDE ID and Type ---
if ($roleTitle === "Admin") {
    if (strcasecmp($rawSearchQuery, 'customer') === 0) {
        // If the search query is "customer", fetch all customers
        $sql = "
            SELECT Customer_ID AS ID, 'customer' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Customer' AS Customer_Type, Phone_Number, Company
            FROM customer
            ORDER BY Name ASC
        ";
        $stmt = $conn->prepare($sql);
    } elseif (strcasecmp($rawSearchQuery, 'lead') === 0) {
        // If the search query is "lead", fetch all leads
        $sql = "
            SELECT Lead_ID AS ID, 'lead' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Lead' AS Customer_Type, Phone_Number, Company
            FROM lead
            ORDER BY Name ASC
        ";
        $stmt = $conn->prepare($sql);
    } else {
        // Default search for both customers and leads
        $sql = "
            SELECT Customer_ID AS ID, 'customer' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Customer' AS Customer_Type, Phone_Number, Company
            FROM customer
            WHERE First_Name LIKE ? OR Last_Name LIKE ? OR Email LIKE ? OR Phone_Number LIKE ?

            UNION

            SELECT Lead_ID AS ID, 'lead' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Lead' AS Customer_Type, Phone_Number, Company
            FROM lead
            WHERE First_Name LIKE ? OR Last_Name LIKE ? OR Email LIKE ? OR Phone_Number LIKE ?
            ORDER BY Name ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssss', $searchQuery, $searchQuery, $searchQuery, $searchQuery,
                                      $searchQuery, $searchQuery, $searchQuery, $searchQuery);
    }
} else {
    if (strcasecmp($rawSearchQuery, 'customer') === 0) {
        // If the search query is "customer", fetch all customers assigned to the sales representative
        $sql = "
            SELECT Customer_ID AS ID, 'customer' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Customer' AS Customer_Type, Phone_Number, Company
            FROM customer
            WHERE Staff_ID = ?
            ORDER BY Name ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $staffID);
    } elseif (strcasecmp($rawSearchQuery, 'lead') === 0) {
        // If the search query is "lead", fetch all leads assigned to the sales representative
        $sql = "
            SELECT Lead_ID AS ID, 'lead' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Lead' AS Customer_Type, Phone_Number, Company
            FROM lead
            WHERE Staff_ID = ?
            ORDER BY Name ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $staffID);
    } else {
        // Default search for both customers and leads assigned to the sales representative
        $sql = "
            SELECT Customer_ID AS ID, 'customer' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Customer' AS Customer_Type, Phone_Number, Company
            FROM customer
            WHERE Staff_ID = ? AND (First_Name LIKE ? OR Last_Name LIKE ? OR Email LIKE ? OR Phone_Number LIKE ?)

            UNION

            SELECT Lead_ID AS ID, 'lead' AS Type, CONCAT(First_Name, ' ', Last_Name) AS Name, Email, 'Lead' AS Customer_Type, Phone_Number, Company
            FROM lead
            WHERE Staff_ID = ? AND (First_Name LIKE ? OR Last_Name LIKE ? OR Email LIKE ? OR Phone_Number LIKE ?)
            ORDER BY Name ASC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('isssssssss', $staffID, $searchQuery, $searchQuery, $searchQuery, $searchQuery,
                                         $staffID, $searchQuery, $searchQuery, $searchQuery, $searchQuery);
    }
}
// --- END MODIFIED SQL QUERIES ---

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
    <!-- Basic styling for buttons -->
    <style>
        .btn-edit, .btn-delete {
            padding: 5px 10px;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            font-size: 0.9em;
            margin-right: 5px;
        }
        .btn-edit { background-color: #f96600; } /* Green */
        .btn-delete { background-color: #f44336; } /* Red */
        .btn-edit:hover { background-color: #f96600; }
        .btn-delete:hover { background-color: #da190b; }
        td.actions-cell { white-space: nowrap; } /* Prevent buttons wrapping */
    </style>
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
            <form method="get" action="Search.php">
                <input type="search" name="q" placeholder="Search..." value="<?php echo htmlspecialchars($rawSearchQuery); ?>" />
               <input type="hidden" name="source" value="HomePage">
            </form>
        </div>
        <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
    </header>

    <!-- SEARCH RESULTS -->
    <main>
        <div class="dashboard-header">
            <h2>Search Results for "<?php echo htmlspecialchars($rawSearchQuery); ?>"</h2>
            <button class="btn-back" onclick="window.location.href='HomePage.php'">Back</button>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Customer Type</th>
                    <th>Phone No</th>
                    <th>Company</th>
                    <th>Actions</th> <!-- Added Actions Header -->
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['Email']); ?></td>
                            <td><?php echo htmlspecialchars($row['Customer_Type']); ?></td>
                            <td><?php echo htmlspecialchars($row['Phone_Number']); ?></td>
                            <td><?php echo htmlspecialchars($row['Company']); ?></td>
                            <!-- Added Actions Cell -->
                            <td class="actions-cell">
                                <a href='ManageEntity.php?action=edit&type=<?php echo $row['Type']; ?>&id=<?php echo $row['ID']; ?>' class='btn btn-edit'>Edit</a>
                                <a href='ManageEntity.php?action=delete&type=<?php echo $row['Type']; ?>&id=<?php echo $row['ID']; ?>' class='btn btn-delete' onclick='return confirm("Are you sure you want to delete this <?php echo $row['Type']; ?>?");'>Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <!-- // filepath: c:\xampp\htdocs\assignment\Search.php -->
                        <td colspan="6">No results found.</td> <!-- Changed colspan to 6 -->
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

</body>
</html>