<?php
session_start();
include '../connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

$feedback = null;

function appendAdminNote(?string $existing, string $note): string
{
    if ($note === '') {
        return (string)$existing;
    }
    $existing = $existing ?? '';
    $prefix = $existing !== '' ? $existing . "\n" : '';
    return $prefix . 'Admin: ' . $note;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_id'], $_POST['action'])) {
    $payment_id = (int)$_POST['payment_id'];
    $action = $_POST['action'];
    $admin_note = trim($_POST['admin_note'] ?? '');

    $stmt = $conn->prepare("SELECT * FROM subscription_payments WHERE Payment_ID = ?");
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$payment) {
        $feedback = ['type' => 'error', 'text' => 'Payment record not found.'];
    } elseif (!in_array($action, ['approve', 'cancel'], true)) {
        $feedback = ['type' => 'error', 'text' => 'Unsupported action.'];
    } else {
        $user_table = $payment['User_Type'] === 'client' ? 'client' : 'technician';
        $id_field = $payment['User_Type'] === 'client' ? 'Client_ID' : 'Technician_ID';
        $user_stmt = $conn->prepare("SELECT Subscription_Expires FROM {$user_table} WHERE {$id_field} = ?");
        $user_stmt->bind_param("i", $payment['User_ID']);
        $user_stmt->execute();
        $user_row = $user_stmt->get_result()->fetch_assoc();
        $user_stmt->close();

        $conn->begin_transaction();
        try {
            if ($action === 'approve') {
                if ($payment['Status'] !== 'pending') {
                    throw new Exception('Only pending payments can be approved.');
                }

                $base_timestamp = time();
                if ($user_row && !empty($user_row['Subscription_Expires']) && $user_row['Subscription_Expires'] !== '0000-00-00 00:00:00') {
                    $current_expiry = strtotime($user_row['Subscription_Expires']);
                    if ($current_expiry > $base_timestamp) {
                        $base_timestamp = $current_expiry;
                    }
                }

                $expires_at = date('Y-m-d H:i:s', strtotime("+{$payment['Plan_Days']} days", $base_timestamp));
                $paid_at = date('Y-m-d H:i:s');
                $combined_notes = appendAdminNote($payment['Notes'], $admin_note);

                $update_payment = $conn->prepare("UPDATE subscription_payments SET Status = 'paid', Paid_At = ?, Expires_At = ?, Notes = ? WHERE Payment_ID = ?");
                $update_payment->bind_param("sssi", $paid_at, $expires_at, $combined_notes, $payment_id);
                $update_payment->execute();
                $update_payment->close();

                $activate_stmt = $conn->prepare("UPDATE {$user_table} SET Is_Subscribed = 1, Subscription_Expires = ? WHERE {$id_field} = ?");
                $activate_stmt->bind_param("si", $expires_at, $payment['User_ID']);
                $activate_stmt->execute();
                $activate_stmt->close();

                $feedback = ['type' => 'success', 'text' => 'Subscription activated successfully.'];
            } else {
                $combined_notes = appendAdminNote($payment['Notes'], $admin_note);

                $cancel_stmt = $conn->prepare("UPDATE subscription_payments SET Status = 'cancelled', Notes = ? WHERE Payment_ID = ?");
                $cancel_stmt->bind_param("si", $combined_notes, $payment_id);
                $cancel_stmt->execute();
                $cancel_stmt->close();

                $feedback = ['type' => 'info', 'text' => 'Payment marked as cancelled.'];
            }

            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            $feedback = ['type' => 'error', 'text' => $e->getMessage()];
        }
    }
}

$filters = [
    'status' => $_GET['status'] ?? 'pending',
    'user_type' => $_GET['type'] ?? 'all',
];

$where = [];
$params = [];
$types = '';

if ($filters['status'] !== 'all') {
    $where[] = 'sp.Status = ?';
    $types .= 's';
    $params[] = $filters['status'];
}

if ($filters['user_type'] !== 'all') {
    $where[] = 'sp.User_Type = ?';
    $types .= 's';
    $params[] = $filters['user_type'];
}

