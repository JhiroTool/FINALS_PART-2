<?php
session_start();

// Enhanced marketplace data for technicians (like products in Lazada/Shopee)
$featured_fixers = [
    [
        'id' => 1,
        'name' => 'Mark Santos',
        'specialty' => 'Aircon & Refrigeration Specialist',
        'rating' => 4.9,
        'reviews' => 324,
        'location' => 'Quezon City',
        'starting_price' => 299,
        'response_time' => '15 mins',
        'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=300&h=300&fit=crop&crop=face',
        'status' => 'Available',
        'verified' => true,
        'services' => [
            ['name' => 'Aircon Cleaning', 'price' => 299],
            ['name' => 'Freon Refill', 'price' => 450],
            ['name' => 'Aircon Installation', 'price' => 800],
            ['name' => 'Refrigerator Repair', 'price' => 350]
        ],
        'badges' => ['Top Rated', 'Quick Response', 'Verified'],
        'completed_jobs' => 456,
        'years_experience' => 8,
        'satisfaction_rate' => 98
    ],
    [
        'id' => 2,
        'name' => 'Joy Mendez',
        'specialty' => 'Home Appliances Expert',
        'rating' => 4.8,
        'reviews' => 189,
        'location' => 'Makati',
        'starting_price' => 250,
        'response_time' => '30 mins',
        'image' => 'https://images.unsplash.com/photo-1494790108755-2616c27955d5?w=300&h=300&fit=crop&crop=face',
        'status' => 'Available',
        'verified' => true,
        'services' => [
            ['name' => 'Washing Machine Repair', 'price' => 350],
            ['name' => 'Dryer Service', 'price' => 280],
            ['name' => 'Microwave Repair', 'price' => 250],
            ['name' => 'Rice Cooker Fix', 'price' => 200]
        ],
        'badges' => ['Female Technician', 'Same Day Service', 'Verified'],
        'completed_jobs' => 298,
        'years_experience' => 5,
        'satisfaction_rate' => 96
    ],
    [
        'id' => 3,
        'name' => 'Roberto Cruz',
        'specialty' => 'Electronics & Gadgets Pro',
        'rating' => 4.7,
        'reviews' => 445,
        'location' => 'Cebu City',
        'starting_price' => 199,
        'response_time' => '1 hour',
        'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=300&h=300&fit=crop&crop=face',
        'status' => 'Busy',
        'verified' => true,
        'services' => [
            ['name' => 'TV Screen Repair', 'price' => 350],
            ['name' => 'Phone Screen Fix', 'price' => 199],
            ['name' => 'Laptop Repair', 'price' => 450],
            ['name' => 'Sound System Fix', 'price' => 280]
        ],
        'badges' => ['Electronics Expert', 'Warranty Included'],
        'completed_jobs' => 612,
        'years_experience' => 12,
        'satisfaction_rate' => 94
    ],
    [
        'id' => 4,
        'name' => 'Linda Garcia',
        'specialty' => 'Electrical & Plumbing',
        'rating' => 4.9,
        'reviews' => 276,
        'location' => 'Davao',
        'starting_price' => 350,
        'response_time' => '20 mins',
        'image' => 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?w=300&h=300&fit=crop&crop=face',
        'status' => 'Available',
        'verified' => true,
        'services' => [
            ['name' => 'Electrical Wiring', 'price' => 500],
            ['name' => 'Socket Installation', 'price' => 350],
            ['name' => 'Plumbing Repair', 'price' => 400],
            ['name' => 'Pipe Installation', 'price' => 600]
        ],
        'badges' => ['Emergency Service', 'Licensed', 'Verified'],
        'completed_jobs' => 387,
        'years_experience' => 10,
        'satisfaction_rate' => 99
    ],
    [
        'id' => 5,
        'name' => 'Carlos Reyes',
        'specialty' => 'Automotive & Motorcycle',
        'rating' => 4.6,
        'reviews' => 156,
        'location' => 'Pasig',
        'starting_price' => 300,
        'response_time' => '45 mins',
        'image' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=300&h=300&fit=crop&crop=face',
        'status' => 'Available',
        'verified' => true,
        'services' => [
            ['name' => 'Motorcycle Tune-up', 'price' => 500],
            ['name' => 'Car Battery Replace', 'price' => 300],
            ['name' => 'Oil Change', 'price' => 250],
            ['name' => 'Tire Repair', 'price' => 150]
        ],
        'badges' => ['Mobile Service', 'Weekend Available'],
        'completed_jobs' => 234,
        'years_experience' => 7,
        'satisfaction_rate' => 92
    ],
    [
        'id' => 6,
        'name' => 'Anna Santos',
        'specialty' => 'Beauty Equipment & Salon',
        'rating' => 4.8,
        'reviews' => 89,
        'location' => 'Manila',
        'starting_price' => 400,
        'response_time' => '25 mins',
        'image' => 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?w=300&h=300&fit=crop&crop=face',
        'status' => 'Available',
        'verified' => true,
        'services' => [
            ['name' => 'Hair Dryer Repair', 'price' => 250],
            ['name' => 'Salon Chair Fix', 'price' => 400],
            ['name' => 'UV Light Repair', 'price' => 350],
            ['name' => 'Sterilizer Service', 'price' => 300]
        ],
        'badges' => ['Salon Specialist', 'Female Technician', 'Verified'],
        'completed_jobs' => 145,
        'years_experience' => 6,
        'satisfaction_rate' => 97
    ]
];

