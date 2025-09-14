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
$stmt = $conn->prepare("SELECT Client_FN, Client_LN FROM client WHERE Client_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

// Handle AJAX requests for cancellation
if (isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $booking_id = intval($_POST['booking_id']);
    
    // Verify ownership and update status
    $cancel_stmt = $conn->prepare("UPDATE booking SET Status = 'cancelled' WHERE Booking_ID = ? AND Client_ID = ? AND Status = 'pending'");
    $cancel_stmt->bind_param("ii", $booking_id, $user_id);
    
    if ($cancel_stmt->execute() && $cancel_stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Unable to cancel booking']);
    }
    $cancel_stmt->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - PinoyFix</title>
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
                            <span>My Service History</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search bookings..." class="search-input" id="bookingSearch">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell" onclick="showNotification('📬 No new notifications', 'info')">
                        <span class="bell-icon">🔔</span>
                        <span class="notification-badge" id="notifBadge" style="display: none;">1</span>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN'] . ' ' . $client['Client_LN']); ?></h3>
                            <p>Premium Client</p>
                        </div>
                        <div class="user-dropdown">
                            <button class="dropdown-btn">⚙️</button>
                            <div class="dropdown-menu">
                                <a href="client_dashboard.php">🏠 Dashboard</a>
                                <a href="update_profile.php">👤 Profile Settings</a>
                                <a href="my_bookings.php">📋 My Bookings</a>
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
            <!-- Hero Section with Quick Stats -->
            <section class="dashboard-hero" style="background: linear-gradient(135deg, #0038A8 0%, #1e40af 50%, #3b82f6 100%); margin-bottom: 3rem;">
                <div class="hero-content">
                    <div class="welcome-text">
                        <h1>My Service History 📋</h1>
                        <p>Track all your repair requests, monitor progress, and manage your service bookings in one place.</p>
                    </div>
                    
                    <?php
                    // Get quick stats for hero
                    $stats_query = "
                        SELECT 
                            SUM(CASE WHEN Status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                            SUM(CASE WHEN Status = 'in-progress' THEN 1 ELSE 0 END) as progress_count,
                            SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                            COUNT(*) as total_count
                        FROM booking 
                        WHERE Client_ID = ?
                    ";
                    
                    $stats_stmt = $conn->prepare($stats_query);
                    $stats_stmt->bind_param("i", $user_id);
                    $stats_stmt->execute();
                    $stats = $stats_stmt->get_result()->fetch_assoc();
                    $stats_stmt->close();
                    ?>
                    
                    <div class="status-cards">
                        <div class="status-card">
                            <div class="card-icon">📊</div>
                            <div class="card-content">
                                <h3><?php echo $stats['total_count'] ?? 0; ?></h3>
                                <p>Total Services</p>
                                <span class="card-trend">All time</span>
                            </div>
                        </div>
                        
                        <div class="status-card">
                            <div class="card-icon">⏳</div>
                            <div class="card-content">
                                <h3><?php echo $stats['pending_count'] ?? 0; ?></h3>
                                <p>Pending</p>
                                <span class="card-trend">Awaiting assignment</span>
                            </div>
                        </div>
                        
                        <div class="status-card">
                            <div class="card-icon">🔧</div>
                            <div class="card-content">
                                <h3><?php echo $stats['progress_count'] ?? 0; ?></h3>
                                <p>In Progress</p>
                                <span class="card-trend">Being worked on</span>
                            </div>
                        </div>
                        
                        <div class="status-card">
                            <div class="card-icon">✅</div>
                            <div class="card-content">
                                <h3><?php echo $stats['completed_count'] ?? 0; ?></h3>
                                <p>Completed</p>
                                <span class="card-trend">Successfully finished</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bookings Sections -->
            <?php
            $statuses = [
                'pending' => [
                    'title' => 'Pending Requests', 
                    'icon' => '⏳', 
                    'class' => 'pending',
                    'description' => 'Waiting for technician assignment'
                ],
                'in-progress' => [
                    'title' => 'Services In Progress', 
                    'icon' => '🔧', 
                    'class' => 'progress',
                    'description' => 'Currently being worked on'
                ],
                'completed' => [
                    'title' => 'Completed Services', 
                    'icon' => '✅', 
                    'class' => 'completed',
                    'description' => 'Successfully finished repairs'
                ]
            ];

            foreach ($statuses as $status => $config):
                try {
                    $bookings_query = "
                        SELECT b.*, t.Technician_FN, t.Technician_LN, t.Technician_Phone, t.Specialization
                        FROM booking b 
                        LEFT JOIN technician t ON b.Technician_ID = t.Technician_ID 
                        WHERE b.Client_ID = ? AND b.Status = ? 
                        ORDER BY b.AptDate DESC
                    ";
                    
                    $bookings_stmt = $conn->prepare($bookings_query);
                    $bookings_stmt->bind_param("is", $user_id, $status);
                    $bookings_stmt->execute();
                    $bookings_result = $bookings_stmt->get_result();
                    $booking_count = $bookings_result->num_rows;
            ?>
                    <section class="marketplace-actions" style="margin-bottom: 3rem;">
                        <div class="section-title-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border-left: 6px solid <?php echo $status === 'pending' ? '#f59e0b' : ($status === 'in-progress' ? '#3b82f6' : '#10b981'); ?>;">
                            <div>
                                <h2 class="section-title">
                                    <span class="title-icon" style="background: <?php echo $status === 'pending' ? 'linear-gradient(135deg, #fef3c7, #fde68a)' : ($status === 'in-progress' ? 'linear-gradient(135deg, #dbeafe, #bfdbfe)' : 'linear-gradient(135deg, #dcfce7, #bbf7d0)'); ?>; color: <?php echo $status === 'pending' ? '#92400e' : ($status === 'in-progress' ? '#1d4ed8' : '#047857'); ?>; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"><?php echo $config['icon']; ?></span>
                                    <?php echo $config['title']; ?>
                                </h2>
                                <p style="color: #64748b; margin-top: 0.5rem;"><?php echo $config['description']; ?></p>
                            </div>
                            <div style="text-align: center;">
                                <div style="background: <?php echo $status === 'pending' ? 'linear-gradient(135deg, #f59e0b, #d97706)' : ($status === 'in-progress' ? 'linear-gradient(135deg, #3b82f6, #2563eb)' : 'linear-gradient(135deg, #10b981, #059669)'); ?>; color: white; padding: 12px 24px; border-radius: 20px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                    <?php echo $booking_count; ?> service<?php echo $booking_count !== 1 ? 's' : ''; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($booking_count > 0): ?>
                            <div class="action-marketplace">
                                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                    <div class="action-item <?php echo $status === 'pending' ? 'featured' : ''; ?>" data-booking-id="<?php echo $booking['Booking_ID']; ?>">
                                        <?php if ($status === 'pending'): ?>
                                            <div class="action-badge">Urgent</div>
                                        <?php endif; ?>
                                        
                                        <div class="action-icon" style="background: <?php echo $status === 'pending' ? 'linear-gradient(135deg, #fef3c7, #fde68a)' : ($status === 'in-progress' ? 'linear-gradient(135deg, #dbeafe, #bfdbfe)' : 'linear-gradient(135deg, #dcfce7, #bbf7d0)'); ?>; color: <?php echo $status === 'pending' ? '#92400e' : ($status === 'in-progress' ? '#1d4ed8' : '#047857'); ?>;">
                                            <?php echo $config['icon']; ?>
                                        </div>
                                        
                                        <div class="action-details">
                                            <h3><?php echo htmlspecialchars($booking['Service_Type']); ?></h3>
                                            <p>Booking ID: #<?php echo str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></p>
                                            
                                            <div class="action-meta">
                                                <span>📅 <?php echo date('M j, Y g:i A', strtotime($booking['AptDate'])); ?></span>
                                                <span>📍 <?php echo date('M j, Y', strtotime($booking['AptDate'])); ?></span>
                                            </div>
                                            
                                            <?php if (!empty($booking['Technician_FN'])): ?>
                                                <div style="background: rgba(0, 56, 168, 0.1); padding: 1rem; border-radius: 12px; margin: 1rem 0;">
                                                    <div style="font-weight: 600; color: #0038A8; margin-bottom: 0.5rem;">
                                                        👨‍🔧 <?php echo htmlspecialchars($booking['Technician_FN'] . ' ' . $booking['Technician_LN']); ?> ✅
                                                    </div>
                                                    <div style="font-family: monospace; color: #059669; font-weight: 600; margin-bottom: 0.25rem;">
                                                        📱 <?php echo htmlspecialchars($booking['Technician_Phone']); ?>
                                                    </div>
                                                    <div style="color: #64748b;">
                                                        🔧 <?php echo htmlspecialchars($booking['Specialization'] ?? 'General Repair'); ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div style="background: rgba(245, 158, 11, 0.1); padding: 1rem; border-radius: 12px; margin: 1rem 0; border-left: 4px solid #f59e0b;">
                                                    <div style="color: #f59e0b; font-weight: 600;">
                                                        🔍 Searching for available technician...
                                                    </div>
                                                    <small style="color: #94a3b8; display: block; margin-top: 0.25rem;">
                                                        Usually assigned within 30 minutes
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($booking['Description'])): ?>
                                                <div style="background: #f8fafc; padding: 0.75rem; border-radius: 8px; margin: 1rem 0; border-left: 3px solid #0038A8;">
                                                    <strong style="color: #374151;">📝 Description:</strong>
                                                    <p style="color: #64748b; font-style: italic; margin-top: 0.25rem; line-height: 1.5;">
                                                        <?php echo htmlspecialchars($booking['Description']); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($status === 'completed' && !empty($booking['Rating'])): ?>
                                                <div style="background: rgba(245, 158, 11, 0.1); padding: 0.75rem; border-radius: 8px; margin: 1rem 0;">
                                                    <strong style="color: #374151;">⭐ Your Rating:</strong>
                                                    <span style="color: #f59e0b; font-size: 1.1rem; margin-left: 0.5rem;">
                                                        <?php 
                                                        $rating = intval($booking['Rating']);
                                                        echo str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
                                                        echo " ($rating/5)";
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
                                            <?php if ($status === 'pending'): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);" onclick="cancelBooking(<?php echo $booking['Booking_ID']; ?>)">
                                                    ❌ Cancel Request
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($booking['Technician_Phone'])): ?>
                                                <a href="tel:<?php echo $booking['Technician_Phone']; ?>" class="action-btn">
                                                    📞 Call Technician
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($status === 'in-progress'): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);" onclick="trackService(<?php echo $booking['Booking_ID']; ?>)">
                                                    📍 Track Progress
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if ($status === 'completed' && empty($booking['Rating'])): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);" onclick="rateService(<?php echo $booking['Booking_ID']; ?>)">
                                                    ⭐ Rate Service
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-activity">
                                <div class="empty-icon"><?php echo $config['icon']; ?></div>
                                <h3>No <?php echo strtolower($config['title']); ?></h3>
                                <p>
                                    <?php
                                    switch($status) {
                                        case 'pending':
                                            echo 'You have no pending service requests. Ready to get your appliances fixed?';
                                            break;
                                        case 'in-progress':
                                            echo 'No services currently in progress. Once a technician accepts your request, it will appear here with real-time updates.';
                                            break;
                                        case 'completed':
                                            echo 'No completed services yet. Your service history will appear here once repairs are finished.';
                                            break;
                                    }
                                    ?>
                                </p>
                                <?php if ($status === 'pending'): ?>
                                    <a href="request_service.php" class="empty-btn">
                                        🔧 Request Service Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>
            <?php
                    $bookings_stmt->close();
                } catch (Exception $e) {
                    echo "<div style='background: rgba(239, 68, 68, 0.1); border: 2px solid rgba(239, 68, 68, 0.3); color: #991b1b; padding: 1.5rem; border-radius: 16px; margin: 2rem; text-align: center; font-weight: 600;'>Error loading bookings: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            endforeach;
            ?>
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
                    <h5>Need Help?</h5>
                    <a href="#">📞 Contact Support</a>
                    <a href="#">💬 Live Chat</a>
                    <a href="#">📧 Email Us</a>
                </div>
                <div class="footer-section">
                    <h5>Quick Links</h5>
                    <a href="client_dashboard.php">🏠 Dashboard</a>
                    <a href="request_service.php">🔧 New Request</a>
                    <a href="my_bookings.php">📋 My Bookings</a>
                </div>
                <div class="footer-section">
                    <h5>Service Guarantee</h5>
                    <p>🛡️ 30-day warranty</p>
                    <p>⭐ 100% satisfaction</p>
                    <p>🔒 Verified technicians</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines</p>
            </div>
        </div>
    </footer>

    <script>
        // Toggle dropdown menu
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const dropdownBtn = document.querySelector('.dropdown-btn');
            
            if (!dropdown.contains(event.target) && !dropdownBtn.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Search functionality
        document.getElementById('bookingSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const bookingCards = document.querySelectorAll('.action-item');
            
            bookingCards.forEach(card => {
                const serviceType = card.querySelector('h3').textContent.toLowerCase();
                const description = card.textContent.toLowerCase();
                
                if (serviceType.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Enhanced cancel booking function with AJAX
        async function cancelBooking(bookingId) {
            if (!confirm('⚠️ Are you sure you want to cancel this booking?\n\nThis action cannot be undone.')) {
                return;
            }

            const card = document.querySelector(`[data-booking-id="${bookingId}"]`);
            const cancelBtn = card.querySelector('button[onclick*="cancelBooking"]');
            
            // Show loading state
            cancelBtn.disabled = true;
            cancelBtn.innerHTML = '⏳ Cancelling...';
            cancelBtn.style.background = '#94a3b8';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=cancel_booking&booking_id=${bookingId}`
                });

                const result = await response.json();

                if (result.success) {
                    // Success animation
                    card.style.transform = 'scale(0.95)';
                    card.style.opacity = '0.7';
                    
                    showNotification('✅ Booking cancelled successfully!', 'success');
                    
                    // Remove card after animation
                    setTimeout(() => {
                        card.remove();
                        // Update stats if needed
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(result.message || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification(`❌ Error: ${error.message}`, 'error');
                
                // Reset button state
                cancelBtn.disabled = false;
                cancelBtn.innerHTML = '❌ Cancel Request';
                cancelBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            }
        }

        function trackService(bookingId) {
            showNotification('📍 Service tracking feature coming soon! You can call your technician for real-time updates.', 'info');
        }

        function rateService(bookingId) {
            const rating = prompt('⭐ Rate this service (1-5 stars):\n\n1 = Poor\n2 = Fair\n3 = Good\n4 = Very Good\n5 = Excellent');
            
            if (rating && rating >= 1 && rating <= 5) {
                showNotification(`⭐ Thank you for rating this service ${rating}/5 stars! Your feedback helps us improve.`, 'success');
                // TODO: Send rating to server
            } else if (rating !== null) {
                showNotification('❌ Please enter a valid rating (1-5)', 'error');
            }
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.textContent = message;
            
            Object.assign(notification.style, {
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '16px 24px',
                borderRadius: '12px',
                color: 'white',
                fontWeight: '600',
                zIndex: '9999',
                maxWidth: '400px',
                boxShadow: '0 8px 32px rgba(0,0,0,0.2)',
                transform: 'translateX(400px)',
                transition: 'all 0.3s ease',
                cursor: 'pointer'
            });

            const colors = {
                success: 'linear-gradient(135deg, #10b981, #059669)',
                error: 'linear-gradient(135deg, #ef4444, #dc2626)',
                info: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                warning: 'linear-gradient(135deg, #f59e0b, #d97706)'
            };
            
            notification.style.background = colors[type] || colors.info;
            document.body.appendChild(notification);
            
            setTimeout(() => notification.style.transform = 'translateX(0)', 100);
            
            setTimeout(() => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => notification.remove(), 300);
            }, 5000);

            notification.addEventListener('click', () => {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => notification.remove(), 300);
            });
        }

        // Add phone call notifications
        document.addEventListener('DOMContentLoaded', function() {
            const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
            phoneLinks.forEach(link => {
                link.addEventListener('click', function() {
                    showNotification('📞 Opening your phone app to call the technician...', 'info');
                });
            });

            // Animate cards on load
            const actionItems = document.querySelectorAll('.action-item');
            actionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                    item.style.transition = 'all 0.6s ease';
                }, index * 100);
            });
        });
    </script>
</body>
</html>