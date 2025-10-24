<?php
session_start();
include '../connection.php';
require_once __DIR__ . '/../booking_workflow_helper.php';

// Check if user is client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

function getWorkflowStatus(mysqli $conn, int $bookingId): array
{
    $workflow = getBookingWorkflow($conn, $bookingId);
    if (!$workflow) {
        return [
            'technicianAccepted' => false,
            'clientConfirmed' => false,
            'technicianConfirmed' => false
        ];
    }
    return [
        'technicianAccepted' => (int)$workflow['Technician_Accepted'] === 1,
        'clientConfirmed' => (int)$workflow['Client_Confirmed'] === 1,
        'technicianConfirmed' => (int)$workflow['Technician_Confirmed'] === 1
    ];
}

$stmt = $conn->prepare("SELECT Client_FN, Client_LN FROM client WHERE Client_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
        $booking_id = intval($_POST['booking_id']);

        header('Content-Type: application/json');
        $cancel_stmt = $conn->prepare("UPDATE booking SET Status = 'cancelled' WHERE Booking_ID = ? AND Client_ID = ? AND Status IN ('pending','awaiting_acceptance','assigned')");
        $cancel_stmt->bind_param("ii", $booking_id, $user_id);

        if ($cancel_stmt->execute() && $cancel_stmt->affected_rows > 0) {
            removeBookingWorkflow($conn, $booking_id);
            echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to cancel booking']);
        }
        $cancel_stmt->close();
        exit();
    }

    if (isset($_POST['action']) && $_POST['action'] === 'confirm_completion') {
        $booking_id = intval($_POST['booking_id']);
        header('Content-Type: application/json');

        $stmt = $conn->prepare("SELECT Status FROM booking WHERE Booking_ID = ? AND Client_ID = ? LIMIT 1");
        $stmt->bind_param('ii', $booking_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($statusValue);
        if (!$stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Booking not found.']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        if (!in_array($statusValue, ['awaiting_acceptance', 'in_progress', 'awaiting_confirmation', 'assigned', 'pending'], true)) {
            echo json_encode(['success' => false, 'message' => 'This booking is already resolved.']);
            exit();
        }

        $result = markBookingConfirmation($conn, $booking_id, 'client');
        if ($result['success']) {
            echo json_encode(['success' => true, 'completed' => $result['completed']]);
        } else {
            echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Unable to confirm completion.']);
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client-dashboard-modern.css">
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
                            <span>My Service History</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search bookings..." class="search-input" id="bookingSearch">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="notification-bell" onclick="showNotification('📬 No new notifications', 'info')">
                        <span class="bell-icon">🔔</span>
                        <span class="notification-badge" id="notifBadge" style="display: none;">1</span>
                    </div>
                    
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN'] . ' ' . $client['Client_LN']); ?></h3>
                            <p>Premium Client</p>
                        </div>
                        <div class="user-dropdown">
                            <button class="dropdown-btn">⚙️</button>
                            <div class="dropdown-menu">
                                <a href="client_dashboard.php">🏠 Dashboard</a>
                                <a href="update_profile.php">👤 Profile Settings</a>
                                <a href="my_bookings.php">📋 My Bookings</a>
                                <a href="billing.php">💳 Billing & Payment</a>
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

    <div class="dashboard-container">
        <div class="container">
            <!-- Hero Section with Quick Stats -->
            <section class="dashboard-hero" style="background: linear-gradient(135deg, #0038A8 0%, #1e40af 50%, #3b82f6 100%); margin-bottom: 3rem;">
                <div class="hero-content">
                    <div class="welcome-text">
                        <h1>My Service History 📋</h1>
                        <p>Track all your repair requests, monitor progress, and manage your service bookings in one place.</p>
                    </div>
                    
                    <?php
                    // Get quick stats for hero
                    $stats_query = "
                        SELECT 
                            SUM(CASE WHEN Status IN ('awaiting_acceptance', 'pending', 'assigned') THEN 1 ELSE 0 END) as awaiting_acceptance_count,
                            SUM(CASE WHEN Status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_count,
                            SUM(CASE WHEN Status = 'awaiting_confirmation' THEN 1 ELSE 0 END) as awaiting_confirmation_count,
                            SUM(CASE WHEN Status = 'completed' THEN 1 ELSE 0 END) as completed_count,
                            COUNT(*) as total_count
                        FROM booking 
                        WHERE Client_ID = ?
                    ";
                    
                    $stats_stmt = $conn->prepare($stats_query);
                    $stats_stmt->bind_param("i", $user_id);
                    $stats_stmt->execute();
                    $stats = $stats_stmt->get_result()->fetch_assoc();
                    $stats_stmt->close();
                    ?>
                    
                    <div class="status-cards">
                        <div class="status-card">
                            <div class="card-icon">📊</div>
                            <div class="card-content">
                                <h3><?php echo $stats['total_count'] ?? 0; ?></h3>
                                <p>Total Services</p>
                                <span class="card-trend">All time</span>
                            </div>
                        </div>

                        <div class="status-card">
                            <div class="card-icon">⏳</div>
                            <div class="card-content">
                                <h3><?php echo $stats['awaiting_acceptance_count'] ?? 0; ?></h3>
                                <p>Awaiting Acceptance</p>
                                <span class="card-trend">Waiting for technician</span>
                            </div>
                        </div>

                        <div class="status-card">
                            <div class="card-icon">🔧</div>
                            <div class="card-content">
                                <h3><?php echo $stats['in_progress_count'] ?? 0; ?></h3>
                                <p>In Progress</p>
                                <span class="card-trend">Being worked on</span>
                            </div>
                        </div>

                        <div class="status-card">
                            <div class="card-icon">📝</div>
                            <div class="card-content">
                                <h3><?php echo $stats['awaiting_confirmation_count'] ?? 0; ?></h3>
                                <p>Awaiting Confirmation</p>
                                <span class="card-trend">Need your approval</span>
                            </div>
                        </div>

                        <div class="status-card">
                            <div class="card-icon">✅</div>
                            <div class="card-content">
                                <h3><?php echo $stats['completed_count'] ?? 0; ?></h3>
                                <p>Completed</p>
                                <span class="card-trend">Successfully finished</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bookings Sections -->
            <?php
            $statuses = [
                'awaiting_acceptance' => [
                    'title' => 'Awaiting Acceptance',
                    'icon' => '⏳',
                    'class' => 'pending',
                    'description' => 'Waiting for a technician to accept your request',
                    'filters' => ['awaiting_acceptance', 'pending', 'assigned']
                ],
                'in_progress' => [
                    'title' => 'Services In Progress',
                    'icon' => '🔧',
                    'class' => 'progress',
                    'description' => 'Currently being worked on',
                    'filters' => ['in_progress']
                ],
                'awaiting_confirmation' => [
                    'title' => 'Awaiting Confirmation',
                    'icon' => '📝',
                    'class' => 'progress',
                    'description' => 'Technician marked this job done. Please confirm once you are satisfied.',
                    'filters' => ['awaiting_confirmation']
                ],
                'completed' => [
                    'title' => 'Completed Services',
                    'icon' => '✅',
                    'class' => 'completed',
                    'description' => 'Successfully finished repairs',
                    'filters' => ['completed']
                ]
            ];

            foreach ($statuses as $status => $config):
                try {
                    $placeholders = implode(',', array_fill(0, count($config['filters']), '?'));
                    $bookings_query = "
                        SELECT b.*, t.Technician_FN, t.Technician_LN, t.Technician_Phone, t.Specialization, t.Service_Pricing,
                               jp.JobPayment_ID, jp.Amount AS JobPayment_Amount, jp.Method AS JobPayment_Method, jp.Status AS JobPayment_Status
                        FROM booking b 
                        LEFT JOIN technician t ON b.Technician_ID = t.Technician_ID 
                        LEFT JOIN job_payments jp ON jp.Booking_ID = b.Booking_ID
                        WHERE b.Client_ID = ? AND b.Status IN ($placeholders)
                        ORDER BY b.AptDate DESC
                    ";
                    
                    $bookings_stmt = $conn->prepare($bookings_query);
                    $types = 'i' . str_repeat('s', count($config['filters']));
                    $params = array_merge([$user_id], $config['filters']);
                    $bookings_stmt->bind_param($types, ...$params);
                    $bookings_stmt->execute();
                    $bookings_result = $bookings_stmt->get_result();
                    $booking_count = $bookings_result->num_rows;

                    $sectionThemes = [
                        'awaiting_acceptance' => [
                            'header_gradient' => 'linear-gradient(135deg, #fef3c7, #fde68a)',
                            'header_accent' => '#92400e',
                            'badge_gradient' => 'linear-gradient(135deg, #f59e0b, #d97706)',
                            'icon_background' => 'linear-gradient(135deg, #fef3c7, #fde68a)',
                            'icon_color' => '#92400e'
                        ],
                        'in_progress' => [
                            'header_gradient' => 'linear-gradient(135deg, #dbeafe, #bfdbfe)',
                            'header_accent' => '#1d4ed8',
                            'badge_gradient' => 'linear-gradient(135deg, #3b82f6, #2563eb)',
                            'icon_background' => 'linear-gradient(135deg, #dbeafe, #bfdbfe)',
                            'icon_color' => '#1d4ed8'
                        ],
                        'awaiting_confirmation' => [
                            'header_gradient' => 'linear-gradient(135deg, #fff7ed, #fde68a)',
                            'header_accent' => '#b45309',
                            'badge_gradient' => 'linear-gradient(135deg, #facc15, #d97706)',
                            'icon_background' => 'linear-gradient(135deg, #fff7ed, #fde68a)',
                            'icon_color' => '#b45309'
                        ],
                        'completed' => [
                            'header_gradient' => 'linear-gradient(135deg, #dcfce7, #bbf7d0)',
                            'header_accent' => '#047857',
                            'badge_gradient' => 'linear-gradient(135deg, #10b981, #059669)',
                            'icon_background' => 'linear-gradient(135deg, #dcfce7, #bbf7d0)',
                            'icon_color' => '#047857'
                        ]
                    ];

                    $sectionTheme = $sectionThemes[$status] ?? $sectionThemes['completed'];

                    $cardThemes = [
                        'awaiting_acceptance' => ['icon_background' => 'linear-gradient(135deg, #fef3c7, #fde68a)', 'icon_color' => '#92400e'],
                        'pending' => ['icon_background' => 'linear-gradient(135deg, #fef3c7, #fde68a)', 'icon_color' => '#92400e'],
                        'assigned' => ['icon_background' => 'linear-gradient(135deg, #fef3c7, #fde68a)', 'icon_color' => '#92400e'],
                        'in_progress' => ['icon_background' => 'linear-gradient(135deg, #dbeafe, #bfdbfe)', 'icon_color' => '#1d4ed8'],
                        'awaiting_confirmation' => ['icon_background' => 'linear-gradient(135deg, #fff7ed, #fde68a)', 'icon_color' => '#b45309'],
                        'completed' => ['icon_background' => 'linear-gradient(135deg, #dcfce7, #bbf7d0)', 'icon_color' => '#047857']
                    ];
            ?>
                    <section class="marketplace-actions" style="margin-bottom: 3rem;">
                        <div class="section-title-container" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; background: white; padding: 2rem; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); border-left: 6px solid <?php echo $sectionTheme['header_accent']; ?>;">
                            <div>
                                <h2 class="section-title">
                                    <span class="title-icon" style="background: <?php echo $sectionTheme['icon_background']; ?>; color: <?php echo $sectionTheme['icon_color']; ?>; width: 60px; height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                        <?php echo $config['icon']; ?>
                                    </span>
                                    <?php echo $config['title']; ?>
                                </h2>
                                <p style="color: #64748b; margin-top: 0.5rem;">&ZeroWidthSpace;<?php echo $config['description']; ?></p>
                            </div>
                            <div style="text-align: center;">
                                <div style="background: <?php echo $sectionTheme['badge_gradient']; ?>; color: white; padding: 12px 24px; border-radius: 20px; font-weight: 700; font-size: 1.1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
                                    <?php echo $booking_count; ?> service<?php echo $booking_count !== 1 ? 's' : ''; ?>
                                </div>
                            </div>
                        </div>
                        <?php if ($booking_count > 0): ?>
                            <div class="action-marketplace">
                                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                    <?php
                                    $workflow = getWorkflowStatus($conn, (int) $booking['Booking_ID']);
                                    $technicianAccepted = $workflow['technicianAccepted'];
                                    $clientConfirmed = $workflow['clientConfirmed'];
                                    $technicianConfirmed = $workflow['technicianConfirmed'];
                                    $currentStatus = $booking['Status'];
                                    ?>
                                    <?php $cardTheme = $cardThemes[$currentStatus] ?? $cardThemes['completed']; ?>
                                    <div class="action-item <?php echo $status === 'awaiting_acceptance' ? 'featured' : ''; ?>" data-booking-id="<?php echo $booking['Booking_ID']; ?>">
                                        <?php if ($currentStatus === 'awaiting_acceptance'): ?>
                                            <div class="action-badge">Awaiting technician</div>
                                        <?php elseif ($currentStatus === 'in_progress'): ?>
                                            <div class="action-badge" style="background: #dbeafe; color: #1d4ed8;">In progress</div>
                                        <?php elseif ($currentStatus === 'awaiting_confirmation'): ?>
                                            <div class="action-badge" style="background: #fef3c7; color: #92400e;">Action needed</div>
                                        <?php endif; ?>
                                        
                                        <div class="action-icon" style="background: <?php echo $cardTheme['icon_background']; ?>; color: <?php echo $cardTheme['icon_color']; ?>;">
                                            <?php echo $config['icon']; ?>
                                        </div>
                                        
                                        <div class="action-details">
                                            <h3><?php echo htmlspecialchars($booking['Service_Type']); ?></h3>
                                            <p>Booking ID: #<?php echo str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></p>
                                            
                                            <div class="action-meta">
                                                <span>📅 <?php echo date('M j, Y g:i A', strtotime($booking['AptDate'])); ?></span>
                                                <span>📍 <?php echo date('M j, Y', strtotime($booking['AptDate'])); ?></span>
                                            </div>
                                            
                                            <?php if (!empty($booking['Technician_FN'])): ?>
                                                <div style="background: rgba(0, 56, 168, 0.1); padding: 1rem; border-radius: 12px; margin: 1rem 0;">
                                                    <div style="font-weight: 600; color: #0038A8; margin-bottom: 0.5rem;">
                                                        👨‍🔧 <?php echo htmlspecialchars($booking['Technician_FN'] . ' ' . $booking['Technician_LN']); ?> ✅
                                                    </div>
                                                    <div style="font-family: monospace; color: #059669; font-weight: 600; margin-bottom: 0.25rem;">
                                                        📱 <?php echo htmlspecialchars($booking['Technician_Phone']); ?>
                                                    </div>
                                                    <div style="color: #64748b;">
                                                        🔧 <?php echo htmlspecialchars($booking['Specialization'] ?? 'General Repair'); ?>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div style="background: rgba(245, 158, 11, 0.1); padding: 1rem; border-radius: 12px; margin: 1rem 0; border-left: 4px solid #f59e0b;">
                                                    <div style="color: #f59e0b; font-weight: 600;">
                                                        🔍 Searching for available technician...
                                                    </div>
                                                    <small style="color: #94a3b8; display: block; margin-top: 0.25rem;">
                                                        Usually assigned within 30 minutes
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($booking['Description'])): ?>
                                                <div style="background: #f8fafc; padding: 0.75rem; border-radius: 8px; margin: 1rem 0; border-left: 3px solid #0038A8;">
                                                    <strong style="color: #374151;">📝 Description:</strong>
                                                    <p style="color: #64748b; font-style: italic; margin-top: 0.25rem; line-height: 1.5;">
                                                        <?php echo htmlspecialchars($booking['Description']); ?>
                                                    </p>
                                                </div>
                                            <?php endif; ?>

                                            <div style="display: flex; flex-direction: column; gap: 0.5rem;">
                                                <?php if ($currentStatus === 'awaiting_acceptance'): ?>
                                                    <div style="background: rgba(245, 158, 11, 0.12); border-radius: 10px; padding: 0.75rem 1rem; color: #92400e; font-weight: 600;">
                                                        <?php echo $technicianAccepted ? 'Technician accepted. Waiting for admin confirmation.' : 'Waiting for the technician to accept this job.'; ?>
                                                    </div>
                                                <?php elseif ($currentStatus === 'in_progress'): ?>
                                                    <div style="background: rgba(56, 189, 248, 0.12); border-radius: 10px; padding: 0.75rem 1rem; color: #0c4a6e; font-weight: 600;">
                                                        <?php echo $technicianAccepted ? 'Technician has accepted and is currently working on this service.' : 'Technician is scheduled—awaiting acceptance confirmation.'; ?>
                                                    </div>
                                                <?php elseif ($currentStatus === 'awaiting_confirmation'): ?>
                                                    <div style="background: rgba(250, 204, 21, 0.15); border-radius: 10px; padding: 0.75rem 1rem; color: #854d0e; font-weight: 600;">
                                                        <?php if ($clientConfirmed): ?>
                                                            You already confirmed completion. Waiting for system update.
                                                        <?php elseif ($technicianConfirmed): ?>
                                                            Technician confirmed the job is done. Please review and confirm completion.
                                                        <?php else: ?>
                                                            Waiting for both you and the technician to confirm completion.
                                                        <?php endif; ?>
                                                    </div>
                                                <?php elseif ($currentStatus === 'completed'): ?>
                                                    <div style="background: rgba(16, 185, 129, 0.12); border-radius: 10px; padding: 0.75rem 1rem; color: #047857; font-weight: 600;">
                                                        Service successfully marked as completed by both sides.
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($status === 'completed' && !empty($booking['JobPayment_ID'])): ?>
                                                <div style="background: rgba(16, 185, 129, 0.1); padding: 0.75rem; border-radius: 8px; margin: 1rem 0; border-left: 3px solid #10b981; color: #047857;">
                                                    <strong>💳 Payment Settled:</strong>
                                                    <span>₱<?php echo number_format((float)$booking['JobPayment_Amount'], 2); ?> via <?php echo htmlspecialchars(ucfirst($booking['JobPayment_Method'])); ?></span>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($status === 'completed' && !empty($booking['Rating'])): ?>
                                                <div style="background: rgba(245, 158, 11, 0.1); padding: 0.75rem; border-radius: 8px; margin: 1rem 0;">
                                                    <strong style="color: #374151;">⭐ Your Rating:</strong>
                                                    <span style="color: #f59e0b; font-size: 1.1rem; margin-left: 0.5rem;">
                                                        <?php 
                                                        $rating = intval($booking['Rating']);
                                                        echo str_repeat('⭐', $rating) . str_repeat('☆', 5 - $rating);
                                                        echo " ($rating/5)";
                                                        ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div style="display: flex; flex-direction: column; gap: 0.75rem; margin-top: 1.5rem;">
                                            <?php if (in_array($currentStatus, ['awaiting_acceptance', 'pending', 'assigned'], true)): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #ef4444, #dc2626);" onclick="cancelBooking(<?php echo $booking['Booking_ID']; ?>)">
                                                    ❌ Cancel Request
                                                </button>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($booking['Technician_Phone'])): ?>
                                                <a href="tel:<?php echo $booking['Technician_Phone']; ?>" class="action-btn">
                                                    📞 Call Technician
                                                </a>
                                            <?php endif; ?>

                                            <?php if ($currentStatus === 'in_progress'): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);" onclick="trackService(<?php echo $booking['Booking_ID']; ?>)">
                                                    📍 Track Progress
                                                </button>
                                            <?php endif; ?>

                                            <?php if ($currentStatus === 'awaiting_confirmation' && !$clientConfirmed): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #22c55e, #15803d);" onclick="confirmCompletion(<?php echo $booking['Booking_ID']; ?>)">
                                                    ✅ Confirm Completion
                                                </button>
                                            <?php elseif ($currentStatus === 'awaiting_confirmation' && $clientConfirmed): ?>
                                                <div class="action-btn" style="background: linear-gradient(135deg, #22c55e, #16a34a); cursor: default;">
                                                    ✅ You confirmed completion
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($status === 'completed' && empty($booking['JobPayment_ID'])): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #10b981, #047857);" onclick="openPaymentModal(<?php echo $booking['Booking_ID']; ?>, <?php echo json_encode((float)($booking['Service_Pricing'] ?? 0)); ?>)">
                                                    💳 Settle Payment
                                                </button>
                                            <?php elseif ($status === 'completed' && !empty($booking['JobPayment_ID'])): ?>
                                                <div class="action-btn" style="background: linear-gradient(135deg, #10b981, #059669); cursor: default;">
                                                    ✅ Paid via <?php echo htmlspecialchars($booking['JobPayment_Method']); ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($status === 'completed' && empty($booking['Rating'])): ?>
                                                <button class="action-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);" onclick="rateService(<?php echo $booking['Booking_ID']; ?>)">
                                                    ⭐ Rate Service
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="empty-activity">
                                <div class="empty-icon"><?php echo $config['icon']; ?></div>
                                <h3>No <?php echo strtolower($config['title']); ?></h3>
                                <p>
                                    <?php
                                    switch($status) {
                                        case 'pending':
                                            echo 'You have no pending service requests. Ready to get your appliances fixed?';
                                            break;
                                        case 'in_progress':
                                            echo 'No services currently in progress. Once a technician accepts your request, it will appear here with real-time updates.';
                                            break;
                                        case 'completed':
                                            echo 'No completed services yet. Your service history will appear here once repairs are finished.';
                                            break;
                                    }
                                    ?>
                                </p>
                                <?php if ($status === 'pending'): ?>
                                    <a href="request_service.php" class="empty-btn">
                                        🔧 Request Service Now
                                    </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </section>
            <?php
                    $bookings_stmt->close();
                } catch (Exception $e) {
                    echo "<div style='background: rgba(239, 68, 68, 0.1); border: 2px solid rgba(239, 68, 68, 0.3); color: #991b1b; padding: 1.5rem; border-radius: 16px; margin: 2rem; text-align: center; font-weight: 600;'>Error loading bookings: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            endforeach;
            ?>
        </div>
    </div>

    <div class="payment-modal" id="paymentModal">
        <div class="payment-modal-content">
            <button type="button" class="payment-close" onclick="closePaymentModal()">×</button>
            <h3>Settle Service Payment</h3>
            <form id="paymentForm">
                <div class="payment-field">
                    <label for="paymentAmount">Amount (₱)</label>
                    <input type="number" id="paymentAmount" name="amount" min="0" step="0.01" required>
                </div>
                <div class="payment-field">
                    <label for="paymentMethod">Payment Method</label>
                    <select id="paymentMethod" name="method" required>
                        <option value="">Select method</option>
                        <option value="cash">Cash</option>
                        <option value="gcash">GCash</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="paymaya">PayMaya</option>
                        <option value="other">Other</option>
                    </select>
                </div>
                <div class="payment-field">
                    <label for="paymentNotes">Notes (optional)</label>
                    <textarea id="paymentNotes" name="notes" rows="3" placeholder="Add reference numbers or remarks"></textarea>
                </div>
                <div class="payment-actions">
                    <button type="button" class="payment-btn secondary" onclick="closePaymentModal()">Cancel</button>
                    <button type="submit" class="payment-btn primary" id="paymentSubmitBtn">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modern Footer -->
    <footer class="modern-footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>PinoyFix</h4>
                    <p>Your trusted repair partner since 2025</p>
                </div>
                <div class="footer-section">
                    <h5>Need Help?</h5>
                    <a href="#">📞 Contact Support</a>
                    <a href="#">💬 Live Chat</a>
                    <a href="#">📧 Email Us</a>
                </div>
                <div class="footer-section">
                    <h5>Quick Links</h5>
                    <a href="client_dashboard.php">🏠 Dashboard</a>
                    <a href="request_service.php">🔧 New Request</a>
                    <a href="my_bookings.php">📋 My Bookings</a>
                </div>
                <div class="footer-section">
                    <h5>Service Guarantee</h5>
                    <p>🛡️ 30-day warranty</p>
                    <p>⭐ 100% satisfaction</p>
                    <p>🔒 Verified technicians</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines</p>
            </div>
        </div>
    </footer>

    <style>
        .payment-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 10000;
            padding: 1.5rem;
        }
        .payment-modal.show {
            display: flex;
        }
        .payment-modal-content {
            background: #fff;
            border-radius: 18px;
            padding: 2rem;
            width: min(420px, 100%);
            box-shadow: 0 25px 60px rgba(15, 23, 42, 0.25);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }
        .payment-modal-content h3 {
            margin: 0;
            font-size: 1.5rem;
            color: #0f172a;
        }
        .payment-close {
            position: absolute;
            top: 12px;
            right: 12px;
            border: none;
            background: transparent;
            font-size: 1.75rem;
            cursor: pointer;
            color: #94a3b8;
        }
        .payment-field {
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
        }
        .payment-field label {
            font-weight: 600;
            color: #1e293b;
        }
        .payment-field input,
        .payment-field select,
        .payment-field textarea {
            border-radius: 12px;
            border: 1px solid #cbd5f5;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .payment-field input:focus,
        .payment-field select:focus,
        .payment-field textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        }
        .payment-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 0.5rem;
        }
        .payment-btn {
            border: none;
            border-radius: 999px;
            padding: 0.75rem 1.75rem;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.95rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .payment-btn.primary {
            background: linear-gradient(135deg, #10b981, #047857);
            color: #fff;
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.2);
        }
        .payment-btn.secondary {
            background: #e2e8f0;
            color: #1e293b;
        }
        .payment-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .payment-btn:hover:not(:disabled) {
            transform: translateY(-1px);
        }
    </style>

    <script>
        // Fixed dropdown functionality
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownBtn = document.querySelector('.dropdown-btn');
            const dropdownMenu = document.querySelector('.dropdown-menu');
            const paymentModal = document.getElementById('paymentModal');
            const paymentForm = document.getElementById('paymentForm');
            const paymentAmountInput = document.getElementById('paymentAmount');
            const paymentMethodSelect = document.getElementById('paymentMethod');
            const paymentNotesInput = document.getElementById('paymentNotes');
            const paymentSubmitBtn = document.getElementById('paymentSubmitBtn');
            let activeBookingId = null;
            
            if (dropdownBtn && dropdownMenu) {
                dropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    dropdownMenu.classList.toggle('show');
                });

                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!dropdownBtn.contains(e.target) && !dropdownMenu.contains(e.target)) {
                        dropdownMenu.classList.remove('show');
                    }
                });
            }

            // Search functionality
            const searchInput = document.getElementById('bookingSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const searchTerm = e.target.value.toLowerCase();
                    const bookingCards = document.querySelectorAll('.action-item');
                    
                    bookingCards.forEach(card => {
                        const serviceType = card.querySelector('h3').textContent.toLowerCase();
                        const description = card.textContent.toLowerCase();
                        
                        if (serviceType.includes(searchTerm) || description.includes(searchTerm)) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Animate cards on load
            const actionItems = document.querySelectorAll('.action-item');
            actionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                    item.style.transition = 'all 0.6s ease';
                }, index * 100);
            });

            // Phone link notifications
            const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
            phoneLinks.forEach(link => {
                link.addEventListener('click', function() {
                    showNotification('📞 Opening your phone app to call the technician...', 'info');
                });
            });

            if (paymentForm) {
                paymentForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    if (!activeBookingId) {
                        return;
                    }

                    const amountValue = parseFloat(paymentAmountInput.value || '0');
                    if (!(amountValue > 0)) {
                        showNotification('❌ Enter a valid amount greater than zero', 'error');
                        return;
                    }

                    const methodValue = paymentMethodSelect.value.trim();
                    if (!methodValue) {
                        showNotification('❌ Select a payment method', 'error');
                        return;
                    }

                    paymentSubmitBtn.disabled = true;
                    paymentSubmitBtn.textContent = 'Processing...';

                    try {
                        const response = await fetch('settle_payment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: new URLSearchParams({
                                booking_id: activeBookingId,
                                amount: amountValue.toFixed(2),
                                method: methodValue,
                                notes: paymentNotesInput.value.trim()
                            })
                        });

                        if (!response.ok) {
                            throw new Error('Unable to record payment');
                        }

                        const result = await response.json();
                        if (!result.success) {
                            throw new Error(result.message || 'Payment failed');
                        }

                        showNotification('✅ Payment recorded successfully', 'success');
                        closePaymentModal();
                        setTimeout(() => window.location.reload(), 1200);
                    } catch (error) {
                        showNotification(`❌ ${error.message}`, 'error');
                    } finally {
                        paymentSubmitBtn.disabled = false;
                        paymentSubmitBtn.textContent = 'Confirm Payment';
                    }
                });
            }

            window.openPaymentModal = function(bookingId, suggestedAmount) {
                activeBookingId = bookingId;
                paymentAmountInput.value = suggestedAmount && suggestedAmount > 0 ? suggestedAmount.toFixed(2) : '';
                paymentMethodSelect.value = '';
                paymentNotesInput.value = '';
                paymentModal.classList.add('show');
            };

            window.closePaymentModal = function() {
                activeBookingId = null;
                paymentModal.classList.remove('show');
            };

            paymentModal.addEventListener('click', function(e) {
                if (e.target === paymentModal) {
                    closePaymentModal();
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && paymentModal.classList.contains('show')) {
                    closePaymentModal();
                }
            });
        });

        // Enhanced cancel booking function with AJAX
        async function cancelBooking(bookingId) {
            if (!confirm('⚠️ Are you sure you want to cancel this booking?\n\nThis action cannot be undone.')) {
                return;
            }

            const card = document.querySelector(`[data-booking-id="${bookingId}"]`);
            const cancelBtn = card.querySelector('button[onclick*="cancelBooking"]');
            
            // Show loading state
            cancelBtn.disabled = true;
            cancelBtn.innerHTML = '⏳ Cancelling...';
            cancelBtn.style.background = '#94a3b8';

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=cancel_booking&booking_id=${bookingId}`
                });

                const result = await response.json();

                if (result.success) {
                    // Success animation
                    card.style.transform = 'scale(0.95)';
                    card.style.opacity = '0.7';
                    
                    showNotification('✅ Booking cancelled successfully!', 'success');
                    
                    // Remove card after animation
                    setTimeout(() => {
                        card.remove();
                        // Update stats if needed
                        location.reload();
                    }, 1000);
                } else {
                    throw new Error(result.message || 'Failed to cancel booking');
                }
            } catch (error) {
                console.error('Error:', error);
                showNotification(`❌ Error: ${error.message}`, 'error');
                
                // Reset button state
                cancelBtn.disabled = false;
                cancelBtn.innerHTML = '❌ Cancel Request';
                cancelBtn.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            }
        }

        async function confirmCompletion(bookingId) {
            const card = document.querySelector(`[data-booking-id="${bookingId}"]`);
            if (!card) {
                showNotification('❌ Unable to locate this booking card.', 'error');
                return;
            }

            if (!confirm('✅ Confirm this service is complete?\n\nOnly do this after verifying the work has been finished to your satisfaction.')) {
                return;
            }

            const confirmBtn = card.querySelector('button[onclick*="confirmCompletion"]');
            if (confirmBtn) {
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = '⏳ Sending confirmation...';
                confirmBtn.style.background = 'linear-gradient(135deg, #94a3b8, #64748b)';
            }

            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=confirm_completion&booking_id=${encodeURIComponent(bookingId)}`
                });

                if (!response.ok) {
                    throw new Error('Server error while confirming.');
                }

                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.message || 'Confirmation failed.');
                }

                showNotification(result.completed ? '🎉 Service completed! Thank you for confirming.' : '👍 Confirmation saved. Awaiting technician update.', 'success');

                setTimeout(() => window.location.reload(), 1000);
            } catch (error) {
                showNotification(`❌ ${error.message}`, 'error');
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '✅ Confirm Completion';
                    confirmBtn.style.background = 'linear-gradient(135deg, #22c55e, #15803d)';
                }
            }
        }

        function trackService(bookingId) {
            showNotification('📍 Service tracking feature coming soon! You can call your technician for real-time updates.', 'info');
        }

        function rateService(bookingId) {
            const rating = prompt('⭐ Rate this service (1-5 stars):\n\n1 = Poor\n2 = Fair\n3 = Good\n4 = Very Good\n5 = Excellent');
            
            if (rating && rating >= 1 && rating <= 5) {
                showNotification(`⭐ Thank you for rating this service ${rating}/5 stars! Your feedback helps us improve.`, 'success');
                // TODO: Send rating to server
            } else if (rating !== null) {
                showNotification('❌ Please enter a valid rating (1-5)', 'error');
            }
        }

        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notif => notif.remove());

            const notification = document.createElement('div');
            notification.className = 'custom-notification';
            notification.textContent = message;
            
            Object.assign(notification.style, {
                position: 'fixed',
                top: '100px',
                right: '20px',
                padding: '16px 24px',
                borderRadius: '12px',
                color: 'white',
                fontWeight: '600',
                zIndex: '9999',
                maxWidth: '400px',
                boxShadow: '0 8px 32px rgba(0,0,0,0.2)',
                transform: 'translateX(450px)',
                transition: 'all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275)',
                cursor: 'pointer'
            });

            const colors = {
                success: 'linear-gradient(135deg, #10b981, #059669)',
                error: 'linear-gradient(135deg, #ef4444, #dc2626)',
                info: 'linear-gradient(135deg, #3b82f6, #2563eb)',
                warning: 'linear-gradient(135deg, #f59e0b, #d97706)'
            };
            
            notification.style.background = colors[type] || colors.info;
            document.body.appendChild(notification);
            
            // Slide in
            setTimeout(() => notification.style.transform = 'translateX(0)', 100);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(450px)';
                setTimeout(() => notification.remove(), 400);
            }, 5000);

            // Click to dismiss
            notification.addEventListener('click', () => {
                notification.style.transform = 'translateX(450px)';
                setTimeout(() => notification.remove(), 400);
            });
        }
    </script>
</body>
</html>