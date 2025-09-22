<?php
session_start();
if (!isset($_SESSION["Email"]) || !isset($_SESSION["Role_Title"])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid ID']);
    exit();
}

$leadId = intval($_GET['id']);

$servername = "localhost";
$username = "root"; 
$password = ""; 
$dbname = "comp1044_database";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit();
}

$sql = "SELECT Notes, Status FROM lead WHERE Lead_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $leadId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();
} else {
    $data = ['Notes' => null, 'Status' => null];
}

$stmt->close();
$conn->close();

header('Content-Type: application/json');
echo json_encode($data);
?>
