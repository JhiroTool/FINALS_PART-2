<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

// Get technician info
$user_id = $_SESSION['user_id'];
$subscription_message = '';
$subscription_type = '';

function isSubscriptionActive($flag, $expires) {
    if (!$flag) {
        return false;
    }
    if (empty($expires) || $expires === '0000-00-00 00:00:00') {
        return true;
    }
    return strtotime($expires) > time();
}

$payment_feedback = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_subscription_payment'])) {
    $amount = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
    $reference = trim($_POST['reference'] ?? '');
    $plan_days = isset($_POST['plan_days']) ? max(1, (int)$_POST['plan_days']) : 30;
    $notes = trim($_POST['notes'] ?? '');

    if ($amount <= 0 || $reference === '') {
        $payment_feedback = ['type' => 'error', 'text' => 'Please provide a valid amount and payment reference.'];
    } else {
        $stmt_payment = $conn->prepare("INSERT INTO subscription_payments (User_ID, User_Type, Amount, Reference, Plan_Days, Notes) VALUES (?, 'technician', ?, ?, ?, ?)");
        $stmt_payment->bind_param("idsis", $user_id, $amount, $reference, $plan_days, $notes);
        if ($stmt_payment->execute()) {
            $payment_feedback = ['type' => 'success', 'text' => 'Payment submitted. Admin will activate premium after verification.'];
        } else {
            $payment_feedback = ['type' => 'error', 'text' => 'Unable to submit payment. Try again later.'];
        }
        $stmt_payment->close();
    }
}

