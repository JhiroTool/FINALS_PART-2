<?php
session_start();
include '../connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$message = '';
$messageType = '';

// Handle approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $technician_id = $_POST['technician_id'];
    $action = $_POST['action'];
    
        if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE technician SET Status = 'approved' WHERE Technician_ID = ? AND Tech_Certificate IS NOT NULL AND Tech_Certificate != ''");
        $stmt->bind_param("i", $technician_id);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $message = "Technician approved successfully!";
            $messageType = "success";
        } else {
            $message = "Unable to approve technician. Ensure a certificate is on file.";
            $messageType = "error";
        }
        $stmt->close();
    } elseif ($action == 'reject') {
        $stmt = $conn->prepare("UPDATE technician SET Status = 'rejected' WHERE Technician_ID = ?");
        $stmt->bind_param("i", $technician_id);
        if ($stmt->execute()) {
            $message = "Technician rejected.";
            $messageType = "success";
        } else {
            $message = "Error rejecting technician.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

// Get pending technicians
$pending_query = "SELECT t.*, a.Street, a.Barangay, a.City, a.Province 
                  FROM technician t 
                  LEFT JOIN technician_address ta ON t.Technician_ID = ta.Technician_ID 
                  LEFT JOIN address a ON ta.Address_ID = a.Address_ID 
                  WHERE t.Status IN ('pending', 'pending_verification', 'pending_certificate', 'pending_review') 
                  ORDER BY FIELD(t.Status, 'pending_verification', 'pending_certificate', 'pending_review', 'pending'), t.Technician_ID DESC";
$pending_result = $conn->query($pending_query);

// Get approved technicians
$approved_query = "SELECT t.*, a.Street, a.Barangay, a.City, a.Province 
                   FROM technician t 
                   LEFT JOIN technician_address ta ON t.Technician_ID = ta.Technician_ID 
                   LEFT JOIN address a ON ta.Address_ID = a.Address_ID 
                   WHERE t.Status = 'approved' 
                   ORDER BY t.Technician_ID DESC";
$approved_result = $conn->query($approved_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Technicians - PinoyFix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_approve.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">PinoyFix Admin</h1>
                        <p class="subtitle">Technician Management</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="admin_dashboard.php" class="btn-secondary">
                        ← Back to Dashboard
                    </a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <!-- Pending Technicians Section -->
            <section class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">⏳</span>
                        Pending Technician Applications
                    </h2>
                    <div class="badge badge-warning">
                        <?php echo $pending_result->num_rows; ?> pending
                    </div>
                </div>

                <?php if ($pending_result->num_rows > 0): ?>
                    <div class="technicians-grid">
                        <?php while ($tech = $pending_result->fetch_assoc()): ?>
                            <div class="technician-card pending">
                                <div class="card-header">
                                    <div class="tech-avatar">
                                        <span><?php echo strtoupper(substr($tech['Technician_FN'], 0, 1) . substr($tech['Technician_LN'], 0, 1)); ?></span>
                                    </div>
                                    <div class="tech-info">
                                        <h3><?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?></h3>
                                        <p class="tech-email"><?php echo htmlspecialchars($tech['Technician_Email']); ?></p>
                                        <p class="tech-phone"><?php echo htmlspecialchars($tech['Technician_Phone']); ?></p>
                                    </div>
                                    <?php
                                    $status_label = ucfirst(str_replace('_', ' ', $tech['Status']));
                                    $status_class = 'status-pending';
                                    if ($tech['Status'] === 'pending_review') {
                                        $status_class = 'status-review';
                                    } elseif ($tech['Status'] === 'pending_certificate' || $tech['Status'] === 'pending_verification') {
                                        $status_class = 'status-warning';
                                    }
                                    ?>
                                    <div class="status-badge <?php echo $status_class; ?>"><?php echo htmlspecialchars($status_label); ?></div>
                                </div>

                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="label">Specialization:</span>
                                        <span class="value"><?php echo htmlspecialchars($tech['Specialization'] ?: 'Not specified'); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Service Rate:</span>
                                        <span class="value">₱<?php echo number_format($tech['Service_Pricing'] ?: 0); ?>/hour</span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Service Area:</span>
                                        <span class="value"><?php echo htmlspecialchars($tech['Service_Location'] ?: 'Not specified'); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Address:</span>
                                        <span class="value">
                                            <?php 
                                            $address_parts = array_filter([
                                                $tech['Street'], 
                                                $tech['Barangay'], 
                                                $tech['City'], 
                                                $tech['Province']
                                            ]);
                                            echo htmlspecialchars(implode(', ', $address_parts) ?: 'Not provided');
                                            ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Certificate:</span>
                                        <span class="value">
                                            <?php if (!empty($tech['Tech_Certificate'])): ?>
                                                <a href="../uploads/certificates/<?php echo htmlspecialchars($tech['Tech_Certificate']); ?>" target="_blank">View certificate</a>
                                            <?php else: ?>
                                                <em>No certificate uploaded</em>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="card-actions">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="technician_id" value="<?php echo $tech['Technician_ID']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn-approve" <?php echo empty($tech['Tech_Certificate']) ? 'disabled title="Upload required"' : ''; ?> onclick="return confirm('Are you sure you want to approve this technician?')">
                                            ✓ Approve
                                        </button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="technician_id" value="<?php echo $tech['Technician_ID']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn-reject" onclick="return confirm('Are you sure you want to reject this technician?')">
                                            ✗ Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">📝</div>
                        <h3>No Pending Applications</h3>
                        <p>All technician applications have been reviewed.</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Approved Technicians Section -->
            <section class="section">
                <div class="section-header">
                    <h2 class="section-title">
                        <span class="section-icon">✅</span>
                        Approved Technicians
                    </h2>
                    <div class="badge badge-success">
                        <?php echo $approved_result->num_rows; ?> approved
                    </div>
                </div>

                <?php if ($approved_result->num_rows > 0): ?>
                    <div class="technicians-grid">
                        <?php while ($tech = $approved_result->fetch_assoc()): ?>
                            <div class="technician-card approved">
                                <div class="card-header">
                                    <div class="tech-avatar approved-avatar">
                                        <span><?php echo strtoupper(substr($tech['Technician_FN'], 0, 1) . substr($tech['Technician_LN'], 0, 1)); ?></span>
                                    </div>
                                    <div class="tech-info">
                                        <h3><?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?></h3>
                                        <p class="tech-email"><?php echo htmlspecialchars($tech['Technician_Email']); ?></p>
                                        <p class="tech-phone"><?php echo htmlspecialchars($tech['Technician_Phone']); ?></p>
                                    </div>
                                    <div class="status-badge status-approved">✓ Approved</div>
                                </div>

                                <div class="card-body">
                                    <div class="info-row">
                                        <span class="label">Specialization:</span>
                                        <span class="value"><?php echo htmlspecialchars($tech['Specialization']); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Rating:</span>
                                        <span class="value">⭐ <?php echo number_format($tech['Ratings'], 1); ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="label">Service Rate:</span>
                                        <span class="value">₱<?php echo number_format($tech['Service_Pricing']); ?>/hour</span>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>No Approved Technicians</h3>
                        <p>No technicians have been approved yet.</p>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Smooth scroll to sections
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>