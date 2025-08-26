<?php
session_start();
include '../connection.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['certificate'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['certificate'];
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if ($file['error'] === 0 && in_array($ext, $allowed)) {
        $target_dir = "../uploads/certificates/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $filename = "cert_" . $user_id . "_" . time() . "." . $ext;
        $target_file = $target_dir . $filename;
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $stmt = $conn->prepare("UPDATE users SET certificate=?, cert_status='pending' WHERE id=?");
            $stmt->bind_param("si", $filename, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = "Certificate uploaded! Await admin approval.";
        } else {
            $error = "Upload failed.";
        }
    } else {
        $error = "Invalid file type or upload error.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Upload Certificate - Technician</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician.css">
</head>
<body>
    <div class="container py-5">
        <div class="tech-card text-center">
            <div class="tech-logo mb-2">
                <img src="https://img.icons8.com/color/96/maintenance.png" alt="Technician Icon">
            </div>
            <h4 class="mb-2" style="font-weight:700;color:#185a9d;">Upload Certification</h4>
            <div class="mb-3" style="color:#43cea2;font-weight:500;">Submit your certificate for admin approval.</div>
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="mb-3">
                <div class="mb-3 text-start">
                    <label for="certificate" class="form-label">Certificate (PDF/JPG/PNG)</label>
                    <input type="file" class="form-control" id="certificate" name="certificate" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Upload</button>
            </form>
            <div class="mt-3 text-center">
                <a href="technician_dashboard.php" class="btn btn-link">Back to Dashboard</a>
            </div>
            <div class="mt-3 upload-status text-center text-muted">
                Status:
                <?php
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT cert_status FROM users WHERE id=?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->bind_result($cert_status);
                $stmt->fetch();
                $stmt->close();
                if ($cert_status === 'approved') {
                    echo '<span class="badge bg-success">Approved</span>';
                } elseif ($cert_status === 'pending') {
                    echo '<span class="badge bg-warning text-dark">Pending</span>';
                } elseif ($cert_status === 'rejected') {
                    echo '<span class="badge bg-danger">Rejected</span>';
                } else {
                    echo '<span class="badge bg-secondary">Not uploaded</span>';
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>