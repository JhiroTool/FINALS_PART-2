<?php

declare(strict_types=1);

/**
 * Shared helper functions for managing premium subscriptions.
 */

/**
 * Return the available subscription plans for a user type.
 *
 * @param string $userType 'technician' or 'client'
 * @return array<int, array{price: float, label: string}>
 */
function getSubscriptionPlans(string $userType): array
{
    $catalog = [
        'technician' => [
            30 => ['price' => 50.00, 'label' => '30 days • ₱50'],
        ],
        'client' => [
            30 => ['price' => 50.00, 'label' => '30 days • ₱50'],
        ],
    ];

    $typeKey = strtolower($userType) === 'client' ? 'client' : 'technician';
    return $catalog[$typeKey];
}

/**
 * Process a self-service subscription purchase.
 *
 * Automatically records a payment, activates premium access, and extends the
 * subscription expiry. Returns metadata for display back to the user.
 */
function processSubscriptionPurchase(
    mysqli $conn,
    string $userType,
    int $userId,
    int $planDays,
    ?string $reference = null,
    ?string $notes = null
): array {
    $userType = strtolower($userType) === 'client' ? 'client' : 'technician';
    $plans = getSubscriptionPlans($userType);

    if (!isset($plans[$planDays])) {
        return [
            'success' => false,
            'message' => 'Selected plan is not available.',
        ];
    }

    $amount = $plans[$planDays]['price'];
    $referenceParam = $reference !== null && $reference !== '' ? $reference : null;
    $notesParam = $notes !== null && $notes !== '' ? $notes : null;

    if ($referenceParam === null) {
        return [
            'success' => false,
            'message' => 'Please enter your GCash transaction reference number.',
        ];
    }

    $userTable = $userType === 'client' ? 'client' : 'technician';
    $idColumn = $userType === 'client' ? 'Client_ID' : 'Technician_ID';

    $conn->begin_transaction();

    try {
        $duplicateCheck = $conn->prepare("SELECT COUNT(*) FROM subscription_payments WHERE Reference = ? AND Reference IS NOT NULL AND Status IN ('pending', 'paid')");
        if ($duplicateCheck) {
            $duplicateCheck->bind_param('s', $referenceParam);
            $duplicateCheck->execute();
            $duplicateCheck->bind_result($existingCount);
            $duplicateCheck->fetch();
            $duplicateCheck->close();
            if ($existingCount > 0) {
                throw new RuntimeException('This transaction reference was already used. Please double-check your payment.');
            }
        }

        $expiresQuery = $conn->prepare("SELECT Subscription_Expires FROM {$userTable} WHERE {$idColumn} = ? LIMIT 1");
        if (!$expiresQuery) {
            throw new RuntimeException('Unable to load subscription details.');
        }
        $expiresQuery->bind_param('i', $userId);
        $expiresQuery->execute();
        $expiresQuery->bind_result($currentExpiryRaw);
        $currentExpiry = null;
        if ($expiresQuery->fetch()) {
            $currentExpiry = $currentExpiryRaw;
        }
        $expiresQuery->close();

        $baseTimestamp = time();
        if ($currentExpiry && $currentExpiry !== '0000-00-00 00:00:00') {
            $existing = strtotime($currentExpiry);
            if ($existing !== false && $existing > $baseTimestamp) {
                $baseTimestamp = $existing;
            }
        }

        $newExpiry = date('Y-m-d H:i:s', strtotime("+{$planDays} days", $baseTimestamp));
        $paidAt = date('Y-m-d H:i:s');

        $insertSql = "
            INSERT INTO subscription_payments
                (User_ID, User_Type, Amount, Reference, Plan_Days, Status, Paid_At, Expires_At, Gateway, Notes)
            VALUES
                (?, ?, ?, ?, ?, 'paid', ?, ?, 'system_auto', ?)
        ";
        $insertStmt = $conn->prepare($insertSql);
        if (!$insertStmt) {
            throw new RuntimeException('Unable to record subscription payment.');
        }
        $insertStmt->bind_param(
            'isdsisss',
            $userId,
            $userType,
            $amount,
            $referenceParam,
            $planDays,
            $paidAt,
            $newExpiry,
            $notesParam
        );
        $insertStmt->execute();
        $insertStmt->close();

        $updateSql = "UPDATE {$userTable} SET Is_Subscribed = 1, Subscription_Expires = ? WHERE {$idColumn} = ?";
        $updateStmt = $conn->prepare($updateSql);
        if (!$updateStmt) {
            throw new RuntimeException('Unable to activate subscription.');
        }
        $updateStmt->bind_param('si', $newExpiry, $userId);
        $updateStmt->execute();
        $updateStmt->close();

        $conn->commit();

        return [
            'success' => true,
            'message' => sprintf('Premium activated! Your plan is now valid until %s.', date('M j, Y', strtotime($newExpiry))),
            'amount' => $amount,
            'paid_at' => $paidAt,
            'expires_at' => $newExpiry,
        ];
    } catch (Throwable $e) {
        $conn->rollback();
        return [
            'success' => false,
            'message' => 'Unable to activate subscription right now. Please try again.',
            'error' => $e->getMessage(),
        ];
    }
}
