<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Dashboard Metrics Model
 * High-performance aggregate queries using explicit joins and indexed columns
 */

require_once __DIR__ . '/../config/database.php';

class Dashboard {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Get aggregate KPI metrics for dashboard cards
     */
    public function getMetrics(): array {
        // 1. Client counts
        $clientStmt = $this->db->query(
            "SELECT 
                COUNT(id) AS total_clients,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_clients
             FROM clients"
        );
        $clientCounts = $clientStmt->fetch() ?: ['total_clients' => 0, 'active_clients' => 0];

        // 2. Current Month Revenue (completed payments)
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');
        $revStmt = $this->db->prepare(
            "SELECT COALESCE(SUM(amount), 0) AS monthly_revenue 
             FROM payments 
             WHERE status = 'completed' 
               AND payment_date >= :month_start 
               AND payment_date <= :month_end"
        );
        $revStmt->execute([
            'month_start' => $monthStart,
            'month_end' => $monthEnd
        ]);
        $monthlyRevenue = (float)($revStmt->fetchColumn() ?: 0);

        // 3. Outstanding Balance from active invoices
        $outStmt = $this->db->query(
            "SELECT COALESCE(SUM(total_amount - paid_amount), 0) AS outstanding 
             FROM invoices 
             WHERE status IN ('unpaid', 'partially_paid', 'overdue')"
        );
        $outstanding = (float)($outStmt->fetchColumn() ?: 0);

        // 4. Upcoming & Overdue Counts
        $today = date('Y-m-d');
        $next30Days = date('Y-m-d', strtotime('+30 days'));

        $dueStmt = $this->db->prepare(
            "SELECT 
                COUNT(CASE WHEN due_date >= :today AND due_date <= :next_30 THEN 1 END) AS upcoming_count,
                COUNT(CASE WHEN due_date < :today_overdue THEN 1 END) AS overdue_count
             FROM invoices 
             WHERE status IN ('unpaid', 'partially_paid', 'overdue')"
        );
        $dueStmt->execute([
            'today' => $today,
            'next_30' => $next30Days,
            'today_overdue' => $today
        ]);
        $dueCounts = $dueStmt->fetch() ?: ['upcoming_count' => 0, 'overdue_count' => 0];

        return [
            'total_clients'    => (int)($clientCounts['total_clients'] ?? 0),
            'active_clients'   => (int)($clientCounts['active_clients'] ?? 0),
            'monthly_revenue'  => $monthlyRevenue,
            'outstanding'      => $outstanding,
            'upcoming_count'   => (int)($dueCounts['upcoming_count'] ?? 0),
            'overdue_count'    => (int)($dueCounts['overdue_count'] ?? 0),
        ];
    }

    /**
     * Get recent completed payments with explicit INNER JOIN on clients
     */
    public function getRecentPayments(int $limit = 6): array {
        $stmt = $this->db->prepare(
            "SELECT 
                p.id, 
                p.receipt_number, 
                p.amount, 
                p.payment_date, 
                p.payment_method, 
                p.status,
                c.company_name, 
                c.client_code
             FROM payments p
             INNER JOIN clients c ON p.client_id = c.id
             WHERE p.status = 'completed'
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get upcoming due invoices / obligations with explicit INNER JOIN on clients
     */
    public function getUpcomingDueList(int $limit = 6): array {
        $today = date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT 
                i.id, 
                i.invoice_number, 
                (i.total_amount - i.paid_amount) AS balance_due, 
                i.due_date, 
                i.status,
                c.company_name, 
                c.client_code
             FROM invoices i
             INNER JOIN clients c ON i.client_id = c.id
             WHERE i.status IN ('unpaid', 'partially_paid', 'overdue')
             ORDER BY i.due_date ASC, i.id ASC
             LIMIT :limit"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
