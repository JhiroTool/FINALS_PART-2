<?php
session_start();
include '../connection.php';

// Check if user is client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get client info
$stmt = $conn->prepare("SELECT Client_FN, Client_LN FROM client WHERE Client_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client_bookings.css">
</head>
<body>
    <div class="client-container">
        <!-- Header -->
        <header class="client-header">
            <div class="header-content">
                <div class="logo-section">
                    <div class="logo-circle">
                        <img src="../images/pinoyfix.png" alt="PinoyFix Logo" class="logo-img">
                    </div>
                    <div>
                        <h1 class="brand-title">PinoyFix</h1>
                        <p class="subtitle">My Bookings</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="client_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <!-- Bookings Sections -->
        <?php
        $statuses = [
            'pending' => ['title' => 'Pending Requests', 'icon' => '⏳', 'class' => 'pending'],
            'in-progress' => ['title' => 'In Progress', 'icon' => '🔧', 'class' => 'progress'],
            'completed' => ['title' => 'Completed Services', 'icon' => '✅', 'class' => 'completed']
        ];

        foreach ($statuses as $status => $config):
            try {
                $bookings_query = "
                    SELECT b.*, t.Technician_FN, t.Technician_LN, t.Technician_Phone, t.Specialization
                    FROM booking b 
                    LEFT JOIN technician t ON b.Technician_ID = t.Technician_ID 
                    WHERE b.Client_ID = ? AND b.Status = ? 
                    ORDER BY b.AptDate DESC
                ";
                
                $bookings_stmt = $conn->prepare($bookings_query);
                $bookings_stmt->bind_param("is", $user_id, $status);
                $bookings_stmt->execute();
                $bookings_result = $bookings_stmt->get_result();
                $booking_count = $bookings_result->num_rows;
        ?>
                <section class="bookings-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="section-icon <?php echo $config['class']; ?>"><?php echo $config['icon']; ?></span>
                            <?php echo $config['title']; ?>
                        </h2>
                        <div class="badge badge-<?php echo $config['class']; ?>">
                            <?php echo $booking_count; ?> bookings
                        </div>
                    </div>

                    <?php if ($booking_count > 0): ?>
                        <div class="bookings-grid">
                            <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                <div class="booking-card <?php echo $config['class']; ?>">
                                    <div class="booking-header">
                                        <div class="service-info">
                                            <h3><?php echo htmlspecialchars($booking['Service_Type']); ?></h3>
                                            <span class="booking-id">#<?php echo str_pad($booking['Booking_ID'], 4, '0', STR_PAD_LEFT); ?></span>
                                        </div>
                                        <div class="booking-status status-<?php echo $status; ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $status)); ?>
                                        </div>
                                    </div>

                                    <div class="booking-details">
                                        <div class="detail-row">
                                            <span class="label">📅 Scheduled:</span>
                                            <span class="value"><?php echo date('M j, Y g:i A', strtotime($booking['AptDate'])); ?></span>
                                        </div>
                                        
                                        <?php if (!empty($booking['Technician_FN'])): ?>
                                            <div class="detail-row">
                                                <span class="label">👨‍🔧 Technician:</span>
                                                <span class="value"><?php echo htmlspecialchars($booking['Technician_FN'] . ' ' . $booking['Technician_LN']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">📱 Contact:</span>
                                                <span class="value"><?php echo htmlspecialchars($booking['Technician_Phone']); ?></span>
                                            </div>
                                            <div class="detail-row">
                                                <span class="label">🔧 Specialty:</span>
                                                <span class="value"><?php echo htmlspecialchars($booking['Specialization']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="detail-row">
                                                <span class="label">👨‍🔧 Technician:</span>
                                                <span class="value pending-assignment">Waiting for assignment</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($booking['Description'])): ?>
                                            <div class="detail-row">
                                                <span class="label">📝 Description:</span>
                                                <span class="value"><?php echo htmlspecialchars($booking['Description']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="booking-actions">
                                        <?php if ($status === 'pending'): ?>
                                            <button class="btn-secondary" onclick="cancelBooking(<?php echo $booking['Booking_ID']; ?>)">
                                                ❌ Cancel
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if (!empty($booking['Technician_Phone'])): ?>
                                            <a href="tel:<?php echo $booking['Technician_Phone']; ?>" class="btn-primary">
                                                📞 Call Technician
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($status === 'completed'): ?>
                                            <button class="btn-accent" onclick="rateService(<?php echo $booking['Booking_ID']; ?>)">
                                                ⭐ Rate Service
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <div class="empty-icon"><?php echo $config['icon']; ?></div>
                            <h3>No <?php echo strtolower($config['title']); ?></h3>
                            <p>
                                <?php
                                switch($status) {
                                    case 'pending':
                                        echo 'You have no pending service requests. <a href="request_service.php">Request a service</a> to get started.';
                                        break;
                                    case 'in-progress':
                                        echo 'No services currently in progress.';
                                        break;
                                    case 'completed':
                                        echo 'No completed services yet.';
                                        break;
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </section>
        <?php
                $bookings_stmt->close();
            } catch (Exception $e) {
                echo "<p>Error loading bookings: " . $e->getMessage() . "</p>";
            }
        endforeach;
        ?>
    </div>

    <script>
        function cancelBooking(bookingId) {
            if (confirm('Are you sure you want to cancel this booking?')) {
                // Add AJAX call to cancel booking
                alert('Booking cancellation feature coming soon!');
            }
        }

        function rateService(bookingId) {
            // Add rating modal or redirect to rating page
            alert('Service rating feature coming soon!');
        }
    </script>
</body>
</html>