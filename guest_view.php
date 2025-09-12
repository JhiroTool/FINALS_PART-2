<?php
session_start();

// Simulated data for demo purposes (in production, this would come from database)
$featured_fixers = [
    [
        'id' => 1,
        'name' => 'Mark Santos',
        'specialty' => 'Aircon Repair',
        'rating' => 4.8,
        'reviews' => 127,
        'location' => 'Quezon City',
        'rate' => '₱500-800',
        'image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face',
        'status' => 'Available',
        'skills' => ['Aircon', 'Refrigerator', 'Electric Fan']
    ],
    [
        'id' => 2,
        'name' => 'Joy Reyes',
        'specialty' => 'Washing Machine',
        'rating' => 4.9,
        'reviews' => 89,
        'location' => 'Makati',
        'rate' => '₱400-600',
        'image' => 'https://images.unsplash.com/photo-1494790108755-2616c27955d5?w=150&h=150&fit=crop&crop=face',
        'status' => 'Available',
        'skills' => ['Washing Machine', 'Dryer', 'Dishwasher']
    ],
    [
        'id' => 3,
        'name' => 'Roberto Cruz',
        'specialty' => 'Electronics',
        'rating' => 4.7,
        'reviews' => 234,
        'location' => 'Cebu City',
        'rate' => '₱300-500',
        'image' => 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=150&h=150&fit=crop&crop=face',
        'status' => 'Busy',
        'skills' => ['TV', 'Radio', 'Sound System', 'Phone']
    ]
];

