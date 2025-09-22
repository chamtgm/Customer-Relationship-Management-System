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

if (!isset($_SESSION['Staff_ID'])) {
    http_response_code(403);
    exit;
}
$staffID = (int)$_SESSION['Staff_ID'];
$notifId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $conn->prepare("
  UPDATE reminder_record
  SET Reminder_ID = 1
  WHERE Reminder_Record_ID = ?
    AND Staff_ID = ?
");
$stmt->bind_param("ii", $notifId, $staffID);
$stmt->execute();
$stmt->close();

// re-count
$stmt2 = $conn->prepare("
  SELECT COUNT(*) AS cnt
  FROM reminder_record
  WHERE Reminder_ID != 1
    AND Staff_ID = ?
");
$stmt2->bind_param("i",$staffID);
$stmt2->execute();
$cnt = $stmt2->get_result()->fetch_assoc()['cnt'];
$stmt2->close();

header('Content-Type: application/json');
echo json_encode(['unreadCount' => (int)$cnt]);
