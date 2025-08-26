<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 0) {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body admin-bg">
    <div class="dashboard-container admin-style">
        <div class="dash-logo-circle admin-logo">
            <img src="https://img.icons8.com/color/96/admin-settings-male.png" alt="Admin Icon" class="dash-logo-img">
        </div>
        <h2>Welcome, Admin!</h2>
        <div class="dash-subtitle">Manage users, technicians, jobs, and view analytics.</div>
        <div class="dash-actions">
            <a href="#" class="dash-btn">Manage Users</a>
            <a href="#" class="dash-btn">View Analytics</a>
            <a href="approve_technicians.php" class="dash-btn">Approve Technicians</a>
        </div>
        <a href="../logout.php" class="logout-btn">Logout</a>
        <footer class="dash-footer">&copy; 2025 PinoyFix. All rights reserved.</footer>
    </div>
</body>
</html>
