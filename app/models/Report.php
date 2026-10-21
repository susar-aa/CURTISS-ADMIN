<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Reporting Model
 */

require_once __DIR__ . '/../config/database.php';

class Report {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get revenue grouped by month for a given date range
     */
    public function getMonthlyRevenue(string $startDate, string $endDate): array {
        $stmt = $this->db->prepare(
            "SELECT 
                DATE_FORMAT(payment_date, '%Y-%m') AS month_label,
                COUNT(id) AS transaction_count,
                SUM(amount) AS total_revenue
             FROM payments
             WHERE status = 'completed'
               AND payment_date >= :start_date 
               AND payment_date <= :end_date
             GROUP BY DATE_FORMAT(payment_date, '%Y-%m')
             ORDER BY month_label ASC"
        );
        $stmt->execute([
            'start_date' => $startDate,
            'end_date'   => $endDate
        ]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get client outstanding balances
     */
    public function getClientOutstandingReport(): array {
        $stmt = $this->db->query(
            "SELECT 
                c.id, 
                c.client_code, 
                c.company_name, 
                c.contact_person, 
                c.phone,
                COUNT(i.id) AS unpaid_invoices_count,
                COALESCE(SUM(i.total_amount), 0) AS total_invoiced,
                COALESCE(SUM(i.paid_amount), 0) AS total_paid,
                COALESCE(SUM(i.total_amount - i.paid_amount), 0) AS total_outstanding
             FROM clients c
             INNER JOIN invoices i ON c.id = i.client_id
             WHERE i.status IN ('unpaid', 'partially_paid', 'overdue')
             GROUP BY c.id
             HAVING total_outstanding > 0
             ORDER BY total_outstanding DESC"
        );
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get subscription analytics (active run-rates by frequency)
     */
    public function getSubscriptionAnalytics(): array {
        $stmt = $this->db->query(
            "SELECT 
                frequency,
                COUNT(id) AS total_count,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_count,
                COALESCE(SUM(CASE WHEN status = 'active' THEN amount ELSE 0 END), 0) AS active_volume
             FROM subscriptions
             GROUP BY frequency"
        );
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get payment history for export
     */
    public function getPaymentHistoryExport(string $startDate, string $endDate): array {
        $stmt = $this->db->prepare(
            "SELECT 
                p.receipt_number,
                c.client_code,
                c.company_name,
                p.payment_date,
                p.payment_method,
                p.reference_no,
                p.amount,
                i.invoice_number,
                p.status
             FROM payments p
             INNER JOIN clients c ON p.client_id = c.id
             LEFT JOIN invoices i ON p.invoice_id = i.id
             WHERE p.payment_date >= :start_date AND p.payment_date <= :end_date
             ORDER BY p.payment_date DESC, p.id DESC"
        );
        $stmt->execute([
            'start_date' => $startDate,
            'end_date'   => $endDate
        ]);
        return $stmt->fetchAll() ?: [];
    }
}
