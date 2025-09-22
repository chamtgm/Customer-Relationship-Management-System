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
$roleTitle = $_SESSION["Role_Title"];
$staffID = $_SESSION["Staff_ID"];

// Get search query
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

// Check for blank search and redirect with error message
if (empty($searchQuery)) {
    header("Location: Interactions.php?error=empty_search");
    exit();
}

// Escape user input
$searchTerm = "%" . $conn->real_escape_string($searchQuery) . "%";

// Modify the SQL query to include staff and entity information for proper display
if ($roleTitle === "Admin") {
    $sql = "SELECT interaction.*,
               staff.First_Name AS Staff_First_Name,
               staff.Last_Name AS Staff_Last_Name,
               customer.First_Name AS Customer_First_Name,
               customer.Last_Name AS Customer_Last_Name,
               lead.First_Name AS Lead_First_Name,
               lead.Last_Name AS Lead_Last_Name,
               CASE
                   WHEN interaction.Customer_ID IS NOT NULL THEN 'Customer'
                   WHEN interaction.Lead_ID IS NOT NULL THEN 'Lead'
                   ELSE 'N/A'
               END AS Entity_Type
           FROM interaction
           LEFT JOIN staff ON interaction.Staff_ID = staff.Staff_ID
           LEFT JOIN customer ON interaction.Customer_ID = customer.Customer_ID
           LEFT JOIN lead ON interaction.Lead_ID = lead.Lead_ID
           WHERE interaction.Interaction_Type LIKE ? 
               OR interaction.Description LIKE ? 
               OR customer.First_Name LIKE ? 
               OR customer.Last_Name LIKE ?
               OR lead.First_Name LIKE ? 
               OR lead.Last_Name LIKE ?
               OR interaction.Interaction_Date LIKE ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sssssss', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
} else {
    // For Sales representatives
    $sql = "SELECT interaction.*,
               staff.First_Name AS Staff_First_Name,
               staff.Last_Name AS Staff_Last_Name,
               customer.First_Name AS Customer_First_Name,
               customer.Last_Name AS Customer_Last_Name,
               lead.First_Name AS Lead_First_Name,
               lead.Last_Name AS Lead_Last_Name,
               CASE
                   WHEN interaction.Customer_ID IS NOT NULL THEN 'Customer'
                   WHEN interaction.Lead_ID IS NOT NULL THEN 'Lead'
                   ELSE 'N/A'
               END AS Entity_Type
           FROM interaction
           LEFT JOIN staff ON interaction.Staff_ID = staff.Staff_ID
           LEFT JOIN customer ON interaction.Customer_ID = customer.Customer_ID
           LEFT JOIN lead ON interaction.Lead_ID = lead.Lead_ID
           WHERE interaction.Staff_ID = ? 
               AND (interaction.Interaction_Type LIKE ? 
                   OR interaction.Description LIKE ? 
                   OR customer.First_Name LIKE ? 
                   OR customer.Last_Name LIKE ?
                   OR lead.First_Name LIKE ? 
                   OR lead.Last_Name LIKE ?
                   OR interaction.Interaction_Date LIKE ?)";
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
    <title>Search Interactions</title>
    <link rel="stylesheet" href="home.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>
    <?php include 'sidebar.php'; ?>

<div class="main-content">
    <header>
        <div class="search-wrapper">
            <svg xmlns="http://www.w3.org/2000/svg" height="20" width="20" fill="#888">
              <path d="M19.021 17.271 14.8 13.05q-.75.65-1.75 1.025-1 .375-2.1.375-2.625 0-4.462-1.837Q4.65 10.775 4.65 8.15q0-2.625 1.838-4.463Q8.325 1.85 10.95 1.85q2.625 0 4.462 1.837Q17.25 5.525 17.25 8.15q0 1.1-.388 2.125T15.863 12l4.2 4.2q.275.275.275.625 0 .35-.275.625-.275.275-.625.275-.35 0-.625-.275ZM10.95 9.95q.75 0 1.35-.6t.6-1.35q0-.75-.6-1.35t-1.35-.6q-.75 0-1.35.6t-.6 1.35q0 .75.6 1.35t1.35.6Z"/>
            </svg>
            <form method="get" action="Search_Interactions.php">
                <input type="search" name="q" placeholder="Search interactions..." value="<?php echo htmlspecialchars($searchQuery); ?>" />
            </form>
        </div>
        <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
    </header>

    <main>
        <div class="dashboard-header">
            <h2>Search Results for "<?php echo htmlspecialchars($searchQuery); ?>"</h2>
            <button class="btn-back" onclick="window.location.href='Interactions.php'">Back</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Interaction ID</th>
                    <th>Type</th>
                    <th>Name</th>
                    <th>Date</th>
                    <th>Interaction Type</th>
                    <th>Description</th>
                    <?php if ($roleTitle === "Admin"): ?>
                        <th>Staff</th>
                    <?php endif; ?>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row["Interaction_ID"]); ?></td>
                            <td><?php echo htmlspecialchars($row["Entity_Type"]); ?></td>
                            <td>
                                <?php
                                // Display Customer or Lead name based on Entity_Type
                                $entityName = 'N/A';
                                if ($row['Entity_Type'] === 'Customer') {
                                    $entityName = trim($row['Customer_First_Name'] . ' ' . $row['Customer_Last_Name']);
                                } elseif ($row['Entity_Type'] === 'Lead') {
                                    $entityName = trim($row['Lead_First_Name'] . ' ' . $row['Lead_Last_Name']);
                                }
                                echo !empty($entityName) ? htmlspecialchars($entityName) : 'N/A';
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($row["Interaction_Date"]); ?></td>
                            <td><?php echo htmlspecialchars($row["Interaction_Type"]); ?></td>
                            <td><?php echo htmlspecialchars($row["Description"]); ?></td>
                            <?php if ($roleTitle === "Admin"): ?>
                                <td>
                                    <?php
                                    $staffName = trim($row['Staff_First_Name'] . ' ' . $row['Staff_Last_Name']);
                                    echo !empty($staffName) ? htmlspecialchars($staffName) : 'Unassigned';
                                    ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <button class="btn-edit" onclick="window.location.href='manageEntity.php?type=interaction&action=edit&id=<?php echo $row['Interaction_ID']; ?>'">Edit</button>
                                <button class="btn-delete" onclick="confirmDelete(<?php echo $row['Interaction_ID']; ?>)">Delete</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="<?php echo $roleTitle === 'Admin' ? '8' : '7'; ?>">No results found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<script>
    function confirmDelete(id) {
        if (confirm("Are you sure you want to delete this interaction? This action cannot be undone.")) {
            window.location.href = 'manageEntity.php?type=interaction&action=delete&id=' + id;
        }
    }
</script>
</body>
</html>