$recent_jobs = [
    [
        'id' => 1,
        'title' => 'Aircon not cooling properly',
        'location' => 'Quezon City',
        'posted' => '2 hours ago',
        'budget' => '₱800',
        'status' => 'Open',
        'description' => 'My split-type aircon is running but not cooling. Might need cleaning or freon.'
    ],
    [
        'id' => 2,
        'title' => 'Washing machine not spinning',
        'location' => 'Makati',
        'posted' => '4 hours ago',
        'budget' => '₱600',
        'status' => 'Open',
        'description' => 'Top load washer stops after wash cycle, won\'t spin to drain water.'
    ],
    [
        'id' => 3,
        'title' => 'TV screen flickering',
        'location' => 'Pasig',
        'posted' => '1 day ago',
        'budget' => '₱400',
        'status' => 'In Progress',
        'description' => '32-inch LED TV has intermittent flickering, especially on dark scenes.'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Services - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/guest-view.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="brand">
                    <img src="images/pinoyfix.png" alt="PinoyFix" class="logo">
                    <div class="brand-text">
                        <h1>PinoyFix</h1>
                        <p>Local repairs, Filipino-first</p>
                    </div>
                </a>
                
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="guest-badge">👀 Guest View</span>
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
            <div class="page-header">
                <h1 class="page-title">Browse PinoyFix Services</h1>
                <p class="page-subtitle">Discover trusted local fixers and available repair jobs</p>
                
                <div class="guest-notice">
                    <strong>🔒 Guest Mode:</strong> You can browse services but need to <a href="login.php" style="color: #0038A8; font-weight: 600;">login</a> or <a href="register.php" style="color: #0038A8; font-weight: 600;">register</a> to book services or apply for jobs.
                </div>
            </div>

            <!-- Featured Fixers Section -->
            <section class="section">
                <div class="section-header">
                    <h2 class="section-title">🌟 Top-Rated Fixers</h2>
                    <a href="#" class="btn btn-ghost" onclick="showLoginModal('view all fixers')">View All</a>
                </div>
                
                <div class="fixers-grid">
                    <?php foreach ($featured_fixers as $fixer): ?>
                    <div class="fixer-card">
                        <div class="fixer-header">
                            <img src="<?php echo $fixer['image']; ?>" alt="<?php echo $fixer['name']; ?>" class="fixer-avatar">
                            <div class="fixer-info">
                                <h3><?php echo $fixer['name']; ?></h3>
                                <p class="fixer-specialty"><?php echo $fixer['specialty']; ?></p>
                                <div class="fixer-rating">
                                    <span class="stars">⭐⭐⭐⭐⭐</span>
                                    <span class="rating-text"><?php echo $fixer['rating']; ?> (<?php echo $fixer['reviews']; ?> reviews)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="fixer-details">
                            <div class="detail-item">
                                📍 <?php echo $fixer['location']; ?>
                            </div>
                            <div class="detail-item">
                                💰 <?php echo $fixer['rate']; ?>
                            </div>
                            <div class="detail-item">
                                <span class="status-badge <?php echo $fixer['status'] === 'Available' ? 'status-available' : 'status-busy'; ?>">
                                    <?php echo $fixer['status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="skills">
                            <?php foreach ($fixer['skills'] as $skill): ?>
                            <span class="skill-tag"><?php echo $skill; ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="action-buttons">
                            <button class="btn btn-primary btn-small" onclick="showLoginModal('book this fixer')">
                                📞 Book Now
                            </button>
                            <button class="btn btn-ghost btn-small" onclick="showLoginModal('view profile')">
                                👤 View Profile
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Recent Jobs Section -->
            <section class="section">
                <div class="section-header">
                    <h2 class="section-title">💼 Recent Repair Jobs</h2>
                    <a href="#" class="btn btn-ghost" onclick="showLoginModal('post a job')">Post a Job</a>
                </div>
                
                <div class="jobs-grid">
                    <?php foreach ($recent_jobs as $job): ?>
                    <div class="job-card">
                        <div class="job-header">
                            <div>
                                <h3 class="job-title"><?php echo $job['title']; ?></h3>
                                <div class="job-meta">
                                    <span>📍 <?php echo $job['location']; ?></span>
                                    <span>⏰ <?php echo $job['posted']; ?></span>
                                    <span class="status-badge <?php echo $job['status'] === 'Open' ? 'status-available' : 'status-busy'; ?>">
                                        <?php echo $job['status']; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <p class="job-description"><?php echo $job['description']; ?></p>

                        <div class="job-footer">
                            <div class="job-budget">Budget: <?php echo $job['budget']; ?></div>
                            <button class="btn btn-primary btn-small" onclick="showLoginModal('apply for this job')">
                                ✋ Apply Now
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="section" style="text-align: center; background: white; padding: 3rem; border-radius: 16px; border: 2px solid #e2e8f0;">
                <h2 style="font-size: 2rem; font-weight: 700; margin-bottom: 1rem;">Ready to Get Started?</h2>
                <p style="color: #64748b; font-size: 1.1rem; margin-bottom: 2rem;">Join thousands of satisfied customers and skilled fixers in the PinoyFix community.</p>
                
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="register.php" class="btn btn-primary" style="padding: 1rem 2rem; font-size: 1rem;">
                        👥 Register as Customer
                    </a>
                    <a href="register.php" class="btn btn-ghost" style="padding: 1rem 2rem; font-size: 1rem;">
                        🔧 Become a Fixer
                    </a>
                </div>
            </section>
        </div>
    </main>

    <!-- Login Modal -->
    <div class="login-overlay" id="loginOverlay">
        <div class="login-modal">
            <div class="modal-icon">🔐</div>
            <h3 class="modal-title">Login Required</h3>
            <p class="modal-text" id="modalText">You need to login to access this feature.</p>
            <div class="modal-buttons">
                <button class="btn btn-ghost" onclick="hideLoginModal()">Cancel</button>
                <a href="login.php" class="btn btn-primary" style="text-decoration: none;">Login Now</a>
            </div>
        </div>
    </div>

    <script>
        function showLoginModal(action) {
            const modal = document.getElementById('loginOverlay');
            const text = document.getElementById('modalText');
            
            text.textContent = `You need to login to ${action}. Join PinoyFix to access all features!`;
            modal.style.display = 'flex';
        }

        function hideLoginModal() {
            document.getElementById('loginOverlay').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('loginOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                hideLoginModal();
            }
        });

        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate cards on scroll
            const cards = document.querySelectorAll('.fixer-card, .job-card');
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            cards.forEach((card) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(card);
            });
        });
    </script>
</body>
</html>