<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Client Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLog.php';

class Client {
    private PDO $db;
    private ActivityLog $activityLog;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->activityLog = new ActivityLog();
    }

    /**
     * Generate next 5-digit sequential client code e.g. "00001"
     */
    public function getNextClientCode(): string {
        $stmt = $this->db->query(
            "SELECT client_code 
             FROM clients 
             ORDER BY id DESC 
             LIMIT 1"
        );
        $lastCode = $stmt->fetchColumn();

        if (!$lastCode) {
            return '00001';
        }

        // Extract integer digits and increment
        $num = (int)preg_replace('/\D/', '', (string)$lastCode);
        return str_pad((string)($num + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get paginated clients with indexed search and status filter
     */
    public function getList(int $limit = 15, int $offset = 0, ?string $search = null, ?string $status = null, bool $archived = false): array {
        $whereClauses = ['c.is_archived = :archived'];
        $params = ['archived' => $archived ? 1 : 0];

        if (!empty($status) && in_array($status, ['active', 'suspended', 'inactive'], true)) {
            $whereClauses[] = 'c.status = :status';
            $params['status'] = $status;
        }

        if (!empty($search)) {
            // Avoid leading wildcards: match prefix or exact code using index
            $cleanSearch = trim($search);
            $whereClauses[] = '(c.company_name LIKE :search_comp OR c.client_code = :search_code OR c.phone LIKE :search_phone)';
            $params['search_comp'] = $cleanSearch . '%';
            $params['search_code'] = $cleanSearch;
            $params['search_phone'] = $cleanSearch . '%';
        }

        $whereSql = implode(' AND ', $whereClauses);

        // 1. Total Count for pagination
        $countStmt = $this->db->prepare("SELECT COUNT(c.id) FROM clients c WHERE {$whereSql}");
        foreach ($params as $key => $val) {
            $countStmt->bindValue($key, $val);
        }
        $countStmt->execute();
        $totalRows = (int)$countStmt->fetchColumn();

        // 2. Fetch records with explicit column selection and single JOIN for active subscription count
        $sql = "SELECT 
                    c.id, 
                    c.client_code, 
                    c.company_name, 
                    c.contact_person, 
                    c.phone, 
                    c.whatsapp, 
                    c.email, 
                    c.curtiss_system, 
                    c.system_url, 
                    c.installation_date, 
                    c.status, 
                    c.is_archived, 
                    c.created_at,
                    COUNT(s.id) AS active_subscriptions
                FROM clients c
                LEFT JOIN subscriptions s ON c.id = s.client_id AND s.status = 'active'
                WHERE {$whereSql}
                GROUP BY c.id
                ORDER BY c.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll() ?: [];

        return [
            'data'       => $records,
            'total'      => $totalRows,
            'limit'      => $limit,
            'offset'     => $offset,
            'total_pages'=> $limit > 0 ? (int)ceil($totalRows / $limit) : 1
        ];
    }

    /**
     * Get single client by ID with explicit columns
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT 
                id, 
                client_code, 
                company_name, 
                contact_person, 
                phone, 
                whatsapp, 
                email, 
                address, 
                curtiss_system, 
                system_url, 
                installation_date, 
                status, 
                is_archived, 
                notes, 
                created_at, 
                updated_at
             FROM clients
             WHERE id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $client = $stmt->fetch();
        return $client ?: null;
    }

    /**
     * Create client
     */
    public function create(array $data, ?int $userId = null): array {
        $clientCode = trim((string)($data['client_code'] ?? ''));
        if (empty($clientCode)) {
            $clientCode = $this->getNextClientCode();
        }

        // Verify uniqueness
        $chk = $this->db->prepare("SELECT id FROM clients WHERE client_code = :code LIMIT 1");
        $chk->execute(['code' => $clientCode]);
        if ($chk->fetch()) {
            // Re-generate next available code if conflict
            $clientCode = $this->getNextClientCode();
        }

        $stmt = $this->db->prepare(
            "INSERT INTO clients (
                client_code, company_name, contact_person, phone, whatsapp, 
                email, address, curtiss_system, system_url, installation_date, 
                status, notes, is_archived, created_at
             ) VALUES (
                :client_code, :company_name, :contact_person, :phone, :whatsapp, 
                :email, :address, :curtiss_system, :system_url, :installation_date, 
                :status, :notes, 0, NOW()
             )"
        );

        $stmt->execute([
            'client_code'       => $clientCode,
            'company_name'      => trim((string)$data['company_name']),
            'contact_person'    => trim((string)$data['contact_person']),
            'phone'             => trim((string)$data['phone']),
            'whatsapp'          => !empty($data['whatsapp']) ? trim((string)$data['whatsapp']) : null,
            'email'             => !empty($data['email']) ? trim((string)$data['email']) : null,
            'address'           => !empty($data['address']) ? trim((string)$data['address']) : null,
            'curtiss_system'    => !empty($data['curtiss_system']) ? trim((string)$data['curtiss_system']) : null,
            'system_url'        => !empty($data['system_url']) ? trim((string)$data['system_url']) : null,
            'installation_date' => !empty($data['installation_date']) ? (string)$data['installation_date'] : null,
            'status'            => (string)($data['status'] ?? 'active'),
            'notes'             => !empty($data['notes']) ? trim((string)$data['notes']) : null
        ]);

        $clientId = (int)$this->db->lastInsertId();

        $this->activityLog->log(
            $clientId,
            'Client Registered',
            sprintf("Client profile '%s' (ID: %s) registered in system.", $data['company_name'], $clientCode),
            $userId
        );

        return [
            'success' => true,
            'id'      => $clientId,
            'code'    => $clientCode
        ];
    }

    /**
     * Update client details
     */
    public function update(int $id, array $data, ?int $userId = null): bool {
        $stmt = $this->db->prepare(
            "UPDATE clients SET
                company_name = :company_name,
                contact_person = :contact_person,
                phone = :phone,
                whatsapp = :whatsapp,
                email = :email,
                address = :address,
                curtiss_system = :curtiss_system,
                system_url = :system_url,
                installation_date = :installation_date,
                status = :status,
                notes = :notes,
                updated_at = NOW()
             WHERE id = :id"
        );

        $result = $stmt->execute([
            'id'                => $id,
            'company_name'      => trim((string)$data['company_name']),
            'contact_person'    => trim((string)$data['contact_person']),
            'phone'             => trim((string)$data['phone']),
            'whatsapp'          => !empty($data['whatsapp']) ? trim((string)$data['whatsapp']) : null,
            'email'             => !empty($data['email']) ? trim((string)$data['email']) : null,
            'address'           => !empty($data['address']) ? trim((string)$data['address']) : null,
            'curtiss_system'    => !empty($data['curtiss_system']) ? trim((string)$data['curtiss_system']) : null,
            'system_url'        => !empty($data['system_url']) ? trim((string)$data['system_url']) : null,
            'installation_date' => !empty($data['installation_date']) ? (string)$data['installation_date'] : null,
            'status'            => (string)($data['status'] ?? 'active'),
            'notes'             => !empty($data['notes']) ? trim((string)$data['notes']) : null
        ]);

        if ($result) {
            $this->activityLog->log(
                $id,
                'Client Updated',
                sprintf("Client details updated (Status: %s)", $data['status']),
                $userId
            );
        }

        return $result;
    }

    /**
     * Toggle archive status
     */
    public function toggleArchive(int $id, ?int $userId = null): bool {
        $client = $this->getById($id);
        if (!$client) {
            return false;
        }

        $newArchiveState = $client['is_archived'] ? 0 : 1;
        $stmt = $this->db->prepare("UPDATE clients SET is_archived = :archived, updated_at = NOW() WHERE id = :id");
        $success = $stmt->execute(['archived' => $newArchiveState, 'id' => $id]);

        if ($success) {
            $action = $newArchiveState ? 'Client Archived' : 'Client Restored';
            $this->activityLog->log($id, $action, "Client {$client['company_name']} {$action}.", $userId);
        }

        return $success;
    }

    /**
     * Get financial and subscription stats for a client profile
     */
    public function getProfileStats(int $clientId): array {
        // Subscriptions count & monthly run-rate
        $subStmt = $this->db->prepare(
            "SELECT 
                COUNT(id) AS total_subs,
                COUNT(CASE WHEN status = 'active' THEN 1 END) AS active_subs,
                COALESCE(SUM(CASE WHEN status = 'active' AND frequency = 'monthly' THEN amount ELSE 0 END), 0) AS monthly_run_rate
             FROM subscriptions
             WHERE client_id = :client_id"
        );
        $subStmt->execute(['client_id' => $clientId]);
        $subStats = $subStmt->fetch() ?: [];

        // Invoices and payments totals using explicit single queries
        $invStmt = $this->db->prepare(
            "SELECT 
                COUNT(id) AS total_invoices,
                COALESCE(SUM(total_amount), 0) AS total_invoiced,
                COALESCE(SUM(paid_amount), 0) AS total_paid,
                COALESCE(SUM(total_amount - paid_amount), 0) AS total_outstanding
             FROM invoices
             WHERE client_id = :client_id AND status != 'cancelled'"
        );
        $invStmt->execute(['client_id' => $clientId]);
        $invStats = $invStmt->fetch() ?: [];

        // Direct payment receipts count
        $payStmt = $this->db->prepare(
            "SELECT COUNT(id) AS total_payments, COALESCE(SUM(amount), 0) AS total_received
             FROM payments
             WHERE client_id = :client_id AND status = 'completed'"
        );
        $payStmt->execute(['client_id' => $clientId]);
        $payStats = $payStmt->fetch() ?: [];

        return [
            'total_subs'         => (int)($subStats['total_subs'] ?? 0),
            'active_subs'        => (int)($subStats['active_subs'] ?? 0),
            'monthly_run_rate'   => (float)($subStats['monthly_run_rate'] ?? 0),
            'total_invoices'     => (int)($invStats['total_invoices'] ?? 0),
            'total_invoiced'     => (float)($invStats['total_invoiced'] ?? 0),
            'total_paid'         => (float)($invStats['total_paid'] ?? 0),
            'total_outstanding'  => (float)($invStats['total_outstanding'] ?? 0),
            'total_payments'     => (int)($payStats['total_payments'] ?? 0),
            'total_received'     => (float)($payStats['total_received'] ?? 0),
        ];
    }

    /**
     * Get payments list for client profile
     */
    public function getPayments(int $clientId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT p.id, p.receipt_number, p.invoice_id, p.payment_date, p.amount, p.payment_method, p.reference_no, p.notes, p.status, i.invoice_number
             FROM payments p
             LEFT JOIN invoices i ON p.invoice_id = i.id
             WHERE p.client_id = :client_id
             ORDER BY p.payment_date DESC, p.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Get invoices list for client profile
     */
    public function getInvoices(int $clientId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT id, invoice_number, issue_date, due_date, total_amount, paid_amount, status
             FROM invoices
             WHERE client_id = :client_id
             ORDER BY issue_date DESC, id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
