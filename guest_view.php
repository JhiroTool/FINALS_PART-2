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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: #1e293b;
            background: #f8fafc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: inherit;
        }

        .logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
        }

        .brand-text h1 {
            font-size: 1.5rem;
            font-weight: 800;
            color: #0038A8;
        }

        .brand-text p {
            font-size: 0.8rem;
            color: #64748b;
        }

        .guest-badge {
            background: linear-gradient(135deg, #f59e0b, #f97316);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .auth-buttons {
            display: flex;
            gap: 12px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #0038A8;
            color: white;
        }

        .btn-primary:hover {
            background: #002d8f;
            transform: translateY(-1px);
        }

        .btn-ghost {
            background: transparent;
            color: #0038A8;
            border: 2px solid #0038A8;
        }

        .btn-ghost:hover {
            background: #0038A8;
            color: white;
        }

        /* Main Content */
        main {
            padding: 2rem 0;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #1e293b, #0038A8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .guest-notice {
            background: linear-gradient(135deg, #fef3c7, #fed7aa);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
        }

        .guest-notice strong {
            color: #92400e;
        }

        /* Section Headers */
        .section {
            margin-bottom: 3rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
        }

        /* Fixer Cards */
        .fixers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .fixer-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            position: relative;
        }

        .fixer-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }

        .fixer-header {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .fixer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            object-fit: cover;
        }

        .fixer-info h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .fixer-specialty {
            color: #0038A8;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .fixer-rating {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .stars {
            color: #f59e0b;
        }

        .rating-text {
            font-size: 0.9rem;
            color: #64748b;
        }

        .fixer-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 1rem 0;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #64748b;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .status-available {
            background: #dcfce7;
            color: #166534;
        }

        .status-busy {
            background: #fef3c7;
            color: #92400e;
        }

        .skills {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin: 1rem 0;
        }

        .skill-tag {
            background: #e2e8f0;
            color: #475569;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 0.85rem;
            flex: 1;
        }

        /* Job Cards */
        .jobs-grid {
            display: grid;
            gap: 1rem;
        }

        .job-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .job-card:hover {
            border-color: #0038A8;
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .job-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 0.5rem;
        }

        .job-meta {
            display: flex;
            gap: 1rem;
            color: #64748b;
            font-size: 0.9rem;
        }

        .job-description {
            color: #475569;
            margin: 1rem 0;
            line-height: 1.5;
        }

        .job-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .job-budget {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0038A8;
        }

        /* Login Overlay */
        .login-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.8);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .login-modal {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 400px;
            text-align: center;
        }

        .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .modal-text {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .fixers-grid {
                grid-template-columns: 1fr;
            }

            .fixer-details {
                grid-template-columns: 1fr;
            }

            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
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
                <a href="login.php" class="btn btn-primary">Login Now</a>
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