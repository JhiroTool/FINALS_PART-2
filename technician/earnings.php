<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get technician info
$stmt = $conn->prepare("SELECT Technician_FN, Technician_LN, Service_Pricing FROM technician WHERE Technician_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$technician = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Earnings - PinoyFix Technician</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician_earnings.css">
</head>
<body>
    <div class="tech-container">
        <!-- Header -->
        <header class="tech-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">PinoyFix</h1>
                        <p class="subtitle">Earnings & Payments</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="technician_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <!-- Earnings Overview -->
        <section class="earnings-overview">
            <div class="overview-grid">
                <?php
                try {
                    // Calculate earnings stats
                    $completed_jobs = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Technician_ID = {$user_id} AND Status = 'completed'")->fetch_assoc()['count'];
                    $total_earnings = $completed_jobs * $technician['Service_Pricing'] * 2; // Assuming 2 hours average
                    $monthly_earnings = $total_earnings; // Simplified for demo
                    $pending_payment = $total_earnings * 0.1; // 10% pending
                } catch (Exception $e) {
                    $completed_jobs = 0;
                    $total_earnings = 0;
                    $monthly_earnings = 0;
                    $pending_payment = 0;
                }
                ?>
                
                <div class="earning-card total">
                    <div class="card-icon">💰</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($total_earnings); ?></h3>
                        <p>Total Earnings</p>
                    </div>
                </div>
                
                <div class="earning-card monthly">
                    <div class="card-icon">📊</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($monthly_earnings); ?></h3>
                        <p>This Month</p>
                    </div>
                </div>
                
                <div class="earning-card pending">
                    <div class="card-icon">⏳</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($pending_payment); ?></h3>
                        <p>Pending Payment</p>
                    </div>
                </div>
                
                <div class="earning-card rate">
                    <div class="card-icon">💵</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($technician['Service_Pricing']); ?></h3>
                        <p>Hourly Rate</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Earnings Chart Placeholder -->
        <section class="chart-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">📈</span>
                    Earnings Trend
                </h2>
            </div>
            <div class="chart-placeholder">
                <div class="chart-info">
                    <h3>📊 Earnings Analytics Coming Soon!</h3>
                    <p>We're working on detailed analytics to help you track your earnings better.</p>
                </div>
            </div>
        </section>

        <!-- Payment History -->
        <section class="payment-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">💳</span>
                    Payment History
                </h2>
            </div>
            
            <div class="payment-list">
                <?php if ($completed_jobs > 0): ?>
                    <?php for ($i = 1; $i <= min($completed_jobs, 5); $i++): ?>
                        <div class="payment-item">
                            <div class="payment-icon">✅</div>
                            <div class="payment-content">
                                <h4>Job Completion Payment #<?php echo str_pad($i, 3, '0', STR_PAD_LEFT); ?></h4>
                                <p>Payment for completed service</p>
                                <span class="payment-date"><?php echo date('M j, Y', strtotime("-{$i} days")); ?></span>
                            </div>
                            <div class="payment-amount">
                                +₱<?php echo number_format($technician['Service_Pricing'] * 2); ?>
                            </div>
                        </div>
                    <?php endfor; ?>
                <?php else: ?>
                    <div class="empty-payments">
                        <div class="empty-icon">💳</div>
                        <h3>No Payment History</h3>
                        <p>Complete jobs to start earning and see your payment history here.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Payment Methods -->
        <section class="payment-methods">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">🏦</span>
                    Payment Information
                </h2>
            </div>
            
            <div class="methods-grid">
                <div class="method-card">
                    <div class="method-icon">🏦</div>
                    <h3>Bank Transfer</h3>
                    <p>Direct deposit to your bank account</p>
                    <button class="method-btn">Set Up Bank</button>
                </div>
                
                <div class="method-card">
                    <div class="method-icon">📱</div>
                    <h3>GCash</h3>
                    <p>Instant payments via GCash wallet</p>
                    <button class="method-btn">Connect GCash</button>
                </div>
                
                <div class="method-card">
                    <div class="method-icon">💳</div>
                    <h3>PayMaya</h3>
                    <p>Quick transfers to PayMaya account</p>
                    <button class="method-btn">Link PayMaya</button>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Payment method setup (placeholder)
        document.querySelectorAll('.method-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const method = this.textContent.trim();
                alert(`${method} setup coming soon! You'll be able to configure your payment preferences.`);
            });
        });
    </script>
</body>
</html>