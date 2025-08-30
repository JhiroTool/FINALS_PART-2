<?php
session_start();
include '../connection.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get pending bookings
$pending_bookings = $conn->query("
    SELECT b.*, c.Client_FN, c.Client_LN, c.Client_Phone 
    FROM booking b 
    JOIN client c ON b.Client_ID = c.Client_ID 
    WHERE b.Status = 'pending' 
    ORDER BY b.AptDate ASC
");

// Get available technicians (only approved ones)
$technicians = $conn->query("SELECT * FROM technician WHERE Status = 'approved'");

// Handle technician assignment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['assign_technician'])) {
    $booking_id = $_POST['booking_id'];
    $technician_id = $_POST['technician_id'];
    
    $stmt = $conn->prepare("UPDATE booking SET Technician_ID = ?, Status = 'assigned' WHERE Booking_ID = ?");
    $stmt->bind_param("ii", $technician_id, $booking_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Technician assigned successfully!'); window.location.reload();</script>";
    }
    $stmt->close();
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
                                <span class="detail-value"><?php echo htmlspecialchars($booking['Client_FN'] . ' ' . $booking['Client_LN']); ?></span>
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
                                    <select name="technician_id" class="form-select" required>
                                        <option value="">Select Available Technician</option>
                                        <?php 
                                        $technicians->data_seek(0); // Reset pointer
                                        while ($tech = $technicians->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $tech['Technician_ID']; ?>">
                                                <?php echo htmlspecialchars($tech['Technician_FN'] . ' ' . $tech['Technician_LN']); ?> 
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                    <button type="submit" name="assign_technician" class="assign-btn">
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