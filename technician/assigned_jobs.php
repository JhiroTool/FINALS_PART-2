<?php
session_start();
include '../connection.php';

// Check if user is technician
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'technician') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$messageType = '';

// Handle status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['new_status'];
    
    $stmt = $conn->prepare("UPDATE booking SET Status = ? WHERE Booking_ID = ? AND Technician_ID = ?");
    $stmt->bind_param("sii", $new_status, $booking_id, $user_id);
    
    if ($stmt->execute()) {
        $message = "Job status updated successfully!";
        $messageType = "success";
    } else {
        $message = "Error updating job status.";
        $messageType = "error";
    }
    $stmt->close();
}

// Get technician info
$tech_stmt = $conn->prepare("SELECT Technician_FN, Technician_LN FROM technician WHERE Technician_ID = ?");
$tech_stmt->bind_param("i", $user_id);
$tech_stmt->execute();
$tech_result = $tech_stmt->get_result();
$technician = $tech_result->fetch_assoc();
$tech_stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assigned Jobs - PinoyFix Technician</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/technician_jobs.css">
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
                        <p class="subtitle">Job Management</p>
                    </div>
                </div>
                <div class="header-actions">
                    <a href="technician_dashboard.php" class="btn-secondary">← Back to Dashboard</a>
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

        <!-- Jobs Sections -->
        <?php
        $statuses = [
            'assigned' => ['title' => 'New Assignments', 'icon' => '🆕', 'class' => 'assigned'],
            'in_progress' => ['title' => 'In Progress', 'icon' => '🔧', 'class' => 'progress'],
            'completed' => ['title' => 'Completed Jobs', 'icon' => '✅', 'class' => 'completed']
        ];

        foreach ($statuses as $status => $config):
            try {
                $jobs_query = "
                    SELECT b.*, c.Client_FN, c.Client_LN, c.Client_Phone, c.Client_Email,
                           a.Street, a.Barangay, a.City, a.Province
                    FROM booking b 
                    LEFT JOIN client c ON b.Client_ID = c.Client_ID 
                    LEFT JOIN client_address ca ON c.Client_ID = ca.Client_ID
                    LEFT JOIN address a ON ca.Address_ID = a.Address_ID
                    WHERE b.Technician_ID = ? AND b.Status = ? 
                    ORDER BY b.AptDate ASC
                ";
                
                $jobs_stmt = $conn->prepare($jobs_query);
                $jobs_stmt->bind_param("is", $user_id, $status);
                $jobs_stmt->execute();
                $jobs_result = $jobs_stmt->get_result();
                $job_count = $jobs_result->num_rows;
        ?>
                <section class="jobs-section">
                    <div class="section-header">
                        <h2 class="section-title">
                            <span class="section-icon <?php echo $config['class']; ?>"><?php echo $config['icon']; ?></span>
                            <?php echo $config['title']; ?>
                        </h2>
                        <div class="badge badge-<?php echo $config['class']; ?>">
                            <?php echo $job_count; ?> jobs
                        </div>
                    </div>

                    <?php if ($job_count > 0): ?>
                        <div class="jobs-grid">
                            <?php while ($job = $jobs_result->fetch_assoc()): ?>
                                <div class="job-card <?php echo $config['class']; ?>">
                                    <div class="job-header">
                                        <div class="client-info">
                                            <div class="client-avatar">
                                                <span><?php echo strtoupper(substr($job['Client_FN'], 0, 1)); ?></span>
                                            </div>
                                            <div>
                                                <h3><?php echo htmlspecialchars($job['Client_FN'] . ' ' . $job['Client_LN']); ?></h3>
                                                <p class="client-contact"><?php echo htmlspecialchars($job['Client_Phone']); ?></p>
                                            </div>
                                        </div>
                                        <div class="job-status status-<?php echo $status; ?>">
                                            <?php echo ucfirst(str_replace('-', ' ', $status)); ?>
                                        </div>
                                    </div>

                                    <div class="job-details">
                                        <div class="detail-row">
                                            <span class="label">Service:</span>
                                            <span class="value"><?php echo htmlspecialchars($job['Service_Type']); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">Date:</span>
                                            <span class="value"><?php echo date('M j, Y g:i A', strtotime($job['AptDate'])); ?></span>
                                        </div>
                                        <div class="detail-row">
                                            <span class="label">Location:</span>
                                            <span class="value">
                                                <?php 
                                                $address_parts = array_filter([
                                                    $job['Street'], 
                                                    $job['Barangay'], 
                                                    $job['City'], 
                                                    $job['Province']
                                                ]);
                                                echo htmlspecialchars(implode(', ', $address_parts) ?: 'Address not provided');
                                                ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($job['Description'])): ?>
                                            <div class="detail-row">
                                                <span class="label">Notes:</span>
                                                <span class="value"><?php echo htmlspecialchars($job['Description']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="job-actions">
                                        <?php if ($status === 'assigned'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $job['Booking_ID']; ?>">
                                                <input type="hidden" name="new_status" value="in_progress">
                                                <button type="submit" name="update_status" class="btn-primary">
                                                    Start Job
                                                </button>
                                            </form>
                                        <?php elseif ($status === 'in_progress'): ?>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="booking_id" value="<?php echo $job['Booking_ID']; ?>">
                                                <input type="hidden" name="new_status" value="completed">
                                                <button type="submit" name="update_status" class="btn-success">
                                                    Mark Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="tel:<?php echo $job['Client_Phone']; ?>" class="btn-secondary">
                                            📞 Call Client
                                        </a>
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
                                    case 'assigned':
                                        echo 'No new job assignments at the moment.';
                                        break;
                                    case 'in_progress':
                                        echo 'No jobs currently in progress.';
                                        break;
                                    case 'completed':
                                        echo 'No completed jobs yet.';
                                        break;
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </section>
        <?php
                $jobs_stmt->close();
            } catch (Exception $e) {
                echo "<p>Error loading jobs: " . $e->getMessage() . "</p>";
            }
        endforeach;
        ?>
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

        // Confirm status updates
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const action = this.querySelector('button').textContent.trim();
                if (!confirm(`Are you sure you want to ${action.toLowerCase()}?`)) {
                    e.preventDefault();
                }
            });
        });
    </script>
</body>
</html>