<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
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
                        <p class="subtitle">System Administration</p>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="admin-info">
                        <div class="admin-avatar">
                            <span>A</span>
                        </div>
                        <div class="admin-details">
                            <p class="admin-name">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                            <p class="admin-role">System Administrator</p>
                        </div>
                    </div>
                    
                    <a href="../logout.php" class="logout-btn">
                        <span class="logout-icon">🚪</span>
                        Logout
                    </a>
                </div>
            </div>
        </header>

        <!-- Dashboard Stats -->
        <section class="stats-section">
            <div class="stats-grid">
                <?php
                include '../connection.php';
                
                // Get stats safely
                try {
                    $pending_techs = $conn->query("SELECT COUNT(*) as count FROM technician WHERE Status = 'pending'")->fetch_assoc()['count'];
                } catch (Exception $e) {
                    $pending_techs = 0;
                }
                
                try {
                    $total_techs = $conn->query("SELECT COUNT(*) as count FROM technician WHERE Status = 'approved'")->fetch_assoc()['count'];
                } catch (Exception $e) {
                    $total_techs = 0;
                }
                
                try {
                    $total_clients = $conn->query("SELECT COUNT(*) as count FROM client")->fetch_assoc()['count'];
                } catch (Exception $e) {
                    $total_clients = 0;
                }
                
                try {
                    $total_bookings = $conn->query("SELECT COUNT(*) as count FROM booking")->fetch_assoc()['count'];
                } catch (Exception $e) {
                    $total_bookings = 0;
                }
                ?>
                
                <div class="stat-card pending">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $pending_techs; ?></h3>
                        <p>Pending Technicians</p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-info">
                        <h3><?php echo $total_techs; ?></h3>
                        <p>Active Technicians</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $total_clients; ?></h3>
                        <p>Total Clients</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo $total_bookings; ?></h3>
                        <p>Total Bookings</p>
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
                <a href="approve_technicians.php" class="action-card primary">
                    <div class="action-icon">✅</div>
                    <div class="action-content">
                        <h3>Approve Technicians</h3>
                        <p>Review and approve pending technician applications</p>
                        <?php if ($pending_techs > 0): ?>
                            <span class="action-badge"><?php echo $pending_techs; ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="manage_users.php" class="action-card secondary">
                    <div class="action-icon">👤</div>
                    <div class="action-content">
                        <h3>Manage Users</h3>
                        <p>View and manage client accounts and settings</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="view_analytics.php" class="action-card accent">
                    <div class="action-icon">📊</div>
                    <div class="action-content">
                        <h3>View Analytics</h3>
                        <p>Monitor platform performance and user metrics</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="system_settings.php" class="action-card neutral">
                    <div class="action-icon">⚙️</div>
                    <div class="action-content">
                        <h3>System Settings</h3>
                        <p>Configure platform settings and preferences</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>

                <!-- Replace the existing manage bookings link with this -->
                <a href="manage_bookings.php" class="action-card primary">
                    <div class="action-icon">📋</div>
                    <div class="action-content">
                        <h3>Manage Bookings</h3>
                        <p>Assign technicians to pending service requests</p>
                        <?php 
                        // Add this PHP code to get pending bookings count
                        try {
                            $pending_bookings = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Status = 'pending'")->fetch_assoc()['count'];
                            if ($pending_bookings > 0): 
                        ?>
                            <span class="action-badge"><?php echo $pending_bookings; ?> pending</span>
                        <?php 
                            endif;
                        } catch (Exception $e) {
                            // Handle error silently
                        }
                        ?>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="activity-section">
            <h2 class="section-title">
                <span class="section-icon">🕒</span>
                Recent Activity
            </h2>
            
            <div class="activity-list">
                <?php
                try {
                    // Get recent technician registrations
                    $recent_activity = $conn->query("
                        SELECT 'technician' as type, Technician_FN as fname, Technician_LN as lname, 
                               Technician_ID as id, Status as status 
                        FROM technician 
                        ORDER BY Technician_ID DESC 
                        LIMIT 5
                    ");
                    
                    if ($recent_activity && $recent_activity->num_rows > 0):
                        while ($activity = $recent_activity->fetch_assoc()):
                ?>
                        <div class="activity-item">
                            <div class="activity-avatar">
                                <span><?php echo strtoupper(substr($activity['fname'], 0, 1)); ?></span>
                            </div>
                            <div class="activity-content">
                                <p><strong><?php echo htmlspecialchars($activity['fname'] . ' ' . $activity['lname']); ?></strong> 
                                   registered as a technician</p>
                                <span class="activity-time">Recently</span>
                            </div>
                            <div class="activity-status <?php echo $activity['status']; ?>">
                                <?php echo ucfirst($activity['status']); ?>
                            </div>
                        </div>
                <?php 
                        endwhile;
                    else:
                ?>
                        <div class="empty-activity">
                            <p>No recent activity</p>
                        </div>
                <?php 
                    endif;
                } catch (Exception $e) {
                ?>
                    <div class="empty-activity">
                        <p>Unable to load recent activity</p>
                    </div>
                <?php } ?>
            </div>
        </section>
    </div>
</body>
</html>

