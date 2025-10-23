<?php
// Add these lines at the very top for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../connection.php';

// Check if user is client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

function isSubscriptionActive($flag, $expires) {
    if (!$flag) {
        return false;
    }
    if (empty($expires)) {
        return true;
    }
    return strtotime($expires) > time();
}

// Get client info
$stmt = $conn->prepare("SELECT c.Client_FN, c.Client_LN, c.Is_Subscribed, c.Subscription_Expires, a.City
                        FROM client c
                        LEFT JOIN client_address ca ON c.Client_ID = ca.Client_ID
                        LEFT JOIN address a ON ca.Address_ID = a.Address_ID
                        WHERE c.Client_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

$client_is_subscribed = isSubscriptionActive((int)($client['Is_Subscribed'] ?? 0), $client['Subscription_Expires'] ?? null);
$client_city = $client['City'] ?? '';
$client_badge = $client_is_subscribed ? 'Premium Client' : 'Standard Client';
$subscription_expiry_text = $client_is_subscribed && $client['Subscription_Expires'] ? date('M j, Y', strtotime($client['Subscription_Expires'])) : null;

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_type = trim($_POST['service_type']);
    $apt_date = $_POST['apt_date'];
    $description = trim($_POST['description']);
    $priority = $_POST['priority'] ?? 'normal';
    
    // Insert booking
    $booking_stmt = $conn->prepare("
        INSERT INTO booking (Client_ID, Service_Type, AptDate, Status, Description) 
        VALUES (?, ?, ?, 'pending', ?)
    ");
    $booking_stmt->bind_param("isss", $user_id, $service_type, $apt_date, $description);

    if ($booking_stmt->execute()) {
        $new_booking_id = $conn->insert_id;
        $message = "🎉 Service request submitted successfully! We'll assign a verified technician within 30 minutes.";
        if ($client_is_subscribed) {
            $lookup_city = $client_city ?? '';
            $tech_stmt = $conn->prepare("
                SELECT t.Technician_ID
                FROM technician t
                LEFT JOIN technician_address ta ON t.Technician_ID = ta.Technician_ID
                LEFT JOIN address a ON ta.Address_ID = a.Address_ID
                WHERE t.Status = 'approved'
                ORDER BY
                    CASE
                        WHEN t.Is_Subscribed = 1 AND (t.Subscription_Expires IS NULL OR t.Subscription_Expires = '0000-00-00 00:00:00' OR t.Subscription_Expires > NOW()) THEN 0
                        ELSE 1
                    END,
                    CASE WHEN a.City = ? THEN 0 ELSE 1 END,
                    t.Technician_ID ASC
                LIMIT 1
            ");
            $tech_stmt->bind_param("s", $lookup_city);
            $tech_stmt->execute();
            $tech_result = $tech_stmt->get_result();
            $selected_tech = $tech_result->fetch_assoc();
            $tech_stmt->close();

            if ($selected_tech && isset($selected_tech['Technician_ID'])) {
                $assign_stmt = $conn->prepare("UPDATE booking SET Technician_ID = ?, Status = 'assigned' WHERE Booking_ID = ?");
                $assign_stmt->bind_param("ii", $selected_tech['Technician_ID'], $new_booking_id);
                if ($assign_stmt->execute()) {
                    $message = "🚀 Priority service confirmed! We matched you with a nearby premium technician.";
                }
                $assign_stmt->close();
            }
        }
        $messageType = "success";
    } else {
        $message = "❌ Error submitting service request. Please try again or contact support.";
        $messageType = "error";
    }
    $booking_stmt->close();
}

// Service categories with icons and descriptions
$service_categories = [
    'Air Conditioning Repair' => ['icon' => '❄️', 'desc' => 'AC cleaning, freon refill, compressor repair'],
    'Refrigerator Repair' => ['icon' => '🧊', 'desc' => 'Cooling issues, defrosting, compressor problems'],
    'Washing Machine Repair' => ['icon' => '🧺', 'desc' => 'Not spinning, water leaks, motor issues'],
    'Television Repair' => ['icon' => '📺', 'desc' => 'Screen problems, no display, audio issues'],
    'Microwave Repair' => ['icon' => '🔥', 'desc' => 'Not heating, turntable, door problems'],
    'Electric Fan Repair' => ['icon' => '🌪️', 'desc' => 'Motor repair, blade replacement, speed control'],
    'Rice Cooker Repair' => ['icon' => '🍚', 'desc' => 'Not cooking, overheating, switch problems'],
    'Laptop Repair' => ['icon' => '💻', 'desc' => 'Screen, keyboard, battery, performance issues'],
    'Mobile Phone Repair' => ['icon' => '📱', 'desc' => 'Screen crack, battery, charging port'],
    'Water Heater Repair' => ['icon' => '🚿', 'desc' => 'Not heating, leaks, thermostat issues'],
    'Oven Repair' => ['icon' => '🔥', 'desc' => 'Temperature control, door, heating elements'],
    'Dishwasher Repair' => ['icon' => '🍽️', 'desc' => 'Not draining, not cleaning, pump issues'],
    'Other Electronics' => ['icon' => '⚡', 'desc' => 'Any other electronic appliance repair']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - PinoyFix</title>
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
                            <span>Request Service</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search service types..." class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN']); ?></h3>
                            <p><?php echo htmlspecialchars($client_badge); ?></p>
                            <?php if ($subscription_expiry_text): ?>
                                <span style="display: inline-block; margin-top: 4px; padding: 4px 10px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); color: #047857; font-size: 0.8rem; font-weight: 600;">Valid until <?php echo htmlspecialchars($subscription_expiry_text); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="header-actions">
                            <a href="client_dashboard.php" class="btn-secondary">← Dashboard</a>
                            <a href="../logout.php" class="btn-logout">🚪 Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="container" style="margin-top: 2rem;">
            <div class="alert alert-<?php echo $messageType; ?> fade-in">
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <div class="container">
            <!-- Hero Section -->
            <section class="dashboard-hero" style="background: linear-gradient(135deg, #0038A8 0%, #1e40af 50%, #3b82f6 100%);">
                <div class="hero-content">
                    <div class="welcome-text">
                        <h1>Request Expert Repair Service 🔧</h1>
                        <p>Connect with verified, top-rated technicians in your area. Get professional repairs with warranty and transparent pricing.</p>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Verified Technicians</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number">4.9</div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Support Available</div>
                        </div>
                    </div>
                </div>
                
                <!-- Service Categories Preview -->
                <div class="status-cards">
                    <div class="status-card active">
                        <div class="card-icon">❄️</div>
                        <div class="card-content">
                            <h3>AC Repair</h3>
                            <p>Most Popular</p>
                            <span class="card-trend">₱300 - ₱800</span>
                        </div>
                    </div>
                    
                    <div class="status-card completed">
                        <div class="card-icon">🧺</div>
                        <div class="card-content">
                            <h3>Appliance Fix</h3>
                            <p>Quick Service</p>
                            <span class="card-trend">₱250 - ₱600</span>
                        </div>
                    </div>
                    
                    <div class="status-card appliances">
                        <div class="card-icon">📺</div>
                        <div class="card-content">
                            <h3>Electronics</h3>
                            <p>Expert Repair</p>
                            <span class="card-trend">₱200 - ₱1000</span>
                        </div>
                    </div>

                    <div class="status-card rating">
                        <div class="card-icon">⚡</div>
                        <div class="card-content">
                            <h3>Emergency</h3>
                            <p>Same Day</p>
                            <span class="card-trend">+₱200 fee</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Service Request Form -->
            <section class="marketplace-actions">
                <h2 class="section-title">
                    <span class="title-icon">🛠️</span>
                    Service Request Form
                </h2>
                
                <form method="POST" class="request-form" id="serviceRequestForm">
                    <div class="action-marketplace" style="display: block;">
                        <!-- Service Type Selection -->
                        <div class="action-item featured" style="grid-column: 1/-1; margin-bottom: 2rem;">
                            <div class="action-badge">Step 1</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>🔧 What needs to be repaired?</h3>
                                <p>Select the appliance or device that requires repair service</p>
                                
                                <div style="margin: 1.5rem 0;">
                                    <select id="service_type" name="service_type" required style="width: 100%; padding: 16px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                        <option value="">Choose your appliance or service type</option>
                                        <?php foreach ($service_categories as $service => $details): ?>
                                        <option value="<?php echo htmlspecialchars($service); ?>" 
                                                data-icon="<?php echo $details['icon']; ?>" 
                                                data-desc="<?php echo $details['desc']; ?>">
                                            <?php echo $details['icon'] . ' ' . $service; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div id="service-description" class="service-description" style="display: none; margin-top: 1rem; padding: 1rem; background: rgba(0, 56, 168, 0.1); border-radius: 12px; border-left: 4px solid #0038A8;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Selection -->
                        <div class="action-item" style="grid-column: 1/-1; margin-bottom: 2rem;">
                            <div class="action-badge">Step 2</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>📅 Preferred Schedule</h3>
                                <p>When would you like the technician to visit?</p>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0;">
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Preferred Date & Time *</label>
                                        <input type="datetime-local" id="apt_date" name="apt_date" required 
                                               min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                                               style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                        <small style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 0.5rem;">📍 Available: Mon-Sat 8AM-6PM, Sun 9AM-5PM</small>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Service Priority</label>
                                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.3s ease;">
                                                <input type="radio" name="priority" value="normal" checked style="width: 20px; height: 20px;">
                                                <div>
                                                    <strong>Normal Service</strong>
                                                    <br><small style="color: #64748b;">Within 24-48 hours</small>
                                                </div>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.3s ease;">
                                                <input type="radio" name="priority" value="urgent" style="width: 20px; height: 20px;">
                                                <div>
                                                    <strong>Urgent Service (+₱200)</strong>
                                                    <br><small style="color: #64748b;">Within 4-8 hours</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Problem Description -->
                        <div class="action-item" style="grid-column: 1/-1; margin-bottom: 2rem;">
                            <div class="action-badge">Step 3</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>📝 Problem Description</h3>
                                <p>Help us understand the issue better for faster service</p>
                                
                                <div style="margin: 1.5rem 0;">
                                    <textarea id="description" name="description" rows="5" 
                                              placeholder="Describe the problem in detail:&#10;• What symptoms are you experiencing?&#10;• When did the problem start?&#10;• Any error messages or unusual sounds?&#10;• What have you tried so far?&#10;&#10;The more details you provide, the better we can help you!"
                                              style="width: 100%; padding: 16px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; font-family: inherit; background: #f8fafc; resize: vertical; line-height: 1.6;"></textarea>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                        <small style="color: #64748b;">💡 Detailed descriptions help technicians prepare the right tools and parts</small>
                                        <div class="character-count" style="color: #94a3b8; font-size: 0.8rem;">
                                            <span id="char-count">0</span>/500 characters
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="action-btn" id="submitBtn" style="min-width: 300px; padding: 18px 36px; font-size: 1.2rem;">
                            🚀 Submit Service Request
                        </button>
                    </div>
                </form>
            </section>

            <!-- Service Information Cards -->
            <section class="insights-section">
                <h2 class="section-title">
                    <span class="title-icon">ℹ️</span>
                    Service Information
                </h2>
                
                <div class="insights-grid">
                    <div class="insight-card maintenance">
                        <div class="insight-icon">🌏</div>
                        <h4>Service Coverage</h4>
                        <p><strong>Metro Manila:</strong> Quezon City, Manila, Makati, BGC, Ortigas<br>
                        <strong>Extended Areas:</strong> Rizal, Cavite, Laguna, Bulacan</p>
                        <small style="color: #64748b;">📍 More cities coming soon!</small>
                    </div>
                    
                    <div class="insight-card communication">
                        <div class="insight-icon">⏰</div>
                        <h4>Response Time</h4>
                        <p><strong>Normal Service:</strong> 24-48 hours<br>
                        <strong>Urgent Service:</strong> 4-8 hours<br>
                        <strong>Emergency:</strong> Same day</p>
                        <small style="color: #64748b;">📞 Call (02) 8123-4567 for emergencies</small>
                    </div>
                    
                    <div class="insight-card feedback">
                        <div class="insight-icon">💰</div>
                        <h4>Transparent Pricing</h4>
                        <p><strong>Service Call:</strong> ₱200-300<br>
                        <strong>Diagnostic:</strong> FREE with repair<br>
                        <strong>Parts:</strong> At cost + 10% markup</p>
                        <small style="color: #64748b;">💳 Cash, GCash, PayMaya accepted</small>
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
                    <h5>Service Guarantee</h5>
                    <p>🛡️ 30-day workmanship warranty</p>
                    <p>⭐ 100% satisfaction guaranteed</p>
                    <p>🔒 All technicians verified & insured</p>
                </div>
                <div class="footer-section">
                    <h5>Contact Support</h5>
                    <p>📞 (02) 8123-4567</p>
                    <p>📧 support@pinoyfix.com</p>
                    <p>💬 24/7 Live Chat Available</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines</p>
            </div>
        </div>
    </footer>

    <style>
        .btn-secondary, .btn-logout {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-secondary {
            background: rgba(0, 56, 168, 0.1);
            color: #0038A8;
            border: 2px solid rgba(0, 56, 168, 0.2);
        }

        .btn-secondary:hover {
            background: #0038A8;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 56, 168, 0.3);
        }

        .btn-logout {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 2px solid rgba(220, 38, 38, 0.2);
        }

        .btn-logout:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .alert {
            padding: 1.5rem 2rem;
            border-radius: 16px;
            font-weight: 600;
            margin-bottom: 2rem;
            border: 2px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .service-description {
            font-style: italic;
            color: #0038A8;
            font-weight: 500;
        }

        .character-count.warning {
            color: #f59e0b !important;
        }

        .character-count.error {
            color: #ef4444 !important;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Priority radio button styling */
        input[type="radio"]:checked + div {
            color: #0038A8;
        }
        
        label:has(input[type="radio"]:checked) {
            border-color: #0038A8 !important;
            background: rgba(0, 56, 168, 0.05) !important;
        }
        
        label:has(input[type="radio"]) {
            transition: all 0.3s ease;
        }
        
        label:has(input[type="radio"]):hover {
            border-color: #0038A8 !important;
            background: rgba(0, 56, 168, 0.05) !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelect = document.getElementById('service_type');
            const serviceDesc = document.getElementById('service-description');
            const descTextarea = document.getElementById('description');
            const charCount = document.getElementById('char-count');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('serviceRequestForm');

            // Show service description
            serviceSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const description = selectedOption.getAttribute('data-desc');
                
                if (description && this.value) {
                    serviceDesc.innerHTML = `💡 <strong>Service includes:</strong> ${description}`;
                    serviceDesc.style.display = 'block';
                    serviceDesc.classList.add('fade-in');
                } else {
                    serviceDesc.style.display = 'none';
                }
            });

            // Character counter
            descTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count;
                
                const counter = document.querySelector('.character-count');
                counter.classList.remove('warning', 'error');
                
                if (count > 400) {
                    counter.classList.add('warning');
                }
                if (count > 500) {
                    counter.classList.add('error');
                    this.value = this.value.substring(0, 500);
                    charCount.textContent = 500;
                }
            });

            // Enhanced form validation
            form.addEventListener('submit', function(e) {
                const serviceType = document.getElementById('service_type').value;
                const aptDate = document.getElementById('apt_date').value;
                
                if (!serviceType) {
                    e.preventDefault();
                    showNotification('Please select a service type', 'error');
                    serviceSelect.focus();
                    return;
                }
                
                if (!aptDate) {
                    e.preventDefault();
                    showNotification('Please select your preferred date and time', 'error');
                    document.getElementById('apt_date').focus();
                    return;
                }
                
                // Check if appointment is at least 1 hour from now
                const selectedDate = new Date(aptDate);
                const minDate = new Date(Date.now() + 60 * 60 * 1000);
                
                if (selectedDate < minDate) {
                    e.preventDefault();
                    showNotification('Please select a date and time at least 1 hour from now', 'error');
                    document.getElementById('apt_date').focus();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Submitting Request...';
                submitBtn.style.background = '#94a3b8';
                submitBtn.style.cursor = 'not-allowed';
            });

            // Auto-hide alerts
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 6000);

            // Animate form sections
            const actionItems = document.querySelectorAll('.action-item');
            actionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                    item.style.transition = 'all 0.6s ease';
                }, index * 200);
            });

            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 16px 24px;
                    border-radius: 12px;
                    color: white;
                    font-weight: 600;
                    z-index: 9999;
                    max-width: 400px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                    transform: translateX(400px);
                    transition: all 0.3s ease;
                    background: ${type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #3b82f6, #2563eb)'};
                `;
                
                notification.textContent = message;
                document.body.appendChild(notification);
                
                setTimeout(() => notification.style.transform = 'translateX(0)', 100);
                setTimeout(() => {
                    notification.style.transform = 'translateX(400px)';
                    setTimeout(() => notification.remove(), 300);
                }, 4000);

                notification.addEventListener('click', () => {
                    notification.style.transform = 'translateX(400px)';
                    setTimeout(() => notification.remove(), 300);
                });
            }
        });
    </script>
</body>
</html>