$where_sql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT sp.*, 
           CASE WHEN sp.User_Type = 'client' THEN c.Client_FN ELSE t.Technician_FN END AS FirstName,
           CASE WHEN sp.User_Type = 'client' THEN c.Client_LN ELSE t.Technician_LN END AS LastName,
           CASE WHEN sp.User_Type = 'client' THEN c.Client_Email ELSE t.Technician_Email END AS Email
    FROM subscription_payments sp
    LEFT JOIN client c ON sp.User_Type = 'client' AND sp.User_ID = c.Client_ID
    LEFT JOIN technician t ON sp.User_Type = 'technician' AND sp.User_ID = t.Technician_ID
    $where_sql
    ORDER BY sp.Created_At DESC
";

$stmt = $conn->prepare($sql);
if ($types !== '') {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payments = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscriptions - PinoyFix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
    <link rel="stylesheet" href="../css/manage_bookings.css">
    <style>
        body {
            background: #f1f5f9;
        }
        .subscriptions-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.1);
        }
        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.25rem;
        }
        .page-subtitle {
            color: #475569;
            margin-bottom: 2rem;
        }
        .filters {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .filters select {
            padding: 0.75rem 1rem;
            border-radius: 12px;
            border: 1px solid #cbd5f5;
            background: #f8fafc;
            font-weight: 600;
            color: #0f172a;
        }
        .payment-card {
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            box-shadow: 0 15px 35px rgba(15, 23, 42, 0.08);
        }
        .payment-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .payment-header h3 {
            font-size: 1.25rem;
            color: #0f172a;
            margin: 0;
        }
        .status-pill {
            padding: 0.35rem 0.75rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .status-pill.pending {
            background: rgba(234, 179, 8, 0.15);
            color: #92400e;
        }
        .status-pill.paid {
            background: rgba(34, 197, 94, 0.15);
            color: #047857;
        }
        .status-pill.cancelled {
            background: rgba(248, 113, 113, 0.15);
            color: #b91c1c;
        }
        .payment-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin: 1rem 0;
        }
        .payment-meta span {
            font-weight: 600;
            color: #1f2937;
        }
        .payment-meta small {
            color: #64748b;
            display: block;
            margin-top: 0.25rem;
        }
        .notes {
            background: rgba(59, 130, 246, 0.06);
            border-radius: 12px;
            padding: 1rem;
            margin-bottom: 1rem;
            color: #1e3a8a;
        }
        .action-form {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            margin-top: 1rem;
        }
        .action-form textarea {
            flex: 1 1 260px;
            min-height: 70px;
            border-radius: 12px;
            border: 1px solid #cbd5f5;
            padding: 0.75rem 1rem;
            background: #f8fafc;
            font-family: inherit;
        }
        .action-form button {
            border: none;
            border-radius: 12px;
            padding: 0.85rem 1.75rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-approve {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            box-shadow: 0 15px 30px rgba(5, 150, 105, 0.25);
        }
        .btn-cancel {
            background: linear-gradient(135deg, #f97316, #ef4444);
            color: white;
            box-shadow: 0 15px 30px rgba(239, 68, 68, 0.25);
        }
        .action-form button:hover {
            transform: translateY(-2px);
        }
        .feedback {
            margin-bottom: 1.5rem;
            padding: 0.85rem 1.2rem;
            border-radius: 12px;
            font-weight: 600;
        }
        .feedback.success {
            background: rgba(16, 185, 129, 0.15);
            color: #047857;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .feedback.error {
            background: rgba(248, 113, 113, 0.15);
            color: #b91c1c;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        .feedback.info {
            background: rgba(59, 130, 246, 0.15);
            color: #1d4ed8;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">Subscription Payments</h1>
                        <p class="subtitle">Review and approve customer and technician premium plans</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="admin-info">
                        <div class="admin-avatar"><span>A</span></div>
                        <div class="admin-details">
                            <p class="admin-name">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                            <p class="admin-role">System Administrator</p>
                        </div>
                    </div>
                    <a href="admin_dashboard.php" class="logout-btn"><span class="logout-icon">←</span> Back to Dashboard</a>
                </div>
            </div>
        </header>

        <main>
            <div class="subscriptions-container">
                <h1 class="page-title">Premium Subscription Payments</h1>
                <p class="page-subtitle">Approve paid plans to unlock priority matching and routing across the platform.</p>

                <?php if ($feedback): ?>
                    <div class="feedback <?php echo htmlspecialchars($feedback['type']); ?>">
                        <?php echo htmlspecialchars($feedback['text']); ?>
                    </div>
                <?php endif; ?>

                <form class="filters" method="GET">
                    <label>
                        <span>Status</span><br>
                        <select name="status" onchange="this.form.submit()">
                            <option value="all" <?php echo $filters['status'] === 'all' ? 'selected' : ''; ?>>All statuses</option>
                            <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="paid" <?php echo $filters['status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="cancelled" <?php echo $filters['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </label>
                    <label>
                        <span>User type</span><br>
                        <select name="type" onchange="this.form.submit()">
                            <option value="all" <?php echo $filters['user_type'] === 'all' ? 'selected' : ''; ?>>All users</option>
                            <option value="client" <?php echo $filters['user_type'] === 'client' ? 'selected' : ''; ?>>Clients</option>
                            <option value="technician" <?php echo $filters['user_type'] === 'technician' ? 'selected' : ''; ?>>Technicians</option>
                        </select>
                    </label>
                </form>

                <?php if ($payments->num_rows === 0): ?>
                    <p>No subscription payments found for the current filters.</p>
                <?php else: ?>
                    <?php while ($payment = $payments->fetch_assoc()): ?>
                        <div class="payment-card">
                            <div class="payment-header">
                                <h3><?php echo htmlspecialchars($payment['FirstName'] . ' ' . $payment['LastName']); ?> • <?php echo ucfirst($payment['User_Type']); ?></h3>
                                <span class="status-pill <?php echo htmlspecialchars($payment['Status']); ?>"><?php echo strtoupper($payment['Status']); ?></span>
                            </div>

                            <div class="payment-meta">
                                <div>
                                    <span>Amount</span>
                                    <small>₱<?php echo number_format($payment['Amount'], 2); ?> <?php echo htmlspecialchars($payment['Currency']); ?></small>
                                </div>
                                <div>
                                    <span>Plan</span>
                                    <small><?php echo (int)$payment['Plan_Days']; ?> days</small>
                                </div>
                                <div>
                                    <span>Submitted</span>
                                    <small><?php echo htmlspecialchars(date('M j, Y g:i A', strtotime($payment['Created_At']))); ?></small>
                                </div>
                                <div>
                                    <span>Reference</span>
                                    <small><?php echo htmlspecialchars($payment['Reference'] ?: 'N/A'); ?></small>
                                </div>
                                <div>
                                    <span>Paid At</span>
                                    <small><?php echo $payment['Paid_At'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($payment['Paid_At']))) : '—'; ?></small>
                                </div>
                                <div>
                                    <span>Expires At</span>
                                    <small><?php echo $payment['Expires_At'] ? htmlspecialchars(date('M j, Y g:i A', strtotime($payment['Expires_At']))) : '—'; ?></small>
                                </div>
                            </div>

                            <?php if (!empty($payment['Notes'])): ?>
                                <div class="notes">
                                    <?php echo nl2br(htmlspecialchars($payment['Notes'])); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($payment['Status'] === 'pending'): ?>
                                <form method="POST" class="action-form">
                                    <input type="hidden" name="payment_id" value="<?php echo (int)$payment['Payment_ID']; ?>">
                                    <textarea name="admin_note" placeholder="Add an optional admin note"></textarea>
                                    <div class="action-buttons">
                                        <button type="submit" name="action" value="approve" class="btn-approve">Approve & Activate</button>
                                        <button type="submit" name="action" value="cancel" class="btn-cancel">Mark as Cancelled</button>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