$stmt = $conn->prepare("SELECT Technician_FN, Technician_LN, Technician_Email, Technician_Phone, Specialization, Service_Pricing, Status, Ratings, Technician_Profile, Tech_Certificate, Is_Subscribed, Subscription_Expires FROM technician WHERE Technician_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$technician = $result->fetch_assoc();
$stmt->close();

if (!$technician) {
    header("Location: ../login.php");
    exit();
}

if (empty($technician['Tech_Certificate'])) {
    header("Location: verify_certification.php?redirect=dashboard");
    exit();
}

$is_subscribed = isSubscriptionActive((int)($technician['Is_Subscribed'] ?? 0), $technician['Subscription_Expires'] ?? null);
$subscription_expiry = ($technician['Subscription_Expires'] && $technician['Subscription_Expires'] !== '0000-00-00 00:00:00')
    ? date('M j, Y', strtotime($technician['Subscription_Expires']))
    : null;

$latest_payment = null;
$payment_stmt = $conn->prepare("SELECT Payment_ID, Amount, Status, Reference, Plan_Days, Created_At FROM subscription_payments WHERE User_ID = ? AND User_Type = 'technician' ORDER BY Created_At DESC LIMIT 1");
$payment_stmt->bind_param("i", $user_id);
$payment_stmt->execute();
$payment_result = $payment_stmt->get_result();
$latest_payment = $payment_result->fetch_assoc();
$payment_stmt->close();

$pending_payment = $latest_payment && $latest_payment['Status'] === 'pending';

if (!$subscription_message && $is_subscribed) {
    $subscription_message = "🌟 Premium active. You're prioritized for client bookings.";
    $subscription_type = 'success';
} elseif (!$subscription_message && $pending_payment) {
    $subscription_message = "⏳ Payment pending review. You'll be upgraded once the admin approves.";
    $subscription_type = 'info';
} elseif (!$subscription_message) {
    $subscription_message = "⚡ Submit a payment to unlock premium booking priority.";
    $subscription_type = 'info';
}

// Get stats
try {
    $awaiting_acceptance_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status IN ('awaiting_acceptance', 'pending', 'assigned')")->fetch_assoc()['count'];
    $in_progress_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'in_progress'")->fetch_assoc()['count'];
    $awaiting_confirmation_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'awaiting_confirmation'")->fetch_assoc()['count'];
    $completed_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'completed'")->fetch_assoc()['count'];

    // Get unread message count
    $unread_query = $conn->query("SELECT COUNT(*) as count FROM messages WHERE Receiver_ID = {$user_id} AND Receiver_Type = 'technician' AND Is_Read = 0");
    $unread_count = $unread_query ? $unread_query->fetch_assoc()['count'] : 0;
} catch (Exception $e) {
    $awaiting_acceptance_jobs = $awaiting_confirmation_jobs = $completed_jobs = $in_progress_jobs = $unread_count = 0;
}

$rating = $technician['Ratings'] ?: '0.0';
$hourly_rate = $technician['Service_Pricing'] ?: '0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician-dashboard.css">
    <link rel="stylesheet" href="../css/technician_dash.css">
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
                            <span>Technician Portal</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search jobs, clients..." class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell">
                        <span class="bell-icon">🔔</span>
                        <?php $open_job_count = $awaiting_acceptance_jobs + $in_progress_jobs + $awaiting_confirmation_jobs; ?>
                        <?php if ($open_job_count > 0 || $unread_count > 0): ?>
                        <span class="notification-badge"><?php echo $open_job_count + $unread_count; ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <?php if (!empty($technician['Technician_Profile'])): ?>
                                <img src="../uploads/profile_pics/<?php echo htmlspecialchars($technician['Technician_Profile']); ?>" alt="Profile Picture">
                            <?php else: ?>
                                <span><?php echo strtoupper(substr($technician['Technician_FN'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($technician['Technician_FN']); ?></h3>
                            <p><?php echo htmlspecialchars($technician['Specialization']); ?></p>
                            <span class="subscription-chip <?php echo $is_subscribed ? 'premium' : 'standard'; ?>">
                                <?php echo $is_subscribed ? 'Premium Technician' : 'Standard Technician'; ?>
                            </span>
                            <?php if ($is_subscribed && $subscription_expiry): ?>
                                <span class="subscription-chip expiry">Valid until <?php echo htmlspecialchars($subscription_expiry); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="user-dropdown">
                            <button class="dropdown-btn">⚙️</button>
                            <div class="dropdown-menu">
                                <a href="technician_dashboard.php">🏠 Dashboard</a>
                                <a href="assigned_jobs.php">📋 My Jobs</a>
                                <a href="messages.php">💬 Messages</a>
                                <a href="update_technician_profile.php">👤 Profile Settings</a>
                                <a href="verify_certification.php">📄 Certificates</a>
                                <a href="earnings.php">💰 Earnings</a>
                                <a href="support.php">🎧 Support Center</a>
                                <hr>
                                <a href="../logout.php" class="logout-link">🚪 Logout</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main>
        <?php if ($payment_feedback): ?>
            <div class="payment-alert <?php echo htmlspecialchars($payment_feedback['type']); ?>">
                <?php echo htmlspecialchars($payment_feedback['text']); ?>
            </div>
        <?php endif; ?>
        <div class="container">
            <!-- Hero Section -->
            <div class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">Welcome back, <?php echo htmlspecialchars($technician['Technician_FN']); ?>! 👋</h1>
                    <p class="hero-subtitle">Ready to help customers and earn money? Here's your job overview and quick access to everything you need.</p>
                </div>
                <div class="subscription-banner <?php echo $subscription_type; ?>">
                    <div class="subscription-info">
                        <h3><?php echo htmlspecialchars($subscription_message); ?></h3>
                        <?php if ($is_subscribed && $subscription_expiry): ?>
                            <span class="subscription-expiry">Valid until <?php echo htmlspecialchars($subscription_expiry); ?></span>
                        <?php endif; ?>
                        <?php if ($latest_payment): ?>
                            <span class="payment-pill status-<?php echo htmlspecialchars($latest_payment['Status']); ?>">
                                Latest payment: <?php echo htmlspecialchars(ucfirst($latest_payment['Status'])); ?><?php if (!empty($latest_payment['Reference'])): ?> · Ref <?php echo htmlspecialchars($latest_payment['Reference']); ?><?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="subscription-actions">
                        <?php if ($is_subscribed): ?>
                            <div class="subscription-success">
                                <span class="success-icon">🚀</span>
                                <div>
                                    <strong>Premium active</strong>
                                    <p>You are prioritized in client searches and booking assignments.</p>
                                </div>
                            </div>
                        <?php elseif ($pending_payment): ?>
                            <div class="subscription-pending">
                                <span class="pending-icon">⏳</span>
                                <div>
                                    <strong>Payment under review</strong>
                                    <p>Admin will activate your premium access after verifying your payment.</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <form method="POST" class="subscription-form" autocomplete="off">
                                <input type="hidden" name="create_subscription_payment" value="1">
                                <div class="subscription-form-grid">
                                    <label>
                                        <span>Plan</span>
                                        <select name="plan_days" required>
                                            <option value="30">30 days - ₱449</option>
                                            <option value="90">90 days - ₱1,149</option>
                                            <option value="180">180 days - ₱2,099</option>
                                        </select>
                                    </label>
                                    <label>
                                        <span>Amount Paid (₱)</span>
                                        <input type="number" name="amount" min="1" step="0.01" placeholder="e.g. 449" required>
                                    </label>
                                    <label>
                                        <span>Payment Reference</span>
                                        <input type="text" name="reference" maxlength="100" placeholder="GCash Ref / Bank Trace" required>
                                    </label>
                                    <label class="full-width">
                                        <span>Notes (optional)</span>
                                        <textarea name="notes" rows="2" placeholder="Add details such as payment channel or proof link"></textarea>
                                    </label>
                                </div>
                                <button type="submit" class="subscription-btn subscribe">
                                    Submit Payment for Verification
                                </button>
                                <p class="payment-hint">After submitting, send your receipt to admin for faster approval.</p>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Status Section -->
            <div class="status-section">
                <div class="status-card">
                    <div class="status-header">
                        <h3>Account Status</h3>
                        <div class="status-badge status-<?php echo $technician['Status']; ?>">
                            <?php
                            switch($technician['Status']) {
                                case 'approved':
                                    echo '✅ Approved';
                                    break;
                                case 'pending':
                                    echo '⏳ Pending Review';
                                    break;
                                case 'rejected':
                                    echo '❌ Rejected';
                                    break;
                                default:
                                    echo '⚠️ Under Review';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <?php if ($technician['Status'] === 'pending'): ?>
                        <div class="status-message">
                            <p>Your technician application is currently under review. You'll be notified once approved.</p>
                        </div>
                    <?php elseif ($technician['Status'] === 'rejected'): ?>
                        <div class="status-message error">
                            <p>Your application was rejected. Please contact support for more information.</p>
                        </div>
                    <?php else: ?>
                        <div class="status-message success">
                            <p>You're all set! Start accepting jobs and helping customers.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card pending">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo $awaiting_acceptance_jobs; ?></h3>
                        <p>Awaiting Acceptance</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-info">
                        <h3><?php echo $in_progress_jobs; ?></h3>
                        <p>In Progress</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">📝</div>
                    <div class="stat-info">
                        <h3><?php echo $awaiting_confirmation_jobs; ?></h3>
                        <p>Awaiting Confirmation</p>
                    </div>
                </div>

                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $completed_jobs; ?></h3>
                        <p>Completed Jobs</p>
                    </div>
                </div>

                <div class="stat-card warning">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <h3>₱<?php echo number_format($hourly_rate); ?></h3>
                        <p>Hourly Rate</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <section class="section">
                <h2 class="section-title">
                    <span class="section-icon">⚡</span>
                    Quick Actions
                </h2>
                
                <div class="actions-grid">
                    <a href="assigned_jobs.php" class="action-card primary">
                        <div class="action-icon">📋</div>
                        <div class="action-content">
                            <h3>View Jobs</h3>
                            <p>Check assigned jobs and update status</p>
                            <?php if ($open_job_count > 0): ?>
                                <span class="action-badge"><?php echo $open_job_count; ?> open</span>
                            <?php endif; ?>
                        </div>
                        <div class="action-arrow">→</div>
                    </a>
                    
                    <a href="messages.php" class="action-card info">
                        <div class="action-icon">💬</div>
                        <div class="action-content">
                            <h3>Messages</h3>
                            <p>Communicate with your clients</p>
                            <?php if ($unread_count > 0): ?>
                                <span class="action-badge"><?php echo $unread_count; ?> unread</span>
                            <?php endif; ?>
                        </div>
                        <div class="action-arrow">→</div>
                    </a>
                    
                    <a href="update_technician_profile.php" class="action-card secondary">
                        <div class="action-icon">👤</div>
                        <div class="action-content">
                            <h3>Update Profile</h3>
                            <p>Manage your profile and service details</p>
                        </div>
                        <div class="action-arrow">→</div>
                    </a>
                    
                    <a href="verify_certification.php" class="action-card accent">
                        <div class="action-icon">📄</div>
                        <div class="action-content">
                            <h3>Upload Certificate</h3>
                            <p>Submit your certification documents</p>
                            <?php if (empty($technician['Tech_Certificate'])): ?>
                                <span class="action-badge">Required</span>
                            <?php endif; ?>
                        </div>
                        <div class="action-arrow">→</div>
                    </a>
                    
                    <a href="earnings.php" class="action-card neutral">
                        <div class="action-icon">💼</div>
                        <div class="action-content">
                            <h3>Earnings</h3>
                            <p>View your earnings and payment history</p>
                        </div>
                        <div class="action-arrow">→</div>
                    </a>
                </div>
            </section>

            <!-- Recent Jobs -->
            <section class="section">
                <h2 class="section-title">
                    <span class="section-icon">🔧</span>
                    Recent Jobs
                </h2>
                
                <div class="jobs-list">
                    <?php
                    try {
                        $recent_jobs = $conn->query("
                            SELECT b.*, c.Client_FN, c.Client_LN, c.Client_Phone
                            FROM booking b 
                            LEFT JOIN client c ON b.Client_ID = c.Client_ID 
                            WHERE b.Technician_ID = {$user_id} 
                            AND b.Status IN ('assigned', 'in_progress', 'completed')
                            ORDER BY 
                                CASE b.Status 
                                    WHEN 'assigned' THEN 1 
                                    WHEN 'in_progress' THEN 2 
                                    WHEN 'completed' THEN 3 
                                END,
                                b.AptDate ASC 
                            LIMIT 5
                        ");
                        
                        if ($recent_jobs && $recent_jobs->num_rows > 0):
                            while ($job = $recent_jobs->fetch_assoc()):
                    ?>
                            <div class="job-item">
                                <div class="job-avatar">
                                    <span><?php echo strtoupper(substr($job['Client_FN'], 0, 1)); ?></span>
                                </div>
                                <div class="job-content">
                                    <h4><?php echo htmlspecialchars($job['Service_Type']); ?></h4>
                                    <p><strong>Client:</strong> <?php echo htmlspecialchars($job['Client_FN'] . ' ' . $job['Client_LN']); ?></p>
                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($job['Client_Phone']); ?></p>
                                    <p><strong>Scheduled:</strong> <?php echo date('M j, Y g:i A', strtotime($job['AptDate'])); ?></p>
                                    <?php if (!empty($job['Description'])): ?>
                                        <p><strong>Details:</strong> <?php echo htmlspecialchars(substr($job['Description'], 0, 100)) . (strlen($job['Description']) > 100 ? '...' : ''); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="job-status status-<?php echo $job['Status']; ?>">
                                    <?php 
                                    switch($job['Status']) {
                                        case 'assigned':
                                            echo '🆕 New Assignment';
                                            break;
                                        case 'in_progress':
                                            echo '🔄 In Progress';
                                            break;
                                        case 'completed':
                                            echo '✅ Completed';
                                            break;
                                        default:
                                            echo ucfirst($job['Status']);
                                    }
                                    ?>
                                </div>
                            </div>
                    <?php 
                            endwhile;
                        else:
                    ?>
                            <div class="empty-jobs">
                                <div class="empty-icon">🔧</div>
                                <h3>No Jobs Assigned Yet</h3>
                                <p>Wait for admin to assign jobs to you!</p>
                            </div>
                    <?php 
                        endif;
                    } catch (Exception $e) {
                    ?>
                        <div class="empty-jobs">
                            <div class="empty-icon">⚠️</div>
                            <h3>Unable to Load Jobs</h3>
                            <p>Please refresh the page or contact support if this persists.</p>
                        </div>
                    <?php } ?>
                </div>
            </section>
        </div>
    </main>

    <script>
        // Dropdown menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtn = document.querySelector('.dropdown-btn');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            
            if (dropdownBtn && dropdownMenu) {
                dropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                document.addEventListener('click', function() {
                    dropdownMenu.classList.remove('show');
                });
            }

            // Add smooth animations on page load
            const elements = document.querySelectorAll('.stat-card, .action-card, .job-item, .status-card');
            
            elements.forEach((element, index) => {
                element.style.opacity = '0';
                element.style.transform = 'translateY(20px)';
                element.style.transition = `opacity 0.5s ease ${index * 0.1}s, transform 0.5s ease ${index * 0.1}s`;
                
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, 100 + (index * 100));
            });
        });

        // Add hover effects for action cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(-4px) scale(1)';
            });
        });
    </script>
    <style>
        .subscription-banner {
            margin-top: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            padding: 1.5rem;
            border-radius: 16px;
            border: 1px solid rgba(59, 130, 246, 0.15);
            background: rgba(59, 130, 246, 0.08);
            box-shadow: 0 12px 32px rgba(59, 130, 246, 0.12);
        }

        .subscription-banner.success {
            border: 1px solid rgba(16, 185, 129, 0.2);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.12), rgba(56, 189, 248, 0.12));
            box-shadow: 0 12px 32px rgba(16, 185, 129, 0.15);
        }

        .subscription-banner.info {
            border: 1px solid rgba(59, 130, 246, 0.2);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(196, 181, 253, 0.08));
        }

        .subscription-info h3 {
            margin: 0;
            font-size: 1.15rem;
            color: #1e3a8a;
        }

        .subscription-banner.success .subscription-info h3 {
            color: #0f766e;
        }

        .subscription-expiry {
            display: inline-block;
            margin-top: 0.75rem;
            padding: 0.35rem 0.75rem;
            border-radius: 12px;
            background: rgba(16, 185, 129, 0.15);
            color: #047857;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .payment-pill {
            display: inline-block;
            margin-top: 0.75rem;
            margin-right: 0.5rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .payment-pill.status-paid {
            background: rgba(16, 185, 129, 0.2);
            color: #047857;
        }

        .payment-pill.status-pending {
            background: rgba(250, 204, 21, 0.25);
            color: #92400e;
        }

        .payment-pill.status-cancelled {
            background: rgba(248, 113, 113, 0.25);
            color: #b91c1c;
        }

        .subscription-actions {
            flex: 1;
        }

        .subscription-success,
        .subscription-pending {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            border-radius: 16px;
            background: rgba(16, 185, 129, 0.12);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #0f766e;
            font-weight: 600;
        }

        .subscription-pending {
            background: rgba(250, 204, 21, 0.12);
            border-color: rgba(234, 179, 8, 0.4);
            color: #92400e;
        }

        .success-icon,
        .pending-icon {
            font-size: 2rem;
        }

        .subscription-form {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            width: 100%;
        }

        .subscription-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1rem 1.5rem;
            width: 100%;
        }

        .subscription-form-grid label {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            font-weight: 600;
            color: #1f2937;
        }

        .subscription-form-grid select,
        .subscription-form-grid input,
        .subscription-form-grid textarea {
            padding: 0.8rem 1rem;
            border-radius: 12px;
            border: 1px solid #d1d5db;
            background: #f9fafb;
            font-size: 0.95rem;
            font-family: inherit;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .subscription-form-grid select:focus,
        .subscription-form-grid input:focus,
        .subscription-form-grid textarea:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
        }

        .subscription-form-grid .full-width {
            grid-column: 1/-1;
        }

        .subscription-btn {
            border: none;
            border-radius: 10px;
            padding: 0.75rem 1.75rem;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.95rem;
            transition: transform 0.2s ease, filter 0.2s ease;
            background: linear-gradient(135deg, #2563eb, #3b82f6);
            color: #ffffff;
            box-shadow: 0 12px 24px rgba(59, 130, 246, 0.3);
        }

        .subscription-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.05);
        }

        .payment-hint {
            margin: -0.5rem 0 0;
            color: #6b7280;
            font-size: 0.85rem;
        }

        .payment-alert {
            margin: 1.5rem auto;
            max-width: 960px;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            font-weight: 600;
            border: 1px solid transparent;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }

        .payment-alert.success {
            background: rgba(16, 185, 129, 0.15);
            border-color: rgba(16, 185, 129, 0.3);
            color: #047857;
        }

        .payment-alert.error {
            background: rgba(248, 113, 113, 0.15);
            border-color: rgba(239, 68, 68, 0.35);
            color: #b91c1c;
        }

        .subscription-chip {
            display: inline-block;
            margin-top: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .subscription-chip.premium {
            background: rgba(16, 185, 129, 0.15);
            color: #047857;
        }

        .subscription-chip.standard {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
        }

        .subscription-chip.expiry {
            background: rgba(14, 165, 233, 0.15);
            color: #0369a1;
            margin-left: 0.5rem;
        }
    </style>
</body>
</html>