$categories = [
    ['name' => 'Aircon & Refrigeration', 'icon' => '❄️', 'count' => 45],
    ['name' => 'Home Appliances', 'icon' => '🏠', 'count' => 38],
    ['name' => 'Electronics & Gadgets', 'icon' => '📱', 'count' => 52],
    ['name' => 'Electrical & Plumbing', 'icon' => '⚡', 'count' => 29],
    ['name' => 'Automotive', 'icon' => '🚗', 'count' => 23],
    ['name' => 'Beauty & Salon', 'icon' => '💄', 'count' => 16]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Find Local Fixers - PinoyFix Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/marketplace-view.css">
</head>
<body>
    <!-- Header -->
    <header class="marketplace-header">
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="brand">
                    <img src="images/pinoyfix.png" alt="PinoyFix" class="logo">
                    <div class="brand-text">
                        <h1>PinoyFix</h1>
                        <p>Marketplace</p>
                    </div>
                </a>
                
                <!-- Search Bar -->
                <div class="search-container">
                    <input type="text" placeholder="Search for services, technicians, or repairs..." class="search-input" onclick="showLoginModal('use search')">
                    <button class="search-btn" onclick="showLoginModal('search')">🔍</button>
                </div>
                
                <div class="header-actions">
                    <span class="guest-badge">👀 Guest Mode</span>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-ghost">Login</a>
                        <a href="register.php" class="btn btn-primary">Sign Up</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Marketplace Hero -->
            <section class="marketplace-hero">
                <div class="hero-content">
                    <h1>Find Trusted Local Fixers</h1>
                    <p>Browse verified technicians, compare ratings and prices, book instantly</p>
                    
                    <div class="guest-notice">
                        <strong>🛍️ Marketplace Preview:</strong> 
                        Browse all services and fixers. <a href="register.php">Register</a> to book instantly or <a href="login.php">login</a> to access full features.
                    </div>
                </div>
            </section>

            <!-- Categories -->
            <section class="categories-section">
                <h2 class="section-title">🛠️ Browse by Category</h2>
                <div class="categories-grid">
                    <?php foreach ($categories as $category): ?>
                    <div class="category-card" onclick="showLoginModal('filter by category')">
                        <div class="category-icon"><?php echo $category['icon']; ?></div>
                        <div class="category-info">
                            <h3><?php echo $category['name']; ?></h3>
                            <p><?php echo $category['count']; ?> fixers available</p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Filters & Sort -->
            <section class="filters-section">
                <div class="filters-header">
                    <h2>Available Fixers</h2>
                    <div class="filter-controls">
                        <select class="filter-select" onchange="showLoginModal('filter results')">
                            <option>All Locations</option>
                            <option>Quezon City</option>
                            <option>Makati</option>
                            <option>Manila</option>
                            <option>Cebu</option>
                        </select>
                        <select class="filter-select" onchange="showLoginModal('sort results')">
                            <option>Sort by Rating</option>
                            <option>Price: Low to High</option>
                            <option>Price: High to Low</option>
                            <option>Most Reviews</option>
                            <option>Response Time</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Marketplace Grid -->
            <section class="marketplace-grid">
                <?php foreach ($featured_fixers as $fixer): ?>
                <div class="product-card">
                    <!-- Product Image -->
                    <div class="product-image">
                        <img src="<?php echo $fixer['image']; ?>" alt="<?php echo $fixer['name']; ?>">
                        <?php if ($fixer['verified']): ?>
                        <div class="verified-badge">✅</div>
                        <?php endif; ?>
                        <div class="status-indicator <?php echo strtolower($fixer['status']); ?>">
                            <?php echo $fixer['status']; ?>
                        </div>
                    </div>

                    <!-- Product Info -->
                    <div class="product-info">
                        <h3 class="product-title"><?php echo $fixer['name']; ?></h3>
                        <p class="product-specialty"><?php echo $fixer['specialty']; ?></p>
                        
                        <!-- Rating -->
                        <div class="rating-section">
                            <div class="stars-rating">
                                <span class="stars">⭐⭐⭐⭐⭐</span>
                                <span class="rating-value"><?php echo $fixer['rating']; ?></span>
                                <span class="review-count">(<?php echo $fixer['reviews']; ?>)</span>
                            </div>
                            <div class="location">📍 <?php echo $fixer['location']; ?></div>
                        </div>

                        <!-- Services Offered -->
                        <div class="services-preview">
                            <h4>Services:</h4>
                            <div class="services-list">
                                <?php foreach (array_slice($fixer['services'], 0, 2) as $service): ?>
                                <div class="service-item">
                                    <span class="service-name"><?php echo $service['name']; ?></span>
                                    <span class="service-price">₱<?php echo $service['price']; ?></span>
                                </div>
                                <?php endforeach; ?>
                                <?php if (count($fixer['services']) > 2): ?>
                                <div class="more-services">+<?php echo count($fixer['services']) - 2; ?> more services</div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Badges -->
                        <div class="badges">
                            <?php foreach ($fixer['badges'] as $badge): ?>
                            <span class="badge"><?php echo $badge; ?></span>
                            <?php endforeach; ?>
                        </div>

                        <!-- Stats -->
                        <div class="stats-row">
                            <div class="stat">
                                <span class="stat-value"><?php echo $fixer['completed_jobs']; ?></span>
                                <span class="stat-label">Jobs Done</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo $fixer['response_time']; ?></span>
                                <span class="stat-label">Response</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo $fixer['satisfaction_rate']; ?>%</span>
                                <span class="stat-label">Satisfaction</span>
                            </div>
                        </div>

                        <!-- Price & Action -->
                        <div class="product-footer">
                            <div class="price-section">
                                <span class="starting-label">Starting from</span>
                                <span class="price">₱<?php echo $fixer['starting_price']; ?></span>
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-primary" onclick="showLoginModal('book <?php echo $fixer['name']; ?>')" 
                                        <?php echo $fixer['status'] !== 'Available' ? 'disabled' : ''; ?>>
                                    <?php echo $fixer['status'] === 'Available' ? '📞 Book Now' : '⏰ Busy'; ?>
                                </button>
                                <button class="btn btn-ghost btn-small" onclick="showLoginModal('view <?php echo $fixer['name']; ?> profile')">
                                    👁️ View
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </section>

            <!-- Marketplace Features -->
            <section class="features-section">
                <h2>Why Choose PinoyFix Marketplace?</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🛡️</div>
                        <h3>Verified Fixers</h3>
                        <p>All technicians are background-checked and skill-verified</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⚡</div>
                        <h3>Quick Response</h3>
                        <p>Get connected with available fixers in minutes</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">💰</div>
                        <h3>Transparent Pricing</h3>
                        <p>See upfront pricing with no hidden fees</p>
                    </div>
                    <div class="feature-card">
                        <div class="feature-icon">⭐</div>
                        <h3>Rated & Reviewed</h3>
                        <p>Real reviews from verified customers</p>
                    </div>
                </div>
            </section>

            <!-- CTA Section -->
            <section class="cta-marketplace">
                <div class="cta-content">
                    <h2>Ready to Get Your Repairs Done?</h2>
                    <p>Join thousands of satisfied customers who found reliable fixers on PinoyFix</p>
                    <div class="cta-buttons">
                        <a href="register.php" class="btn btn-primary btn-large">🚀 Sign Up & Book Now</a>
                        <a href="register.php" class="btn btn-ghost btn-large">🔧 Become a Verified Fixer</a>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Enhanced Login Modal -->
    <div class="login-overlay" id="loginOverlay">
        <div class="login-modal">
            <div class="modal-icon">🛍️</div>
            <h3 class="modal-title">Join PinoyFix Marketplace</h3>
            <p class="modal-text" id="modalText">Create an account to book services and access all marketplace features!</p>
            <div class="modal-buttons">
                <button class="btn btn-ghost" onclick="hideLoginModal()">Browse More</button>
                <a href="register.php" class="btn btn-primary">Sign Up Now</a>
            </div>
        </div>
    </div>

    <!-- Same JavaScript as before -->
    <script>
        function showLoginModal(action) {
            const modal = document.getElementById('loginOverlay');
            const text = document.getElementById('modalText');
            
            text.textContent = `Sign up to ${action} and access the full PinoyFix marketplace!`;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function hideLoginModal() {
            const modal = document.getElementById('loginOverlay');
            modal.style.animation = 'fadeOut 0.3s ease-out';
            
            setTimeout(() => {
                modal.style.display = 'none';
                modal.style.animation = '';
                document.body.style.overflow = 'auto';
            }, 300);
        }

        document.getElementById('loginOverlay').addEventListener('click', function(e) {
            if (e.target === this) hideLoginModal();
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') hideLoginModal();
        });

        // Scroll animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.product-card, .category-card, .feature-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
                observer.observe(card);
            });
        });

        const styleSheet = document.createElement('style');
        styleSheet.textContent = `@keyframes fadeOut { from { opacity: 1; } to { opacity: 0; } }`;
        document.head.appendChild(styleSheet);
    </script>
</body>
</html>