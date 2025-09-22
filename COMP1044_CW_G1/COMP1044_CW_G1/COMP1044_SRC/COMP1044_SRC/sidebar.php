<?php
// sidebar.php

// Ensure the session is started (if not already done in the main page)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "comp1044_database";

// Create connection (or include your connection file if you have one)
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Verify user is logged in (you might have already done this in your main page)
if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header("Location: LoginPage.php");
    exit();
}

$roleTitle = $_SESSION["Role_Title"];
$staffID   = $_SESSION["Staff_ID"];

// Query for unread reminders count
if ($roleTitle === "Admin") {
    // Admin sees all unread reminders
    $unreadSql = "SELECT COUNT(*) AS total_unread 
                  FROM reminder_record 
                  WHERE Reminder_ID != 1";
    $unreadRes = $conn->query($unreadSql);
    $rowUnread = $unreadRes->fetch_assoc();
    $unreadCount = $rowUnread['total_unread'] ?? 0;
} else {
    // Non-admin users see only their own unread reminders
    $unreadSql = "SELECT COUNT(*) AS total_unread 
                  FROM reminder_record 
                  WHERE Reminder_ID != 1 AND Staff_ID = ?";
    $stmtUnread = $conn->prepare($unreadSql);
    $stmtUnread->bind_param("i", $staffID);
    $stmtUnread->execute();
    $unreadRes = $stmtUnread->get_result();
    $rowUnread = $unreadRes->fetch_assoc();
    $stmtUnread->close();
    $unreadCount = $rowUnread['total_unread'] ?? 0;
}

// Show "9+" if unreadCount is greater than 9
$unreadBadge = ($unreadCount > 9) ? '9+' : $unreadCount;
?>

<!-- Sidebar HTML -->
<div class="sidebar">
  <div class="brand">
    <a href="HomePage.php" style="text-decoration: none; color: inherit;">
      <i class="fas fa-robot"></i> ABB Robotics CRM
    </a>
  </div>
  <?php
    $currentPage = basename($_SERVER['PHP_SELF']);
  ?>
  <ul>
    <li>
      <a href="HomePage.php" class="<?php echo $currentPage == 'HomePage.php' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i> Dashboard
      </a>
    </li>
    <li>
      <a href="LeadPage.php" class="<?php echo $currentPage == 'LeadPage.php' ? 'active' : ''; ?>">
        <i class="fas fa-user-plus"></i> Leads
      </a>
    </li>
    <li>
      <a href="CustomerPage.php" class="<?php echo $currentPage == 'CustomerPage.php' ? 'active' : ''; ?>">
        <i class="fas fa-users"></i> Customers
      </a>
    </li>
    <li>
      <a href="Interactions.php" class="<?php echo $currentPage == 'Interactions.php' ? 'active' : ''; ?>">
        <i class="fas fa-comments"></i> Interactions
      </a>
    </li>
    <li>
      <a href="ReminderPage.php" class="<?php echo $currentPage == 'ReminderPage.php' ? 'active' : ''; ?>">
        <i class="fas fa-bell"></i> Reminder
        <?php if ($unreadCount > 0): ?>
          <span id="unreadBadge" class="badge"><?php echo $unreadBadge; ?></span>
        <?php endif; ?>
      </a>
    </li>
    <?php if ($roleTitle === "Admin"): ?>
    <li>
    <a href="Settings.php" class="<?php echo ($currentPage == 'Settings.php' && $_SESSION['Role_Title'] === 'Admin') ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i> Staff Management
      </a>
    </li>
    <?php endif; ?>
  </ul>
  <div class="logout">
    <a href="Logout.php"><i class="fas fa-sign-out-alt"></i> Log out</a>
  </div>
</div>

<?php
$conn->close();
?>
