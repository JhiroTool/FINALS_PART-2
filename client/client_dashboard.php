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

// Initialize variables with default values
$pending_requests = 0;
$accepted_bookings = 0;
$completed_bookings = 0;
$appliances_count = 0;
$recent_bookings = null;
$available_techs = null;

// Get client statistics
try {
    // Count pending service requests (waiting for technician to accept)
    $pending_stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking WHERE Client_ID = ? AND Status = 'pending'");
    $pending_stmt->bind_param("i", $user_id);
    $pending_stmt->execute();
    $pending_requests = $pending_stmt->get_result()->fetch_assoc()['count'];
    $pending_stmt->close();

    // Count accepted/in-progress bookings
    $accepted_stmt = $conn->prepare("SELECT COUNT(*) as count FROM booking WHERE Client_ID = ? AND Status IN ('accepted', 'in-progress')");
    $accepted_stmt->bind_param("i", $user_id);
    $accepted_stmt->execute();
    $accepted_bookings = $accepted_stmt->get_result()->fetch_assoc()['count'];
    $accepted_stmt->close();

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

    // Get recent bookings with status information
    $recent_stmt = $conn->prepare("
        SELECT b.*, t.Technician_FN, t.Technician_LN, t.Technician_Phone 
        FROM booking b 
        LEFT JOIN technician t ON b.Technician_ID = t.Technician_ID 
        WHERE b.Client_ID = ? 
        ORDER BY b.Created_At DESC 
        LIMIT 5
    ");
    $recent_stmt->bind_param("i", $user_id);
    $recent_stmt->execute();
    $recent_bookings = $recent_stmt->get_result();
    $recent_stmt->close();

    // Get available technicians (for display purposes only - not for booking)
    $techs_stmt = $conn->prepare("
        SELECT t.*, 
               COALESCE((SELECT COUNT(*) FROM booking WHERE Technician_ID = t.Technician_ID AND Status = 'completed'), 0) as completed_jobs,
               COALESCE((SELECT AVG(Rating) FROM booking WHERE Technician_ID = t.Technician_ID AND Rating > 0), 4.5) as avg_rating
        FROM technician t 
        WHERE t.Status = 'approved' 
        ORDER BY completed_jobs DESC, avg_rating DESC
        LIMIT 6
    ");
    $techs_stmt->execute();
    $available_techs = $techs_stmt->get_result();
    $techs_stmt->close();

} catch (Exception $e) {
    // Variables are already initialized above
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($client['Client_FN']); ?>'s Dashboard - PinoyFix</title>
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
                        <input type="text" placeholder="Search services, booking history..." class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell">
                        <span class="bell-icon">🔔</span>
                        <?php if (($pending_requests + $accepted_bookings) > 0): ?>
                        <span class="notification-badge"><?php echo ($pending_requests + $accepted_bookings); ?></span>
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
                                <a href="client_dashboard.php">🏠 Dashboard</a>
                                <a href="update_profile.php">👤 Profile Settings</a>
                                <a href="my_bookings.php">📋 My Bookings</a>
                                <a href="messages.php">💬 Messages</a>
                                <a href="my_appliances.php">📱 My Appliances</a>
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
                        <p>Your personal repair hub - request services and let skilled technicians come to you.</p>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="stat-number"><?php echo $pending_requests; ?></div>
                            <div class="stat-label">Pending Requests</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number"><?php echo $accepted_bookings; ?></div>
                            <div class="stat-label">Active Services</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number"><?php echo $completed_bookings; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <a href="request_service.php" class="hero-btn primary">
                            <span class="btn-icon">📝</span>
                            <span>Request New Service</span>
                        </a>
                        <a href="my_bookings.php" class="hero-btn secondary">
                            <span class="btn-icon">📋</span>
                            <span>Track My Requests</span>
                        </a>
                    </div>
                </div>
                
                <!-- Status Cards -->
                <div class="status-cards">
                    <div class="status-card pending">
                        <div class="card-icon">⏳</div>
                        <div class="card-content">
                            <h3><?php echo $pending_requests; ?></h3>
                            <p>Pending Requests</p>
                            <span class="card-trend">Waiting for technician</span>
                        </div>
                    </div>
                    
                    <div class="status-card active">
                        <div class="card-icon">🔧</div>
                        <div class="card-content">
                            <h3><?php echo $accepted_bookings; ?></h3>
                            <p>Active Services</p>
                            <span class="card-trend">Being worked on</span>
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
                            <p>My Appliances</p>
                            <span class="card-trend">Under your care</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How It Works Section -->
            <section class="how-it-works">
                <h2 class="section-title">
                    <span class="title-icon">🔧</span>
                    How PinoyFix Works
                </h2>
                <p class="section-subtitle">Simple process to get your appliances fixed</p>
                
                <div class="process-steps">
                    <div class="process-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>📝 Request Service</h3>
                            <p>Submit your repair request with details about your appliance and issue.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>🔍 Technicians Review</h3>
                            <p>Qualified technicians in your area will review and bid on your request.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>✅ Get Matched</h3>
                            <p>A technician accepts your request and contacts you to schedule service.</p>
                        </div>
                    </div>
                    
                    <div class="process-step">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>🔧 Service Complete</h3>
                            <p>Your appliance gets fixed, you pay, and rate the service experience.</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Quick Actions -->
            <section class="marketplace-actions">
                <h2 class="section-title">
                    <span class="title-icon">⚡</span>
                    Quick Actions
                </h2>
                
                <div class="action-marketplace">
                    <div class="action-item featured">
                        <div class="action-badge">Start Here</div>
                        <div class="action-icon">📝</div>
                        <div class="action-details">
                            <h3>Request Service</h3>
                            <p>Post your repair need and let technicians compete</p>
                            <div class="action-meta">
                                <span>⚡ Quick posting</span>
                                <span>📍 Local technicians</span>
                            </div>
                        </div>
                        <a href="request_service.php" class="action-btn">Create Request</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">📋</div>
                        <div class="action-details">
                            <h3>My Service Requests</h3>
                            <p>Track all your service requests and responses</p>
                            <div class="action-meta">
                                <span><?php echo $pending_requests; ?> pending</span>
                                <span><?php echo $accepted_bookings; ?> active</span>
                            </div>
                        </div>
                        <a href="my_bookings.php" class="action-btn">View All</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">📱</div>
                        <div class="action-details">
                            <h3>My Appliances</h3>
                            <p>Manage your registered appliances and warranties</p>
                            <div class="action-meta">
                                <span><?php echo $appliances_count; ?> registered</span>
                                <span>🔒 Warranty tracking</span>
                            </div>
                        </div>
                        <a href="my_appliances.php" class="action-btn">Manage</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">💬</div>
                        <div class="action-details">
                            <h3>Messages</h3>
                            <p>Chat with technicians about your services</p>
                            <div class="action-meta">
                                <?php
                                // Get unread message count safely
                                try {
                                    $unread_query = $conn->query("SELECT COUNT(*) as count FROM messages WHERE Receiver_ID = {$user_id} AND Receiver_Type = 'client' AND Is_Read = 0");
                                    $unread_count = $unread_query ? $unread_query->fetch_assoc()['count'] : 0;
                                } catch (Exception $e) {
                                    $unread_count = 0;
                                }
                                ?>
                                <span><?php echo $unread_count; ?> unread</span>
                                <span>💬 Real-time chat</span>
                            </div>
                        </div>
                        <a href="messages.php" class="action-btn">Open Chat</a>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">👤</div>
                        <div class="action-details">
                            <h3>Profile Settings</h3>
                            <p>Update your information and preferences</p>
                            <div class="action-meta">
                                <span>📧 Contact info</span>
                                <span>📍 Address</span>
                            </div>
                        </div>
                        <a href="update_profile.php" class="action-btn">Edit</a>
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
                            Recent Service Requests
                        </h2>
                        <p class="section-subtitle">Your latest requests and their status</p>
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
                                case 'accepted': echo '👨‍🔧'; break;
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
                                    <?php 
                                    switch($booking['Status']) {
                                        case 'pending':
                                            echo 'Waiting for Technician';
                                            break;
                                        case 'accepted':
                                            echo 'Technician Assigned';
                                            break;
                                        case 'in-progress':
                                            echo 'Service in Progress';
                                            break;
                                        case 'completed':
                                            echo 'Service Completed';
                                            break;
                                        default:
                                            echo ucfirst($booking['Status']);
                                    }
                                    ?>
                                </span>
                            </div>
                            <p class="timeline-description">
                                <?php if (!empty($booking['Technician_FN'])): ?>
                                    👨‍🔧 Technician: <?php echo htmlspecialchars($booking['Technician_FN'] . ' ' . $booking['Technician_LN']); ?>
                                    <br>📞 Phone: <?php echo htmlspecialchars($booking['Technician_Phone']); ?>
                                <?php else: ?>
                                    🔍 Looking for available technicians in your area...
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($booking['Description'])): ?>
                                <p class="timeline-details">
                                    <strong>Issue:</strong> <?php echo htmlspecialchars(substr($booking['Description'], 0, 100)) . (strlen($booking['Description']) > 100 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>
                            <div class="timeline-meta">
                                <span class="timeline-date">📅 Requested: <?php echo date('M j, Y g:i A', strtotime($booking['Created_At'])); ?></span>
                                <?php if ($booking['AptDate']): ?>
                                    <span class="timeline-date">🕒 Scheduled: <?php echo date('M j, Y g:i A', strtotime($booking['AptDate'])); ?></span>
                                <?php endif; ?>
                                <span class="timeline-id">#<?php echo $booking['Booking_ID']; ?></span>
                            </div>
                        </div>
                        <div class="timeline-actions">
                            <a href="booking_details.php?id=<?php echo $booking['Booking_ID']; ?>" class="timeline-btn">View Details</a>
                            <?php if (!empty($booking['Technician_ID'])): ?>
                                <a href="messages.php?conversation_id=<?php echo $booking['Technician_ID']; ?>" class="timeline-btn secondary">Message</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-activity">
                    <div class="empty-icon">📝</div>
                    <h3>No Service Requests Yet</h3>
                    <p>Start your repair journey by posting your first service request!</p>
                    <a href="request_service.php" class="empty-btn">Create First Request</a>
                </div>
                <?php endif; ?>
            </section>

            <!-- Available Technicians (Information Only) -->
            <?php if ($available_techs && $available_techs->num_rows > 0): ?>
            <section class="available-techs">
                <h2 class="section-title">
                    <span class="title-icon">👨‍🔧</span>
                    Available Technicians in Your Area
                </h2>
                <p class="section-subtitle">These skilled professionals might respond to your service requests</p>
                
                <div class="techs-grid">
                    <?php while ($tech = $available_techs->fetch_assoc()): ?>
                    <div class="tech-card">
                        <div class="tech-avatar">
                            <?php if (!empty($tech['Technician_Profile'])): ?>
                                <img src="../uploads/profile_pics/<?php echo htmlspecialchars($tech['Technician_Profile']); ?>" alt="Profile">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($tech['Technician_FN'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="tech-info">
                            <h4><?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?></h4>
                            <p class="tech-specialty"><?php echo htmlspecialchars($tech['Specialization'] ?? 'General Repair'); ?></p>
                            <div class="tech-stats">
                                <span class="tech-rating">⭐ <?php echo number_format($tech['avg_rating'], 1); ?></span>
                                <span class="tech-jobs"><?php echo $tech['completed_jobs']; ?> jobs completed</span>
                            </div>
                            <p class="tech-price">₱<?php echo number_format($tech['Service_Pricing'] ?? 500); ?>/hour</p>
                        </div>
                        <div class="tech-status">
                            <span class="status-badge available">Available</span>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                
                <div class="techs-note">
                    <p>💡 <strong>Note:</strong> You cannot directly book these technicians. Post a service request and let them compete for your job!</p>
                </div>
            </section>
            <?php endif; ?>

            <!-- Tips & Insights -->
            <section class="insights-section">
                <h2 class="section-title">
                    <span class="title-icon">💡</span>
                    Tips for Better Service Requests
                </h2>
                
                <div class="insights-grid">
                    <div class="insight-card details">
                        <div class="insight-icon">📝</div>
                        <h4>Provide Clear Details</h4>
                        <p>The more details you provide about the issue, the better quotes you'll receive.</p>
                        <a href="request_service.php" class="insight-link">Create Request →</a>
                    </div>
                    
                    <div class="insight-card photos">
                        <div class="insight-icon">📷</div>
                        <h4>Include Photos</h4>
                        <p>Pictures help technicians understand the problem before they visit.</p>
                        <a href="request_service.php" class="insight-link">Add Photos →</a>
                    </div>
                    
                    <div class="insight-card timing">
                        <div class="insight-icon">⏰</div>
                        <h4>Be Flexible with Timing</h4>
                        <p>Flexible scheduling gets more responses from available technicians.</p>
                        <a href="my_bookings.php" class="insight-link">Manage Schedule →</a>
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

            // Process steps animation
            const processSteps = document.querySelectorAll('.process-step');
            const observerOptions = {
                threshold: 0.5,
                rootMargin: '0px 0px -100px 0px'
            };

            const stepsObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('animate-step');
                        }, index * 200);
                    }
                });
            }, observerOptions);

            processSteps.forEach(step => {
                stepsObserver.observe(step);
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

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            .animate-step {
                animation: stepReveal 0.8s ease-out forwards;
            }

            @keyframes stepReveal {
                from {
                    opacity: 0;
                    transform: translateY(30px) scale(0.9);
                }
                to {
                    opacity: 1;
                    transform: translateY(0) scale(1);
                }
            }

            .process-step {
                opacity: 0;
                transform: translateY(30px) scale(0.9);
            }

            .status-badge.available {
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                padding: 0.25rem 0.5rem;
                border-radius: 6px;
                font-size: 0.75rem;
                font-weight: 600;
            }

            .techs-note {
                background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(139, 92, 246, 0.1));
                border: 1px solid rgba(59, 130, 246, 0.2);
                border-radius: 12px;
                padding: 1rem;
                margin-top: 1.5rem;
                text-align: center;
            }

            .techs-note p {
                margin: 0;
                color: var(--gray-700);
            }

            .timeline-btn.secondary {
                background: transparent;
                color: var(--primary);
                border: 1px solid var(--primary);
                margin-left: 0.5rem;
            }

            .timeline-btn.secondary:hover {
                background: var(--primary);
                color: white;
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
