<?php
// Add these lines at the very top for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$service_type_input = '';
$technician_id_input = '';
$apt_date_input = '';
$description_input = '';
$priority_input = 'normal';

// Service categories with icons and descriptions
$service_categories = [
    'Air Conditioning Repair' => ['icon' => '❄️', 'desc' => 'AC cleaning, freon refill, compressor repair'],
    'Refrigerator Repair' => ['icon' => '🧊', 'desc' => 'Cooling issues, defrosting, compressor problems'],
    'Washing Machine Repair' => ['icon' => '🧺', 'desc' => 'Not spinning, water leaks, motor issues'],
    'Television Repair' => ['icon' => '📺', 'desc' => 'Screen problems, no display, audio issues'],
    'Microwave Repair' => ['icon' => '🔥', 'desc' => 'Not heating, turntable, door problems'],
    'Electric Fan Repair' => ['icon' => '🌪️', 'desc' => 'Motor repair, blade replacement, speed control'],
    'Rice Cooker Repair' => ['icon' => '🍚', 'desc' => 'Not cooking, overheating, switch problems'],
    'Laptop Repair' => ['icon' => '💻', 'desc' => 'Screen, keyboard, battery, performance issues'],
    'Mobile Phone Repair' => ['icon' => '📱', 'desc' => 'Screen crack, battery, charging port'],
    'Water Heater Repair' => ['icon' => '🚿', 'desc' => 'Not heating, leaks, thermostat issues'],
    'Oven Repair' => ['icon' => '🔥', 'desc' => 'Temperature control, door, heating elements'],
    'Dishwasher Repair' => ['icon' => '🍽️', 'desc' => 'Not draining, not cleaning, pump issues'],
    'Appliance Installation' => ['icon' => '🧰', 'desc' => 'Professional setup of new appliances and fixtures'],
    'Electrical Wiring & Installation' => ['icon' => '⚡', 'desc' => 'Wiring upgrades, breaker panels, lighting installs'],
    'Plumbing Leak Repair' => ['icon' => '🚰', 'desc' => 'Pipe leaks, clog removal, faucet replacement'],
    'Carpentry & Woodwork' => ['icon' => '🪵', 'desc' => 'Cabinet repairs, shelving, custom wood projects'],
    'General Home Maintenance' => ['icon' => '🛠️', 'desc' => 'Handyman tasks, preventive maintenance, inspections'],
    'CCTV & Security Installation' => ['icon' => '📹', 'desc' => 'Camera setup, smart locks, security upgrades'],
    'Other Electronics' => ['icon' => '🔌', 'desc' => 'Any other electronic appliance repair']
];

// Map each service category to required technician specializations
$service_specialization_map = [
    'Air Conditioning Repair' => ['HVAC'],
    'Refrigerator Repair' => ['Appliance Repair', 'Electrical'],
    'Washing Machine Repair' => ['Appliance Repair'],
    'Television Repair' => ['Electronics'],
    'Microwave Repair' => ['Appliance Repair', 'Electronics'],
    'Electric Fan Repair' => ['Electrical', 'Appliance Repair'],
    'Rice Cooker Repair' => ['Appliance Repair', 'Electronics'],
    'Laptop Repair' => ['Electronics'],
    'Mobile Phone Repair' => ['Electronics'],
    'Water Heater Repair' => ['HVAC', 'Electrical'],
    'Oven Repair' => ['Appliance Repair', 'Electrical'],
    'Dishwasher Repair' => ['Appliance Repair'],
    'Appliance Installation' => ['Appliance Repair', 'Electrical'],
    'Electrical Wiring & Installation' => ['Electrical'],
    'Plumbing Leak Repair' => ['Plumbing'],
    'Carpentry & Woodwork' => ['Carpentry'],
    'General Home Maintenance' => ['General Maintenance'],
    'CCTV & Security Installation' => ['Electronics'],
    'Other Electronics' => []
];

function isSubscriptionActive($flag, $expires) {
    if (!$flag) {
        return false;
    }
    if (empty($expires)) {
        return true;
    }
    return strtotime($expires) > time();
}

