<?php
session_start();


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