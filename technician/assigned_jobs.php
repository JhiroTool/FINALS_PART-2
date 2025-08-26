<?php
session_start();
include '../connection.php';

// Only allow logged-in technicians
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$tech_id = $_SESSION['user_id'];

// Fetch assigned jobs
$stmt = $conn->prepare("SELECT id, description, status, created_at FROM jobs WHERE technician_id=? ORDER BY created_at DESC");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assigned Jobs</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body technician-bg">
    <div class="container py-5">
        <div class="card mx-auto" style="max-width:700px;">
            <div class="card-body">
                <h3 class="mb-4 text-center">Assigned Jobs</h3>
                <?php if (count($jobs) > 0): ?>
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Job ID</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th>Assigned At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jobs as $job): ?>
                                <tr>
                                    <td><?php echo $job['id']; ?></td>
                                    <td><?php echo htmlspecialchars($job['description']); ?></td>
                                    <td><?php echo ucfirst($job['status']); ?></td>
                                    <td><?php echo $job['created_at']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No jobs assigned yet.</div>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="technician_dashboard.php" class="btn btn-link">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>