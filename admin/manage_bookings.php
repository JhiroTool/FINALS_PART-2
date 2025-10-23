<?php
session_start();
include '../connection.php';

function technicianCoversLocation($coverage, $city, $province, $barangay = '')
{
    if (!$coverage) {
        return false;
    }

    $coverage = strtolower($coverage);
    $city = strtolower((string) $city);
    $barangay = strtolower((string) $barangay);

    $matchSegment = function ($haystack, $needle) {
        if (!$needle) {
            return false;
        }

        $pattern = '/(^|\b|\s)' . preg_quote($needle, '/') . '($|\b|\s)/';
        return (bool) preg_match($pattern, $haystack);
    };

    if ($barangay && $matchSegment($coverage, $barangay)) {
        return true;
    }
    if ($city && $matchSegment($coverage, $city)) {
        return true;
    }

    $tokens = preg_split('/[,;|\\/]+/', $coverage);
    foreach ($tokens as $token) {
        $token = trim($token);
        if ($token === '') {
            continue;
        }
        if (in_array($token, ['all', 'anywhere', 'nationwide'], true)) {
            return true;
        }
        if ($barangay && $matchSegment($token, $barangay)) {
            return true;
        }
        if ($city && $matchSegment($token, $city)) {
            return true;
        }
    }

    return false;
}

