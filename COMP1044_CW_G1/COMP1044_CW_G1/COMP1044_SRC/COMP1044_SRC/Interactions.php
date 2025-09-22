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
$staffID = $_SESSION["Staff_ID"];
$roleTitle = $_SESSION["Role_Title"]; // Retrieve role title from session

// Fetch interactions based on role
if ($roleTitle === "Admin") {
    // Admin can see all interactions, staff, and associated entity (customer or lead)
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
            ORDER BY interaction.Interaction_ID ASC";
    $result = $conn->query($sql);
    $interactions = $result->fetch_all(MYSQLI_ASSOC);
} else {
    // Sales representatives can only see their own interactions and associated entity
    $sql = "SELECT interaction.*,
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
            LEFT JOIN customer ON interaction.Customer_ID = customer.Customer_ID
            LEFT JOIN lead ON interaction.Lead_ID = lead.Lead_ID
            WHERE interaction.Staff_ID = ?
            ORDER BY interaction.Interaction_ID ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staffID);
    $stmt->execute();
    $result = $stmt->get_result();
    $interactions = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Interactions</title>
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
                    <input type="search" name="q" placeholder="Search interactions..." />
                </form>
            </div>
            <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
        </header>

        <main>
          <?php if (isset($_GET['error']) && $_GET['error'] === 'empty_search'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> Please enter a search term before searching.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['error']) && $_GET['error'] === 'HasRelatedRecords'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> Cannot delete this interaction because it has related records. Delete those records first.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['error']) && $_GET['error'] === 'HasRelationships'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> Cannot delete this interaction because it has related records. Please remove those records first.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['error']) && $_GET['error'] === 'CannotDelete'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> An unexpected error occurred while trying to delete this interaction. Please try again later.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['success']) && $_GET['success'] === 'Deleted'): ?>
          <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Success:</strong> Interaction deleted successfully.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['success']) && $_GET['success'] === 'Updated'): ?>
          <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Success:</strong> Interaction updated successfully.
          </div>
          <?php endif; ?>

          <?php if (isset($_GET['success']) && $_GET['success'] === 'Added'): ?>
          <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Success:</strong> New interaction added successfully.
          </div>
          <?php endif; ?>

          <?php if (isset($_GET['error']) && $_GET['error'] === 'UpdateFailed'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> Failed to update the interaction. Please try again.
          </div>
          <?php endif; ?>
          
            <div class="dashboard-header">
                <h2>Interactions Dashboard</h2>
                <button onclick="window.location.href='manageInteraction.php'">Add New</button>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Interaction ID</th>
                        <th>Type</th> <!-- Added Type column -->
                        <th>Name</th> <!-- Changed from Customer Name -->
                        <th>Date</th>
                        <th>Interaction Type</th>
                        <th>Description</th>
                        <?php if ($roleTitle === "Admin"): ?>
                            <th>Staff</th>
                        <?php endif; ?>
                        <th>Actions</th> <!-- New Actions column -->
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($interactions)): ?>
                        <?php foreach ($interactions as $interaction): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($interaction["Interaction_ID"]); ?></td>
                                <td><?php echo htmlspecialchars($interaction["Entity_Type"]); ?></td> <!-- Display Entity Type -->
                                <td>
                                    <?php
                                    // Display Customer or Lead name based on Entity_Type
                                    $entityName = 'N/A';
                                    if ($interaction['Entity_Type'] === 'Customer') {
                                        $entityName = trim($interaction['Customer_First_Name'] . ' ' . $interaction['Customer_Last_Name']);
                                    } elseif ($interaction['Entity_Type'] === 'Lead') {
                                        $entityName = trim($interaction['Lead_First_Name'] . ' ' . $interaction['Lead_Last_Name']);
                                    }
                                    echo !empty($entityName) ? htmlspecialchars($entityName) : 'N/A';
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($interaction["Interaction_Date"]); ?></td>
                                <td><?php echo htmlspecialchars($interaction["Interaction_Type"]); ?></td>
                                <td><?php echo htmlspecialchars($interaction["Description"]); ?></td>
                                <?php if ($roleTitle === "Admin"): ?>
                                    <td>
                                        <?php
                                        $staffName = trim($interaction['Staff_First_Name'] . ' ' . $interaction['Staff_Last_Name']);
                                        echo !empty($staffName) ? htmlspecialchars($staffName) : 'Unassigned';
                                        ?>
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <button class="btn-edit" onclick="window.location.href='manageEntity.php?type=interaction&action=edit&id=<?php echo $interaction['Interaction_ID']; ?>'">Edit</button>
                                    <button class="btn-delete" onclick="confirmDelete(<?php echo $interaction['Interaction_ID']; ?>)">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <!-- Adjust colspan based on whether Admin or not -->
                            <td colspan="<?php echo $roleTitle === "Admin" ? 8 : 7; ?>">No interactions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
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