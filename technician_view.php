<?php
session_start();

// Simulated data for demo purposes
$available_jobs = [
    [
        'id' => 1,
        'title' => 'Aircon not cooling properly',
        'description' => 'Split-type aircon running but not cooling. Might need cleaning or freon refill.',
        'location' => 'Quezon City',
        'budget' => '₱500-800',
        'posted' => '2 hours ago',
        'status' => 'Open',
        'urgency' => 'Normal',
        'client_rating' => 4.8,
        'client_jobs' => 12,
        'skills_needed' => ['Aircon Repair', 'Freon Refill', 'Cleaning']
    ],
    [
        'id' => 2,
        'title' => 'Washing machine not spinning',
        'description' => 'Top load washer stops after wash cycle, won\'t spin to drain water. Makes clicking sound.',
        'location' => 'Makati',
        'budget' => '₱400-600',
        'posted' => '4 hours ago',
        'status' => 'Open',
        'urgency' => 'High',
        'client_rating' => 4.9,
        'client_jobs' => 8,
        'skills_needed' => ['Washing Machine', 'Motor Repair', 'Electronics']
    ],
    [
        'id' => 3,
        'title' => 'TV screen flickering',
        'description' => '32-inch LED TV has intermittent flickering, especially on dark scenes. Started last week.',
        'location' => 'Pasig',
        'budget' => '₱300-500',
        'posted' => '1 day ago',
        'status' => 'In Progress',
        'urgency' => 'Low',
        'client_rating' => 4.6,
        'client_jobs' => 15,
        'skills_needed' => ['TV Repair', 'Electronics', 'LED Display']
    ],
    [
        'id' => 4,
        'title' => 'Rice cooker overheating',
        'description' => 'Rice cooker gets too hot and burns rice. Automatic shutoff not working properly.',
        'location' => 'Manila',
        'budget' => '₱200-400',
        'posted' => '6 hours ago',
        'status' => 'Open',
        'urgency' => 'Normal',
        'client_rating' => 4.7,
        'client_jobs' => 20,
        'skills_needed' => ['Small Appliances', 'Thermostat', 'Safety Systems']
    ]
];

