<?php
session_start();
include '../connection.php';

// Only allow logged-in technicians
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

// Get technician info, certificate status, and profile picture
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, cert_status, profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($firstname, $lastname, $cert_status, $profile_pic);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician_dashboard.css">
</head>
<body>
    <div class="container py-5">
        <div class="profile-card text-center">
            <div class="avatar mb-2">
                <?php if (!empty($profile_pic)): ?>
                    <img src="../uploads/profile_pics/<?php echo htmlspecialchars($profile_pic); ?>" alt="Profile Picture" style="width:70px;height:70px;border-radius:50%;">
                <?php else: ?>
                    <img src="https://img.icons8.com/color/96/maintenance.png" alt="Technician Avatar">
                <?php endif; ?>
            </div>
            <h2 class="mb-1" style="font-weight:700;color:#185a9d;">
                <?php echo htmlspecialchars($firstname . ' ' . $lastname); ?>
            </h2>
            <div class="mb-3" style="color:#43cea2;font-weight:500;">Technician Dashboard</div>
            <div class="cert-status">
                <?php
                    if ($cert_status === 'approved') {
                        echo '<span class="icon text-success">&#10004;</span> <span class="badge bg-success">Certificate Approved</span>';
                    } elseif ($cert_status === 'pending') {
                        echo '<span class="icon text-warning">&#9203;</span> <span class="badge bg-warning text-dark">Certificate Pending</span>';
                    } elseif ($cert_status === 'rejected') {
                        echo '<span class="icon text-danger">&#10060;</span> <span class="badge bg-danger">Certificate Rejected</span>';
                    } else {
                        echo '<span class="icon text-secondary">&#9888;</span> <span class="badge bg-secondary">No Certificate Uploaded</span>';
                    }
                ?>
            </div>
            <div class="action-btns d-grid gap-3 mb-2">
                <a href="assigned_jobs.php" class="btn btn-outline-primary">🗂 Assigned Jobs</a>
                <a href="update_status.php" class="btn btn-outline-info">🔄 Update Status</a>
                <a href="update_technician_profile.php" class="btn btn-outline-secondary">👤 Update Profile</a>
                <a href="verify_certification.php" class="btn btn-outline-warning">📄 Upload Certificate</a>
            </div>
            <a href="../logout.php" class="btn btn-danger w-100 logout-btn">Logout</a>
            <footer class="dash-footer text-center">&copy; 2025 PinoyFix. All rights reserved.</footer>
        </div>
    </div>
</body>
</html>
