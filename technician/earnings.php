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

$wallet_balance = 0.00;
$total_paid = 0.00;
$monthly_paid = 0.00;
$pending_amount = 0.00;
$completed_jobs = 0;
$recent_payments = [];

try {
    $wallet_stmt = $conn->prepare("SELECT Balance FROM technician_wallet WHERE Technician_ID = ?");
    $wallet_stmt->bind_param("i", $user_id);
    $wallet_stmt->execute();
    $wallet_result = $wallet_stmt->get_result();
    if ($wallet_row = $wallet_result->fetch_assoc()) {
        $wallet_balance = (float)$wallet_row['Balance'];
    }
    $wallet_stmt->close();

    $sum_stmt = $conn->prepare("SELECT 
            COALESCE(SUM(CASE WHEN Status = 'paid' THEN Amount END), 0) AS total_paid,
            COALESCE(SUM(CASE WHEN Status = 'pending' THEN Amount END), 0) AS pending_amount
        FROM job_payments
        WHERE Technician_ID = ?");
    $sum_stmt->bind_param("i", $user_id);
    $sum_stmt->execute();
    $sum_result = $sum_stmt->get_result()->fetch_assoc();
    $sum_stmt->close();

    if ($sum_result) {
        $total_paid = (float)$sum_result['total_paid'];
        $pending_amount = (float)$sum_result['pending_amount'];
    }

    $monthly_stmt = $conn->prepare("SELECT COALESCE(SUM(Amount), 0) AS month_paid
        FROM job_payments
        WHERE Technician_ID = ? AND Status = 'paid' AND MONTH(Confirmed_At) = MONTH(CURDATE()) AND YEAR(Confirmed_At) = YEAR(CURDATE())");
    $monthly_stmt->bind_param("i", $user_id);
    $monthly_stmt->execute();
    $monthly_paid = (float)$monthly_stmt->get_result()->fetch_assoc()['month_paid'];
    $monthly_stmt->close();

    $count_stmt = $conn->prepare("SELECT COUNT(*) AS job_count FROM job_payments WHERE Technician_ID = ? AND Status = 'paid'");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $completed_jobs = (int)$count_stmt->get_result()->fetch_assoc()['job_count'];
    $count_stmt->close();

    $recent_stmt = $conn->prepare("SELECT jp.JobPayment_ID, jp.Amount, jp.Method, jp.Status, jp.Confirmed_At,
            b.Service_Type, b.Booking_ID, c.Client_FN, c.Client_LN
        FROM job_payments jp
        LEFT JOIN booking b ON jp.Booking_ID = b.Booking_ID
        LEFT JOIN client c ON b.Client_ID = c.Client_ID
        WHERE jp.Technician_ID = ?
        ORDER BY jp.Confirmed_At DESC, jp.JobPayment_ID DESC
        LIMIT 10");
    $recent_stmt->bind_param("i", $user_id);
    $recent_stmt->execute();
    $recent_payments = $recent_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $recent_stmt->close();
} catch (Exception $e) {
    error_log('Technician earnings error: ' . $e->getMessage());
}
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
                <div class="earning-card total">
                    <div class="card-icon">💰</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($total_paid, 2); ?></h3>
                        <p>Total Paid</p>
                    </div>
                </div>
                
                <div class="earning-card monthly">
                    <div class="card-icon">📊</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($monthly_paid, 2); ?></h3>
                        <p>This Month</p>
                    </div>
                </div>
                
                <div class="earning-card pending">
                    <div class="card-icon">⏳</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($pending_amount, 2); ?></h3>
                        <p>Pending Earnings</p>
                    </div>
                </div>
                
                <div class="earning-card rate">
                    <div class="card-icon">💵</div>
                    <div class="card-content">
                        <h3>₱<?php echo number_format($wallet_balance, 2); ?></h3>
                        <p>Wallet Balance</p>
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
                    Recent Payments
                </h2>
            </div>
            
            <div class="payment-list">
                <?php if (count($recent_payments) > 0): ?>
                    <?php foreach ($recent_payments as $payment): ?>
                        <div class="payment-item <?php echo $payment['Status'] === 'pending' ? 'pending' : 'paid'; ?>">
                            <div class="payment-icon"><?php echo $payment['Status'] === 'pending' ? '⏳' : '✅'; ?></div>
                            <div class="payment-content">
                                <h4><?php echo htmlspecialchars($payment['Service_Type'] ?? 'Service Payment'); ?> · #<?php echo str_pad($payment['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></h4>
                                <p>
                                    Client: <?php echo htmlspecialchars(trim(($payment['Client_FN'] ?? '') . ' ' . ($payment['Client_LN'] ?? '')) ?: 'N/A'); ?>
                                    · Method: <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $payment['Method']))); ?>
                                </p>
                                <span class="payment-date">Confirmed: <?php echo $payment['Confirmed_At'] ? date('M j, Y g:i A', strtotime($payment['Confirmed_At'])) : '—'; ?></span>
                            </div>
                            <div class="payment-amount">
                                <?php echo $payment['Status'] === 'pending' ? 'Pending' : '+₱' . number_format($payment['Amount'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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