$top_technicians = [
    [
        'name' => 'Mark Santos',
        'rating' => 4.9,
        'completed_jobs' => 245,
        'specialty' => 'Aircon & Refrigeration',
        'earnings_month' => '₱28,500',
        'location' => 'Quezon City'
    ],
    [
        'name' => 'Joy Mendez',
        'rating' => 4.8,
        'completed_jobs' => 189,
        'specialty' => 'Washing Machines',
        'earnings_month' => '₱22,300',
        'location' => 'Makati'
    ],
    [
        'name' => 'Roberto Cruz',
        'rating' => 4.9,
        'completed_jobs' => 312,
        'specialty' => 'Electronics & TV',
        'earnings_month' => '₱35,800',
        'location' => 'Cebu City'
    ]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Opportunities - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/technician-view.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="brand">
                    <img src="images/pinoyfix.png" alt="PinoyFix" class="logo">
                    <div class="brand-text">
                        <h1>PinoyFix</h1>
                        <p>Technician Portal</p>
                    </div>
                </a>
                
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <span class="guest-badge">🔧 Technician Preview</span>
                    <div class="auth-buttons">
                        <a href="login.php" class="btn btn-ghost">Login</a>
                        <a href="register.php" class="btn btn-primary">Join as Fixer</a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">Earn Money Fixing What You Love</h1>
                    <p class="hero-subtitle">Join PinoyFix as a verified technician and connect with customers who need your skills</p>
                    
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number">₱25,000</div>
                            <div class="stat-label">Average Monthly Earnings</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">1,200+</div>
                            <div class="stat-label">Active Jobs Posted Daily</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">4.8★</div>
                            <div class="stat-label">Average Technician Rating</div>
                        </div>
                    </div>
                    
                    <div class="guest-notice">
                        <strong>👨‍🔧 Preview Mode:</strong> This shows what technicians see. <a href="register.php" style="color: #CE1126; font-weight: 600;">Register as a Fixer</a> to apply for jobs and start earning!
                    </div>
                </div>
            </div>

            <!-- Available Jobs Section -->
            <section class="section">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">🎯 Available Jobs Near You</h2>
                        <p class="section-subtitle">Fresh repair opportunities posted by verified customers</p>
                    </div>
                    <div class="filter-controls">
                        <select class="filter-select" onchange="showLoginModal('change filters')">
                            <option>All Categories</option>
                            <option>Aircon & Refrigeration</option>
                            <option>Washing Machine</option>
                            <option>Electronics</option>
                            <option>Small Appliances</option>
                        </select>
                        <button class="btn btn-ghost" onclick="showLoginModal('set location filter')">📍 Location</button>
                    </div>
                </div>
                
                <div class="jobs-grid">
                    <?php foreach ($available_jobs as $job): ?>
                    <div class="job-card <?php echo strtolower($job['urgency']); ?>">
                        <div class="job-header">
                            <div class="job-info">
                                <h3 class="job-title"><?php echo $job['title']; ?></h3>
                                <div class="job-meta">
                                    <span class="location">📍 <?php echo $job['location']; ?></span>
                                    <span class="time">⏰ <?php echo $job['posted']; ?></span>
                                    <span class="urgency-badge urgency-<?php echo strtolower($job['urgency']); ?>">
                                        <?php echo $job['urgency']; ?> Priority
                                    </span>
                                </div>
                            </div>
                            <div class="job-budget">
                                <?php echo $job['budget']; ?>
                            </div>
                        </div>
                        
                        <p class="job-description"><?php echo $job['description']; ?></p>
                        
                        <div class="skills-needed">
                            <span class="skills-label">Skills needed:</span>
                            <?php foreach ($job['skills_needed'] as $skill): ?>
                            <span class="skill-tag"><?php echo $skill; ?></span>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="client-info">
                            <div class="client-rating">
                                <span class="stars">⭐⭐⭐⭐⭐</span>
                                <span><?php echo $job['client_rating']; ?> • <?php echo $job['client_jobs']; ?> jobs posted</span>
                            </div>
                            <div class="job-actions">
                                <button class="btn btn-primary btn-small" onclick="showLoginModal('apply for this job')" 
                                        <?php echo $job['status'] !== 'Open' ? 'disabled' : ''; ?>>
                                    <?php echo $job['status'] === 'Open' ? '✋ Apply Now' : '🔒 ' . $job['status']; ?>
                                </button>
                                <button class="btn btn-ghost btn-small" onclick="showLoginModal('view job details')">
                                    👁️ Details
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Top Technicians Section -->
            <section class="section">
                <div class="section-header">
                    <div>
                        <h2 class="section-title">🏆 Top Performing Fixers</h2>
                        <p class="section-subtitle">See what successful technicians are earning on PinoyFix</p>
                    </div>
                </div>
                
                <div class="technicians-grid">
                    <?php foreach ($top_technicians as $index => $tech): ?>
                    <div class="tech-card <?php echo $index === 0 ? 'top-performer' : ''; ?>">
                        <?php if ($index === 0): ?>
                        <div class="crown">👑</div>
                        <?php endif; ?>
                        <div class="tech-info">
                            <h3><?php echo $tech['name']; ?></h3>
                            <p class="specialty"><?php echo $tech['specialty']; ?></p>
                            <p class="location">📍 <?php echo $tech['location']; ?></p>
                        </div>
                        <div class="tech-stats">
                            <div class="stat">
                                <span class="stat-value">⭐ <?php echo $tech['rating']; ?></span>
                                <span class="stat-label">Rating</span>
                            </div>
                            <div class="stat">
                                <span class="stat-value"><?php echo $tech['completed_jobs']; ?></span>
                                <span class="stat-label">Jobs Done</span>
                            </div>
                            <div class="stat earnings">
                                <span class="stat-value"><?php echo $tech['earnings_month']; ?></span>
                                <span class="stat-label">This Month</span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- How It Works -->
            <section class="section how-it-works">
                <h2 class="section-title">🚀 How to Get Started</h2>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h3>Create Profile</h3>
                        <p>Sign up and showcase your repair skills, certifications, and experience</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h3>Get Verified</h3>
                        <p>Complete our verification process to build trust with customers</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h3>Apply for Jobs</h3>
                        <p>Browse available jobs and submit proposals to interested customers</p>
                    </div>
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h3>Earn Money</h3>
                        <p>Complete repairs, get paid securely, and build your reputation</p>
                    </div>
                </div>
            </section>

            <!-- Call to Action -->
            <section class="cta-section">
                <div class="cta-content">
                    <h2>Ready to Start Your Fixing Journey?</h2>
                    <p>Join thousands of skilled technicians earning good money on PinoyFix</p>
                    <div class="cta-buttons">
                        <a href="register.php" class="btn btn-primary btn-large">
                            🚀 Become a Verified Fixer
                        </a>
                        <a href="login.php" class="btn btn-ghost btn-large">
                            🔑 Already Registered? Login
                        </a>
                    </div>
                    
                    <div class="cta-features">
                        <div class="feature">✅ Free to join</div>
                        <div class="feature">✅ Set your own rates</div>
                        <div class="feature">✅ Work on your schedule</div>
                        <div class="feature">✅ Secure payments</div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Login Modal -->
    <div class="login-overlay" id="loginOverlay">
        <div class="login-modal">
            <div class="modal-icon">🔧</div>
            <h3 class="modal-title">Join PinoyFix as a Fixer</h3>
            <p class="modal-text" id="modalText">You need to register as a technician to access this feature.</p>
            <div class="modal-buttons">
                <button class="btn btn-ghost" onclick="hideLoginModal()">Cancel</button>
                <a href="register.php" class="btn btn-primary">Register Now</a>
            </div>
        </div>
    </div>

    <script>
        function showLoginModal(action) {
            const modal = document.getElementById('loginOverlay');
            const text = document.getElementById('modalText');
            
            text.textContent = `You need to register as a verified technician to ${action}. Join PinoyFix to start earning!`;
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

        // Close modal when clicking outside
        document.getElementById('loginOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                hideLoginModal();
            }
        });

        // Add entrance animations
        document.addEventListener('DOMContentLoaded', function() {
            const cards = document.querySelectorAll('.job-card, .tech-card, .step-card');
            
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

        // Add fadeOut animation
        const styleSheet = document.createElement('style');
        styleSheet.textContent = `
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
        `;
        document.head.appendChild(styleSheet);
    </script>
</body>
</html>