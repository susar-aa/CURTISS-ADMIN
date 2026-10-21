<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Payment & Receipt Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLog.php';

class Payment {
    private PDO $db;
    private ActivityLog $activityLog;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->activityLog = new ActivityLog();
    }

    /**
     * Generate 5-digit sequential receipt number e.g. "REC-00001"
     */
    public function getNextReceiptNumber(): string {
        $stmt = $this->db->query("SELECT receipt_number FROM payments ORDER BY id DESC LIMIT 1");
        $lastRec = $stmt->fetchColumn();

        if (!$lastRec) {
            return 'REC-00001';
        }

        $num = (int)preg_replace('/\D/', '', (string)$lastRec);
        return 'REC-' . str_pad((string)($num + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get paginated payments list with explicit JOINs
     */
    public function getList(int $limit = 15, int $offset = 0, ?string $search = null, ?string $method = null, ?int $clientId = null): array {
        $whereClauses = ['1=1'];
        $params = [];

        if (!empty($method)) {
            $whereClauses[] = 'p.payment_method = :method';
            $params['method'] = $method;
        }

        if ($clientId !== null && $clientId > 0) {
            $whereClauses[] = 'p.client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        if (!empty($search)) {
            $cleanSearch = trim($search);
            $whereClauses[] = '(p.receipt_number LIKE :search_rec OR c.company_name LIKE :search_comp OR i.invoice_number LIKE :search_inv OR p.reference_no LIKE :search_ref)';
            $params['search_rec'] = $cleanSearch . '%';
            $params['search_comp'] = $cleanSearch . '%';
            $params['search_inv'] = $cleanSearch . '%';
            $params['search_ref'] = $cleanSearch . '%';
        }

        $whereSql = implode(' AND ', $whereClauses);

        // Count
        $countSql = "SELECT COUNT(p.id) 
                     FROM payments p 
                     INNER JOIN clients c ON p.client_id = c.id 
                     LEFT JOIN invoices i ON p.invoice_id = i.id 
                     WHERE {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $totalRows = (int)$countStmt->fetchColumn();

        // Paginated records
        $sql = "SELECT 
                    p.id, 
                    p.receipt_number, 
                    p.client_id, 
                    p.invoice_id, 
                    p.payment_date, 
                    p.amount, 
                    p.payment_method, 
                    p.reference_no, 
                    p.notes, 
                    p.custom_description,
                    p.proof_path,
                    p.pdf_path,
                    p.status, 
                    p.created_at,
                    c.client_code, 
                    c.company_name, 
                    c.phone,
                    i.invoice_number,
                    i.total_amount AS invoice_total
                FROM payments p
                INNER JOIN clients c ON p.client_id = c.id
                LEFT JOIN invoices i ON p.invoice_id = i.id
                WHERE {$whereSql}
                ORDER BY p.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $records = $stmt->fetchAll() ?: [];

        return [
            'data'        => $records,
            'total'       => $totalRows,
            'limit'       => $limit,
            'offset'      => $offset,
            'total_pages' => $limit > 0 ? (int)ceil($totalRows / $limit) : 1
        ];
    }

    /**
     * Get single payment by ID with explicit JOINs
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT 
                p.id, 
                p.receipt_number, 
                p.client_id, 
                p.invoice_id, 
                p.payment_date, 
                p.amount, 
                p.payment_method, 
                p.reference_no, 
                p.notes, 
                p.custom_description,
                p.proof_path,
                p.pdf_path,
                p.status, 
                p.created_at,
                c.client_code, 
                c.company_name, 
                c.contact_person, 
                c.phone, 
                c.whatsapp, 
                c.email, 
                c.address,
                i.invoice_number, 
                i.total_amount AS invoice_total, 
                i.paid_amount AS invoice_paid, 
                (i.total_amount - i.paid_amount) AS invoice_balance,
                i.status AS invoice_status
             FROM payments p
             INNER JOIN clients c ON p.client_id = c.id
             LEFT JOIN invoices i ON p.invoice_id = i.id
             WHERE p.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get all payments recorded against a specific invoice
     */
    public function getByInvoiceId(int $invoiceId): array {
        $stmt = $this->db->prepare(
            "SELECT 
                p.id, 
                p.receipt_number, 
                p.payment_date, 
                p.amount, 
                p.payment_method, 
                p.reference_no, 
                p.notes, 
                p.proof_path,
                p.pdf_path,
                p.status, 
                p.created_at
             FROM payments p
             WHERE p.invoice_id = :invoice_id
             ORDER BY p.id DESC"
        );
        $stmt->execute(['invoice_id' => $invoiceId]);
        return $stmt->fetchAll();
    }

    /**
     * Record payment with overpayment & duplicate protection in a transaction
     */
    public function record(array $data, ?string $proofPath = null, ?int $userId = null): array {
        $clientId = (int)($data['client_id'] ?? 0);
        $invoiceId = !empty($data['invoice_id']) ? (int)$data['invoice_id'] : null;
        $amount = round((float)($data['amount'] ?? 0), 2);
        $paymentDate = !empty($data['payment_date']) ? (string)$data['payment_date'] : date('Y-m-d');
        $paymentMethod = !empty($data['payment_method']) ? (string)$data['payment_method'] : 'bank_transfer';
        $referenceNo = !empty($data['reference_no']) ? trim((string)$data['reference_no']) : null;
        $notes = !empty($data['notes']) ? trim((string)$data['notes']) : null;
        $customDesc = !empty($data['custom_description']) ? trim((string)$data['custom_description']) : null;

        if ($clientId <= 0 || $amount <= 0.00) {
            return ['success' => false, 'message' => 'Valid client and payment amount are required.'];
        }

        // Duplicate check (same client, same amount, same date, same reference)
        if (!empty($referenceNo)) {
            $dupStmt = $this->db->prepare(
                "SELECT id FROM payments 
                 WHERE client_id = :client_id 
                   AND reference_no = :ref 
                   AND payment_date = :pdate 
                   AND status = 'completed' 
                 LIMIT 1"
            );
            $dupStmt->execute([
                'client_id' => $clientId,
                'ref'       => $referenceNo,
                'pdate'     => $paymentDate
            ]);
            if ($dupStmt->fetch()) {
                return ['success' => false, 'message' => "A payment with reference '{$referenceNo}' is already recorded for this date."];
            }
        }

        $receiptNumber = $this->getNextReceiptNumber();

        $this->db->beginTransaction();

        try {
            // Check invoice if attached
            if ($invoiceId !== null && $invoiceId > 0) {
                $invStmt = $this->db->prepare(
                    "SELECT id, invoice_number, total_amount, paid_amount, status 
                     FROM invoices 
                     WHERE id = :inv_id FOR UPDATE"
                );
                $invStmt->execute(['inv_id' => $invoiceId]);
                $invoice = $invStmt->fetch();

                if (!$invoice) {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Specified invoice not found.'];
                }

                if ($invoice['status'] === 'cancelled') {
                    $this->db->rollBack();
                    return ['success' => false, 'message' => 'Cannot record payment for a cancelled invoice.'];
                }

                $remainingBalance = round((float)$invoice['total_amount'] - (float)$invoice['paid_amount'], 2);

                // Prevent Overpayment
                if ($amount > $remainingBalance + 0.001) {
                    $this->db->rollBack();
                    return [
                        'success' => false, 
                        'message' => sprintf("Payment amount (LKR %s) exceeds remaining invoice balance (LKR %s).", number_format($amount, 2), number_format($remainingBalance, 2))
                    ];
                }

                // Update invoice paid amount and status
                $newPaidAmount = round((float)$invoice['paid_amount'] + $amount, 2);
                $newInvoiceStatus = ($newPaidAmount >= (float)$invoice['total_amount']) ? 'paid' : 'partially_paid';

                $updateInvStmt = $this->db->prepare(
                    "UPDATE invoices SET 
                        paid_amount = :paid, 
                        status = :status, 
                        updated_at = NOW() 
                     WHERE id = :id"
                );
                $updateInvStmt->execute([
                    'paid'   => $newPaidAmount,
                    'status' => $newInvoiceStatus,
                    'id'     => $invoiceId
                ]);
            }

            // Insert Payment
            $payStmt = $this->db->prepare(
                "INSERT INTO payments (
                    receipt_number, client_id, invoice_id, payment_date, amount, 
                    payment_method, reference_no, notes, custom_description, proof_path, status, created_at
                 ) VALUES (
                    :receipt_number, :client_id, :invoice_id, :payment_date, :amount, 
                    :payment_method, :reference_no, :notes, :custom_desc, :proof_path, 'completed', NOW()
                 )"
            );
            $payStmt->execute([
                'receipt_number' => $receiptNumber,
                'client_id'      => $clientId,
                'invoice_id'     => $invoiceId,
                'payment_date'   => $paymentDate,
                'amount'         => $amount,
                'payment_method' => $paymentMethod,
                'reference_no'   => $referenceNo,
                'notes'          => $notes,
                'custom_desc'    => $customDesc,
                'proof_path'     => $proofPath
            ]);

            $paymentId = (int)$this->db->lastInsertId();

            $logDesc = sprintf("Recorded payment %s of LKR %s (%s).", $receiptNumber, number_format($amount, 2), ucwords(str_replace('_', ' ', $paymentMethod)));
            if ($invoiceId) {
                $logDesc .= " Applied to invoice #{$invoice['invoice_number']}.";
            }
            $this->activityLog->log($clientId, 'Payment Received', $logDesc, $userId);

            $this->db->commit();

            return [
                'success' => true,
                'id'      => $paymentId,
                'receipt' => $receiptNumber
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()];
        }
    }

    /**
     * Cancel/Reverse payment transaction (Reverses invoice balance without deleting audit record)
     */
    public function cancel(int $id, string $reason, ?int $userId = null): array {
        $payment = $this->getById($id);
        if (!$payment) {
            return ['success' => false, 'message' => 'Payment record not found.'];
        }

        if ($payment['status'] === 'cancelled') {
            return ['success' => false, 'message' => 'This payment is already cancelled.'];
        }

        $this->db->beginTransaction();

        try {
            // If attached to an invoice, deduct paid_amount and restore invoice status
            if (!empty($payment['invoice_id'])) {
                $invStmt = $this->db->prepare("SELECT id, invoice_number, total_amount, paid_amount FROM invoices WHERE id = :id FOR UPDATE");
                $invStmt->execute(['id' => $payment['invoice_id']]);
                $invoice = $invStmt->fetch();

                if ($invoice) {
                    $newPaid = max(0.00, round((float)$invoice['paid_amount'] - (float)$payment['amount'], 2));
                    $newStatus = ($newPaid <= 0.00) ? 'unpaid' : 'partially_paid';

                    $upInv = $this->db->prepare("UPDATE invoices SET paid_amount = :paid, status = :status, updated_at = NOW() WHERE id = :id");
                    $upInv->execute([
                        'paid'   => $newPaid,
                        'status' => $newStatus,
                        'id'     => $payment['invoice_id']
                    ]);
                }
            }

            // Mark payment as cancelled
            $cancelNote = !empty($payment['notes']) ? ($payment['notes'] . " | Cancelled: " . $reason) : ("Cancelled: " . $reason);
            $payUp = $this->db->prepare("UPDATE payments SET status = 'cancelled', notes = :notes, updated_at = NOW() WHERE id = :id");
            $payUp->execute([
                'notes' => $cancelNote,
                'id'    => $id
            ]);

            $this->activityLog->log(
                (int)$payment['client_id'],
                'Payment Cancelled/Reversed',
                sprintf("Reversed receipt %s (LKR %s). Reason: %s", $payment['receipt_number'], number_format((float)$payment['amount'], 2), $reason),
                $userId
            );

            $this->db->commit();

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Reversal failed: ' . $e->getMessage()];
        }
    }

    /**
     * Update saved PDF path
     */
    public function updatePdfPath(int $id, string $path): void {
        $stmt = $this->db->prepare("UPDATE payments SET pdf_path = :path WHERE id = :id");
        $stmt->execute(['path' => $path, 'id' => $id]);
    }
}
