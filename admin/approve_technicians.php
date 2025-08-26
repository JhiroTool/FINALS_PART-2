<?php
session_start();
include '../connection.php';

// Only allow logged-in admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 0) {
    header("Location: ../login.php");
    exit();
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tech_id'], $_POST['action'])) {
    $tech_id = intval($_POST['tech_id']);
    $action = $_POST['action'] === 'approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE users SET cert_status=? WHERE id=?");
    $stmt->bind_param("si", $action, $tech_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch technicians with pending certificates
$stmt = $conn->prepare("SELECT id, firstname, lastname, certificate FROM users WHERE role=1 AND cert_status='pending'");
$stmt->execute();
$result = $stmt->get_result();
$technicians = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Approve Technicians</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/dashboard.css">
</head>
<body class="dashboard-body admin-bg">
    <div class="container py-5">
        <div class="card mx-auto" style="max-width:700px;">
            <div class="card-body">
                <h3 class="mb-4 text-center">Approve Technician Certificates</h3>
                <?php if (count($technicians) > 0): ?>
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Certificate</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($technicians as $tech): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($tech['firstname'] . ' ' . $tech['lastname']); ?></td>
                                <td>
                                    <?php
                                    $ext = strtolower(pathinfo($tech['certificate'], PATHINFO_EXTENSION));
                                    if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                                        echo '<img src="../uploads/certificates/' . htmlspecialchars($tech['certificate']) . '" alt="Certificate" style="max-width:120px;max-height:120px;border-radius:8px;">';
                                    } elseif ($ext === 'pdf') {
                                        echo '<a href="../uploads/certificates/' . htmlspecialchars($tech['certificate']) . '" target="_blank" class="btn btn-outline-secondary btn-sm">View PDF</a>';
                                    } else {
                                        echo 'No certificate file';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="tech_id" value="<?php echo $tech['id']; ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">Approve</button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="alert alert-info text-center">No pending technician certificates.</div>
                <?php endif; ?>
                <div class="mt-3 text-center">
                    <a href="admin_dashboard.php" class="btn btn-link">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>