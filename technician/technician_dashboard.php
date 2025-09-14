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

// Get stats
try {
    $pending_bookings = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'assigned'")->fetch_assoc()['count'];
    $completed_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'completed'")->fetch_assoc()['count'];
    $in_progress_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'in_progress'")->fetch_assoc()['count'];
    
    // Get unread message count
    $unread_query = $conn->query("SELECT COUNT(*) as count FROM messages WHERE Receiver_ID = {$user_id} AND Receiver_Type = 'technician' AND Is_Read = 0");
    $unread_count = $unread_query ? $unread_query->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $pending_bookings = $completed_jobs = $in_progress_jobs = $unread_count = 0;
}

$rating = $technician['Ratings'] ?: '0.0';
$hourly_rate = $technician['Service_Pricing'] ?: '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician-dashboard.css">
    <link rel="stylesheet" href="../css/technician_dash.css">
</head>
<body>
    <!-- Modern Header -->
    <header class="modern-header">
        <div class="container">
            <div class="header-content">
                <!-- Brand -->
                <div class="brand-section">
                    <div class="logo-container">
                        <img src="../images/pinoyfix.png" alt="PinoyFix" class="brand-logo">
                        <div class="brand-text">
                            <h1>PinoyFix</h1>
                            <span>Technician Portal</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search jobs, clients..." class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell">
                        <span class="bell-icon">🔔</span>
                        <?php if ($pending_bookings > 0 || $unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $pending_bookings + $unread_count; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php if (!empty($technician['Technician_Profile'])): ?>
                                <img src="../uploads/profile_pics/<?php echo htmlspecialchars($technician['Technician_Profile']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($technician['Technician_FN'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($technician['Technician_FN']); ?></h3>
                            <p><?php echo htmlspecialchars($technician['Specialization']); ?></p>
                        </div>
                        <div class="user-dropdown">
                            <button class="dropdown-btn">⚙️</button>
                            <div class="dropdown-menu">
                                <a href="technician_dashboard.php">🏠 Dashboard</a>
                                <a href="assigned_jobs.php">📋 My Jobs</a>
                                <a href="messages.php">💬 Messages</a>
                                <a href="update_technician_profile.php">👤 Profile Settings</a>
                                <a href="verify_certification.php">📄 Certificates</a>
                                <a href="earnings.php">💰 Earnings</a>
                                <a href="support.php">🎧 Support Center</a>
                                <hr>
                                <a href="../logout.php" class="logout-link">🚪 Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">Welcome back, <?php echo htmlspecialchars($technician['Technician_FN']); ?>! 👋</h1>
                    <p class="hero-subtitle">Ready to help customers and earn money? Here's your job overview and quick access to everything you need.</p>
                </div>
            </div>

            <!-- Status Section -->
            <div class="status-section">
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
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
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

            <!-- Quick Actions -->
            <section class="section">
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
                    
                    <a href="messages.php" class="action-card info">
                        <div class="action-icon">💬</div>
                        <div class="action-content">
                            <h3>Messages</h3>
                            <p>Communicate with your clients</p>
                            <?php if ($unread_count > 0): ?>
                                <span class="action-badge"><?php echo $unread_count; ?> unread</span>
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
            <section class="section">
                <h2 class="section-title">
                    <span class="section-icon">🔧</span>
                    Recent Jobs
                </h2>
                
                <div class="jobs-list">
                    <?php
                    try {
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
                                        <p><strong>Details:</strong> <?php echo htmlspecialchars(substr($job['Description'], 0, 100)) . (strlen($job['Description']) > 100 ? '...' : ''); ?></p>
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
                            <div class="empty-icon">⚠️</div>
                            <h3>Unable to Load Jobs</h3>
                            <p>Please refresh the page or contact support if this persists.</p>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Dropdown menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtn = document.querySelector('.dropdown-btn');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (dropdownBtn && dropdownMenu) {
                dropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function() {
                    dropdownMenu.classList.remove('show');
                });
            }

            // Add smooth animations on page load
            const elements = document.querySelectorAll('.stat-card, .action-card, .job-item, .status-card');
            
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
                
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 + (index * 100));
            });
        });

        // Add hover effects for action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-4px) scale(1)';
            });
        });
    </script>
</body>
</html>
