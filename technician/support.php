<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'technician') {
    header('Location: ../login.php');
    exit();
}

$technician_id = (int)$_SESSION['user_id'];

$tech_stmt = $conn->prepare('SELECT Technician_FN, Technician_LN, Technician_Email FROM technician WHERE Technician_ID = ? LIMIT 1');
$tech_stmt->bind_param('i', $technician_id);
$tech_stmt->execute();
$technician = $tech_stmt->get_result()->fetch_assoc();
$tech_stmt->close();

if (!$technician) {
    header('Location: ../logout.php');
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Center - PinoyFix</title>
    <link rel="stylesheet" href="../css/technician-dashboard.css">
    <style>
        .support-shell {
            padding: 6rem 0 3rem;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            min-height: 100vh;
        }

        .support-wrapper {
            background: #fff;
            border-radius: 24px;
            padding: 3rem;
            box-shadow: 0 18px 48px rgba(15, 23, 42, 0.1);
        }

        .support-wrapper h2 {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
        }

        .support-wrapper p.lead {
            color: #475569;
            margin-top: 0.75rem;
            font-size: 1.05rem;
        }

        .support-grid {
            margin-top: 2.5rem;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.75rem;
        }

        .support-card {
            padding: 1.8rem;
            background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid rgba(148, 163, 184, 0.25);
            border-radius: 18px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .support-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }

        .support-card p {
            color: #475569;
            line-height: 1.6;
        }

        .support-card a {
            color: #2563eb;
            font-weight: 600;
            text-decoration: none;
        }

        .support-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.14);
        }

        @media (max-width: 768px) {
            .support-wrapper {
                padding: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <a href="technician_dashboard.php" class="brand-link">
                        <img src="../images/pinoyfix.png" alt="PinoyFix" class="brand-logo">
                        <div>
                            <h1>PinoyFix</h1>
                            <span>Technician Support</span>
                        </div>
                    </a>
                </div>
                <div class="header-actions">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($technician['Technician_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($technician['Technician_FN'] . ' ' . $technician['Technician_LN']); ?></h3>
                            <p><?php echo htmlspecialchars($technician['Technician_Email']); ?></p>
                        </div>
                        <div class="user-buttons">
                            <a href="technician_dashboard.php" class="btn-secondary">← Dashboard</a>
                            <a href="../logout.php" class="btn-logout">🚪 Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="support-shell">
        <div class="container">
            <section class="support-wrapper">
                <h2>Need help with a job?</h2>
                <p class="lead">Reach the PinoyFix support team anytime.</p>
                <div class="support-grid">
                    <div class="support-card">
                        <h3>Live Chat</h3>
                        <p>Open the in-app chat to talk with our coordinators.</p>
                    </div>
                    <div class="support-card">
                        <h3>Email</h3>
                        <p>Send job concerns to <a href="mailto:support@pinoyfix.com">support@pinoyfix.com</a>.</p>
                    </div>
                    <div class="support-card">
                        <h3>Hotline</h3>
                        <p>Call (02) 8123-4567 for urgent escalations.</p>
                    </div>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
