<?php
session_start();
include '../connection.php';

// Check if user is client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get client info
$stmt = $conn->prepare("SELECT Client_FN, Client_LN, Client_Email, Client_Phone FROM client WHERE Client_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

// Get client statistics
try {
    // Count active bookings
    $active_stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking WHERE Client_ID = ? AND Status IN ('pending', 'in-progress')");
    $active_stmt->bind_param("i", $user_id);
    $active_stmt->execute();
    $active_bookings = $active_stmt->get_result()->fetch_assoc()['count'];
    $active_stmt->close();

    // Count completed bookings
    $completed_stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking WHERE Client_ID = ? AND Status = 'completed'");
    $completed_stmt->bind_param("i", $user_id);
    $completed_stmt->execute();
    $completed_bookings = $completed_stmt->get_result()->fetch_assoc()['count'];
    $completed_stmt->close();

    // Count registered appliances
    $appliances_stmt = $conn->prepare("SELECT COUNT(*) as count FROM client_appliance WHERE Client_ID = ?");
    $appliances_stmt->bind_param("i", $user_id);
    $appliances_stmt->execute();
    $appliances_count = $appliances_stmt->get_result()->fetch_assoc()['count'];
    $appliances_stmt->close();

    // Get recent bookings
    $recent_stmt = $conn->prepare("
        SELECT b.*, t.Technician_FN, t.Technician_LN 
        FROM booking b 
        LEFT JOIN technician t ON b.Technician_ID = t.Technician_ID 
        WHERE b.Client_ID = ? 
        ORDER BY b.AptDate DESC 
        LIMIT 3
    ");
    $recent_stmt->bind_param("i", $user_id);
    $recent_stmt->execute();
    $recent_bookings = $recent_stmt->get_result();
    $recent_stmt->close();

    // Get client's booking history
    $client_bookings = $conn->prepare("
        SELECT b.*, t.Technician_ID
        FROM booking b 
        LEFT JOIN technician t ON b.Technician_ID = t.Technician_ID
        WHERE b.Client_ID = ? 
        ORDER BY b.AptDate DESC
    ");
    $client_bookings->bind_param("i", $user_id);
    $client_bookings->execute();
    $bookings = $client_bookings->get_result();

} catch (Exception $e) {
    $active_bookings = 0;
    $completed_bookings = 0;
    $appliances_count = 0;
    $recent_bookings = null;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Dashboard - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client_dashboard.css">
</head>
<body>
    <div class="client-container">
        <!-- Header -->
        <header class="client-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">PinoyFix</h1>
                        <p class="subtitle">Client Portal</p>
                    </div>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($client['Client_FN'] . ' ' . $client['Client_LN']); ?></h3>
                        <p><?php echo htmlspecialchars($client['Client_Email']); ?></p>
                    </div>
                    <a href="../logout.php" class="logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <!-- Welcome Section -->
        <section class="welcome-section">
            <div class="welcome-content">
                <h2>Welcome back, <?php echo htmlspecialchars($client['Client_FN']); ?>! 👋</h2>
                <p>Manage your appliance repairs, track service requests, and connect with trusted technicians.</p>
            </div>
        </section>

        <!-- Stats Overview -->
        <section class="stats-section">
            <div class="stats-grid">
                <div class="stat-card active">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-content">
                        <h3><?php echo $active_bookings; ?></h3>
                        <p>Active Services</p>
                    </div>
                </div>
                <div class="stat-card completed">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo $completed_bookings; ?></h3>
                        <p>Completed Services</p>
                    </div>
                </div>
                <div class="stat-card appliances">
                    <div class="stat-icon">📱</div>
                    <div class="stat-content">
                        <h3><?php echo $appliances_count; ?></h3>
                        <p>Registered Appliances</p>
                    </div>
                </div>
                <div class="stat-card rating">
                    <div class="stat-icon">⭐</div>
                    <div class="stat-content">
                        <h3>4.8</h3>
                        <p>Average Rating</p>
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
                <a href="request_service.php" class="action-card primary">
                    <div class="action-icon">🔧</div>
                    <div class="action-content">
                        <h3>Request Service</h3>
                        <p>Book a repair service for your appliances</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="my_bookings.php" class="action-card secondary">
                    <div class="action-icon">📋</div>
                    <div class="action-content">
                        <h3>My Bookings</h3>
                        <p>Track your service requests and status</p>
                        <?php if ($active_bookings > 0): ?>
                            <span class="action-badge"><?php echo $active_bookings; ?> active</span>
                        <?php endif; ?>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="my_appliances.php" class="action-card accent">
                    <div class="action-icon">📱</div>
                    <div class="action-content">
                        <h3>My Appliances</h3>
                        <p>Manage your registered appliances</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
                
                <a href="update_profile.php" class="action-card neutral">
                    <div class="action-icon">👤</div>
                    <div class="action-content">
                        <h3>Update Profile</h3>
                        <p>Edit your personal information</p>
                    </div>
                    <div class="action-arrow">→</div>
                </a>
            </div>
        </section>

        <!-- Recent Activity -->
        <section class="recent-section">
            <h2 class="section-title">
                <span class="section-icon">📊</span>
                Recent Activity
            </h2>
            
            <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                <div class="activity-list">
                    <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                        <div class="activity-item">
                            <div class="activity-icon status-<?php echo strtolower($booking['Status']); ?>">
                                <?php
                                switch($booking['Status']) {
                                    case 'pending': echo '⏳'; break;
                                    case 'in-progress': echo '🔧'; break;
                                    case 'completed': echo '✅'; break;
                                    default: echo '📋'; break;
                                }
                                ?>
                            </div>
                            <div class="activity-content">
                                <h4><?php echo htmlspecialchars($booking['Service_Type']); ?></h4>
                                <p>
                                    <?php if (!empty($booking['Technician_FN'])): ?>
                                        Technician: <?php echo htmlspecialchars($booking['Technician_FN'] . ' ' . $booking['Technician_LN']); ?>
                                    <?php else: ?>
                                        Waiting for technician assignment
                                    <?php endif; ?>
                                </p>
                                <span class="activity-date"><?php echo date('M j, Y g:i A', strtotime($booking['AptDate'])); ?></span>
                            </div>
                            <div class="activity-status status-<?php echo strtolower($booking['Status']); ?>">
                                <?php echo ucfirst($booking['Status']); ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
                <div class="section-footer">
                    <a href="my_bookings.php" class="view-all-btn">View All Bookings →</a>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No Recent Activity</h3>
                    <p>You haven't made any service requests yet. Start by requesting a repair service!</p>
                    <a href="request_service.php" class="empty-action-btn">Request First Service</a>
                </div>
            <?php endif; ?>
        </section>

        <!-- Tips Section -->
        <section class="tips-section">
            <h2 class="section-title">
                <span class="section-icon">💡</span>
                Helpful Tips
            </h2>
            
            <div class="tips-grid">
                <div class="tip-card">
                    <div class="tip-icon">🔍</div>
                    <h4>Before Booking</h4>
                    <p>Take clear photos of the issue and note any error messages for faster diagnosis.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">📱</div>
                    <h4>Stay Connected</h4>
                    <p>Keep your phone accessible for technician communication and updates.</p>
                </div>
                <div class="tip-card">
                    <div class="tip-icon">⭐</div>
                    <h4>Rate & Review</h4>
                    <p>Help other clients by rating your experience after service completion.</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="client-footer">
            <p>&copy; 2025 PinoyFix. All rights reserved.</p>
        </footer>
    </div>

    <script>
        // Add some interactive animations
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on load
            const statNumbers = document.querySelectorAll('.stat-content h3');
            statNumbers.forEach(stat => {
                const finalNumber = parseInt(stat.textContent);
                let currentNumber = 0;
                const increment = Math.ceil(finalNumber / 20);
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        currentNumber = finalNumber;
                        clearInterval(timer);
                    }
                    stat.textContent = currentNumber;
                }, 50);
            });

            // Add hover effects to action cards
            const actionCards = document.querySelectorAll('.action-card');
            actionCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-4px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                });
            });
        });
    </script>
</body>
</html>
