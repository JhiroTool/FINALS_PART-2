<?php
// Simple form handler for demo purposes
$messages = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contact'])) {
    $name = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $msg  = htmlspecialchars(trim($_POST['message'] ?? ''));
    if (!$name || !$email || !$msg) {
        $messages[] = ['type' => 'error', 'text' => 'Please fill all fields with valid data.'];
    } else {
        // In production, save to DB or send email. Here we simulate success.
        $messages[] = ['type' => 'success', 'text' => 'Thanks, ' . $name . '! Your message has been received.'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PinoyFix — Connect. Repair. Empower.</title>
    <meta name="description" content="PinoyFix: a community-driven platform connecting Filipinos with trusted local fixers for home, gadgets, and more." />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css">
</head>
<body>
    <!-- Modern Header -->
    <header class="modern-header">
        <div class="container">
            <div class="header-content">
                <!-- Brand -->
                <div class="brand-section">
                    <div class="logo-container">
                        <img src="images/pinoyfix.png" alt="PinoyFix" class="brand-logo">
                        <div class="brand-text">
                            <h1>PinoyFix</h1>
                            <span>Connect. Repair. Empower.</span>
                        </div>
                    </div>
                </div>

                <!-- Navigation Links -->
                <div class="nav-section">
                    <nav class="modern-nav">
                        <a href="#features" class="nav-link">Features</a>
                        <a href="#how" class="nav-link">How it Works</a>
                        <a href="#testimonials" class="nav-link">Reviews</a>
                        <a href="#get-started" class="nav-link">Get Started</a>
                    </nav>
                </div>

                <!-- Auth Buttons -->
                <div class="user-section">
                    <a href="login.php" class="hero-btn secondary">
                        <span class="btn-icon">🔐</span>
                        <span>Login</span>
                    </a>
                    <a href="register.php" class="hero-btn primary">
                        <span class="btn-icon">👤</span>
                        <span>Sign Up</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="dashboard-container">
        <div class="container">
            <!-- Hero Section -->
            <section class="dashboard-hero" style="background: linear-gradient(135deg, #0038A8 0%, #1e40af 50%, #3b82f6 100%);">
                <div class="hero-content">
                    <div class="welcome-text">
                        <span class="hero-badge">Community → Ka-Support</span>
                        <h1>PinoyFix — Connect with trusted local fixers in your neighborhood 🛠️</h1>
                        <p>Fast, transparent, and Filipino-focused platform for home repairs, gadget fixes, and on-site services. Find vetted technicians, see reviews, and schedule same-day visits.</p>
                    </div>
                    
                    <div class="hero-actions">
                        <a href="marketplace.php" class="hero-btn primary">
                            <span class="btn-icon">🔍</span>
                            <span>Find a Fixer</span>
                        </a>
                        <a href="register.php?type=technician" class="hero-btn secondary">
                            <span class="btn-icon">🔧</span>
                            <span>Become a Fixer</span>
                        </a>
                    </div>
                </div>
                
                <!-- Feature Cards -->
                <div class="status-cards">
                    <div class="status-card active">
                        <div class="card-icon">⚡</div>
                        <div class="card-content">
                            <h3>Quick Booking</h3>
                            <p>Same-Day Service</p>
                            <span class="card-trend">Book in real-time slots</span>
                        </div>
                    </div>
                    
                    <div class="status-card completed">
                        <div class="card-icon">✅</div>
                        <div class="card-content">
                            <h3>Verified Pros</h3>
                            <p>Community-Vetted</p>
                            <span class="card-trend">Transparent pricing</span>
                        </div>
                    </div>
                    
                    <div class="status-card appliances">
                        <div class="card-icon">💰</div>
                        <div class="card-content">
                            <h3>Secure Payments</h3>
                            <p>Cashless Options</p>
                            <span class="card-trend">Escrow protection</span>
                        </div>
                    </div>
                    
                    <div class="status-card appliances">
                        <div class="card-icon">🛡️</div>
                        <div class="card-content">
                            <h3>Service Guarantee</h3>
                            <p>30-Day Warranty</p>
                            <span class="card-trend">Quality assured/span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Get Started Panel -->
            <section class="marketplace-actions" id="get-started">
                <div class="action-marketplace" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; align-items: start;">
                    <div>
                        <h2 class="section-title">
                            <span class="title-icon">🚀</span>
                            Start in 3 Simple Steps
                        </h2>
                        
                        <div class="steps-container" style="margin-bottom: 2rem;">
                            <div class="step-item" style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">
                                <div class="step-number" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #0038A8, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem;">1</div>
                                <div>
                                    <h4 style="margin: 0 0 0.5rem 0; color: #1e293b; font-weight: 700;">Tell us the problem</h4>
                                    <p style="margin: 0; color: #64748b; line-height: 1.6;">Describe your issue (e.g., rice cooker leak, AC not cooling, phone screen crack)</p>
                                </div>
                            </div>
                            
                            <div class="step-item" style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">
                                <div class="step-number" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #0038A8, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem;">2</div>
                                <div>
                                    <h4 style="margin: 0 0 0.5rem 0; color: #1e293b; font-weight: 700;">Choose a local fixer</h4>
                                    <p style="margin: 0; color: #64748b; line-height: 1.6;">Browse matched profiles with ratings, reviews, and transparent pricing</p>
                                </div>
                            </div>
                            
                            <div class="step-item" style="display: flex; align-items: start; gap: 1rem; margin-bottom: 1.5rem; padding: 1.5rem; background: white; border-radius: 16px; box-shadow: 0 4px 16px rgba(0,0,0,0.08);">
                                <div class="step-number" style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #0038A8, #3b82f6); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 1.2rem;">3</div>
                                <div>
                                    <h4 style="margin: 0 0 0.5rem 0; color: #1e293b; font-weight: 700;">Approve estimate & schedule</h4>
                                    <p style="margin: 0; color: #64748b; line-height: 1.6;">Confirm the quote, schedule the visit, and pay securely after completion</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Form Card -->
                    <div class="contact-card" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.12); border: 2px solid #f1f5f9;">
                        <h3 style="margin: 0 0 1rem 0; color: #1e293b; font-weight: 700; font-size: 1.3rem;">Get a Quick Estimate 💬</h3>
                        
                        <!-- Messages -->
                        <?php foreach ($messages as $m): ?>
                        <div class="form-message <?php echo $m['type'] === 'success' ? 'success' : 'error' ?>" style="padding: 12px 16px; border-radius: 12px; margin-bottom: 1rem; font-weight: 600; <?php echo $m['type'] === 'success' ? 'background: #dcfce7; color: #166534; border: 2px solid #bbf7d0;' : 'background: #fef2f2; color: #991b1b; border: 2px solid #fecaca;'; ?>">
                            <?php echo $m['text'] ?>
                        </div>
                        <?php endforeach; ?>

                        <form method="post" action="#get-started" style="display: flex; flex-direction: column; gap: 1rem;">
                            <input name="name" type="text" placeholder="Your name" required style="padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc; transition: all 0.3s ease;" />
                            
                            <input name="email" type="email" placeholder="Email address" required style="padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc; transition: all 0.3s ease;" />
                            
                            <textarea name="message" placeholder="Briefly describe the issue (e.g., 'Aircon not cooling, might need cleaning')" rows="4" required style="padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc; resize: vertical; transition: all 0.3s ease;"></textarea>
                            
                            <input type="hidden" name="contact" value="1" />
                            
                            <div style="display: flex; gap: 1rem;">
                                <button type="submit" class="hero-btn primary" style="flex: 1;">
                                    <span class="btn-icon">📨</span>
                                    <span>Send Request</span>
                                </button>
                                <a href="#how" class="hero-btn secondary btn-small">
                                    <span class="btn-icon">❓</span>
                                    <span>Learn How</span>
                                </a>
                            </div>
                        </form>
                        
                        <p style="margin: 1rem 0 0 0; font-size: 0.9rem; color: #64748b; text-align: center;">
                            Free estimates • No obligation • Quick response
                        </p>
                    </div>
                </div>
            </section>

            <!-- Features Section -->
            <section class="marketplace-actions" id="features">
                <h2 class="section-title">
                    <span class="title-icon">⭐</span>
                    Why Choose PinoyFix?
                </h2>
                <p class="section-subtitle">Designed for Filipino communities — affordable rates, local talents, and support in Tagalog and English.</p>
                
                <div class="action-marketplace">
                    <div class="action-item">
                        <div class="action-icon">🔎</div>
                        <div class="action-details">
                            <h3>Local Matches</h3>
                            <p>Find fixers near you to reduce wait times and travel fees</p>
                            <div class="action-meta">
                                <span>📍 Nearby technicians</span>
                                <span>⏰ Faster service</span>
                            </div>
                        </div>
                    </div>

                    <div class="action-item featured">
                        <div class="action-badge">Popular</div>
                        <div class="action-icon">⭐</div>
                        <div class="action-details">
                            <h3>Ratings & Warranty</h3>
                            <p>Post-job guarantee and customer reviews keep quality high</p>
                            <div class="action-meta">
                                <span>🛡️ Service guarantee</span>
                                <span>⭐ Community ratings</span>
                            </div>
                        </div>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">💬</div>
                        <div class="action-details">
                            <h3>In-app Chat</h3>
                            <p>Coordinate details with the fixer before arrival</p>
                            <div class="action-meta">
                                <span>📱 Real-time messaging</span>
                                <span>🔔 Instant notifications</span>
                            </div>
                        </div>
                    </div>

                    <div class="action-item">
                        <div class="action-icon">🏆</div>
                        <div class="action-details">
                            <h3>Support Local</h3>
                            <p>Empower small, independent Filipino repair businesses</p>
                            <div class="action-meta">
                                <span>🇵🇭 Filipino-owned</span>
                                <span>💪 Community-driven</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- How It Works -->
            <section class="insights-section" id="how">
                <h2 class="section-title">
                    <span class="title-icon">🔧</span>
                    How PinoyFix Works
                </h2>
                
                <div class="insights-grid">
                    <div class="insight-card maintenance">
                        <div class="insight-icon">📝</div>
                        <h4>1. Post the Job</h4>
                        <p>Describe your repair need with images or a short description. Be specific about the problem.</p>
                    </div>
                    
                    <div class="insight-card communication">
                        <div class="insight-icon">👥</div>
                        <h4>2. Get 3 Estimates</h4>
                        <p>Receive quotes from local verified technicians — compare prices, ratings, and reviews.</p>
                    </div>
                    
                    <div class="insight-card feedback">
                        <div class="insight-icon">✅</div>
                        <h4>3. Pay & Rate</h4>
                        <p>Pay securely through the app and rate the technician after job completion.</p>
                    </div>
                </div>
            </section>

            <!-- Testimonials -->
            <section class="activity-stream" id="testimonials">
                <div class="stream-header">
                    <div>
                        <h2 class="section-title">
                            <span class="title-icon">💬</span>
                            What Our Community Says
                        </h2>
                        <p class="section-subtitle">Real reviews from Filipino families who found reliable fixers through PinoyFix</p>
                    </div>
                </div>
                
                <div class="testimonials-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 2rem;">
                    <div class="testimonial-card" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border: 2px solid #f1f5f9;">
                        <div class="rating" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-style: italic; margin-bottom: 1.5rem; color: #374151; line-height: 1.6;">"Kuya Mark fixed our aircon in 2 hours! Very professional and hindi mahal. Highly recommended!"</p>
                        <div class="client-info">
                            <div style="font-weight: 700; color: #1e293b;">Maria S.</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Quezon City • Aircon Repair • ₱800</div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border: 2px solid #f1f5f9;">
                        <div class="rating" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-style: italic; margin-bottom: 1.5rem; color: #374151; line-height: 1.6;">"Same-day washing machine repair! Ate Joy was very kind and explained everything clearly. Salamat!"</p>
                        <div class="client-info">
                            <div style="font-weight: 700; color: #1e293b;">Roberto L.</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Makati • Appliance Repair • ₱1,200</div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border: 2px solid #f1f5f9;">
                        <div class="rating" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-style: italic; margin-bottom: 1.5rem; color: #374151; line-height: 1.6;">"Fixed my laptop screen perfectly. Very transparent sa pricing and guaranteed ang work niya for 6 months."</p>
                        <div class="client-info">
                            <div style="font-weight: 700; color: #1e293b;">Jenny C.</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Cebu City • Gadget Repair • ₱2,500</div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border: 2px solid #f1f5f9;">
                        <div class="rating" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-style: italic; margin-bottom: 1.5rem; color: #374151; line-height: 1.6;">"Electrical wiring sa kitchen. Si Manong Rico very experienced and safety-first talaga. Worth it!"</p>
                        <div class="client-info">
                            <div style="font-weight: 700; color: #1e293b;">Carlos M.</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Davao • Electrical Work • ₱3,800</div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border: 2px solid #f1f5f9;">
                        <div class="rating" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-style: italic; margin-bottom: 1.5rem; color: #374151; line-height: 1.6;">"Plumbing emergency solved agad! Ate Linda came within 30 minutes. Professional and mabait pa."</p>
                        <div class="client-info">
                            <div style="font-weight: 700; color: #1e293b;">Anna T.</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Pasig • Plumbing • ₱1,500</div>
                        </div>
                    </div>
                    
                    <div class="testimonial-card" style="background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border: 2px solid #f1f5f9;">
                        <div class="rating" style="color: #f59e0b; font-size: 1.2rem; margin-bottom: 1rem;">⭐⭐⭐⭐⭐</div>
                        <p style="font-style: italic; margin-bottom: 1.5rem; color: #374151; line-height: 1.6;">"Phone screen replacement done perfectly. Kuya Jun even cleaned the phone for free. Astig!"</p>
                        <div class="client-info">
                            <div style="font-weight: 700; color: #1e293b;">Michael P.</div>
                            <div style="color: #64748b; font-size: 0.9rem;">Iloilo • Mobile Repair • ₱1,800</div>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <p style="color: #64748b; margin-bottom: 1rem;">Join thousands of satisfied customers</p>
                    <a href="marketplace.php" class="hero-btn primary">
                        <span class="btn-icon">🚀</span>
                        <span>Start Your Repair Journey</span>
                    </a>
                </div>
            </section>

            <!-- Become a Fixer CTA -->
            <section style="background: linear-gradient(135deg, #f8fafc, #e2e8f0); border-radius: 24px; padding: 4rem 2rem; text-align: center; margin: 4rem 0;">
                <h2 style="font-size: 2.5rem; font-weight: 800; color: #1e293b; margin-bottom: 1rem;">Become a PinoyFix Technician 👨‍🔧</h2>
                <p style="font-size: 1.2rem; color: #64748b; margin-bottom: 2rem; max-width: 600px; margin-left: auto; margin-right: auto;">Sign up to grow your client base. Tools for schedules, invoicing, and reputation management included.</p>
                
                <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                    <a href="register.php?type=technician" class="hero-btn primary" style="font-size: 1.2rem; padding: 20px 40px;">
                        <span class="btn-icon">🔧</span>
                        <span>Join as Technician</span>
                    </a>
                    <a href="#features" class="hero-btn secondary" style="font-size: 1.2rem; padding: 20px 40px;">
                        <span class="btn-icon">📖</span>
                        <span>Learn More</span>
                    </a>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-top: 3rem; max-width: 800px; margin-left: auto; margin-right: auto;">
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">📈</div>
                        <div style="font-weight: 700; margin-bottom: 0.5rem;">Grow Your Business</div>
                        <div style="color: #64748b; font-size: 0.9rem;">Reach more customers online</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">💰</div>
                        <div style="font-weight: 700; margin-bottom: 0.5rem;">Flexible Earnings</div>
                        <div style="color: #64748b; font-size: 0.9rem;">Set your own rates and schedule</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">🛠️</div>
                        <div style="font-weight: 700; margin-bottom: 0.5rem;">Professional Tools</div>
                        <div style="color: #64748b; font-size: 0.9rem;">Invoicing, scheduling, and more</div>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 2rem; margin-bottom: 0.5rem;">⭐</div>
                        <div style="font-weight: 700; margin-bottom: 0.5rem;">Build Reputation</div>
                        <div style="color: #64748b; font-size: 0.9rem;">Customer reviews and ratings</div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modern Footer -->
    <footer class="modern-footer" style="background: #1e293b; color: white; padding: 4rem 0 2rem 0;">
        <div class="container">
            <div class="footer-content" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; margin-bottom: 3rem;">
                <div class="footer-section">
                    <h4 style="color: white; margin-bottom: 1rem; font-weight: 700;">PinoyFix</h4>
                    <p style="color: #94a3b8; line-height: 1.6; margin-bottom: 1rem;">Built for Philippine communities — connecting neighbors with trusted local fixers since 2025.</p>
                    <p style="margin-top: 1rem; color: #f59e0b; font-weight: 600;">🏆 #1 Community Repair Platform</p>
                </div>
                <div class="footer-section">
                    <h5 style="color: white; margin-bottom: 1rem; font-weight: 600;">For Clients</h5>
                    <a href="marketplace.php" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">🔍 Find Fixers</a>
                    <a href="register.php" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">👤 Sign Up</a>
                    <a href="login.php" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">🔐 Login</a>
                    <a href="#how" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">❓ How It Works</a>
                </div>
                <div class="footer-section">
                    <h5 style="color: white; margin-bottom: 1rem; font-weight: 600;">For Technicians</h5>
                    <a href="register.php?type=technician" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">🔧 Join as Fixer</a>
                    <a href="login.php" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">📊 Technician Portal</a>
                    <a href="#features" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">📈 Grow Business</a>
                </div>
                <div class="footer-section">
                    <h5 style="color: white; margin-bottom: 1rem; font-weight: 600;">Support & Legal</h5>
                    <p style="color: #94a3b8; margin-bottom: 0.5rem;">📞 (02) 8123-4567</p>
                    <p style="color: #94a3b8; margin-bottom: 0.5rem;">📧 support@pinoyfix.com</p>
                    <p style="color: #94a3b8; margin-bottom: 1rem;">💬 24/7 Community Help</p>
                    <a href="#" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">Privacy Policy</a>
                    <a href="#" style="color: #94a3b8; text-decoration: none; display: block; margin-bottom: 0.5rem; transition: color 0.3s ease;">Terms of Service</a>
                </div>
            </div>
            <div class="footer-bottom" style="border-top: 2px solid #334155; padding-top: 2rem; text-align: center;">
                <p style="color: #94a3b8; margin: 0;">&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines — Connecting communities, one repair at a time.</p>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(a=>{
            a.addEventListener('click', e=>{
                const target = document.querySelector(a.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({behavior:'smooth', block:'start'});
                }
            });
        });

        // Enhanced animations and interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Animate status cards
            const statusCards = document.querySelectorAll('.status-card');
            statusCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.classList.add('animate-slide-up');
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

            // Form input focus effects
            const inputs = document.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.style.borderColor = '#0038A8';
                    this.style.boxShadow = '0 0 0 4px rgba(0, 56, 168, 0.1)';
                    this.style.background = 'white';
                });
                
                input.addEventListener('blur', function() {
                    this.style.borderColor = '#e2e8f0';
                    this.style.boxShadow = 'none';
                    this.style.background = '#f8fafc';
                });
            });

            // Testimonial cards animation
            const testimonialCards = document.querySelectorAll('.testimonial-card');
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const testimonialObserver = new IntersectionObserver((entries) => {
                entries.forEach((entry, index) => {
                    if (entry.isIntersecting) {
                        setTimeout(() => {
                            entry.target.classList.add('animate-fade-in');
                        }, index * 100);
                    }
                });
            }, observerOptions);

            testimonialCards.forEach(card => {
                testimonialObserver.observe(card);
            });
        });

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            .hero-badge {
                display: inline-block;
                background: rgba(255, 255, 255, 0.2);
                color: white;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 0.9rem;
                font-weight: 600;
                margin-bottom: 1rem;
                backdrop-filter: blur(10px);
            }

            .btn-small {
                font-size: 0.8rem !important;
                padding: 8px 16px !important;
            }

            /* Footer link hover effects */
            .footer-section a:hover {
                color: #0038A8 !important;
            }

            @keyframes slideUp {
                from { opacity: 0; transform: translateY(30px); }
                to { opacity: 1; transform: translateY(0); }
            }

            @keyframes fadeIn {
                from { opacity: 0; transform: translateX(-20px); }
                to { opacity: 1; transform: translateX(0); }
            }

            .animate-slide-up {
                animation: slideUp 0.6s ease-out forwards;
            }

            .animate-fade-in {
                animation: fadeIn 0.6s ease-out forwards;
            }

            .nav-link {
                color: #64748b;
                text-decoration: none;
                font-weight: 500;
                padding: 8px 16px;
                border-radius: 8px;
                transition: all 0.3s ease;
            }

            .nav-link:hover {
                color: #0038A8;
                background: rgba(0, 56, 168, 0.1);
            }

            .modern-nav {
                display: flex;
                gap: 1rem;
                align-items: center;
            }

            .nav-section {
                flex: 1;
                display: flex;
                justify-content: center;
            }

            @media (max-width: 768px) {
                .nav-section {
                    display: none;
                }
                
                .header-content {
                    flex-wrap: wrap;
                }
                
                .action-marketplace {
                    grid-template-columns: 1fr !important;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>