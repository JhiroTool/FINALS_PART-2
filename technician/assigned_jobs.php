<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE booking SET Status = ? WHERE Booking_ID = ? AND Technician_ID = ?");
    $stmt->bind_param("sii", $new_status, $booking_id, $user_id);
    
    if ($stmt->execute()) {
        $message = "Job status updated successfully! 🎉";
        $messageType = "success";
    } else {
        $message = "Error updating job status. Please try again.";
        $messageType = "error";
    }
    $stmt->close();
}

// Get technician info
$tech_stmt = $conn->prepare("SELECT Technician_FN, Technician_LN, Technician_Profile FROM technician WHERE Technician_ID = ?");
$tech_stmt->bind_param("i", $user_id);
$tech_stmt->execute();
$tech_result = $tech_stmt->get_result();
$technician = $tech_result->fetch_assoc();
$tech_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management - PinoyFix Marketplace</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/jobs.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <div class="navbar-content">
                <a href="../index.php" class="navbar-brand">
                    <img src="../images/pinoyfix.png" alt="PinoyFix" class="logo">
                    <div class="brand-info">
                        <h1>PinoyFix</h1>
                        <p>Job Management Hub</p>
                    </div>
                </a>
                
                <div class="navbar-nav">
                    <a href="technician_dashboard.php" class="nav-link back">
                        ← Dashboard
                    </a>
                    <a href="../logout.php" class="nav-link logout">
                        🚪 Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <!-- Hero Banner -->
            <section class="hero-banner">
                <div class="hero-content">
                    <h1 class="hero-title">Your Job Dashboard 🔧</h1>
                    <p class="hero-subtitle">
                        Manage all your repair assignments with ease. Track new opportunities, 
                        monitor ongoing projects, and celebrate completed work.
                    </p>
                </div>
            </section>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <span><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <!-- Jobs Sections -->
            <?php
            $statuses = [
                'assigned' => [
                    'title' => 'New Assignments',
                    'icon' => '🆕',
                    'class' => 'assigned',
                    'description' => 'Fresh opportunities waiting for you'
                ],
                'in_progress' => [
                    'title' => 'Active Projects',
                    'icon' => '🔧',
                    'class' => 'progress',
                    'description' => 'Jobs currently in progress'
                ],
                'completed' => [
                    'title' => 'Completed Work',
                    'icon' => '✅',
                    'class' => 'completed',
                    'description' => 'Successfully finished projects'
                ]
            ];

            foreach ($statuses as $status => $config):
                try {
                    $jobs_query = "
                        SELECT b.*, c.Client_FN, c.Client_LN, c.Client_Phone, c.Client_Email,
                               a.Street, a.Barangay, a.City, a.Province
                        FROM booking b 
                        LEFT JOIN client c ON b.Client_ID = c.Client_ID 
                        LEFT JOIN client_address ca ON c.Client_ID = ca.Client_ID
                        LEFT JOIN address a ON ca.Address_ID = a.Address_ID
                        WHERE b.Technician_ID = ? AND b.Status = ? 
                        ORDER BY b.AptDate ASC
                    ";
                    
                    $jobs_stmt = $conn->prepare($jobs_query);
                    $jobs_stmt->bind_param("is", $user_id, $status);
                    $jobs_stmt->execute();
                    $jobs_result = $jobs_stmt->get_result();
                    $job_count = $jobs_result->num_rows;
            ?>
                    <section class="jobs-section">
                        <div class="section-header">
                            <div class="section-title">
                                <div class="section-icon <?php echo $config['class']; ?>">
                                    <?php echo $config['icon']; ?>
                                </div>
                                <div>
                                    <h2><?php echo $config['title']; ?></h2>
                                    <p style="font-size: 0.875rem; color: var(--gray-600); margin: 0;">
                                        <?php echo $config['description']; ?>
                                    </p>
                                </div>
                            </div>
                            <div class="status-badge <?php echo $config['class']; ?>">
                                <?php echo $job_count; ?> Jobs
                            </div>
                        </div>

                        <?php if ($job_count > 0): ?>
                            <div class="jobs-grid">
                                <?php while ($job = $jobs_result->fetch_assoc()): ?>
                                    <div class="job-card <?php echo $config['class']; ?>">
                                        <div class="job-header">
                                            <div class="client-avatar">
                                                <span><?php echo strtoupper(substr($job['Client_FN'], 0, 1)); ?></span>
                                            </div>
                                            <div class="client-details">
                                                <h3><?php echo htmlspecialchars($job['Client_FN'] . ' ' . $job['Client_LN']); ?></h3>
                                                <p class="client-phone"><?php echo htmlspecialchars($job['Client_Phone']); ?></p>
                                                <div class="job-status-tag <?php echo $status; ?>">
                                                    <?php 
                                                    $status_icons = [
                                                        'assigned' => '🆕',
                                                        'in_progress' => '⚡',
                                                        'completed' => '✅'
                                                    ];
                                                    echo $status_icons[$status] . ' ' . ucfirst(str_replace('_', ' ', $status));
                                                    ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="job-info">
                                            <div class="info-row">
                                                <span class="info-label">🔧 Service:</span>
                                                <div class="service-type"><?php echo htmlspecialchars($job['Service_Type']); ?></div>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label">📅 Schedule:</span>
                                                <span class="info-value"><?php echo date('l, M j, Y @ g:i A', strtotime($job['AptDate'])); ?></span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label">📧 Email:</span>
                                                <span class="info-value"><?php echo htmlspecialchars($job['Client_Email']); ?></span>
                                            </div>
                                            
                                            <div class="info-row">
                                                <span class="info-label">📍 Location:</span>
                                                <span class="info-value">
                                                    <?php 
                                                    $address_parts = array_filter([
                                                        $job['Street'], 
                                                        $job['Barangay'], 
                                                        $job['City'], 
                                                        $job['Province']
                                                    ]);
                                                    echo htmlspecialchars(implode(', ', $address_parts) ?: 'Address not provided');
                                                    ?>
                                                </span>
                                            </div>
                                            
                                            <?php if (!empty($job['Description'])): ?>
                                                <div class="info-row">
                                                    <span class="info-label">📝 Notes:</span>
                                                    <span class="info-value"><?php echo htmlspecialchars($job['Description']); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="job-actions">
                                            <?php if ($status === 'assigned'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $job['Booking_ID']; ?>">
                                                    <input type="hidden" name="new_status" value="in_progress">
                                                    <button type="submit" name="update_status" class="btn btn-primary">
                                                        🚀 Start Project
                                                    </button>
                                                </form>
                                            <?php elseif ($status === 'in_progress'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $job['Booking_ID']; ?>">
                                                    <input type="hidden" name="new_status" value="completed">
                                                    <button type="submit" name="update_status" class="btn btn-success">
                                                        ✅ Mark Complete
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            
                                            <a href="tel:<?php echo $job['Client_Phone']; ?>" class="btn btn-secondary">
                                                📞 Call Client
                                            </a>
                                            
                                            <a href="mailto:<?php echo $job['Client_Email']; ?>" class="btn btn-secondary">
                                                📧 Send Email
                                            </a>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-state">
                                <div class="empty-icon"><?php echo $config['icon']; ?></div>
                                <h3>No <?php echo strtolower($config['title']); ?> yet</h3>
                                <p>
                                    <?php
                                    switch($status) {
                                        case 'assigned':
                                            echo 'No new assignments at the moment. New repair requests will appear here when they\'re assigned to you by our admin team.';
                                            break;
                                        case 'in_progress':
                                            echo 'No active projects right now. Start working on your assigned jobs to see them here!';
                                            break;
                                        case 'completed':
                                            echo 'No completed projects yet. Finish your first job to build your success story here!';
                                            break;
                                    }
                                    ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </section>
            <?php
                    $jobs_stmt->close();
                } catch (Exception $e) {
                    echo "<div class='alert alert-error'>⚠️ Error loading jobs: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            endforeach;
            ?>
        </div>
    </main>

    <script>
        // Enhanced page load animations
        document.addEventListener('DOMContentLoaded', function() {
            const elements = document.querySelectorAll('.job-card, .jobs-section, .hero-banner, .alert');
            
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(30px) scale(0.95)';
                element.style.transition = `all 0.8s cubic-bezier(0.4, 0, 0.2, 1) ${index * 0.1}s`;
                
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0) scale(1)';
                }, 200 + (index * 100));
            });
        });

        // Auto-hide alerts with slide animation
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transform = 'translateX(100%)';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 6000);

        // Enhanced confirmation dialogs
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = this.querySelector('button');
                const action = button.textContent.trim();
                
                if (!confirm(`🤔 Are you sure you want to ${action.toLowerCase()}?\n\nThis action cannot be undone.`)) {
                    e.preventDefault();
                } else {
                    // Add loading state
                    button.style.opacity = '0.7';
                    button.innerHTML = '⏳ Processing...';
                    button.disabled = true;
                }
            });
        });

        // Advanced hover effects with smooth transitions
        document.querySelectorAll('.job-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.03)';
                this.style.boxShadow = '0 30px 60px rgba(0, 0, 0, 0.2)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-10px) scale(1.02)';
                this.style.boxShadow = '0 25px 50px rgba(0, 0, 0, 0.15)';
            });
        });

        // Add ripple effect to buttons
        document.querySelectorAll('.btn').forEach(button => {
            button.addEventListener('click', function(e) {
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.width = ripple.style.height = size + 'px';
                ripple.style.left = x + 'px';
                ripple.style.top = y + 'px';
                ripple.classList.add('ripple');
                
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add CSS for ripple effect
        const style = document.createElement('style');
        style.textContent = `
            .btn { position: relative; overflow: hidden; }
            .ripple {
                position: absolute;
                border-radius: 50%;
                background: rgba(255, 255, 255, 0.6);
                transform: scale(0);
                animation: ripple 0.6s ease-out;
                pointer-events: none;
            }
            @keyframes ripple {
                to { transform: scale(2); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Smooth scroll for internal links
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
    </script>
</body>
</html>