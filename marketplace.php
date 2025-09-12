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
    <style>
        /* Modern Marketplace Styles */
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background: #f8fafc;
            color: #1e293b;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        /* Header */
        header {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }

        .brand img {
            height: 40px;
        }

        .brand h1 {
            margin: 0;
            color: #0038A8;
            font-size: 1.5rem;
        }

        .brand span {
            color: #64748b;
            font-size: 0.9rem;
        }

        .search-container {
            flex: 1;
            max-width: 500px;
        }

        .search-input {
            width: 100%;
            padding: 14px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            background: #f8fafc;
        }

        .search-input:focus {
            border-color: #0038A8;
            box-shadow: 0 0 0 4px rgba(0, 56, 168, 0.1);
            outline: none;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background: linear-gradient(135deg, #0038A8, #3b82f6);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 56, 168, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 56, 168, 0.4);
        }

        .btn-secondary {
            background: rgba(0, 56, 168, 0.1);
            color: #0038A8;
            border: 2px solid rgba(0, 56, 168, 0.2);
        }

        .btn-secondary:hover {
            background: #0038A8;
            color: white;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #0038A8, #3b82f6);
            color: white;
            border-radius: 20px;
            padding: 4rem 2rem;
            text-align: center;
            margin: 2rem 0;
        }

        .hero h1 {
            font-size: 3rem;
            font-weight: 800;
            margin: 0 0 1rem 0;
        }

        .hero p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 800px;
            margin: 0 auto 2rem auto;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin: 2rem 0;
        }

        .hero-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 3rem;
        }

        .stat-card {
            text-align: center;
        }

        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .stat-card .value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-card .label {
            opacity: 0.9;
        }

        /* Categories */
        .section {
            margin: 4rem 0;
        }

        .section-title {
            text-align: center;
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .section-subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 3rem;
            font-size: 1.1rem;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .category-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            border: 2px solid #f1f5f9;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .category-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-color: #0038A8;
        }

        .category-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .category-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .category-card p {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Technician Cards */
        .technicians-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }

        .tech-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 2px solid #f1f5f9;
            transition: all 0.3s ease;
        }

        .tech-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.12);
            border-color: #0038A8;
        }

        .tech-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .tech-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0038A8, #3b82f6);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(0, 56, 168, 0.3);
        }

        .tech-info h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 0.25rem 0;
        }

        .tech-specialty {
            color: #0038A8;
            font-weight: 600;
            margin: 0;
        }

        .tech-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .stars {
            color: #f59e0b;
        }

        .rating-value {
            font-weight: 700;
            color: #1e293b;
        }

        .review-count {
            color: #64748b;
            font-size: 0.9rem;
        }

        .tech-services {
            margin: 1.5rem 0;
        }

        .tech-services h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1e293b;
            margin: 0 0 1rem 0;
        }

        .services-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .service-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 8px;
        }

        .service-name {
            color: #374151;
            font-weight: 500;
        }

        .service-price {
            color: #059669;
            font-weight: 700;
        }

        .tech-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin: 1rem 0;
        }

        .badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .tech-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1.5rem 0;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 12px;
        }

        .tech-stat {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #64748b;
        }

        .tech-actions {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.9rem;
        }

        /* Status indicators */
        .status-available {
            color: #059669;
            font-weight: 600;
        }

        .status-busy {
            color: #dc2626;
            font-weight: 600;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 24px;
            padding: 4rem 2rem;
            text-align: center;
            margin: 4rem 0;
        }

        .cta-section h2 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 1rem;
        }

        .cta-section p {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 2rem;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn-large {
            padding: 20px 40px;
            font-size: 1.2rem;
        }

        /* Footer */
        footer {
            background: #1e293b;
            color: white;
            padding: 3rem 0 2rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h4 {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .footer-section h5 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #cbd5e1;
        }

        .footer-section p {
            color: #94a3b8;
            line-height: 1.6;
            margin: 0.5rem 0;
        }

        .footer-section a {
            color: #94a3b8;
            text-decoration: none;
            display: block;
            margin: 0.5rem 0;
        }

        .footer-section a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 2rem;
            text-align: center;
        }

        .footer-bottom p {
            color: #94a3b8;
            margin: 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 0 1rem;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-container {
                order: 1;
                max-width: 100%;
            }
            
            .hero h1 {
                font-size: 2rem;
            }
            
            .technicians-grid {
                grid-template-columns: 1fr;
            }
            
            .tech-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="brand">
                    <img src="images/pinoyfix.png" alt="PinoyFix" onerror="this.style.display='none';">
                    <div>
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