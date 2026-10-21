<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Subscription Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLog.php';

class Subscription {
    private PDO $db;
    private ActivityLog $activityLog;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->activityLog = new ActivityLog();
    }

    /**
     * Compute next due date from frequency and base date
     */
    public static function computeDueDate(string $frequency, string $baseDate): ?string {
        $timestamp = strtotime($baseDate);
        if (!$timestamp) {
            return null;
        }

        switch ($frequency) {
            case 'monthly':
                return date('Y-m-d', strtotime('+1 month', $timestamp));
            case 'yearly':
                return date('Y-m-d', strtotime('+1 year', $timestamp));
            case 'one_time':
                return date('Y-m-d', $timestamp);
            case 'custom':
            default:
                return null;
        }
    }

    /**
     * Get computed status for a subscription (active, due_soon, overdue, expired, suspended, cancelled)
     */
    public static function evaluateStatus(array $sub): array {
        $status = $sub['status'] ?? 'active';
        $dueDate = $sub['next_due_date'] ?? null;
        
        if ($status !== 'active') {
            return [
                'key'   => $status,
                'label' => ucfirst($status),
                'badge' => 'badge-ios-subtle'
            ];
        }

        if (empty($dueDate)) {
            return [
                'key'   => 'active',
                'label' => 'Active',
                'badge' => 'badge-ios-black'
            ];
        }

        $today = new DateTime('today');
        $due = new DateTime($dueDate);
        $diff = (int)$today->diff($due)->format('%r%a'); // negative if past

        if ($diff < 0) {
            return [
                'key'   => 'overdue',
                'label' => 'Overdue (' . abs($diff) . 'd)',
                'badge' => 'badge-ios-danger'
            ];
        } elseif ($diff <= 7) {
            return [
                'key'   => 'due_soon',
                'label' => 'Due Soon (' . $diff . 'd)',
                'badge' => 'badge-ios-warning'
            ];
        }

        return [
            'key'   => 'active',
            'label' => 'Active',
            'badge' => 'badge-ios-black'
        ];
    }

    /**
     * Get subscriptions for a specific client
     */
    public function getByClientId(int $clientId): array {
        $stmt = $this->db->prepare(
            "SELECT id, client_id, plan_name, frequency, amount, start_date, next_due_date, status, created_at, updated_at
             FROM subscriptions
             WHERE client_id = :client_id
             ORDER BY status = 'active' DESC, next_due_date ASC, id DESC"
        );
        $stmt->execute(['client_id' => $clientId]);
        $rows = $stmt->fetchAll() ?: [];

        foreach ($rows as &$row) {
            $row['computed_status'] = self::evaluateStatus($row);
        }

        return $rows;
    }

    /**
     * Find single subscription by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, client_id, plan_name, frequency, amount, start_date, next_due_date, status, created_at, updated_at
             FROM subscriptions
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $sub = $stmt->fetch();
        if ($sub) {
            $sub['computed_status'] = self::evaluateStatus($sub);
            return $sub;
        }
        return null;
    }

    /**
     * Create new subscription with period duplicate protection
     */
    public function create(array $data, ?int $userId = null): array {
        $clientId = (int)$data['client_id'];
        $planName = trim((string)$data['plan_name']);
        $frequency = (string)$data['frequency'];
        $amount = (float)$data['amount'];
        $startDate = (string)$data['start_date'];
        $nextDueDate = !empty($data['next_due_date']) ? (string)$data['next_due_date'] : self::computeDueDate($frequency, $startDate);
        $status = (string)($data['status'] ?? 'active');

        // Prevent duplicate subscription with the exact same plan, start date, and next due date
        $chkStmt = $this->db->prepare(
            "SELECT id FROM subscriptions 
             WHERE client_id = :client_id 
               AND plan_name = :plan_name 
               AND start_date = :start_date 
               AND next_due_date = :next_due_date 
             LIMIT 1"
        );
        $chkStmt->execute([
            'client_id'     => $clientId,
            'plan_name'     => $planName,
            'start_date'    => $startDate,
            'next_due_date' => $nextDueDate
        ]);

        if ($chkStmt->fetch()) {
            return [
                'success' => false,
                'message' => 'A subscription with the exact same plan name and billing period already exists for this client.'
            ];
        }

        $stmt = $this->db->prepare(
            "INSERT INTO subscriptions (client_id, plan_name, frequency, amount, start_date, next_due_date, status, created_at)
             VALUES (:client_id, :plan_name, :frequency, :amount, :start_date, :next_due_date, :status, NOW())"
        );
        $stmt->execute([
            'client_id'     => $clientId,
            'plan_name'     => $planName,
            'frequency'     => $frequency,
            'amount'        => $amount,
            'start_date'    => $startDate,
            'next_due_date' => $nextDueDate,
            'status'        => $status
        ]);

        $subId = (int)$this->db->lastInsertId();
        $this->activityLog->log(
            $clientId,
            'Subscription Added',
            sprintf("Added subscription '%s' (%s, LKR %s, Due: %s)", $planName, ucfirst($frequency), number_format($amount, 2), $nextDueDate ?? 'N/A'),
            $userId
        );

        return [
            'success' => true,
            'id'      => $subId
        ];
    }

    /**
     * Update an existing subscription
     */
    public function update(int $id, array $data, ?int $userId = null): bool {
        $sub = $this->getById($id);
        if (!$sub) {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE subscriptions 
             SET plan_name = :plan_name,
                 frequency = :frequency,
                 amount = :amount,
                 start_date = :start_date,
                 next_due_date = :next_due_date,
                 status = :status,
                 updated_at = NOW()
             WHERE id = :id"
        );

        $result = $stmt->execute([
            'id'            => $id,
            'plan_name'     => trim((string)$data['plan_name']),
            'frequency'     => (string)$data['frequency'],
            'amount'        => (float)$data['amount'],
            'start_date'    => (string)$data['start_date'],
            'next_due_date' => !empty($data['next_due_date']) ? (string)$data['next_due_date'] : null,
            'status'        => (string)$data['status']
        ]);

        if ($result) {
            $this->activityLog->log(
                (int)$sub['client_id'],
                'Subscription Updated',
                sprintf("Updated subscription '%s' (Status: %s, Next Due: %s)", $data['plan_name'], $data['status'], $data['next_due_date'] ?? 'N/A'),
                $userId
            );
        }

        return $result;
    }

    /**
     * Renew subscription: advance due date and record renewal
     */
    public function renew(int $id, ?string $customDueDate = null, ?int $userId = null): array {
        $sub = $this->getById($id);
        if (!$sub) {
            return ['success' => false, 'message' => 'Subscription not found.'];
        }

        $currentDueDate = $sub['next_due_date'] ?? $sub['start_date'];
        $newDueDate = $customDueDate;

        if (empty($newDueDate)) {
            $newDueDate = self::computeDueDate($sub['frequency'], $currentDueDate);
        }

        if (empty($newDueDate) || $newDueDate === $currentDueDate) {
            return ['success' => false, 'message' => 'Invalid renewal due date calculation.'];
        }

        $stmt = $this->db->prepare(
            "UPDATE subscriptions 
             SET next_due_date = :next_due_date,
                 status = 'active',
                 updated_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute([
            'id'            => $id,
            'next_due_date' => $newDueDate
        ]);

        $this->activityLog->log(
            (int)$sub['client_id'],
            'Subscription Renewed',
            sprintf("Renewed subscription '%s'. Previous Due: %s, New Due: %s", $sub['plan_name'], $currentDueDate, $newDueDate),
            $userId
        );

        return ['success' => true, 'new_due_date' => $newDueDate];
    }
}
