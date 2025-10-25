<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    header('Location: ../login.php');
    exit();
}

$client_id = (int)$_SESSION['user_id'];

$client_stmt = $conn->prepare('SELECT Client_FN, Client_LN, Client_Email FROM client WHERE Client_ID = ? LIMIT 1');
$client_stmt->bind_param('i', $client_id);
$client_stmt->execute();
$client = $client_stmt->get_result()->fetch_assoc();
$client_stmt->close();

if (!$client) {
    header('Location: ../logout.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing & Payment - PinoyFix</title>
    <link rel="stylesheet" href="../css/client-dashboard-modern.css">
</head>
<body>
    <header class="modern-header">
        <div class="container">
            <div class="header-content">
                <div class="brand-section">
                    <div class="logo-container">
                        <img src="../images/pinoyfix.png" alt="PinoyFix" class="brand-logo">
                        <div class="brand-text">
                            <h1>PinoyFix</h1>
                            <span>Billing & Payment</span>
                        </div>
                    </div>
                </div>
                <div class="user-section">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN'] . ' ' . $client['Client_LN']); ?></h3>
                            <p><?php echo htmlspecialchars($client['Client_Email']); ?></p>
                        </div>
                        <div class="user-dropdown">
                            <a href="client_dashboard.php" class="btn-secondary">← Dashboard</a>
                            <a href="../logout.php" class="btn-logout">🚪 Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="dashboard-container">
        <div class="container">
            <section class="insights-section" style="margin-top: 2rem;">
                <h2 class="section-title">
                    <span class="title-icon">💳</span>
                    Billing Overview
                </h2>
                <div class="insights-grid">
                    <div class="insight-card">
                        <h4>Subscription Payments</h4>
                        <p>No billing records yet.</p>
                    </div>
                    <div class="insight-card communication">
                        <h4>Upcoming Invoices</h4>
                        <p>Stay tuned! Detailed invoice tracking is coming soon.</p>
                    </div>
                    <div class="insight-card feedback">
                        <h4>Need Assistance?</h4>
                        <p>Email <a href="mailto:support@pinoyfix.com">support@pinoyfix.com</a> for payment concerns.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
