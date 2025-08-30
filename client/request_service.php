<?php
// Add these lines at the very top for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
include '../connection.php';

// Check if user is client
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'client') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Get client info
$stmt = $conn->prepare("SELECT Client_FN, Client_LN FROM client WHERE Client_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_type = trim($_POST['service_type']);
    $apt_date = $_POST['apt_date'];
    $description = trim($_POST['description']);
    
    // Insert booking
    $booking_stmt = $conn->prepare("
        INSERT INTO booking (Client_ID, Service_Type, AptDate, Status, Description) 
        VALUES (?, ?, ?, 'pending', ?)
    ");
    $booking_stmt->bind_param("isss", $user_id, $service_type, $apt_date, $description);
    
    if ($booking_stmt->execute()) {
        $message = "Service request submitted successfully! We'll assign a technician soon.";
        $messageType = "success";
    } else {
        $message = "Error submitting service request. Please try again.";
        $messageType = "error";
    }
    $booking_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - PinoyFix</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/client_request.css">
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
                        <p class="subtitle">Request Service</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="client_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
                    <a href="../logout.php" class="btn-logout">Logout</a>
                </div>
            </div>
        </header>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Request Form -->
        <section class="request-section">
            <div class="section-header">
                <h2 class="section-title">
                    <span class="section-icon">🔧</span>
                    Request Repair Service
                </h2>
                <p>Tell us about your appliance issue and we'll connect you with the right technician.</p>
            </div>

            <form method="POST" class="request-form">
                <!-- Service Type -->
                <div class="form-section">
                    <h3 class="form-section-title">Service Information</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="service_type">What needs to be repaired?</label>
                            <select id="service_type" name="service_type" required>
                                <option value="">Select service type</option>
                                <option value="Air Conditioning Repair">Air Conditioning Repair</option>
                                <option value="Refrigerator Repair">Refrigerator Repair</option>
                                <option value="Washing Machine Repair">Washing Machine Repair</option>
                                <option value="Television Repair">Television Repair</option>
                                <option value="Microwave Repair">Microwave Repair</option>
                                <option value="Electric Fan Repair">Electric Fan Repair</option>
                                <option value="Rice Cooker Repair">Rice Cooker Repair</option>
                                <option value="Laptop Repair">Laptop Repair</option>
                                <option value="Mobile Phone Repair">Mobile Phone Repair</option>
                                <option value="Water Heater Repair">Water Heater Repair</option>
                                <option value="Oven Repair">Oven Repair</option>
                                <option value="Dishwasher Repair">Dishwasher Repair</option>
                                <option value="Other Electronics">Other Electronics</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Schedule -->
                <div class="form-section">
                    <h3 class="form-section-title">Preferred Schedule</h3>
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="apt_date">Preferred Date & Time</label>
                            <input type="datetime-local" id="apt_date" name="apt_date" required 
                                   min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>">
                        </div>
                    </div>
                </div>

                <!-- Problem Description -->
                <div class="form-section">
                    <h3 class="form-section-title">Problem Description</h3>
                    <div class="form-grid">
                        <div class="form-group full-width">
                            <label for="description">Describe the problem in detail</label>
                            <textarea id="description" name="description" rows="5" 
                                      placeholder="Please describe what's wrong with your appliance, any error messages, sounds, or symptoms you've noticed..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Service Areas Info -->
                <div class="info-section">
                    <div class="info-card">
                        <h4>📍 Service Areas</h4>
                        <p>We currently serve Metro Manila, Rizal, Cavite, Laguna, and Bulacan. More areas coming soon!</p>
                    </div>
                    <div class="info-card">
                        <h4>⏰ Service Hours</h4>
                        <p>Monday to Saturday: 8:00 AM - 6:00 PM<br>Sunday: 9:00 AM - 5:00 PM</p>
                    </div>
                    <div class="info-card">
                        <h4>💰 Pricing</h4>
                        <p>Service fee starts at ₱200. Final cost depends on the repair needed and parts required.</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        🔧 Submit Service Request
                    </button>
                </div>
            </form>
        </section>
    </div>

    <script>
        // Auto-hide alerts
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-20px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);

        // Form validation
        document.querySelector('.request-form').addEventListener('submit', function(e) {
            const serviceType = document.getElementById('service_type').value;
            const aptDate = document.getElementById('apt_date').value;
            
            if (!serviceType || !aptDate) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return;
            }
            
            // Check if appointment is at least 1 hour from now
            const selectedDate = new Date(aptDate);
            const minDate = new Date(Date.now() + 60 * 60 * 1000); // 1 hour from now
            
            if (selectedDate < minDate) {
                e.preventDefault();
                alert('Please select a date and time at least 1 hour from now.');
                return;
            }
        });
    </script>
</body>
</html>