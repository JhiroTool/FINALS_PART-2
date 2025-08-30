<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Session check: ";
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    echo "FAILED - Redirecting to login";
    exit();
} else {
    echo "PASSED<br>";
}

echo "Database connection: ";
include '../connection.php';
if ($conn) {
    echo "SUCCESS<br>";
} else {
    echo "FAILED<br>";
    exit();
}

echo "Testing queries:<br>";

// Test each query
try {
    $pending_techs = $conn->query("SELECT COUNT(*) as count FROM technician WHERE Status = 'pending'")->fetch_assoc()['count'];
    echo "Pending techs: $pending_techs<br>";
} catch (Exception $e) {
    echo "Pending techs query failed: " . $e->getMessage() . "<br>";
}

try {
    $total_techs = $conn->query("SELECT COUNT(*) as count FROM technician WHERE Status = 'approved'")->fetch_assoc()['count'];
    echo "Total techs: $total_techs<br>";
} catch (Exception $e) {
    echo "Total techs query failed: " . $e->getMessage() . "<br>";
}

try {
    $total_clients = $conn->query("SELECT COUNT(*) as count FROM client")->fetch_assoc()['count'];
    echo "Total clients: $total_clients<br>";
} catch (Exception $e) {
    echo "Total clients query failed: " . $e->getMessage() . "<br>";
}

echo "All tests completed!";
?>