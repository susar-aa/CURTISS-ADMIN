<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Invoice Model
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ActivityLog.php';

class Invoice {
    private PDO $db;
    private ActivityLog $activityLog;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->activityLog = new ActivityLog();
    }

    /**
     * Generate 5-digit sequential invoice number e.g. "INV-00001"
     */
    public function getNextInvoiceNumber(): string {
        $stmt = $this->db->query(
            "SELECT invoice_number 
             FROM invoices 
             ORDER BY id DESC 
             LIMIT 1"
        );
        $lastInv = $stmt->fetchColumn();

        if (!$lastInv) {
            return 'INV-00001';
        }

        // Extract digits
        $num = (int)preg_replace('/\D/', '', (string)$lastInv);
        return 'INV-' . str_pad((string)($num + 1), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Get paginated invoice list with client JOIN and filters
     */
    public function getList(int $limit = 15, int $offset = 0, ?string $search = null, ?string $status = null, ?int $clientId = null): array {
        $whereClauses = ['1=1'];
        $params = [];

        if (!empty($status)) {
            $whereClauses[] = 'i.status = :status';
            $params['status'] = $status;
        }

        if ($clientId !== null && $clientId > 0) {
            $whereClauses[] = 'i.client_id = :client_id';
            $params['client_id'] = $clientId;
        }

        if (!empty($search)) {
            $cleanSearch = trim($search);
            // Search by prefix or exact code using index
            $whereClauses[] = '(i.invoice_number LIKE :search_inv OR c.company_name LIKE :search_comp OR c.client_code = :search_code)';
            $params['search_inv'] = $cleanSearch . '%';
            $params['search_comp'] = $cleanSearch . '%';
            $params['search_code'] = $cleanSearch;
        }

        $whereSql = implode(' AND ', $whereClauses);

        // 1. Total Count for pagination
        $countSql = "SELECT COUNT(i.id) 
                     FROM invoices i 
                     INNER JOIN clients c ON i.client_id = c.id 
                     WHERE {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $totalRows = (int)$countStmt->fetchColumn();

        // 2. Fetch paginated records with explicit column selection
        $sql = "SELECT 
                    i.id, 
                    i.invoice_number, 
                    i.client_id, 
                    i.issue_date, 
                    i.due_date, 
                    i.subtotal, 
                    i.discount, 
                    i.tax, 
                    i.total_amount, 
                    i.paid_amount, 
                    (i.total_amount - i.paid_amount) AS balance_due,
                    i.status, 
                    i.pdf_path,
                    i.created_at,
                    c.client_code, 
                    c.company_name, 
                    c.contact_person, 
                    c.phone
                FROM invoices i
                INNER JOIN clients c ON i.client_id = c.id
                WHERE {$whereSql}
                ORDER BY i.id DESC
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
     * Get single invoice with client details by ID
     */
    public function getById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT 
                i.id, 
                i.invoice_number, 
                i.client_id, 
                i.issue_date, 
                i.due_date, 
                i.subtotal, 
                i.discount, 
                i.tax, 
                i.total_amount, 
                i.paid_amount, 
                (i.total_amount - i.paid_amount) AS balance_due,
                i.status, 
                i.payment_terms, 
                i.notes, 
                i.pdf_path, 
                i.created_at, 
                i.updated_at,
                c.client_code, 
                c.company_name, 
                c.contact_person, 
                c.phone, 
                c.whatsapp, 
                c.email, 
                c.address,
                c.curtiss_system
             FROM invoices i
             INNER JOIN clients c ON i.client_id = c.id
             WHERE i.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $inv = $stmt->fetch();
        return $inv ?: null;
    }

    /**
     * Get line items for an invoice
     */
    public function getItems(int $invoiceId): array {
        $stmt = $this->db->prepare(
            "SELECT id, invoice_id, item_order, description, quantity, unit_price, line_total
             FROM invoice_items
             WHERE invoice_id = :invoice_id
             ORDER BY item_order ASC, id ASC"
        );
        $stmt->execute(['invoice_id' => $invoiceId]);
        return $stmt->fetchAll() ?: [];
    }

    /**
     * Create invoice and line items in a single transaction with decimal-safe arithmetic
     */
    public function create(array $data, array $items, ?int $userId = null): array {
        $clientId = (int)($data['client_id'] ?? 0);
        $invoiceNumber = trim((string)($data['invoice_number'] ?? ''));
        if (empty($invoiceNumber)) {
            $invoiceNumber = $this->getNextInvoiceNumber();
        }

        $issueDate = (string)($data['issue_date'] ?? date('Y-m-d'));
        $dueDate = (string)($data['due_date'] ?? date('Y-m-d', strtotime('+14 days')));
        $discount = max(0.00, (float)($data['discount'] ?? 0.00));
        $tax = max(0.00, (float)($data['tax'] ?? 0.00));
        $paymentTerms = !empty($data['payment_terms']) ? trim((string)$data['payment_terms']) : 'Due upon receipt';
        $notes = !empty($data['notes']) ? trim((string)$data['notes']) : null;
        $status = (string)($data['status'] ?? 'unpaid'); // 'draft' or 'unpaid'

        // Calculate line items server-side with decimal safety
        $subtotal = 0.00;
        $processedItems = [];
        $order = 1;

        foreach ($items as $item) {
            $desc = trim((string)($item['description'] ?? ''));
            if ($desc === '') {
                continue;
            }
            $qty = max(0.01, (float)($item['quantity'] ?? 1.00));
            $unitPrice = max(0.00, (float)($item['unit_price'] ?? 0.00));
            $lineTotal = round($qty * $unitPrice, 2);
            $subtotal += $lineTotal;

            $processedItems[] = [
                'order'      => $order++,
                'desc'       => $desc,
                'qty'        => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal
            ];
        }

        if (empty($processedItems)) {
            return ['success' => false, 'message' => 'Please add at least one line item with a description and unit price.'];
        }

        $subtotal = round($subtotal, 2);
        $totalAmount = round($subtotal - $discount + $tax, 2);
        if ($totalAmount < 0.00) {
            $totalAmount = 0.00;
        }

        $this->db->beginTransaction();

        try {
            // Check uniqueness of invoice number
            $chk = $this->db->prepare("SELECT id FROM invoices WHERE invoice_number = :num LIMIT 1");
            $chk->execute(['num' => $invoiceNumber]);
            if ($chk->fetch()) {
                $invoiceNumber = $this->getNextInvoiceNumber();
            }

            // Insert Invoice
            $stmt = $this->db->prepare(
                "INSERT INTO invoices (
                    invoice_number, client_id, issue_date, due_date, subtotal, discount, tax, 
                    total_amount, paid_amount, status, payment_terms, notes, created_at
                 ) VALUES (
                    :invoice_number, :client_id, :issue_date, :due_date, :subtotal, :discount, :tax, 
                    :total_amount, 0.00, :status, :payment_terms, :notes, NOW()
                 )"
            );

            $stmt->execute([
                'invoice_number' => $invoiceNumber,
                'client_id'      => $clientId,
                'issue_date'     => $issueDate,
                'due_date'       => $dueDate,
                'subtotal'       => $subtotal,
                'discount'       => $discount,
                'tax'            => $tax,
                'total_amount'   => $totalAmount,
                'status'         => $status,
                'payment_terms'  => $paymentTerms,
                'notes'          => $notes
            ]);

            $invoiceId = (int)$this->db->lastInsertId();

            // Batch insert items to prevent N+1 queries
            $itemSql = "INSERT INTO invoice_items (invoice_id, item_order, description, quantity, unit_price, line_total, created_at) VALUES ";
            $values = [];
            $itemParams = [];

            foreach ($processedItems as $i => $item) {
                $values[] = "(:inv_id_{$i}, :order_{$i}, :desc_{$i}, :qty_{$i}, :price_{$i}, :total_{$i}, NOW())";
                $itemParams["inv_id_{$i}"] = $invoiceId;
                $itemParams["order_{$i}"] = $item['order'];
                $itemParams["desc_{$i}"] = $item['desc'];
                $itemParams["qty_{$i}"] = $item['qty'];
                $itemParams["price_{$i}"] = $item['unit_price'];
                $itemParams["total_{$i}"] = $item['line_total'];
            }

            $itemSql .= implode(', ', $values);
            $itemStmt = $this->db->prepare($itemSql);
            $itemStmt->execute($itemParams);

            $this->activityLog->log(
                $clientId,
                'Invoice Created',
                sprintf("Created invoice %s (%s, Total: LKR %s)", $invoiceNumber, ucfirst($status), number_format($totalAmount, 2)),
                $userId
            );

            $this->db->commit();

            return [
                'success' => true,
                'id'      => $invoiceId,
                'number'  => $invoiceNumber
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to create invoice: ' . $e->getMessage()];
        }
    }

    /**
     * Update invoice details and items (Prevent accidental modification of finalized/paid invoices)
     */
    public function update(int $id, array $data, array $items, ?int $userId = null): array {
        $inv = $this->getById($id);
        if (!$inv) {
            return ['success' => false, 'message' => 'Invoice not found.'];
        }

        // Prevent modifying paid or cancelled invoices
        if (in_array($inv['status'], ['paid', 'cancelled'], true)) {
            return ['success' => false, 'message' => "Cannot modify an invoice with status '{$inv['status']}'."];
        }

        $issueDate = (string)($data['issue_date'] ?? $inv['issue_date']);
        $dueDate = (string)($data['due_date'] ?? $inv['due_date']);
        $discount = max(0.00, (float)($data['discount'] ?? 0.00));
        $tax = max(0.00, (float)($data['tax'] ?? 0.00));
        $paymentTerms = !empty($data['payment_terms']) ? trim((string)$data['payment_terms']) : $inv['payment_terms'];
        $notes = !empty($data['notes']) ? trim((string)$data['notes']) : null;
        $status = (string)($data['status'] ?? $inv['status']);

        // Calculate line items server-side with decimal safety
        $subtotal = 0.00;
        $processedItems = [];
        $order = 1;

        foreach ($items as $item) {
            $desc = trim((string)($item['description'] ?? ''));
            if ($desc === '') {
                continue;
            }
            $qty = max(0.01, (float)($item['quantity'] ?? 1.00));
            $unitPrice = max(0.00, (float)($item['unit_price'] ?? 0.00));
            $lineTotal = round($qty * $unitPrice, 2);
            $subtotal += $lineTotal;

            $processedItems[] = [
                'order'      => $order++,
                'desc'       => $desc,
                'qty'        => $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal
            ];
        }

        if (empty($processedItems)) {
            return ['success' => false, 'message' => 'Please add at least one line item with a description and unit price.'];
        }

        $subtotal = round($subtotal, 2);
        $totalAmount = round($subtotal - $discount + $tax, 2);
        if ($totalAmount < 0.00) {
            $totalAmount = 0.00;
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                "UPDATE invoices SET
                    issue_date = :issue_date,
                    due_date = :due_date,
                    subtotal = :subtotal,
                    discount = :discount,
                    tax = :tax,
                    total_amount = :total_amount,
                    status = :status,
                    payment_terms = :payment_terms,
                    notes = :notes,
                    updated_at = NOW()
                 WHERE id = :id"
            );

            $stmt->execute([
                'id'            => $id,
                'issue_date'    => $issueDate,
                'due_date'      => $dueDate,
                'subtotal'      => $subtotal,
                'discount'      => $discount,
                'tax'           => $tax,
                'total_amount'  => $totalAmount,
                'status'        => $status,
                'payment_terms' => $paymentTerms,
                'notes'         => $notes
            ]);

            // Replace line items
            $delStmt = $this->db->prepare("DELETE FROM invoice_items WHERE invoice_id = :inv_id");
            $delStmt->execute(['inv_id' => $id]);

            $itemSql = "INSERT INTO invoice_items (invoice_id, item_order, description, quantity, unit_price, line_total, created_at) VALUES ";
            $values = [];
            $itemParams = [];

            foreach ($processedItems as $i => $item) {
                $values[] = "(:inv_id_{$i}, :order_{$i}, :desc_{$i}, :qty_{$i}, :price_{$i}, :total_{$i}, NOW())";
                $itemParams["inv_id_{$i}"] = $id;
                $itemParams["order_{$i}"] = $item['order'];
                $itemParams["desc_{$i}"] = $item['desc'];
                $itemParams["qty_{$i}"] = $item['qty'];
                $itemParams["price_{$i}"] = $item['unit_price'];
                $itemParams["total_{$i}"] = $item['line_total'];
            }

            $itemSql .= implode(', ', $values);
            $itemStmt = $this->db->prepare($itemSql);
            $itemStmt->execute($itemParams);

            $this->activityLog->log(
                (int)$inv['client_id'],
                'Invoice Updated',
                sprintf("Updated invoice %s (Total: LKR %s, Status: %s)", $inv['invoice_number'], number_format($totalAmount, 2), ucfirst($status)),
                $userId
            );

            $this->db->commit();

            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to update invoice: ' . $e->getMessage()];
        }
    }

    /**
     * Finalize a draft invoice to active unpaid status
     */
    public function finalize(int $id, ?int $userId = null): bool {
        $inv = $this->getById($id);
        if (!$inv || $inv['status'] !== 'draft') {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE invoices SET status = 'unpaid', updated_at = NOW() WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);

        if ($success) {
            $this->activityLog->log(
                (int)$inv['client_id'],
                'Invoice Finalized',
                sprintf("Finalized draft invoice %s as ready for billing.", $inv['invoice_number']),
                $userId
            );
        }

        return $success;
    }

    /**
     * Cancel an invoice
     */
    public function cancel(int $id, ?int $userId = null): bool {
        $inv = $this->getById($id);
        if (!$inv || $inv['status'] === 'cancelled' || $inv['status'] === 'paid') {
            return false;
        }

        $stmt = $this->db->prepare("UPDATE invoices SET status = 'cancelled', updated_at = NOW() WHERE id = :id");
        $success = $stmt->execute(['id' => $id]);

        if ($success) {
            $this->activityLog->log(
                (int)$inv['client_id'],
                'Invoice Cancelled',
                sprintf("Cancelled invoice %s (Amount: LKR %s).", $inv['invoice_number'], number_format((float)$inv['total_amount'], 2)),
                $userId
            );
        }

        return $success;
    }

    /**
     * Update saved PDF file path
     */
    public function updatePdfPath(int $id, string $path): void {
        $stmt = $this->db->prepare("UPDATE invoices SET pdf_path = :path WHERE id = :id");
        $stmt->execute(['path' => $path, 'id' => $id]);
    }

    /**
     * Automatically generate recurring billing obligations based on subscription frequency
     * (Prevents duplicate billing for the same period)
     */
    public function generateRecurringObligations(?int $userId = null): array {
        require_once __DIR__ . '/Subscription.php';
        $today = date('Y-m-d');

        $stmt = $this->db->prepare(
            "SELECT s.id, s.client_id, s.plan_name, s.frequency, s.amount, s.start_date, s.next_due_date, c.company_name
             FROM subscriptions s
             INNER JOIN clients c ON s.client_id = c.id
             WHERE s.status = 'active' 
               AND s.next_due_date IS NOT NULL 
               AND s.next_due_date <= :today"
        );
        $stmt->execute(['today' => $today]);
        $dueSubs = $stmt->fetchAll() ?: [];

        $generatedCount = 0;
        foreach ($dueSubs as $sub) {
            $desc = $sub['plan_name'] . ' (' . ucfirst($sub['frequency']) . ' Subscription)';

            // Check if invoice for this client, due date and item already exists
            $chk = $this->db->prepare(
                "SELECT i.id FROM invoices i 
                 INNER JOIN invoice_items ii ON i.id = ii.invoice_id
                 WHERE i.client_id = :client_id 
                   AND i.due_date = :due_date 
                   AND ii.description = :desc
                 LIMIT 1"
            );
            $chk->execute([
                'client_id' => $sub['client_id'],
                'due_date'  => $sub['next_due_date'],
                'desc'      => $desc
            ]);
            if ($chk->fetch()) {
                continue; // Duplicate period prevented
            }

            $invRes = $this->create([
                'client_id'      => $sub['client_id'],
                'invoice_number' => $this->getNextInvoiceNumber(),
                'issue_date'     => $today,
                'due_date'       => $sub['next_due_date'],
                'discount'       => 0.00,
                'tax'            => 0.00,
                'status'         => 'unpaid',
                'payment_terms'  => 'Recurring Subscription Due'
            ], [
                [
                    'description' => $desc,
                    'quantity'    => 1.00,
                    'unit_price'  => (float)$sub['amount']
                ]
            ], $userId);

            if ($invRes['success']) {
                $generatedCount++;
                $newDue = Subscription::computeDueDate($sub['frequency'], $sub['next_due_date']);
                if ($newDue) {
                    $upSub = $this->db->prepare("UPDATE subscriptions SET next_due_date = :ndue, updated_at = NOW() WHERE id = :id");
                    $upSub->execute(['ndue' => $newDue, 'id' => $sub['id']]);
                }
            }
        }

        return ['count' => $generatedCount];
    }
}
