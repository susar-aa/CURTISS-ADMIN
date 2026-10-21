<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Due Date & Reminder Model
 */

require_once __DIR__ . '/../config/database.php';

class Reminder {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get upcoming billing obligations with evaluated due status
     */
    public function getUpcomingObligations(): array {
        $today = date('Y-m-d');
        $next7Days = date('Y-m-d', strtotime('+7 days'));

        // 1. Unpaid Invoices with client details
        $invStmt = $this->db->prepare(
            "SELECT 
                i.id AS invoice_id, 
                NULL AS subscription_id,
                i.invoice_number, 
                'Invoice' AS item_type,
                (i.total_amount - i.paid_amount) AS amount_due,
                i.due_date,
                i.status,
                c.id AS client_id,
                c.client_code,
                c.company_name,
                c.contact_person,
                c.phone,
                c.whatsapp
             FROM invoices i
             INNER JOIN clients c ON i.client_id = c.id
             WHERE i.status IN ('unpaid', 'partially_paid', 'overdue')
             ORDER BY i.due_date ASC"
        );
        $invStmt->execute();
        $invoices = $invStmt->fetchAll() ?: [];

        // 2. Active Subscriptions due soon
        $subStmt = $this->db->prepare(
            "SELECT 
                NULL AS invoice_id,
                s.id AS subscription_id,
                s.plan_name AS invoice_number,
                'Subscription' AS item_type,
                s.amount AS amount_due,
                s.next_due_date AS due_date,
                s.status,
                c.id AS client_id,
                c.client_code,
                c.company_name,
                c.contact_person,
                c.phone,
                c.whatsapp
             FROM subscriptions s
             INNER JOIN clients c ON s.client_id = c.id
             WHERE s.status = 'active' AND s.next_due_date IS NOT NULL
             ORDER BY s.next_due_date ASC"
        );
        $subStmt->execute();
        $subscriptions = $subStmt->fetchAll() ?: [];

        $all = array_merge($invoices, $subscriptions);

        // Sort by due date ASC
        usort($all, function ($a, $b) {
            return strcmp($a['due_date'], $b['due_date']);
        });

        // Group into buckets
        $categorized = [
            'overdue'     => [],
            'due_today'   => [],
            'due_7_days'  => [],
            'upcoming'    => []
        ];

        foreach ($all as &$item) {
            $dueDate = $item['due_date'];
            $dueTime = strtotime($dueDate);
            $todayTime = strtotime($today);

            if ($dueTime < $todayTime) {
                $item['due_category'] = 'overdue';
                $item['days_diff'] = (int)round(($todayTime - $dueTime) / 86400);
                $categorized['overdue'][] = $item;
            } elseif ($dueDate === $today) {
                $item['due_category'] = 'due_today';
                $item['days_diff'] = 0;
                $categorized['due_today'][] = $item;
            } elseif ($dueTime <= strtotime($next7Days)) {
                $item['due_category'] = 'due_7_days';
                $item['days_diff'] = (int)round(($dueTime - $todayTime) / 86400);
                $categorized['due_7_days'][] = $item;
            } else {
                $item['due_category'] = 'upcoming';
                $item['days_diff'] = (int)round(($dueTime - $todayTime) / 86400);
                $categorized['upcoming'][] = $item;
            }
        }

        return $categorized;
    }

    /**
     * Build prefilled WhatsApp message and link
     */
    public static function buildWhatsAppLink(string $phone, string $clientName, float $amount, string $dueDate, string $itemRef, string $category): string {
        $cleanPhone = preg_replace('/\D/', '', $phone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '94' . substr($cleanPhone, 1);
        }

        $formattedAmount = 'LKR ' . number_format($amount, 2);
        $payUrl = BASE_URL . '/index.php?page=pay';

        $greeting = "Dear " . $clientName . ",\n\n";
        if ($category === 'overdue') {
            $msg = $greeting . "This is a friendly reminder from Curtiss that payment for *" . $itemRef . "* (" . $formattedAmount . ") was due on *" . $dueDate . "* and is currently overdue.\n\nPlease settle at your earliest convenience. Official bank details:\n" . $payUrl . "\n\nThank you,\nCurtiss Team";
        } elseif ($category === 'due_today') {
            $msg = $greeting . "This is a payment notification from Curtiss. Your invoice *" . $itemRef . "* for *" . $formattedAmount . "* is due today (" . $dueDate . ").\n\nOfficial payment details:\n" . $payUrl . "\n\nThank you,\nCurtiss Team";
        } else {
            $msg = $greeting . "This is an upcoming payment reminder from Curtiss regarding *" . $itemRef . "* for *" . $formattedAmount . "* due on *" . $dueDate . "*.\n\nOfficial bank remittance details:\n" . $payUrl . "\n\nThank you,\nCurtiss Team";
        }

        return 'https://api.whatsapp.com/send?phone=' . urlencode($cleanPhone) . '&text=' . urlencode($msg);
    }

    /**
     * Check if reminder sent today
     */
    public function hasSentToday(int $clientId, string $type): bool {
        $stmt = $this->db->prepare(
            "SELECT id FROM reminder_logs 
             WHERE client_id = :client_id 
               AND reminder_type = :type 
               AND DATE(sent_at) = CURDATE() 
             LIMIT 1"
        );
        $stmt->execute(['client_id' => $clientId, 'type' => $type]);
        return (bool)$stmt->fetch();
    }

    /**
     * Log reminder
     */
    public function log(int $clientId, ?int $invId, ?int $subId, string $type, string $message, ?int $userId = null): void {
        $stmt = $this->db->prepare(
            "INSERT INTO reminder_logs (client_id, invoice_id, subscription_id, reminder_type, message, sent_by, sent_at)
             VALUES (:client_id, :inv_id, :sub_id, :type, :msg, :sent_by, NOW())"
        );
        $stmt->execute([
            'client_id' => $clientId,
            'inv_id'    => $invId,
            'sub_id'    => $subId,
            'type'      => $type,
            'msg'       => $message,
            'sent_by'   => $userId
        ]);
    }
}
