<?php
session_start();
header('Content-Type: application/json');
include '../connection.php';
require_once __DIR__ . '/../booking_workflow_helper.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'client') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$client_id = (int)$_SESSION['user_id'];
$booking_id = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$method = isset($_POST['method']) ? trim($_POST['method']) : '';
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';

if ($booking_id <= 0 || $amount <= 0 || $method === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payment details provided']);
    exit();
}

$booking_stmt = $conn->prepare("SELECT Booking_ID, Technician_ID, Status FROM booking WHERE Booking_ID = ? AND Client_ID = ?");
$booking_stmt->bind_param('ii', $booking_id, $client_id);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();
$booking_stmt->close();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit();
}

if (empty($booking['Technician_ID'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No technician assigned to this booking']);
    exit();
}

if (!in_array($booking['Status'], ['awaiting_payment', 'awaiting_payout', 'completed'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'This booking is not waiting for payment settlement']);
    exit();
}

$existing_stmt = $conn->prepare("SELECT JobPayment_ID FROM job_payments WHERE Booking_ID = ?");
$existing_stmt->bind_param('i', $booking_id);
$existing_stmt->execute();
$existing_stmt->store_result();
$has_payment = $existing_stmt->num_rows > 0;
$existing_stmt->close();

if ($has_payment) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Payment already recorded for this booking']);
    exit();
}

$confirmed_at = date('Y-m-d H:i:s');

try {
    $conn->begin_transaction();

    $insert_payment = $conn->prepare("INSERT INTO job_payments (Booking_ID, Client_ID, Technician_ID, Amount, Method, Notes, Status, Confirmed_By, Confirmed_At) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'client', ?)");
    $insert_payment->bind_param('iiidsss', $booking_id, $client_id, $booking['Technician_ID'], $amount, $method, $notes, $confirmed_at);
    if (!$insert_payment->execute()) {
        throw new Exception('Failed to record job payment');
    }
    $insert_payment->close();

    $insert_legacy = $conn->prepare("INSERT INTO payment (Booking_ID, Payment_Amount, Payment_Method, Payment_Status) VALUES (?, ?, ?, 'completed')");
    $insert_legacy->bind_param('ids', $booking_id, $amount, $method);
    if (!$insert_legacy->execute()) {
        throw new Exception('Failed to record legacy payment entry');
    }
    $insert_legacy->close();

    $wallet_stmt = $conn->prepare("INSERT INTO technician_wallet (Technician_ID, Balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE Balance = Balance + VALUES(Balance), Updated_At = CURRENT_TIMESTAMP");
    $wallet_stmt->bind_param('id', $booking['Technician_ID'], $amount);
    if (!$wallet_stmt->execute()) {
        throw new Exception('Failed to update technician wallet');
    }
    $wallet_stmt->close();

    $earnings_stmt = $conn->prepare("SELECT Earnings_ID FROM technician_earnings WHERE Technician_ID = ? AND Booking_ID = ?");
    $earnings_stmt->bind_param('ii', $booking['Technician_ID'], $booking_id);
    $earnings_stmt->execute();
    $earnings_stmt->store_result();

    if ($earnings_stmt->num_rows > 0) {
        $earnings_stmt->bind_result($earnings_id);
        $earnings_stmt->fetch();
        $earnings_stmt->close();

        $update_earnings = $conn->prepare("UPDATE technician_earnings SET Amount = ?, Date_Earned = ?, Status = 'paid' WHERE Earnings_ID = ?");
        $update_earnings->bind_param('dsi', $amount, $confirmed_at, $earnings_id);
        if (!$update_earnings->execute()) {
            throw new Exception('Failed to update technician earnings');
        }
        $update_earnings->close();
    } else {
        $earnings_stmt->close();
        $insert_earnings = $conn->prepare("INSERT INTO technician_earnings (Technician_ID, Booking_ID, Amount, Date_Earned, Status) VALUES (?, ?, ?, ?, 'paid')");
        $insert_earnings->bind_param('iids', $booking['Technician_ID'], $booking_id, $amount, $confirmed_at);
        if (!$insert_earnings->execute()) {
            throw new Exception('Failed to insert technician earnings');
        }
        $insert_earnings->close();
    }

    if (!markWorkflowPaymentRecorded($conn, $booking_id)) {
        throw new Exception('Failed to update booking payment status');
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Unable to complete payment at this time']);
}

exit();
