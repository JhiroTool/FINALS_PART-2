<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

// Get technician info
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT Technician_FN, Technician_LN, Technician_Email, Technician_Phone, Specialization, Service_Pricing, Status, Ratings, Technician_Profile, Tech_Certificate FROM technician WHERE Technician_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$technician = $result->fetch_assoc();
$stmt->close();

if (!$technician) {
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician_dashboard.css">
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
                        <p class="subtitle">Technician Portal</p>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="tech-info">
                        <div class="tech-avatar">
                            <?php if (!empty($technician['Technician_Profile'])): ?>
                                <img src="../uploads/profile_pics/<?php echo htmlspecialchars($technician['Technician_Profile']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($technician['Technician_FN'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="tech-details">
                            <p class="tech-name">Welcome, <?php echo htmlspecialchars($technician['Technician_FN']); ?>!</p>
                            <p class="tech-role">
                                <?php echo htmlspecialchars($technician['Specialization'] ?: 'Technician'); ?>
                                <?php if ($technician['Status'] === 'approved'): ?>
                                    <span class="verified-badge">✓ Verified</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                    
                    <a href="../logout.php" class="logout-btn">
                        <span class="logout-icon">🚪</span>
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Status Section -->
        <section class="status-section">
            <div class="status-card">
                <div class="status-header">
                    <h3>Account Status</h3>
                    <div class="status-badge status-<?php echo $technician['Status']; ?>">
                        <?php
                        switch($technician['Status']) {
                            case 'approved':
                                echo '✅ Approved';
                                break;
                            case 'pending':
                                echo '⏳ Pending Review';
                                break;
                            case 'rejected':
                                echo '❌ Rejected';
                                break;
                            default:
                                echo '⚠️ Under Review';
                        }
                        ?>
                    </div>
                </div>
                
                <?php if ($technician['Status'] === 'pending'): ?>
                    <div class="status-message">
                        <p>Your technician application is currently under review. You'll be notified once approved.</p>
                    </div>
                <?php elseif ($technician['Status'] === 'rejected'): ?>
                    <div class="status-message error">
                        <p>Your application was rejected. Please contact support for more information.</p>
                    </div>
                <?php else: ?>
                    <div class="status-message success">
                        <p>You're all set! Start accepting jobs and helping customers.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Dashboard Stats -->
        <section class="stats-section">
            <div class="stats-grid">
                <?php
                // Get technician stats - Fix the queries
                try {
                    // Show assigned jobs (jobs given by admin) as pending for technician
                    $pending_bookings = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'assigned'")->fetch_assoc()['count'];
                } catch (Exception $e) {
                    $pending_bookings = 0;
                }
                
                try {
                    $completed_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'completed'")->fetch_assoc()['count'];
                } catch (Exception $e) {
                    $completed_jobs = 0;
                }
                
                try {
                    // Add in-progress jobs
                    $in_progress_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'in_progress'")->fetch_assoc()['count'];
                } catch (Exception $e) {
                    $in_progress_jobs = 0;
                }
                
                $rating = $technician['Ratings'] ?: '0.0';
                $hourly_rate = $technician['Service_Pricing'] ?: '0';
                ?>
                
                <div class="stat-card pending">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo $pending_bookings; ?></h3>
                        <p>New Assignments</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-info">
                        <h3><?php echo $in_progress_jobs; ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $completed_jobs; ?></h3>
                        <p>Completed Jobs</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($hourly_rate); ?></h3>
                        <p>Hourly Rate</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="actions-section">
            <h2 class="section-title">
                <span class="section-icon">⚡</span>
                Quick Actions
            </h2>
            
            <div class="actions-grid">
                <a href="assigned_jobs.php" class="action-card primary">
                    <div class="action-icon">📋</div>
                    <div class="action-content">
                        <h3>View Jobs</h3>
                        <p>Check assigned jobs and update status</p>
                        <?php if ($pending_bookings > 0): ?>
                            <span class="action-badge"><?php echo $pending_bookings; ?> new</span>
                        <?php endif; ?>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="update_technician_profile.php" class="action-card secondary">
                    <div class="action-icon">👤</div>
                    <div class="action-content">
                        <h3>Update Profile</h3>
                        <p>Manage your profile and service details</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="verify_certification.php" class="action-card accent">
                    <div class="action-icon">📄</div>
                    <div class="action-content">
                        <h3>Upload Certificate</h3>
                        <p>Submit your certification documents</p>
                        <?php if (empty($technician['Tech_Certificate'])): ?>
                            <span class="action-badge">Required</span>
                        <?php endif; ?>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="earnings.php" class="action-card neutral">
                    <div class="action-icon">💼</div>
                    <div class="action-content">
                        <h3>Earnings</h3>
                        <p>View your earnings and payment history</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
            </div>
        </section>

        <!-- Recent Jobs -->
        <section class="jobs-section">
            <h2 class="section-title">
                <span class="section-icon">🔧</span>
                Recent Jobs
            </h2>
            
            <div class="jobs-list">
                <?php
                try {
                    // Show all jobs assigned to this technician (not just pending)
                    $recent_jobs = $conn->query("
                        SELECT b.*, c.Client_FN, c.Client_LN, c.Client_Phone
                        FROM booking b 
                        LEFT JOIN client c ON b.Client_ID = c.Client_ID 
                        WHERE b.Technician_ID = {$user_id} 
                        AND b.Status IN ('assigned', 'in_progress', 'completed')
                        ORDER BY 
                            CASE b.Status 
                                WHEN 'assigned' THEN 1 
                                WHEN 'in_progress' THEN 2 
                                WHEN 'completed' THEN 3 
                            END,
                            b.AptDate ASC 
                        LIMIT 5
                    ");
                    
                    if ($recent_jobs && $recent_jobs->num_rows > 0):
                        while ($job = $recent_jobs->fetch_assoc()):
                ?>
                        <div class="job-item">
                            <div class="job-avatar">
                                <span><?php echo strtoupper(substr($job['Client_FN'], 0, 1)); ?></span>
                            </div>
                            <div class="job-content">
                                <h4><?php echo htmlspecialchars($job['Service_Type']); ?></h4>
                                <p><strong>Client:</strong> <?php echo htmlspecialchars($job['Client_FN'] . ' ' . $job['Client_LN']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($job['Client_Phone']); ?></p>
                                <p><strong>Scheduled:</strong> <?php echo date('M j, Y g:i A', strtotime($job['AptDate'])); ?></p>
                                <?php if (!empty($job['Description'])): ?>
                                    <p><strong>Details:</strong> <?php echo htmlspecialchars($job['Description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="job-status status-<?php echo $job['Status']; ?>">
                                <?php 
                                switch($job['Status']) {
                                    case 'assigned':
                                        echo '🆕 New Assignment';
                                        break;
                                    case 'in_progress':
                                        echo '🔄 In Progress';
                                        break;
                                    case 'completed':
                                        echo '✅ Completed';
                                        break;
                                    default:
                                        echo ucfirst($job['Status']);
                                }
                                ?>
                            </div>
                        </div>
                <?php 
                        endwhile;
                    else:
                ?>
                        <div class="empty-jobs">
                            <div class="empty-icon">🔧</div>
                            <h3>No Jobs Assigned Yet</h3>
                            <p>Wait for admin to assign jobs to you!</p>
                        </div>
                <?php 
                    endif;
                } catch (Exception $e) {
                ?>
                    <div class="empty-jobs">
                        <p>Unable to load recent jobs: <?php echo $e->getMessage(); ?></p>
                    </div>
                <?php } ?>
            </div>
        </section>
    </div>
</body>
</html>