// Get client info
$stmt = $conn->prepare("SELECT c.Client_FN, c.Client_LN, c.Is_Subscribed, c.Subscription_Expires, a.City, a.Province, a.Barangay
                        FROM client c
                        LEFT JOIN client_address ca ON c.Client_ID = ca.Client_ID
                        LEFT JOIN address a ON ca.Address_ID = a.Address_ID
                        WHERE c.Client_ID = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$client = $result->fetch_assoc();
$stmt->close();

$client_is_subscribed = isSubscriptionActive((int)($client['Is_Subscribed'] ?? 0), $client['Subscription_Expires'] ?? null);
$client_city = $client['City'] ?? '';
$client_badge = $client_is_subscribed ? 'Premium Client' : 'Standard Client';
$subscription_expiry_text = $client_is_subscribed && $client['Subscription_Expires'] ? date('M j, Y', strtotime($client['Subscription_Expires'])) : null;

// Prepare service slug map for filtering
$service_slug_map = [];
foreach (array_keys($service_categories) as $service_name) {
    $service_slug_map[$service_name] = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $service_name));
}

$approved_technicians = [];
$services_with_available = array_fill_keys(array_keys($service_categories), false);
$available_specializations = [];
$service_technician_map = [];

$tech_stmt = $conn->prepare("SELECT Technician_ID, Technician_FN, Technician_LN, Technician_Phone, Specialization, Service_Pricing, Service_Location, Is_Subscribed, Subscription_Expires FROM technician WHERE Status = 'approved'");
if ($tech_stmt && $tech_stmt->execute()) {
    $tech_result = $tech_stmt->get_result();
    while ($tech_row = $tech_result->fetch_assoc()) {
        $specializations = parseTechnicianSpecializations($tech_row['Specialization'] ?? '');
        foreach ($specializations as $spec_token) {
            $available_specializations[] = $spec_token;
        }

        $supported_services = [];
        foreach ($service_specialization_map as $service_name => $required_specs) {
            if (technicianSupportsService($required_specs, $specializations)) {
                $supported_services[] = $service_name;
            }
        }

        if (empty($supported_services)) {
            continue;
        }

        $covers_client = true;
        $coverage_raw = $tech_row['Service_Location'] ?? '';
        if ($coverage_raw !== null && trim($coverage_raw) !== '') {
            $covers_client = doesTechnicianCoverLocation(
                $coverage_raw,
                $client['City'] ?? null,
                $client['Province'] ?? null,
                $client['Barangay'] ?? null
            );
        }

        $technician_entry = [
            'id' => (int) $tech_row['Technician_ID'],
            'name' => trim(($tech_row['Technician_FN'] ?? '') . ' ' . ($tech_row['Technician_LN'] ?? '')),
            'phone' => $tech_row['Technician_Phone'] ?? '',
            'pricing' => $tech_row['Service_Pricing'] ?? '',
            'specializations' => $specializations,
            'supported_services' => $supported_services,
            'supported_service_slugs' => array_map(function ($service_name) use ($service_slug_map) {
                return $service_slug_map[$service_name] ?? '';
            }, $supported_services),
            'covers_client' => $covers_client,
            'is_premium' => (int)($tech_row['Is_Subscribed'] ?? 0) === 1 && (
                empty($tech_row['Subscription_Expires']) ||
                $tech_row['Subscription_Expires'] === '0000-00-00 00:00:00' ||
                strtotime($tech_row['Subscription_Expires']) > time()
            )
        ];

        if ($covers_client) {
            foreach ($supported_services as $service_name) {
                $services_with_available[$service_name] = true;
                $service_technician_map[$service_name][] = $technician_entry;
            }
        }

        $approved_technicians[] = $technician_entry;
    }
    $tech_stmt->close();
}

$available_specializations = array_unique($available_specializations);

$service_technician_map = array_map(function ($list) {
    usort($list, function ($a, $b) {
        if ($a['is_premium'] === $b['is_premium']) {
            return strcmp($a['name'], $b['name']);
        }
        return $a['is_premium'] ? -1 : 1;
    });
    return $list;
}, $service_technician_map);

$technician_map_js = [];
foreach ($service_technician_map as $service_name => $techs) {
    $slug = $service_slug_map[$service_name] ?? '';
    if ($slug === '') {
        continue;
    }
    $technician_map_js[$slug] = array_map(function ($tech) {
        return [
            'id' => $tech['id'],
            'name' => $tech['name'],
            'pricing' => $tech['pricing'],
            'phone' => $tech['phone'],
            'is_premium' => $tech['is_premium'],
            'covers_client' => $tech['covers_client']
        ];
    }, $techs);
}

$selected_service_slug = '';
$initial_technicians = [];
$selected_technician_id = $technician_id_input;

