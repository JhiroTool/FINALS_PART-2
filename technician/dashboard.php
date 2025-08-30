<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

$technician_id = $_SESSION['user_id'];

// Get assigned bookings
$assigned_bookings = $conn->prepare("
    SELECT b.*, c.Client_FN, c.Client_LN, c.Client_Phone, c.Client_Email
    FROM booking b 
    JOIN client c ON b.Client_ID = c.Client_ID 
    WHERE b.Technician_ID = ? 
    ORDER BY b.AptDate ASC
");
$assigned_bookings->bind_param("i", $technician_id);
$assigned_bookings->execute();
$bookings = $assigned_bookings->get_result();

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE booking SET Status = ? WHERE Booking_ID = ? AND Technician_ID = ?");
    $stmt->bind_param("sii", $new_status, $booking_id, $technician_id);
    
    if ($stmt->execute()) {
        echo "<script>alert('Status updated successfully!'); window.location.reload();</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Technician Dashboard - PinoyFix</title>
    <style>
        .booking-card {
            border: 1px solid #ddd;
            padding: 20px;
            margin: 10px 0;
            border-radius: 8px;
        }
        .assigned { border-left: 4px solid #2196F3; }
        .in-progress { border-left: 4px solid #FF9800; }
        .completed { border-left: 4px solid #4CAF50; }
    </style>
</head>
<body>
    <h1>My Assigned Jobs</h1>
    
    <?php if ($bookings->num_rows == 0): ?>
        <p>No bookings assigned to you yet.</p>
    <?php else: ?>
        <?php while ($booking = $bookings->fetch_assoc()): ?>
            <div class="booking-card <?php echo $booking['Status']; ?>">
                <h3>Job #<?php echo $booking['Booking_ID']; ?></h3>
                <p><strong>Client:</strong> <?php echo $booking['Client_FN'] . ' ' . $booking['Client_LN']; ?></p>
                <p><strong>Phone:</strong> <?php echo $booking['Client_Phone']; ?></p>
                <p><strong>Email:</strong> <?php echo $booking['Client_Email']; ?></p>
                <p><strong>Service:</strong> <?php echo $booking['Service_Type']; ?></p>
                <p><strong>Scheduled:</strong> <?php echo date('M d, Y g:i A', strtotime($booking['AptDate'])); ?></p>
                <p><strong>Description:</strong> <?php echo $booking['Description']; ?></p>
                <p><strong>Current Status:</strong> <?php echo ucfirst($booking['Status']); ?></p>
                
                <form method="POST" style="margin-top: 15px;">
                    <input type="hidden" name="booking_id" value="<?php echo $booking['Booking_ID']; ?>">
                    <select name="status">
                        <option value="assigned" <?php echo $booking['Status'] == 'assigned' ? 'selected' : ''; ?>>Assigned</option>
                        <option value="in-progress" <?php echo $booking['Status'] == 'in-progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="completed" <?php echo $booking['Status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                    <button type="submit" name="update_status">Update Status</button>
                </form>
            </div>
        <?php endwhile; ?>
    <?php endif; ?>
</body>
</html>