$assignment_notice = $_SESSION['assignment_notice'] ?? null;
$assignment_notice_type = $_SESSION['assignment_notice_type'] ?? null;
unset($_SESSION['assignment_notice'], $_SESSION['assignment_notice_type']);

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get pending bookings
$pending_bookings = $conn->query("
    SELECT b.*, c.Client_FN, c.Client_LN, c.Client_Phone, c.Is_Subscribed, c.Subscription_Expires,
           addr.City AS Client_City, addr.Province AS Client_Province, addr.Barangay AS Client_Barangay
    FROM booking b 
    JOIN client c ON b.Client_ID = c.Client_ID 
    LEFT JOIN (
        SELECT ca.Client_ID,
               MAX(a.City) AS City,
               MAX(a.Province) AS Province,
               MAX(a.Barangay) AS Barangay
        FROM client_address ca
        JOIN address a ON ca.Address_ID = a.Address_ID
        GROUP BY ca.Client_ID
    ) addr ON addr.Client_ID = c.Client_ID
    WHERE b.Status = 'pending' 
    ORDER BY c.Is_Subscribed DESC,
             CASE WHEN c.Subscription_Expires IS NULL OR c.Subscription_Expires = '0000-00-00 00:00:00' OR c.Subscription_Expires > NOW() THEN 0 ELSE 1 END,
             b.AptDate ASC
");

// Get available technicians (only approved ones)
$technicians_result = $conn->query("SELECT * FROM technician WHERE Status = 'approved' ORDER BY Is_Subscribed DESC, Technician_ID ASC");
$technician_rows = [];
if ($technicians_result) {
    while ($row = $technicians_result->fetch_assoc()) {
        $technician_rows[] = $row;
    }
}

// Handle technician assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_technician'])) {
    $booking_id = isset($_POST['booking_id']) ? (int) $_POST['booking_id'] : 0;
    $technician_id = isset($_POST['technician_id']) ? (int) $_POST['technician_id'] : 0;

    $location_stmt = $conn->prepare("
        SELECT addr.City, addr.Province, addr.Barangay
        FROM booking b
        LEFT JOIN (
            SELECT ca.Client_ID,
                   MAX(a.City) AS City,
                   MAX(a.Province) AS Province,
                   MAX(a.Barangay) AS Barangay
            FROM client_address ca
            JOIN address a ON ca.Address_ID = a.Address_ID
            GROUP BY ca.Client_ID
        ) addr ON addr.Client_ID = b.Client_ID
        WHERE b.Booking_ID = ?
        LIMIT 1
    ");
    $location_stmt->bind_param("i", $booking_id);
    $location_stmt->execute();
    $location = $location_stmt->get_result()->fetch_assoc();
    $location_stmt->close();

    $tech_stmt = $conn->prepare("SELECT Service_Location FROM technician WHERE Technician_ID = ?");
    $tech_stmt->bind_param("i", $technician_id);
    $tech_stmt->execute();
    $tech_row = $tech_stmt->get_result()->fetch_assoc();
    $tech_stmt->close();

    if (!$booking_id || !$technician_id || !$location || !$tech_row || !technicianCoversLocation($tech_row['Service_Location'] ?? '', $location['City'] ?? '', $location['Province'] ?? '', $location['Barangay'] ?? '')) {
        $_SESSION['assignment_notice'] = 'Selected technician does not cover the client location.';
        $_SESSION['assignment_notice_type'] = 'error';
        header("Location: manage_bookings.php");
        exit();
    }

    $stmt = $conn->prepare("UPDATE booking SET Technician_ID = ?, Status = 'assigned' WHERE Booking_ID = ?");
    $stmt->bind_param("ii", $technician_id, $booking_id);

    if ($stmt->execute()) {
        $_SESSION['assignment_notice'] = 'Technician assigned successfully!';
        $_SESSION['assignment_notice_type'] = 'success';
    } else {
        $_SESSION['assignment_notice'] = 'Failed to assign technician. Please try again.';
        $_SESSION['assignment_notice_type'] = 'error';
    }
    $stmt->close();

    header("Location: manage_bookings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - PinoyFix Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin_dashboard.css">
    <link rel="stylesheet" href="../css/manage_bookings.css">
</head>
<body>
    <div class="admin-container">
        <!-- Header -->
        <header class="admin-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">Manage Bookings</h1>
                        <p class="subtitle">Assign technicians to service requests</p>
                    </div>
                </div>
                
                <div class="header-right">
                    <div class="admin-info">
                        <div class="admin-avatar">
                            <span>A</span>
                        </div>
                        <div class="admin-details">
                            <p class="admin-name">Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                            <p class="admin-role">System Administrator</p>
                        </div>
                    </div>
                    
                    <a href="admin_dashboard.php" class="logout-btn">
                        <span class="logout-icon">←</span>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </header>

        <!-- Stats Section -->
        <?php if ($assignment_notice): ?>
            <div class="admin-alert <?php echo htmlspecialchars($assignment_notice_type ?? 'info'); ?>">
                <?php echo htmlspecialchars($assignment_notice); ?>
            </div>
        <?php endif; ?>
        <section class="stats-section">
            <div class="stats-grid">
                <?php
                $pending_count = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Status = 'pending'")->fetch_assoc()['count'];
                $assigned_count = $conn->query("SELECT COUNT(*) as count FROM booking WHERE Status = 'assigned'")->fetch_assoc()['count'];
                $total_bookings = $conn->query("SELECT COUNT(*) as count FROM booking")->fetch_assoc()['count'];
                $available_techs = $conn->query("SELECT COUNT(*) as count FROM technician WHERE Status = 'approved'")->fetch_assoc()['count'];
                ?>
                
                <div class="stat-card pending">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-info">
                        <h3><?php echo $pending_count; ?></h3>
                        <p>Pending Bookings</p>
                    </div>
                </div>
                
                <div class="stat-card success">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $assigned_count; ?></h3>
                        <p>Assigned Bookings</p>
                    </div>
                </div>
                
                <div class="stat-card info">
                    <div class="stat-icon">📋</div>
                    <div class="stat-info">
                        <h3><?php echo $total_bookings; ?></h3>
                        <p>Total Bookings</p>
                    </div>
                </div>
                
                <div class="stat-card warning">
                    <div class="stat-icon">🔧</div>
                    <div class="stat-info">
                        <h3><?php echo $available_techs; ?></h3>
                        <p>Available Technicians</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bookings Section -->
        <section class="activity-section">
            <h2 class="section-title">
                <span class="section-icon">📋</span>
                Pending Bookings
            </h2>
            
            <?php if ($pending_bookings->num_rows > 0): ?>
                <?php while ($booking = $pending_bookings->fetch_assoc()): ?>
                    <div class="booking-card pending">
                        <div class="booking-header">
                            <h3 class="booking-id">Booking <?php echo $booking['Booking_ID']; ?></h3>
                            <span class="status-badge <?php echo $booking['Status']; ?>">
                                <?php echo ucfirst($booking['Status']); ?>
                            </span>
                        </div>
                        
                        <div class="booking-details">
                            <div class="detail-item">
                                <span class="detail-label">Client:</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($booking['Client_FN'] . ' ' . $booking['Client_LN']); ?>
                                    <?php
                                    $client_is_premium = (int)$booking['Is_Subscribed'] === 1 && (!isset($booking['Subscription_Expires']) || $booking['Subscription_Expires'] === '0000-00-00 00:00:00' || strtotime($booking['Subscription_Expires']) > time());
                                    if ($client_is_premium):
                                    ?>
                                        <span class="badge premium">Premium</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Phone:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['Client_Phone']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Service:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($booking['Service_Type']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Scheduled:</span>
                                <span class="detail-value"><?php echo date('M d, Y g:i A', strtotime($booking['AptDate'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Location:</span>
                                <span class="detail-value">
                                    <?php
                                    $client_location_parts = array_filter([
                                        $booking['Client_Barangay'] ?? null,
                                        $booking['Client_City'] ?? null,
                                        $booking['Client_Province'] ?? null
                                    ]);
                                    echo htmlspecialchars($client_location_parts ? implode(', ', $client_location_parts) : 'Not provided');
                                    ?>
                                </span>
                            </div>
                        </div>
                        
                        <?php if (!empty($booking['Description'])): ?>
                            <div class="description-section">
                                <h4>Service Description</h4>
                                <p><?php echo htmlspecialchars($booking['Description']); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($booking['Status'] == 'pending'): ?>
                            <div class="assignment-form">
                                <h4>Assign Technician</h4>
                                <form method="POST" class="form-group">
                                    <input type="hidden" name="booking_id" value="<?php echo $booking['Booking_ID']; ?>">
                                    <?php
                                    $client_city = $booking['Client_City'] ?? '';
                                    $client_province = $booking['Client_Province'] ?? '';
                                    $client_barangay = $booking['Client_Barangay'] ?? '';
                                    $matched_techs = [];
                                    foreach ($technician_rows as $tech) {
                                        if (technicianCoversLocation($tech['Service_Location'] ?? '', $client_city, $client_province, $client_barangay)) {
                                            $matched_techs[] = $tech;
                                        }
                                    }
                                    ?>
                                    <select name="technician_id" class="form-select" required <?php echo empty($matched_techs) ? 'disabled' : ''; ?>>
                                        <option value="">Select Available Technician</option>
                                        <?php foreach ($matched_techs as $tech): ?>
                                            <?php
                                            $tech_is_premium = (int) $tech['Is_Subscribed'] === 1 && (!isset($tech['Subscription_Expires']) || $tech['Subscription_Expires'] === '0000-00-00 00:00:00' || strtotime($tech['Subscription_Expires']) > time());
                                            ?>
                                            <option value="<?php echo $tech['Technician_ID']; ?>" <?php echo $tech_is_premium ? 'data-premium="1"' : ''; ?>>
                                                <?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?><?php echo $tech_is_premium ? ' 🌟' : ''; ?> · <?php echo htmlspecialchars($tech['Service_Location'] ?? ''); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($matched_techs)): ?>
                                        <p class="no-tech">No approved technicians cover this location.</p>
                                    <?php endif; ?>
                                    <button type="submit" name="assign_technician" class="assign-btn" <?php echo empty($matched_techs) ? 'disabled' : ''; ?>>
                                        Assign Technician
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>No Pending Bookings</h3>
                    <p>All service requests have been assigned to technicians.</p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>

<style>
    .badge.premium {
        display: inline-block;
        margin-left: 0.5rem;
        padding: 0.2rem 0.5rem;
        border-radius: 999px;
        background: rgba(16, 185, 129, 0.15);
        color: #047857;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .no-tech {
        margin-top: 0.75rem;
        color: #dc2626;
        font-weight: 600;
    }

    .admin-alert {
        margin: 1rem 0;
        padding: 1rem 1.25rem;
        border-radius: 12px;
        font-weight: 600;
    }

    .admin-alert.success {
        background: rgba(16, 185, 129, 0.15);
        color: #047857;
        border: 1px solid rgba(16, 185, 129, 0.3);
    }

    .admin-alert.error {
        background: rgba(239, 68, 68, 0.15);
        color: #991b1b;
        border: 1px solid rgba(239, 68, 68, 0.3);
    }
</style>