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
        LIMIT 5
    ");
    $recent_stmt->bind_param("i", $user_id);
    $recent_stmt->execute();
    $recent_bookings = $recent_stmt->get_result();
    $recent_stmt->close();

    // Get available technicians for recommendations
    $techs_stmt = $conn->prepare("
        SELECT t.*, 
               (SELECT COUNT(*) FROM booking WHERE Technician_ID = t.Technician_ID AND Status = 'completed') as completed_jobs,
               (SELECT AVG(Rating) FROM booking WHERE Technician_ID = t.Technician_ID AND Rating > 0) as avg_rating
        FROM technician t 
        WHERE t.Status = 'available' 
        ORDER BY completed_jobs DESC, avg_rating DESC
        LIMIT 6
    ");
    $techs_stmt->execute();
    $recommended_techs = $techs_stmt->get_result();
    $techs_stmt->close();

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
    <title><?php echo $client['Client_FN']; ?>'s Dashboard - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client-dashboard-modern.css">
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
                            <span>Client Portal</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search services, technicians, or booking history..." class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell">
                        <span class="bell-icon">🔔</span>
                        <?php if ($active_bookings > 0): ?>
                        <span class="notification-badge"><?php echo $active_bookings; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN']); ?></h3>
                            <p>Premium Client</p>
                        </div>
                        <div class="user-dropdown">
                            <button class="dropdown-btn">⚙️</button>
                            <div class="dropdown-menu">
                                <a href="update_profile.php">👤 Profile Settings</a>
                                <a href="billing.php">💳 Billing & Payment</a>
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

    <div class="dashboard-container">
        <div class="container">
            <!-- Hero Dashboard Section -->
            <section class="dashboard-hero">
                <div class="hero-content">
                    <div class="welcome-text">
                        <h1>Welcome back, <?php echo htmlspecialchars($client['Client_FN']); ?>! 🎉</h1>
                        <p>Your personal repair hub - manage services, track progress, and connect with top-rated fixers.</p>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="stat-number"><?php echo $active_bookings; ?></div>
                            <div class="stat-label">Active Services</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number"><?php echo $completed_bookings; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number"><?php echo $appliances_count; ?></div>
                            <div class="stat-label">Appliances</div>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <a href="request_service.php" class="hero-btn primary">
                            <span class="btn-icon">🔧</span>
                            <span>Request New Service</span>
                        </a>
                        <a href="../marketplace.php" class="hero-btn secondary">
                            <span class="btn-icon">🛍️</span>
                            <span>Browse Marketplace</span>
                        </a>
                    </div>
                </div>
                
                <!-- Status Cards -->
                <div class="status-cards">
                    <div class="status-card active">
                        <div class="card-icon">🔧</div>
                        <div class="card-content">
                            <h3><?php echo $active_bookings; ?></h3>
                            <p>Active Services</p>
                            <span class="card-trend">Currently being worked on</span>
                        </div>
                    </div>
                    
                    <div class="status-card completed">
                        <div class="card-icon">✅</div>
                        <div class="card-content">
                            <h3><?php echo $completed_bookings; ?></h3>
                            <p>Completed Services</p>
                            <span class="card-trend">Successfully finished</span>
                        </div>
                    </div>
                    
                    <div class="status-card appliances">
                        <div class="card-icon">📱</div>
                        <div class="card-content">
                            <h3><?php echo $appliances_count; ?></h3>
                            <p>Registered Appliances</p>
                            <span class="card-trend">Under your care</span>
                        </div>
                    </div>

                    <div class="status-card rating">
                        <div class="card-icon">⭐</div>
                        <div class="card-content">
                            <h3>4.8</h3>
                            <p>Your Average Rating</p>
                            <span class="card-trend">Excellent client!</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions Marketplace Style -->
            <section class="marketplace-actions">
                <h2 class="section-title">
                    <span class="title-icon">⚡</span>
                    Quick Actions
                </h2>
                
                <div class="action-marketplace">
                    <div class="action-item featured">
                        <div class="action-badge">Most Used</div>
                        <div class="action-icon">🔧</div>
                        <div class="action-details">
                            <h3>Request Service</h3>
                            <p>Book repair for any appliance</p>
                            <div class="action-meta">
                                <span>⚡ Quick booking</span>
                                <span>📱 Instant quotes</span>
                            </div>
                        </div>
                        <a href="request_service.php" class="action-btn">Book Now</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">📋</div>
                        <div class="action-details">
                            <h3>My Bookings</h3>
                            <p>Track all your service requests</p>
                            <div class="action-meta">
                                <span><?php echo $active_bookings; ?> active</span>
                                <span><?php echo $completed_bookings; ?> completed</span>
                            </div>
                        </div>
                        <a href="my_bookings.php" class="action-btn">View All</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">📱</div>
                        <div class="action-details">
                            <h3>My Appliances</h3>
                            <p>Manage registered devices</p>
                            <div class="action-meta">
                                <span><?php echo $appliances_count; ?> registered</span>
                                <span>🔒 Warranty tracked</span>
                            </div>
                        </div>
                        <a href="my_appliances.php" class="action-btn">Manage</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">👤</div>
                        <div class="action-details">
                            <h3>Profile Settings</h3>
                            <p>Update your information</p>
                            <div class="action-meta">
                                <span>📧 Contact info</span>
                                <span>📍 Address</span>
                            </div>
                        </div>
                        <a href="update_profile.php" class="action-btn">Edit</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">💳</div>
                        <div class="action-details">
                            <h3>Payment & Billing</h3>
                            <p>Manage payment methods</p>
                            <div class="action-meta">
                                <span>💰 Auto-pay</span>
                                <span>📊 History</span>
                            </div>
                        </div>
                        <a href="billing.php" class="action-btn">Manage</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">🎧</div>
                        <div class="action-details">
                            <h3>Support Center</h3>
                            <p>Get help when you need it</p>
                            <div class="action-meta">
                                <span>24/7 chat</span>
                                <span>📞 Call support</span>
                            </div>
                        </div>
                        <a href="support.php" class="action-btn">Contact</a>
                    </div>
                </div>
            </section>

            <!-- Recent Activity Stream -->
            <section class="activity-stream">
                <div class="stream-header">
                    <div>
                        <h2 class="section-title">
                            <span class="title-icon">📊</span>
                            Recent Activity
                        </h2>
                        <p class="section-subtitle">Your latest service requests and updates</p>
                    </div>
                    <a href="my_bookings.php" class="view-all-link">View All →</a>
                </div>
                
                <?php if ($recent_bookings && $recent_bookings->num_rows > 0): ?>
                <div class="activity-timeline">
                    <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                    <div class="timeline-item status-<?php echo strtolower($booking['Status']); ?>">
                        <div class="timeline-marker">
                            <?php
                            switch($booking['Status']) {
                                case 'pending': echo '⏳'; break;
                                case 'in-progress': echo '🔧'; break;
                                case 'completed': echo '✅'; break;
                                default: echo '📋'; break;
                            }
                            ?>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h4><?php echo htmlspecialchars($booking['Service_Type']); ?></h4>
                                <span class="timeline-status status-<?php echo strtolower($booking['Status']); ?>">
                                    <?php echo ucfirst($booking['Status']); ?>
                                </span>
                            </div>
                            <p class="timeline-description">
                                <?php if (!empty($booking['Technician_FN'])): ?>
                                    👨‍🔧 Technician: <?php echo htmlspecialchars($booking['Technician_FN'] . ' ' . $booking['Technician_LN']); ?>
                                <?php else: ?>
                                    🔍 Searching for available technician...
                                <?php endif; ?>
                            </p>
                            <div class="timeline-meta">
                                <span class="timeline-date">📅 <?php echo date('M j, Y g:i A', strtotime($booking['AptDate'])); ?></span>
                                <span class="timeline-id">#<?php echo $booking['Booking_ID']; ?></span>
                            </div>
                        </div>
                        <div class="timeline-actions">
                            <a href="booking_details.php?id=<?php echo $booking['Booking_ID']; ?>" class="timeline-btn">View Details</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-activity">
                    <div class="empty-icon">📋</div>
                    <h3>No Recent Activity</h3>
                    <p>Start your repair journey by requesting your first service!</p>
                    <a href="request_service.php" class="empty-btn">Request First Service</a>
                </div>
                <?php endif; ?>
            </section>

            <!-- Recommended Technicians -->
            <?php if ($recommended_techs && $recommended_techs->num_rows > 0): ?>
            <section class="recommended-techs">
                <h2 class="section-title">
                    <span class="title-icon">⭐</span>
                    Top-Rated Technicians
                </h2>
                <p class="section-subtitle">Highly recommended fixers based on your service history</p>
                
                <div class="techs-grid">
                    <?php while ($tech = $recommended_techs->fetch_assoc()): ?>
                    <div class="tech-card">
                        <div class="tech-avatar">
                            <span><?php echo strtoupper(substr($tech['Technician_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="tech-info">
                            <h4><?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?></h4>
                            <p class="tech-specialty"><?php echo htmlspecialchars($tech['Specialization'] ?? 'General Repair'); ?></p>
                            <div class="tech-stats">
                                <span class="tech-rating">⭐ <?php echo number_format($tech['avg_rating'] ?? 4.5, 1); ?></span>
                                <span class="tech-jobs"><?php echo $tech['completed_jobs']; ?> jobs</span>
                            </div>
                        </div>
                        <div class="tech-actions">
                            <a href="book_technician.php?id=<?php echo $tech['Technician_ID']; ?>" class="tech-btn">Book Now</a>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Tips & Insights -->
            <section class="insights-section">
                <h2 class="section-title">
                    <span class="title-icon">💡</span>
                    Smart Tips & Insights
                </h2>
                
                <div class="insights-grid">
                    <div class="insight-card maintenance">
                        <div class="insight-icon">🔧</div>
                        <h4>Preventive Maintenance</h4>
                        <p>Schedule regular checkups to avoid costly repairs later.</p>
                        <a href="maintenance_tips.php" class="insight-link">Learn More →</a>
                    </div>
                    
                    <div class="insight-card communication">
                        <div class="insight-icon">📱</div>
                        <h4>Stay Connected</h4>
                        <p>Enable notifications for real-time service updates.</p>
                        <a href="notification_settings.php" class="insight-link">Settings →</a>
                    </div>
                    
                    <div class="insight-card feedback">
                        <div class="insight-icon">⭐</div>
                        <h4>Your Feedback Matters</h4>
                        <p>Rate completed services to help improve our platform.</p>
                        <a href="my_reviews.php" class="insight-link">View Reviews →</a>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>PinoyFix</h4>
                    <p>Your trusted repair partner since 2025</p>
                </div>
                <div class="footer-section">
                    <h5>Quick Links</h5>
                    <a href="support.php">Support</a>
                    <a href="terms.php">Terms</a>
                    <a href="privacy.php">Privacy</a>
                </div>
                <div class="footer-section">
                    <h5>Contact</h5>
                    <p>📞 (02) 8123-4567</p>
                    <p>📧 support@pinoyfix.com</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines</p>
            </div>
        </div>
    </footer>

    <script>
        // Enhanced animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate hero stats
            const heroNumbers = document.querySelectorAll('.stat-number');
            heroNumbers.forEach(stat => {
                const finalNumber = parseInt(stat.textContent) || 0;
                let currentNumber = 0;
                const increment = Math.ceil(finalNumber / 30) || 1;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        currentNumber = finalNumber;
                        clearInterval(timer);
                    }
                    stat.textContent = currentNumber;
                }, 50);
            });

            // Animate status cards
            const statusCards = document.querySelectorAll('.status-card');
            statusCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-slide-up');
            });

            // Action items hover effects
            const actionItems = document.querySelectorAll('.action-item');
            actionItems.forEach(item => {
                item.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-8px) scale(1.02)';
                    this.style.boxShadow = '0 20px 40px rgba(0,0,0,0.15)';
                });
                
                item.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) scale(1)';
                    this.style.boxShadow = '';
                });
            });

            // Timeline items animation
            const timelineItems = document.querySelectorAll('.timeline-item');
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const timelineObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-fade-in');
                    }
                });
            }, observerOptions);

            timelineItems.forEach(item => {
                timelineObserver.observe(item);
            });

            // Search functionality
            const searchInput = document.querySelector('.search-input');
            searchInput.addEventListener('focus', function() {
                this.parentElement.classList.add('search-focused');
            });

            searchInput.addEventListener('blur', function() {
                this.parentElement.classList.remove('search-focused');
            });

            // Dropdown menu
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
        });

        // Add some CSS animations via JavaScript
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateX(-20px);
                }
                to {
                    opacity: 1;
                    transform: translateX(0);
                }
            }

            .animate-slide-up {
                animation: slideUp 0.6s ease-out forwards;
            }

            .animate-fade-in {
                animation: fadeIn 0.6s ease-out forwards;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
