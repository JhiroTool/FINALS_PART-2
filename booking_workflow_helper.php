<?php

function ensureBookingWorkflowTable(mysqli $conn): void
{
    $sql = "CREATE TABLE IF NOT EXISTS booking_workflow (
        Workflow_ID INT AUTO_INCREMENT PRIMARY KEY,
        Booking_ID INT NOT NULL,
        Technician_ID INT NOT NULL,
        Technician_Accepted TINYINT(1) NOT NULL DEFAULT 0,
        Client_Confirmed TINYINT(1) NOT NULL DEFAULT 0,
        Technician_Confirmed TINYINT(1) NOT NULL DEFAULT 0,
        Created_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        Updated_At TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        CONSTRAINT fk_booking_workflow_booking FOREIGN KEY (Booking_ID) REFERENCES booking(Booking_ID) ON DELETE CASCADE,
        CONSTRAINT fk_booking_workflow_technician FOREIGN KEY (Technician_ID) REFERENCES technician(Technician_ID) ON DELETE CASCADE,
        UNIQUE KEY uniq_booking (Booking_ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $conn->query($sql);
}

function resetBookingToPending(mysqli $conn, int $bookingId): bool
{
    ensureBookingWorkflowTable($conn);
    $stmt = $conn->prepare("UPDATE booking SET Technician_ID = NULL, Status = 'pending' WHERE Booking_ID = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $success = $stmt->affected_rows > 0;
    $stmt->close();

    if ($success) {
        removeBookingWorkflow($conn, $bookingId);
    }

    return $success;
}

function initializeBookingWorkflow(mysqli $conn, int $bookingId, int $technicianId): void
{
    ensureBookingWorkflowTable($conn);
    $stmt = $conn->prepare("INSERT INTO booking_workflow (Booking_ID, Technician_ID) VALUES (?, ?) ON DUPLICATE KEY UPDATE Technician_ID = VALUES(Technician_ID), Technician_Accepted = 0, Client_Confirmed = 0, Technician_Confirmed = 0");
    if ($stmt) {
        $stmt->bind_param('ii', $bookingId, $technicianId);
        $stmt->execute();
        $stmt->close();
    }
}

function removeBookingWorkflow(mysqli $conn, int $bookingId): void
{
    ensureBookingWorkflowTable($conn);
    $stmt = $conn->prepare("DELETE FROM booking_workflow WHERE Booking_ID = ?");
    if ($stmt) {
        $stmt->bind_param('i', $bookingId);
        $stmt->execute();
        $stmt->close();
    }
}

function getBookingWorkflow(mysqli $conn, int $bookingId): ?array
{
    ensureBookingWorkflowTable($conn);
    $stmt = $conn->prepare("SELECT Booking_ID, Technician_ID, Technician_Accepted, Client_Confirmed, Technician_Confirmed FROM booking_workflow WHERE Booking_ID = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
}

function markTechnicianAcceptance(mysqli $conn, int $bookingId, int $technicianId): bool
{
    ensureBookingWorkflowTable($conn);
    $stmt = $conn->prepare("UPDATE booking_workflow SET Technician_Accepted = 1 WHERE Booking_ID = ? AND Technician_ID = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('ii', $bookingId, $technicianId);
    $stmt->execute();
    $success = $stmt->affected_rows > 0;
    $stmt->close();

    if ($success) {
        $statusStmt = $conn->prepare("UPDATE booking SET Status = 'in_progress' WHERE Booking_ID = ? AND Status IN ('assigned','pending','awaiting_acceptance') LIMIT 1");
        if ($statusStmt) {
            $statusStmt->bind_param('i', $bookingId);
            $statusStmt->execute();
            $statusStmt->close();
        }
    }

    return $success;
}

function markBookingConfirmation(mysqli $conn, int $bookingId, string $actor): array
{
    ensureBookingWorkflowTable($conn);
    $workflow = getBookingWorkflow($conn, $bookingId);
    if (!$workflow) {
        return ['success' => false, 'completed' => false, 'message' => 'Workflow record not found.'];
    }

    $field = $actor === 'technician' ? 'Technician_Confirmed' : 'Client_Confirmed';
    if (!in_array($field, ['Technician_Confirmed', 'Client_Confirmed'], true)) {
        return ['success' => false, 'completed' => false, 'message' => 'Invalid actor.'];
    }

    if ((int)$workflow[$field] === 1) {
        return ['success' => true, 'completed' => ((int)$workflow['Client_Confirmed'] === 1 && (int)$workflow['Technician_Confirmed'] === 1)];
    }

    $stmt = $conn->prepare("UPDATE booking_workflow SET {$field} = 1 WHERE Booking_ID = ? LIMIT 1");
    if (!$stmt) {
        return ['success' => false, 'completed' => false, 'message' => 'Could not update confirmation.'];
    }
    $stmt->bind_param('i', $bookingId);
    $stmt->execute();
    $stmt->close();

    $updated = getBookingWorkflow($conn, $bookingId);
    $completed = $updated && (int)$updated['Client_Confirmed'] === 1 && (int)$updated['Technician_Confirmed'] === 1;

    $statusStmt = $conn->prepare("SELECT Status FROM booking WHERE Booking_ID = ? LIMIT 1");
    if ($statusStmt) {
        $statusStmt->bind_param('i', $bookingId);
        $statusStmt->execute();
        $statusResult = $statusStmt->get_result();
        $statusAssoc = $statusResult ? $statusResult->fetch_assoc() : null;
        $currentStatus = $statusAssoc['Status'] ?? null;
        $statusStmt->close();

        if ($completed) {
            $updateStatus = $conn->prepare("UPDATE booking SET Status = 'completed' WHERE Booking_ID = ? LIMIT 1");
            if ($updateStatus) {
                $updateStatus->bind_param('i', $bookingId);
                $updateStatus->execute();
                $updateStatus->close();
            }
        } elseif ($currentStatus && $currentStatus !== 'awaiting_confirmation') {
            $updateStatus = $conn->prepare("UPDATE booking SET Status = 'awaiting_confirmation' WHERE Booking_ID = ? LIMIT 1");
            if ($updateStatus) {
                $updateStatus->bind_param('i', $bookingId);
                $updateStatus->execute();
                $updateStatus->close();
            }
        }
    }

    return ['success' => true, 'completed' => $completed];
}

function doesTechnicianCoverLocation(?string $coverage, ?string $city, ?string $province, ?string $barangay = ''): bool
{
    if (!$coverage) {
        return false;
    }

    $coverage = strtolower($coverage);
    $city = strtolower((string)$city);
    $province = strtolower((string)$province);
    $barangay = strtolower((string)$barangay);

    $matchSegment = function (string $haystack, ?string $needle): bool {
        if (!$needle) {
            return false;
        }
        $pattern = '/(^|\b|\s)' . preg_quote($needle, '/') . '($|\b|\s)/';
        return (bool)preg_match($pattern, $haystack);
    };

    if ($barangay && $matchSegment($coverage, $barangay)) {
        return true;
    }
    if ($city && $matchSegment($coverage, $city)) {
        return true;
    }
    if ($province && $matchSegment($coverage, $province)) {
        return true;
    }

    $tokens = preg_split('/[,;|\/]+/', $coverage);
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
        if ($province && $matchSegment($token, $province)) {
            return true;
        }
    }

    return false;
}
