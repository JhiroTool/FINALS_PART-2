<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get current technician data
$stmt = $conn->prepare("SELECT Technician_FN, Technician_LN, Tech_Certificate, Status FROM technician WHERE Technician_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$technician = $result->fetch_assoc();
$stmt->close();

// Handle certificate upload
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES['certificate']) && $_FILES['certificate']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/certificates/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['certificate']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = ['pdf', 'jpg', 'jpeg', 'png'];
        
        if (in_array(strtolower($file_extension), $allowed_extensions)) {
            $new_filename = 'cert_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['certificate']['tmp_name'], $upload_path)) {
                // Delete old certificate if exists
                if (!empty($technician['Tech_Certificate']) && file_exists($upload_dir . $technician['Tech_Certificate'])) {
                    unlink($upload_dir . $technician['Tech_Certificate']);
                }
                
                // Update database
                $current_status = $technician['Status'] ?? '';
                $update_stmt = $conn->prepare("UPDATE technician SET Tech_Certificate = ?, Status = CASE WHEN Status IN ('approved', 'rejected') THEN Status ELSE 'pending_review' END WHERE Technician_ID = ?");
                $update_stmt->bind_param("si", $new_filename, $user_id);
                
                if ($update_stmt->execute()) {
                    if (!in_array($current_status, ['approved', 'rejected'], true)) {
                        $technician['Status'] = 'pending_review';
                    }
                    $message = "Certificate uploaded successfully! It will be reviewed by our admin team.";
                    $messageType = "success";
                    $technician['Tech_Certificate'] = $new_filename;
                } else {
                    $message = "Error saving certificate to database.";
                    $messageType = "error";
                }
                $update_stmt->close();
            } else {
                $message = "Error uploading certificate file.";
                $messageType = "error";
            }
        } else {
            $message = "Invalid file type. Please upload PDF, JPG, JPEG, or PNG files only.";
            $messageType = "error";
        }
    } else {
        $message = "Please select a certificate file to upload.";
        $messageType = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification - PinoyFix Technician</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician_certificate.css">
</head>
<body>
    <div class="tech-container">
        <!-- Header -->
        <header class="tech-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">PinoyFix</h1>
                        <p class="subtitle">Certificate Verification</p>
                    </div>
                </div>
                <div class="status-banner">
                    <?php
                    $status = $technician['Status'] ?? '';
                    switch ($status) {
                        case 'approved':
                            echo '<div class="status approved">✅ Your documents are verified. You are fully approved for bookings.</div>';
                            break;
                        case 'pending_review':
                            echo '<div class="status pending">⏳ Certificate under admin review. You will be notified once approved.</div>';
                            break;
                        case 'pending_certificate':
                            echo '<div class="status alert">⚠️ Upload your certificate to continue the approval process.</div>';
                            break;
                        case 'rejected':
                            echo '<div class="status rejected">❌ Your application was rejected. Please contact support for next steps.</div>';
                            break;
                        default:
                            echo '<div class="status info">ℹ️ Complete the steps below to finish your technician verification.</div>';
                    }
                    ?>
                </div>
                <div class="header-actions">
                    <a href="technician_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Certificate Section -->
        <section class="certificate-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">📄</span>
                    Professional Certification
                </h2>
            </div>

            <div class="certificate-info">
                <div class="info-card">
                    <h3>Why Upload Your Certificate?</h3>
                    <ul>
                        <li>✅ Builds trust with clients</li>
                        <li>✅ Increases your booking chances</li>
                        <li>✅ Shows your professional credentials</li>
                        <li>✅ Required for account verification</li>
                    </ul>
                </div>

                <div class="accepted-formats">
                    <h4>Accepted Documents:</h4>
                    <div class="format-list">
                        <span class="format-item">📄 Professional Certificates</span>
                        <span class="format-item">🎓 Training Certificates</span>
                        <span class="format-item">🏆 Industry Certifications</span>
                        <span class="format-item">📋 License Documents</span>
                    </div>
                </div>
            </div>

            <!-- Current Certificate Status -->
            <div class="current-status">
                <h3>Current Status</h3>
                <?php if (!empty($technician['Tech_Certificate'])): ?>
                    <div class="status-uploaded">
                        <div class="status-icon">✅</div>
                        <div class="status-content">
                            <h4>Certificate Uploaded</h4>
                            <p>Your certificate has been uploaded and is under review.</p>
                            <a href="../uploads/certificates/<?php echo htmlspecialchars($technician['Tech_Certificate']); ?>" 
                               target="_blank" class="view-certificate">
                                👁️ View Current Certificate
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="status-missing">
                        <div class="status-icon">⚠️</div>
                        <div class="status-content">
                            <h4>No Certificate Uploaded</h4>
                            <p>Please upload your professional certificate to verify your account.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Upload Form -->
            <form method="POST" enctype="multipart/form-data" class="certificate-form">
                <div class="upload-section">
                    <h3><?php echo !empty($technician['Tech_Certificate']) ? 'Replace Certificate' : 'Upload Certificate'; ?></h3>
                    
                    <div class="file-upload">
                        <input type="file" id="certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png" required>
                        <label for="certificate" class="upload-area">
                            <div class="upload-icon">📁</div>
                            <div class="upload-text">
                                <h4>Choose Certificate File</h4>
                                <p>PDF, JPG, JPEG, or PNG (max 5MB)</p>
                            </div>
                        </label>
                        <div class="file-info" id="fileInfo" style="display: none;">
                            <span class="file-name"></span>
                            <span class="file-size"></span>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">
                            📤 Upload Certificate
                        </button>
                    </div>
                </div>
            </form>

            <!-- Guidelines -->
            <div class="guidelines">
                <h3>Upload Guidelines</h3>
                <div class="guidelines-grid">
                    <div class="guideline-item">
                        <div class="guideline-icon">📸</div>
                        <h4>Clear Image</h4>
                        <p>Ensure text is readable and image is not blurry</p>
                    </div>
                    <div class="guideline-item">
                        <div class="guideline-icon">📄</div>
                        <h4>Complete Document</h4>
                        <p>Upload the full certificate, not just a portion</p>
                    </div>
                    <div class="guideline-item">
                        <div class="guideline-icon">✅</div>
                        <h4>Valid Certificate</h4>
                        <p>Must be from a recognized institution</p>
                    </div>
                    <div class="guideline-item">
                        <div class="guideline-icon">🔒</div>
                        <h4>Secure Upload</h4>
                        <p>Your documents are encrypted and secure</p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // File upload handling
        document.getElementById('certificate').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const fileInfo = document.getElementById('fileInfo');
            
            if (file) {
                const fileName = file.name;
                const fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                
                fileInfo.querySelector('.file-name').textContent = fileName;
                fileInfo.querySelector('.file-size').textContent = fileSize;
                fileInfo.style.display = 'block';
                
                // Check file size (5MB limit)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    fileInfo.style.display = 'none';
                }
            } else {
                fileInfo.style.display = 'none';
            }
        });

        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>