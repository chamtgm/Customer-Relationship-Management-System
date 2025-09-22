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

// Retrieve session variables
$roleTitle = $_SESSION["Role_Title"];
$staffID = $_SESSION["Staff_ID"];
$userEmail = $_SESSION["Email"];

// Define columns and primary key for the table
$primaryKey = 'Lead_ID';
$columns = [
    'First_Name',
    'Last_Name',
    'Email',
    'Phone_Number',
    'Company', // Add other columns as needed
    'Address',
    'Notes',
    'Status'
];

// Modify SQL query based on role
if ($roleTitle === "Admin") {
    // Admin can see all leads and their assigned staff
    $sql = "SELECT lead.*, staff.First_Name AS Staff_First_Name, staff.Last_Name AS Staff_Last_Name 
            FROM lead 
            LEFT JOIN staff ON lead.Staff_ID = staff.Staff_ID 
            ORDER BY Lead_ID ASC";
    $result = $conn->query($sql);
} else {
    // Sales representatives can only see their own leads
    $sql = "SELECT * FROM lead WHERE Staff_ID = ? ORDER BY Lead_ID ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $staffID);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>ABB Robotics CRM - Leads</title>
  <link rel="stylesheet" href="home.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
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
        <form method="get" action="Search.php">
          <input type="search" name="q" placeholder="Search..." />
          <input type="hidden" name="source" value="LeadPage">
        </form>
      </div>
      <div class="username"><b><?php echo htmlspecialchars($roleTitle); ?></b></div>
    </header>

    <!-- DASHBOARD CONTENT -->
    <main>
          <?php if (isset($_GET['error']) && $_GET['error'] === 'empty_search'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> Please enter a search term before searching.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['error']) && $_GET['error'] === 'HasRelatedRecords'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> Cannot delete this lead because it has related interactions or reminders. Delete those records first.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['error']) && $_GET['error'] === 'HasRelationships'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> Cannot delete this lead because it has related records. Please remove those records first.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['error']) && $_GET['error'] === 'CannotDelete'): ?>
          <div class="error-message" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Error:</strong> An unexpected error occurred while trying to delete this lead. Please try again later.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['success']) && $_GET['success'] === 'Deleted'): ?>
          <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Success:</strong> Lead deleted successfully.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['success']) && $_GET['success'] === 'Updated'): ?>
          <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Success:</strong> Lead information updated successfully.
          </div>
          <?php endif; ?>
          
          <?php if (isset($_GET['success']) && $_GET['success'] === 'Added'): ?>
          <div class="success-message" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong>Success:</strong> New lead added successfully.
          </div>
          <?php endif; ?>
          
      <div class="dashboard-header">
        <h2>Leads Dashboard</h2>
        <button onclick="window.location.href='manageEntity.php?type=lead'">Add New</button>
      </div>

      <table>
          <thead>
              <tr>
                  <th><?php echo $primaryKey; ?></th>
                  <?php foreach ($columns as $col): ?>
                      <th><?php echo ucfirst(str_replace('_', ' ', $col)); ?></th>
                  <?php endforeach; ?>
                  <?php if ($roleTitle === "Admin"): ?>
                      <th>Staff</th>
                  <?php endif; ?>
                  <th>Actions</th>
              </tr>
          </thead>
          <tbody>
              <?php
              if ($result && $result->num_rows > 0):
                  while ($row = $result->fetch_assoc()):
              ?>
                  <tr>
                      <td><?php echo htmlspecialchars($row[$primaryKey]); ?></td>
                      <?php foreach ($columns as $col): ?>
                          <td><?php echo htmlspecialchars($row[$col]); ?></td>
                      <?php endforeach; ?>
                      <?php if ($roleTitle === "Admin"): ?>
                          <td>
                              <?php
                              $staffName = trim($row['Staff_First_Name'] . ' ' . $row['Staff_Last_Name']);
                              echo !empty($staffName) ? htmlspecialchars($staffName) : 'Unassigned';
                              ?>
                          </td>
                      <?php endif; ?>
                      <td>
                          <button class="btn-edit" onclick="window.location.href='manageEntity.php?type=lead&action=edit&id=<?php echo $row[$primaryKey]; ?>'">Edit</button>
                          <button class="btn-delete" onclick="if(confirm('Are you sure you want to delete this record?')) window.location.href='manageEntity.php?type=lead&action=delete&id=<?php echo $row[$primaryKey]; ?>'">Delete</button>
                      </td>
                  </tr>
              <?php
                  endwhile;
              else:
              ?>
                  <tr>
                      <td colspan="<?php echo count($columns) + ($roleTitle === "Admin" ? 3 : 2); ?>">No records found.</td>
                  </tr>
              <?php endif; ?>
          </tbody>
      </table>
    </main>
  </div>
</body>
</html>