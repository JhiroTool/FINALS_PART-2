<?php
session_start();
include '../connection.php';

// Only allow logged-in technicians
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 1) {
    header("Location: ../login.php");
    exit();
}

$tech_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['job_id'], $_POST['status'])) {
    $job_id = intval($_POST['job_id']);
    $status = $_POST['status'];
    $stmt = $conn->prepare("UPDATE jobs SET status=? WHERE id=? AND technician_id=?");
    $stmt->bind_param("sii", $status, $job_id, $tech_id);
    if ($stmt->execute()) {
        $success = "Job status updated!";
    } else {
        $error = "Failed to update status.";
    }
    $stmt->close();
}

// Fetch assigned jobs
$stmt = $conn->prepare("SELECT id, description, status FROM jobs WHERE technician_id=?");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$result = $stmt->get_result();
$jobs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch current profile picture
$stmt = $conn->prepare("SELECT profile_pic FROM users WHERE id=?");
$stmt->bind_param("i", $tech_id);
$stmt->execute();
$stmt->bind_result($profile_pic);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Update Job Status</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body technician-bg">
    <div class="container py-5">
        <div class="card mx-auto" style="max-width:600px;">
            <div class="card-body">
                <h3 class="mb-4 text-center">Update Job Status</h3>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if (count($jobs) > 0): ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label for="job_id" class="form-label">Select Job</label>
                            <select name="job_id" id="job_id" class="form-select" required>
                                <?php foreach ($jobs as $job): ?>
                                    <option value="<?php echo $job['id']; ?>">
                                        <?php echo "Job #{$job['id']} - {$job['description']} (Current: {$job['status']})"; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">New Status</label>
                            <select name="status" id="status" class="form-select" required>
                                <option value="pending">Pending</option>
                                <option value="in progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Update Status</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info text-center">No assigned jobs found.</div>
                <?php endif; ?>

                <div class="mt-3 text-center">
                    <a href="technician_dashboard.php" class="btn btn-link">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>