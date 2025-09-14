<?php
// Add error reporting at the very top
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Check if connection file exists
if (!file_exists('connection.php')) {
    die('Error: connection.php file not found. Please check file path.');
}

include 'connection.php';

// Check if connection is successful
if (!isset($conn) || $conn->connect_error) {
    die('Database connection failed: ' . (isset($conn) ? $conn->connect_error : 'Connection object not found'));
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_type = $_SESSION['user_type'] ?? null;
$user_name = '';

if ($is_logged_in && $user_type === 'client') {
    try {
        $stmt = $conn->prepare("SELECT Client_FN FROM client WHERE Client_ID = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("i", $_SESSION['user_id']);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $result = $stmt->get_result();
        $client = $result->fetch_assoc();
        $user_name = $client['Client_FN'] ?? '';
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error getting client info: " . $e->getMessage());
        $user_name = 'User';
    }
}

// Get featured technicians with enhanced data
$featured_techs = null;
try {
    $techs_stmt = $conn->prepare("
        SELECT t.Technician_ID, t.Technician_FN, t.Technician_LN, t.Technician_Phone, 
               t.Specialization, t.Status, t.Technician_Email,
               (SELECT COUNT(*) FROM booking WHERE Technician_ID = t.Technician_ID AND Status = 'completed') as completed_jobs,
               (SELECT AVG(CAST(Rating AS DECIMAL(3,2))) FROM booking WHERE Technician_ID = t.Technician_ID AND Rating > 0) as avg_rating,
               (SELECT COUNT(*) FROM booking WHERE Technician_ID = t.Technician_ID AND Rating > 0) as total_reviews
        FROM technician t 
        WHERE t.Status = 'available' 
        ORDER BY completed_jobs DESC, avg_rating DESC
        LIMIT 20
    ");
    
    if ($techs_stmt) {
        $techs_stmt->execute();
        $featured_techs = $techs_stmt->get_result();
        $techs_stmt->close();
    }
} catch (Exception $e) {
    error_log("Error getting featured technicians: " . $e->getMessage());
    $featured_techs = null;
}

// Service categories with counts
$service_categories = [
    'Air Conditioning Repair' => ['icon' => '❄️', 'price' => '₱300 - ₱800', 'desc' => 'AC cleaning, freon refill, compressor repair', 'popular' => true, 'count' => 45],
    'Refrigerator Repair' => ['icon' => '🧊', 'price' => '₱250 - ₱600', 'desc' => 'Cooling issues, defrosting, compressor problems', 'popular' => true, 'count' => 38],
    'Washing Machine Repair' => ['icon' => '🧺', 'price' => '₱200 - ₱500', 'desc' => 'Not spinning, water leaks, motor issues', 'popular' => true, 'count' => 32],
    'Television Repair' => ['icon' => '📺', 'price' => '₱300 - ₱1000', 'desc' => 'Screen problems, no display, audio issues', 'popular' => false, 'count' => 28],
    'Microwave Repair' => ['icon' => '🔥', 'price' => '₱150 - ₱400', 'desc' => 'Not heating, turntable, door problems', 'popular' => false, 'count' => 22],
    'Electric Fan Repair' => ['icon' => '🌪️', 'price' => '₱100 - ₱300', 'desc' => 'Motor repair, blade replacement, speed control', 'popular' => false, 'count' => 18],
    'Laptop Repair' => ['icon' => '💻', 'price' => '₱500 - ₱2000', 'desc' => 'Screen, keyboard, battery, performance issues', 'popular' => true, 'count' => 35],
    'Mobile Phone Repair' => ['icon' => '📱', 'price' => '₱200 - ₱800', 'desc' => 'Screen crack, battery, charging port', 'popular' => true, 'count' => 42]
];

// Helper function to generate random but consistent data
function getTechnicianServices($specialization) {
    $services = [
        'Air Conditioner' => [
            ['name' => 'AC Cleaning', 'price' => 299],
            ['name' => 'Freon Refill', 'price' => 450],
            ['name' => 'Compressor Repair', 'price' => 800],
            ['name' => 'AC Installation', 'price' => 600]
        ],
        'Refrigerator' => [
            ['name' => 'Cooling System Fix', 'price' => 350],
            ['name' => 'Defrost Repair', 'price' => 280],
            ['name' => 'Door Seal Replace', 'price' => 200],
            ['name' => 'Thermostat Fix', 'price' => 300]
        ],
        'General' => [
            ['name' => 'Diagnostic Check', 'price' => 150],
            ['name' => 'Basic Repair', 'price' => 250],
            ['name' => 'Part Replacement', 'price' => 300],
            ['name' => 'Maintenance', 'price' => 200]
        ]
    ];
    
    foreach ($services as $key => $serviceList) {
        if (stripos($specialization, $key) !== false) {
            return $serviceList;
        }
    }
    
    return $services['General'];
}

function getTechnicianBadges($rating, $completed_jobs) {
    $badges = [];
    if ($rating >= 4.8) $badges[] = 'Top Rated';
    if ($completed_jobs > 100) $badges[] = 'Experienced';
    if ($completed_jobs > 50) $badges[] = 'Verified';
    $badges[] = 'Quick Response';
    return array_slice($badges, 0, 3); // Max 3 badges
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Marketplace - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/marketplace.css">
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="brand">
                    <div class="logo-container">
                        <img src="images/pinoyfix.png" alt="PinoyFix" class="logo-image">
                    </div>
                    <div class="brand-text">
                        <h1>PinoyFix</h1>
                        <span>Service Marketplace</span>
                    </div>
                </a>
                
                <div class="search-container">
                    <input type="text" placeholder="Search for services, technicians, or locations..." class="search-input" id="marketplaceSearch">
                </div>
                
                <div class="auth-buttons">
                    <?php if ($is_logged_in): ?>
                        <?php if ($user_type === 'client'): ?>
                            <span style="color: #0038A8; font-weight: 600;">Hello, <?php echo htmlspecialchars($user_name); ?>!</span>
                            <a href="client/client_dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
                            <a href="logout.php" class="btn btn-secondary">🚪 Logout</a>
                        <?php else: ?>
                            <a href="technician/technician_dashboard.php" class="btn btn-secondary">📊 Dashboard</a>
                            <a href="logout.php" class="btn btn-secondary">🚪 Logout</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-secondary">🔐 Login</a>
                        <a href="register.php" class="btn btn-primary">👤 Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <div style="min-height: 80vh;">
        <div class="container">
            <!-- Hero Section -->
            <section class="hero">
                <h1>Find Expert Repair Services 🛠️</h1>
                <p>Connect with verified, top-rated technicians across the Philippines. Get your appliances fixed by professionals with transparent pricing and guaranteed quality.</p>
                
                <div class="hero-actions">
                    <?php if ($is_logged_in && $user_type === 'client'): ?>
                        <a href="client/request_service.php" class="btn btn-primary btn-large">
                            <span>🔧</span>
                            <span>Request Service Now</span>
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-large">
                            <span>👤</span>
                            <span>Sign Up to Book</span>
                        </a>
                    <?php endif; ?>
                    <a href="#services" class="btn btn-secondary btn-large">
                        <span>👀</span>
                        <span>Browse Services</span>
                    </a>
                </div>
                
                <div class="hero-stats">
                    <div class="stat-card">
                        <div class="icon">👨‍🔧</div>
                        <div class="value">500+</div>
                        <div class="label">Verified Technicians</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">⭐</div>
                        <div class="value">4.9</div>
                        <div class="label">Average Rating</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">🔧</div>
                        <div class="value">50k+</div>
                        <div class="label">Repairs Completed</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">🛡️</div>
                        <div class="value">24/7</div>
                        <div class="label">Support Available</div>
                    </div>
                </div>
            </section>

            <!-- Service Categories -->
            <section class="section" id="services">
                <h2 class="section-title">🛍️ Browse by Service</h2>
                <p class="section-subtitle">Choose from our most popular repair categories</p>
                
                <div class="categories-grid">
                    <?php foreach ($service_categories as $service_name => $details): ?>
                    <div class="category-card">
                        <div class="category-icon"><?php echo $details['icon']; ?></div>
                        <h3><?php echo htmlspecialchars($service_name); ?></h3>
                        <p><?php echo $details['count']; ?> technicians available</p>
                        <div style="color: #059669; font-weight: 700; margin-top: 0.5rem;">
                            <?php echo $details['price']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Available Technicians -->
            <section class="section">
                <h2 class="section-title">⭐ Available Technicians</h2>
                <p class="section-subtitle">Meet our verified and top-rated repair experts</p>
                
                <?php if ($featured_techs && $featured_techs->num_rows > 0): ?>
                <div class="technicians-grid">
                    <?php while ($tech = $featured_techs->fetch_assoc()): 
                        $services = getTechnicianServices($tech['Specialization'] ?? '');
                        $badges = getTechnicianBadges($tech['avg_rating'] ?? 4.5, $tech['completed_jobs']);
                        $responseTime = rand(10, 60) . ' mins';
                    ?>
                    <div class="tech-card" data-tech="<?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?>">
                        <div class="tech-header">
                            <div class="tech-avatar">
                                <span><?php echo strtoupper(substr($tech['Technician_FN'], 0, 1)); ?></span>
                            </div>
                            <div class="tech-info">
                                <h3><?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?></h3>
                                <p class="tech-specialty"><?php echo htmlspecialchars($tech['Specialization'] ?? 'General Repair'); ?></p>
                                <div class="status-available">✅ Available Now</div>
                            </div>
                        </div>
                        
                        <div class="tech-rating">
                            <span class="stars">⭐⭐⭐⭐⭐</span>
                            <span class="rating-value"><?php echo number_format($tech['avg_rating'] ?? 4.5, 1); ?></span>
                            <span class="review-count">(<?php echo $tech['total_reviews'] ?? rand(10, 100); ?> reviews)</span>
                        </div>
                        
                        <div class="tech-services">
                            <h4>Services Offered:</h4>
                            <div class="services-list">
                                <?php foreach (array_slice($services, 0, 3) as $service): ?>
                                <div class="service-item">
                                    <span class="service-name"><?php echo $service['name']; ?></span>
                                    <span class="service-price">₱<?php echo $service['price']; ?></span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="tech-badges">
                            <?php foreach ($badges as $badge): ?>
                            <span class="badge"><?php echo $badge; ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="tech-stats">
                            <div class="tech-stat">
                                <span class="stat-value"><?php echo $tech['completed_jobs']; ?></span>
                                <span class="stat-label">Jobs Done</span>
                            </div>
                            <div class="tech-stat">
                                <span class="stat-value"><?php echo $responseTime; ?></span>
                                <span class="stat-label">Response Time</span>
                            </div>
                            <div class="tech-stat">
                                <span class="stat-value"><?php echo rand(90, 100); ?>%</span>
                                <span class="stat-label">Satisfaction</span>
                            </div>
                        </div>
                        
                        <div style="background: #f8fafc; padding: 1rem; border-radius: 12px; margin: 1rem 0;">
                            <div style="font-size: 0.9rem; color: #64748b; line-height: 1.4;">
                                📱 <?php echo htmlspecialchars($tech['Technician_Phone']); ?><br>
                                📍 Available in your area<br>
                                🔧 Specializes in <?php echo htmlspecialchars($tech['Specialization'] ?? 'General Repair'); ?>
                            </div>
                        </div>
                        
                        <div class="tech-actions">
                            <?php if ($is_logged_in && $user_type === 'client'): ?>
                                <a href="client/request_service.php?tech_id=<?php echo $tech['Technician_ID']; ?>" class="btn btn-primary">
                                    📞 Book Now
                                </a>
                                <a href="tel:<?php echo $tech['Technician_Phone']; ?>" class="btn btn-secondary btn-small">
                                    📱 Call
                                </a>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-primary" style="flex: 1;">
                                    🔐 Login to Book
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div style="text-align: center; padding: 4rem 2rem; background: white; border-radius: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.05);">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">👨‍🔧</div>
                    <h3 style="color: #1e293b; margin-bottom: 1rem;">No Technicians Available</h3>
                    <p style="color: #64748b; margin-bottom: 2rem;">We're currently onboarding more technicians to serve you better.</p>
                    <a href="register.php" class="btn btn-primary">Get Notified When Available</a>
                </div>
                <?php endif; ?>
            </section>

            <!-- Call to Action -->
            <section class="cta-section">
                <h2>Ready to Fix Your Appliances? 🔧</h2>
                <p>Join thousands of satisfied customers who trust PinoyFix for reliable, professional appliance repairs.</p>
                
                <div class="cta-buttons">
                    <?php if ($is_logged_in && $user_type === 'client'): ?>
                        <a href="client/request_service.php" class="btn btn-primary btn-large">
                            <span>🚀</span>
                            <span>Book Your First Service</span>
                        </a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-primary btn-large">
                            <span>👤</span>
                            <span>Sign Up Now</span>
                        </a>
                        <a href="login.php" class="btn btn-secondary btn-large">
                            <span>🔐</span>
                            <span>Already Have Account?</span>
                        </a>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>PinoyFix</h4>
                    <p>Your trusted repair marketplace since 2025</p>
                    <p style="color: #f59e0b; font-weight: 600; margin-top: 1rem;">🏆 #1 Appliance Repair Platform in the Philippines</p>
                </div>
                
                <div class="footer-section">
                    <h5>Popular Services</h5>
                    <a href="#services">❄️ AC Repair</a>
                    <a href="#services">🧊 Refrigerator Fix</a>
                    <a href="#services">🧺 Washing Machine</a>
                    <a href="#services">📺 TV Repair</a>
                </div>
                
                <div class="footer-section">
                    <h5>For Clients</h5>
                    <?php if ($is_logged_in && $user_type === 'client'): ?>
                        <a href="client/client_dashboard.php">📊 Dashboard</a>
                        <a href="client/request_service.php">🔧 Book Service</a>
                        <a href="client/my_bookings.php">📋 My Bookings</a>
                    <?php else: ?>
                        <a href="register.php">👤 Sign Up</a>
                        <a href="login.php">🔐 Login</a>
                    <?php endif; ?>
                </div>
                
                <div class="footer-section">
                    <h5>Support</h5>
                    <p>📞 (02) 8123-4567</p>
                    <p>📧 support@pinoyfix.com</p>
                    <p>💬 24/7 Live Chat</p>
                    <p style="color: #10b981; font-weight: 600;">🛡️ Service Guarantee</p>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines</p>
            </div>
        </div>
    </footer>

    <script>
        // Search functionality
        document.getElementById('marketplaceSearch').addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const techCards = document.querySelectorAll('[data-tech]');
            
            techCards.forEach(card => {
                const techName = card.getAttribute('data-tech').toLowerCase();
                const techText = card.textContent.toLowerCase();
                
                if (techName.includes(searchTerm) || techText.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });

        // Smooth scroll for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });

            // Animate cards on load
            const cards = document.querySelectorAll('.tech-card, .category-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                    card.style.transition = 'all 0.6s ease';
                }, index * 100);
            });
        });
    </script>
</body>
</html>