if ($service_type_input !== '' && isset($service_technician_map[$service_type_input])) {
    $selected_service_slug = $service_slug_map[$service_type_input] ?? '';
    $initial_technicians = $service_technician_map[$service_type_input];
}

function parseTechnicianSpecializations(?string $raw): array
{
    if (!$raw) {
        return [];
    }
    $tokens = preg_split('/[,;|\/]+/', strtolower($raw));
    $normalized = [];
    foreach ($tokens as $token) {
        $token = trim($token);
        if ($token !== '') {
            $normalized[] = $token;
        }
    }
    if (empty($normalized)) {
        $normalized[] = strtolower(trim($raw));
    }
    return array_unique($normalized);
}

function technicianSupportsService(array $requiredSpecs, array $technicianSpecs): bool
{
    if (empty($requiredSpecs)) {
        return !empty($technicianSpecs);
    }
    foreach ($requiredSpecs as $required) {
        $required = strtolower($required);
        foreach ($technicianSpecs as $techSpec) {
            if ($required === $techSpec) {
                return true;
            }
        }
    }
    return false;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service_type = trim($_POST['service_type'] ?? '');
    $technician_id_post = isset($_POST['technician_id']) ? (int)$_POST['technician_id'] : 0;
    $apt_date = $_POST['apt_date'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $priority = $_POST['priority'] ?? 'normal';

    $service_type_input = $service_type;
    $technician_id_input = $technician_id_post ? (string)$technician_id_post : '';
    $apt_date_input = $apt_date;
    $description_input = $description;
    $priority_input = $priority;

    $valid_service = isset($service_categories[$service_type]) && !empty($services_with_available[$service_type]) && !empty($service_technician_map[$service_type]);
    if (!$valid_service) {
        $message = "❌ Please select a service with available technicians.";
        $messageType = "error";
    } elseif ($technician_id_post <= 0) {
        $message = "❌ Please choose a technician for your selected service.";
        $messageType = "error";
    } elseif (empty($apt_date)) {
        $message = "❌ Please provide your preferred schedule.";
        $messageType = "error";
    } else {
        $eligible_technicians = array_filter($service_technician_map[$service_type], function ($tech) use ($technician_id_post) {
            return $tech['id'] === $technician_id_post;
        });

        if (empty($eligible_technicians)) {
            $message = "❌ Selected technician is no longer available for this service.";
            $messageType = "error";
        } else {
            $booking_stmt = $conn->prepare("
                INSERT INTO booking (Client_ID, Technician_ID, Service_Type, AptDate, Status, Description)
                VALUES (?, ?, ?, ?, 'awaiting_acceptance', ?)
            ");
            if ($booking_stmt) {
                $booking_stmt->bind_param("iisss", $user_id, $technician_id_post, $service_type, $apt_date, $description);
                if ($booking_stmt->execute()) {
                    $new_booking_id = $conn->insert_id;
                    initializeBookingWorkflow($conn, $new_booking_id, $technician_id_post);
                    $message = "✅ Service request submitted! Please wait while the technician reviews and accepts your booking.";
                    $messageType = "success";
                    $service_type_input = '';
                    $technician_id_input = '';
                    $apt_date_input = '';
                    $description_input = '';
                    $priority_input = 'normal';
                } else {
                    $message = "❌ Error submitting service request. Please try again or contact support.";
                    $messageType = "error";
                }
                $booking_stmt->close();
            } else {
                $message = "❌ Unable to create booking. Please try again later.";
                $messageType = "error";
            }
        }
    }
}

$filtered_service_categories = [];
foreach ($service_categories as $service_name => $details) {
    if (!empty($service_technician_map[$service_name])) {
        $filtered_service_categories[$service_name] = $details;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - PinoyFix</title>
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
                            <span>Request Service</span>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="search-section">
                    <div class="search-container">
                        <input type="text" placeholder="Search service types..." class="search-input">
                        <button class="search-btn">🔍</button>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="user-section">
                    <div class="user-menu">
                        <div class="user-avatar">
                            <span><?php echo strtoupper(substr($client['Client_FN'], 0, 1)); ?></span>
                        </div>
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($client['Client_FN']); ?></h3>
                            <p><?php echo htmlspecialchars($client_badge); ?></p>
                            <?php if ($subscription_expiry_text): ?>
                                <span style="display: inline-block; margin-top: 4px; padding: 4px 10px; border-radius: 10px; background: rgba(16, 185, 129, 0.15); color: #047857; font-size: 0.8rem; font-weight: 600;">Valid until <?php echo htmlspecialchars($subscription_expiry_text); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="header-actions">
                            <a href="client_dashboard.php" class="btn-secondary">← Dashboard</a>
                            <a href="../logout.php" class="btn-logout">🚪 Logout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Messages -->
    <?php if ($message): ?>
        <div class="container" style="margin-top: 2rem;">
            <div class="alert alert-<?php echo $messageType; ?> fade-in">
                <?php echo $message; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="dashboard-container">
        <div class="container">
            <!-- Hero Section -->
            <section class="dashboard-hero" style="background: linear-gradient(135deg, #0038A8 0%, #1e40af 50%, #3b82f6 100%);">
                <div class="hero-content">
                    <div class="welcome-text">
                        <h1>Request Expert Repair Service 🔧</h1>
                        <p>Connect with verified, top-rated technicians in your area. Get professional repairs with warranty and transparent pricing.</p>
                    </div>
                    
                    <div class="hero-stats">
                        <div class="hero-stat">
                            <div class="stat-number">500+</div>
                            <div class="stat-label">Verified Technicians</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number">4.9</div>
                            <div class="stat-label">Average Rating</div>
                        </div>
                        <div class="hero-stat">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Support Available</div>
                        </div>
                    </div>
                </div>
                
                <!-- Service Categories Preview -->
                <div class="status-cards">
                    <div class="status-card active">
                        <div class="card-icon">❄️</div>
                        <div class="card-content">
                            <h3>AC Repair</h3>
                            <p>Most Popular</p>
                            <span class="card-trend">₱300 - ₱800</span>
                        </div>
                    </div>
                    
                    <div class="status-card completed">
                        <div class="card-icon">🧺</div>
                        <div class="card-content">
                            <h3>Appliance Fix</h3>
                            <p>Quick Service</p>
                            <span class="card-trend">₱250 - ₱600</span>
                        </div>
                    </div>
                    
                    <div class="status-card appliances">
                        <div class="card-icon">📺</div>
                        <div class="card-content">
                            <h3>Electronics</h3>
                            <p>Expert Repair</p>
                            <span class="card-trend">₱200 - ₱1000</span>
                        </div>
                    </div>

                    <div class="status-card rating">
                        <div class="card-icon">⚡</div>
                        <div class="card-content">
                            <h3>Emergency</h3>
                            <p>Same Day</p>
                            <span class="card-trend">+₱200 fee</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Service Request Form -->
            <section class="marketplace-actions">
                <h2 class="section-title">
                    <span class="title-icon">🛠️</span>
                    Service Request Form
                </h2>
                
                <form method="POST" class="request-form" id="serviceRequestForm">
                    <div class="action-marketplace" style="display: block;">
                        <!-- Service Type Selection -->
                        <div class="action-item featured" style="grid-column: 1/-1; margin-bottom: 2rem;">
                            <div class="action-badge">Step 1</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>🔧 What needs to be repaired?</h3>
                                <p>Select the appliance or device that requires repair service</p>
                                
                                <div style="margin: 1.5rem 0;">
                                    <select id="service_type" name="service_type" required style="width: 100%; padding: 16px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                        <option value="">Choose your appliance or service type</option>
                                        <?php if (empty($filtered_service_categories)): ?>
                                        <option value="" disabled>No verified technicians available yet</option>
                                        <?php else: ?>
                                        <?php foreach ($filtered_service_categories as $service => $details): ?>
                                        <?php $slug = $service_slug_map[$service] ?? ''; ?>
                                        <option value="<?php echo htmlspecialchars($service); ?>"
                                                data-icon="<?php echo $details['icon']; ?>"
                                                data-desc="<?php echo $details['desc']; ?>"
                                                data-slug="<?php echo htmlspecialchars($slug); ?>"
                                                <?php echo $service_type_input === $service ? 'selected' : ''; ?>>
                                            <?php echo $details['icon'] . ' ' . $service; ?>
                                        </option>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <div id="service-description" class="service-description" style="display: none; margin-top: 1rem; padding: 1rem; background: rgba(0, 56, 168, 0.1); border-radius: 12px; border-left: 4px solid #0038A8;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="action-item" style="grid-column: 1/-1; margin-bottom: 2rem;">
                            <div class="action-badge">Step 1.5</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>👨‍🔧 Choose a technician</h3>
                                <p>Select from technicians who cover your area for the chosen service</p>

                                <div style="margin: 1.5rem 0;">
                                    <select id="technician_id" name="technician_id" required style="width: 100%; padding: 16px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;" <?php echo empty($service_type_input) ? 'disabled' : ''; ?>>
                                        <option value="">Choose a technician</option>
                                    </select>
                                    <div id="technician-helper" style="margin-top: 0.75rem; color: #64748b; font-size: 0.9rem;"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Schedule Selection -->
                        <div class="action-item" style="grid-column: 1/-1; margin-bottom: 2rem;">
                            <div class="action-badge">Step 2</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>📅 Preferred Schedule</h3>
                                <p>When would you like the technician to visit?</p>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin: 1.5rem 0;">
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Preferred Date & Time *</label>
                                        <input type="datetime-local" id="apt_date" name="apt_date" required 
                                               min="<?php echo date('Y-m-d\TH:i', strtotime('+1 hour')); ?>"
                                               value="<?php echo htmlspecialchars($apt_date_input); ?>"
                                               style="width: 100%; padding: 16px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; background: #f8fafc;">
                                        <small style="color: #64748b; font-size: 0.85rem; display: block; margin-top: 0.5rem;">📍 Available: Mon-Sat 8AM-6PM, Sun 9AM-5PM</small>
                                    </div>
                                    
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 0.5rem; color: #374151;">Service Priority</label>
                                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.3s ease;">
                                                <input type="radio" name="priority" value="normal" <?php echo $priority_input === 'normal' ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                                                <div>
                                                    <strong>Normal Service</strong>
                                                    <br><small style="color: #64748b;">Within 24-48 hours</small>
                                                </div>
                                            </label>
                                            <label style="display: flex; align-items: center; gap: 0.75rem; padding: 1rem; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: all 0.3s ease;">
                                                <input type="radio" name="priority" value="urgent" <?php echo $priority_input === 'urgent' ? 'checked' : ''; ?> style="width: 20px; height: 20px;">
                                                <div>
                                                    <strong>Urgent Service (+₱200)</strong>
                                                    <br><small style="color: #64748b;">Within 4-8 hours</small>
                                                </div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Problem Description -->
                        <div class="action-item" style="grid-column: 1/-1; margin-bottom: 2rem;">
                            <div class="action-badge">Step 3</div>
                            <div class="action-details" style="width: 100%;">
                                <h3>📝 Problem Description</h3>
                                <p>Help us understand the issue better for faster service</p>
                                
                                <div style="margin: 1.5rem 0;">
                                    <textarea id="description" name="description" rows="5" 
                                              placeholder="Describe the problem in detail:&#10;• What symptoms are you experiencing?&#10;• When did the problem start?&#10;• Any error messages or unusual sounds?&#10;• What have you tried so far?&#10;&#10;The more details you provide, the better we can help you!"
                                              style="width: 100%; padding: 16px 20px; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 1rem; font-family: inherit; background: #f8fafc; resize: vertical; line-height: 1.6;"><?php echo htmlspecialchars($description_input); ?></textarea>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 0.5rem;">
                                        <small style="color: #64748b;">💡 Detailed descriptions help technicians prepare the right tools and parts</small>
                                        <div class="character-count" style="color: #94a3b8; font-size: 0.8rem;">
                                            <span id="char-count">0</span>/500 characters
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div style="text-align: center; margin-top: 2rem;">
                        <button type="submit" class="action-btn" id="submitBtn" style="min-width: 300px; padding: 18px 36px; font-size: 1.2rem;">
                            🚀 Submit Service Request
                        </button>
                    </div>
                </form>
            </section>

            <!-- Service Information Cards -->
            <section class="insights-section">
                <h2 class="section-title">
                    <span class="title-icon">ℹ️</span>
                    Service Information
                </h2>
                
                <div class="insights-grid">
                    <div class="insight-card maintenance">
                        <div class="insight-icon">🌏</div>
                        <h4>Service Coverage</h4>
                        <p><strong>Metro Manila:</strong> Quezon City, Manila, Makati, BGC, Ortigas<br>
                        <strong>Extended Areas:</strong> Rizal, Cavite, Laguna, Bulacan</p>
                        <small style="color: #64748b;">📍 More cities coming soon!</small>
                    </div>
                    
                    <div class="insight-card communication">
                        <div class="insight-icon">⏰</div>
                        <h4>Response Time</h4>
                        <p><strong>Normal Service:</strong> 24-48 hours<br>
                        <strong>Urgent Service:</strong> 4-8 hours<br>
                        <strong>Emergency:</strong> Same day</p>
                        <small style="color: #64748b;">📞 Call (02) 8123-4567 for emergencies</small>
                    </div>
                    
                    <div class="insight-card feedback">
                        <div class="insight-icon">💰</div>
                        <h4>Transparent Pricing</h4>
                        <p><strong>Service Call:</strong> ₱200-300<br>
                        <strong>Diagnostic:</strong> FREE with repair<br>
                        <strong>Parts:</strong> At cost + 10% markup</p>
                        <small style="color: #64748b;">💳 Cash, GCash, PayMaya accepted</small>
                    </div>
                </div>
            </section>
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
                    <h5>Service Guarantee</h5>
                    <p>🛡️ 30-day workmanship warranty</p>
                    <p>⭐ 100% satisfaction guaranteed</p>
                    <p>🔒 All technicians verified & insured</p>
                </div>
                <div class="footer-section">
                    <h5>Contact Support</h5>
                    <p>📞 (02) 8123-4567</p>
                    <p>📧 support@pinoyfix.com</p>
                    <p>💬 24/7 Live Chat Available</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 PinoyFix. All rights reserved. Made with ❤️ in the Philippines</p>
            </div>
        </div>
    </footer>

    <style>
        .btn-secondary, .btn-logout {
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
        }

        .btn-secondary {
            background: rgba(0, 56, 168, 0.1);
            color: #0038A8;
            border: 2px solid rgba(0, 56, 168, 0.2);
        }

        .btn-secondary:hover {
            background: #0038A8;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 56, 168, 0.3);
        }

        .btn-logout {
            background: rgba(220, 38, 38, 0.1);
            color: #dc2626;
            border: 2px solid rgba(220, 38, 38, 0.2);
        }

        .btn-logout:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.3);
        }

        .alert {
            padding: 1.5rem 2rem;
            border-radius: 16px;
            font-weight: 600;
            margin-bottom: 2rem;
            border: 2px solid;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: #047857;
            border-color: rgba(16, 185, 129, 0.3);
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-color: rgba(239, 68, 68, 0.3);
        }

        .service-description {
            font-style: italic;
            color: #0038A8;
            font-weight: 500;
        }

        .character-count.warning {
            color: #f59e0b !important;
        }

        .character-count.error {
            color: #ef4444 !important;
        }

        .header-actions {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        /* Priority radio button styling */
        input[type="radio"]:checked + div {
            color: #0038A8;
        }
        
        label:has(input[type="radio"]:checked) {
            border-color: #0038A8 !important;
            background: rgba(0, 56, 168, 0.05) !important;
        }
        
        label:has(input[type="radio"]) {
            transition: all 0.3s ease;
        }
        
        label:has(input[type="radio"]):hover {
            border-color: #0038A8 !important;
            background: rgba(0, 56, 168, 0.05) !important;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const serviceSelect = document.getElementById('service_type');
            const technicianSelect = document.getElementById('technician_id');
            const technicianHelper = document.getElementById('technician-helper');
            const serviceDesc = document.getElementById('service-description');
            const descTextarea = document.getElementById('description');
            const charCount = document.getElementById('char-count');
            const submitBtn = document.getElementById('submitBtn');
            const form = document.getElementById('serviceRequestForm');

            // Show service description
            const technicianMap = <?php echo json_encode($technician_map_js); ?>;

            function populateTechnicians(serviceSlug, selectedId = '') {
                technicianSelect.innerHTML = '<option value="">Choose a technician</option>';
                technicianSelect.disabled = true;
                technicianHelper.textContent = '';

                if (!serviceSlug || !technicianMap[serviceSlug] || technicianMap[serviceSlug].length === 0) {
                    technicianHelper.textContent = 'No technicians cover this service in your area yet. Please choose a different service or check back later.';
                    return;
                }

                technicianMap[serviceSlug].forEach(tech => {
                    const option = document.createElement('option');
                    option.value = tech.id;
                    const prefix = tech.is_premium ? '🌟 Premium · ' : '';
                    option.textContent = `${prefix}${tech.name}${tech.pricing ? ' · ₱' + tech.pricing : ''}`;
                    if (selectedId && String(tech.id) === String(selectedId)) {
                        option.selected = true;
                    }
                    technicianSelect.appendChild(option);
                });

                technicianSelect.disabled = false;
                technicianHelper.textContent = 'Tip: Premium technicians are prioritized and respond faster.';
            }

            function handleServiceChange(triggerPopulate = true) {
                const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
                const description = selectedOption ? selectedOption.getAttribute('data-desc') : '';
                const serviceSlug = selectedOption ? selectedOption.getAttribute('data-slug') : '';

                if (description && serviceSelect.value) {
                    serviceDesc.innerHTML = `💡 <strong>Service includes:</strong> ${description}`;
                    serviceDesc.style.display = 'block';
                    serviceDesc.classList.add('fade-in');
                } else {
                    serviceDesc.style.display = 'none';
                }

                if (triggerPopulate) {
                    populateTechnicians(serviceSlug, '');
                }
            }

            handleServiceChange(false);
            if (serviceSelect.value) {
                populateTechnicians(serviceSelect.options[serviceSelect.selectedIndex].getAttribute('data-slug'), '<?php echo htmlspecialchars($selected_technician_id); ?>');
            } else {
                technicianSelect.disabled = true;
            }

            serviceSelect.addEventListener('change', function() {
                technicianSelect.value = '';
                handleServiceChange(true);
            });

            const initialDescLength = descTextarea.value.length;
            charCount.textContent = initialDescLength;

            // Character counter
            descTextarea.addEventListener('input', function() {
                const count = this.value.length;
                charCount.textContent = count;
                
                const counter = document.querySelector('.character-count');
                counter.classList.remove('warning', 'error');
                
                if (count > 400) {
                    counter.classList.add('warning');
                }
                if (count > 500) {
                    counter.classList.add('error');
                    this.value = this.value.substring(0, 500);
                    charCount.textContent = 500;
                }
            });

            form.addEventListener('submit', function(e) {
                const serviceType = serviceSelect.value;
                const technicianValue = technicianSelect.value;
                const technicianDisabled = technicianSelect.disabled;
                const aptDate = document.getElementById('apt_date').value;

                if (!serviceType) {
                    e.preventDefault();
                    showNotification('Please select a service type', 'error');
                    serviceSelect.focus();
                    return;
                }

                if (technicianDisabled || !technicianValue) {
                    e.preventDefault();
                    showNotification('Please choose an available technician for this service', 'error');
                    technicianSelect.focus();
                    return;
                }

                if (!aptDate) {
                    e.preventDefault();
                    showNotification('Please select your preferred date and time', 'error');
                    document.getElementById('apt_date').focus();
                    return;
                }

                // Check if appointment is at least 1 hour from now
                const selectedDate = new Date(aptDate);
                const minDate = new Date(Date.now() + 60 * 60 * 1000);
                
                if (selectedDate < minDate) {
                    e.preventDefault();
                    showNotification('Please select a date and time at least 1 hour from now', 'error');
                    document.getElementById('apt_date').focus();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '⏳ Submitting Request...';
                submitBtn.style.background = '#94a3b8';
                submitBtn.style.cursor = 'not-allowed';
            });

            // Auto-hide alerts
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 6000);

            // Animate form sections
            const actionItems = document.querySelectorAll('.action-item');
            actionItems.forEach((item, index) => {
                item.style.opacity = '0';
                item.style.transform = 'translateY(30px)';
                setTimeout(() => {
                    item.style.opacity = '1';
                    item.style.transform = 'translateY(0)';
                    item.style.transition = 'all 0.6s ease';
                }, index * 200);
            });

            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 16px 24px;
                    border-radius: 12px;
                    color: white;
                    font-weight: 600;
                    z-index: 9999;
                    max-width: 400px;
                    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
                    transform: translateX(400px);
                    transition: all 0.3s ease;
                    background: ${type === 'error' ? 'linear-gradient(135deg, #ef4444, #dc2626)' : 'linear-gradient(135deg, #3b82f6, #2563eb)'};
                `;
                
                notification.textContent = message;
                document.body.appendChild(notification);
                
                setTimeout(() => notification.style.transform = 'translateX(0)', 100);
                setTimeout(() => {
                    notification.style.transform = 'translateX(400px)';
                    setTimeout(() => notification.remove(), 300);
                }, 4000);

                notification.addEventListener('click', () => {
                    notification.style.transform = 'translateX(400px)';
                    setTimeout(() => notification.remove(), 300);
                });
            }
        });
    </script>
</body>
</html>