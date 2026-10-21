<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Billing Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../services/PdfService.php';

class BillingController {
    private Invoice $invoiceModel;
    private Client $clientModel;
    private Payment $paymentModel;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->invoiceModel = new Invoice();
        $this->clientModel = new Client();
        $this->paymentModel = new Payment();
    }

    /**
     * List invoices with filtering and pagination
     */
    public function index(): void {
        $page = max(1, (int)($_GET['p'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $search = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $clientId = !empty($_GET['client_id']) ? (int)$_GET['client_id'] : null;

        $result = $this->invoiceModel->getList($limit, $offset, $search ?: null, $status ?: null, $clientId);

        $invoices = $result['data'];
        $totalInvoices = $result['total'];
        $totalPages = $result['total_pages'];
        $currentPage = $page;

        $pageTitle = 'Billing & Invoices';
        $activeNav = 'billing';

        require __DIR__ . '/../views/billing/index.php';
    }

    /**
     * Show create invoice form
     */
    public function create(): void {
        $nextInv = $this->invoiceModel->getNextInvoiceNumber();
        $selectedClientId = !empty($_GET['client_id']) ? (int)$_GET['client_id'] : null;

        // Fetch active clients for dropdown with explicit columns
        $clientsResult = $this->clientModel->getList(100, 0, null, 'active', false);
        $clients = $clientsResult['data'];

        $error = $_SESSION['billing_error'] ?? null;
        unset($_SESSION['billing_error']);

        $pageTitle = 'Create Invoice';
        $activeNav = 'billing';

        require __DIR__ . '/../views/billing/create.php';
    }

    /**
     * Store new invoice
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['billing_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=billing_create');
            exit;
        }

        $clientId = (int)($_POST['client_id'] ?? 0);
        if ($clientId <= 0) {
            $_SESSION['billing_error'] = 'Please select a valid client.';
            header('Location: ' . BASE_URL . '/index.php?page=billing_create');
            exit;
        }

        $rawItems = $_POST['items'] ?? [];
        if (!is_array($rawItems) || empty($rawItems)) {
            $_SESSION['billing_error'] = 'Please add at least one line item.';
            header('Location: ' . BASE_URL . '/index.php?page=billing_create');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->invoiceModel->create($_POST, $rawItems, $userId);

        if ($res['success']) {
            $invoiceId = (int)$res['id'];
            // Generate and cache PDF file
            $invoice = $this->invoiceModel->getById($invoiceId);
            $items = $this->invoiceModel->getItems($invoiceId);
            if ($invoice) {
                $pdfPath = PdfService::saveInvoicePdf($invoice, $items);
                $this->invoiceModel->updatePdfPath($invoiceId, $pdfPath);
            }

            $_SESSION['flash_success'] = "Invoice {$res['number']} created successfully.";
            header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . $invoiceId);
            exit;
        }

        $_SESSION['billing_error'] = $res['message'] ?? 'Failed to save invoice.';
        header('Location: ' . BASE_URL . '/index.php?page=billing_create');
        exit;
    }

    /**
     * View invoice details & actions
     */
    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceModel->getById($id);

        if (!$invoice) {
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        $items = $this->invoiceModel->getItems($id);
        $payments = $this->paymentModel->getByInvoiceId($id);

        $db = Database::getConnection();
        $bankStmt = $db->query("SELECT bank_name, branch, account_name, account_number FROM bank_accounts WHERE is_primary = 1 AND status = 'active' LIMIT 1");
        $primaryBank = $bankStmt->fetch() ?: [
            'bank_name' => 'Commercial Bank',
            'branch' => 'Kurunegala City Office',
            'account_name' => 'G L DASUN SUSARA SENARATHNE',
            'account_number' => '8018526390'
        ];

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $pageTitle = 'Invoice ' . $invoice['invoice_number'];
        $activeNav = 'billing';

        require __DIR__ . '/../views/billing/show.php';
    }

    /**
     * Show invoice edit form
     */
    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceModel->getById($id);

        if (!$invoice || in_array($invoice['status'], ['paid', 'cancelled'], true)) {
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        $items = $this->invoiceModel->getItems($id);

        $error = $_SESSION['billing_error'] ?? null;
        unset($_SESSION['billing_error']);

        $pageTitle = 'Edit Invoice ' . $invoice['invoice_number'];
        $activeNav = 'billing';

        require __DIR__ . '/../views/billing/edit.php';
    }

    /**
     * Handle invoice update
     */
    public function update(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['billing_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=billing_edit&id=' . $id);
            exit;
        }

        $rawItems = $_POST['items'] ?? [];
        if (!is_array($rawItems) || empty($rawItems)) {
            $_SESSION['billing_error'] = 'Please add at least one line item.';
            header('Location: ' . BASE_URL . '/index.php?page=billing_edit&id=' . $id);
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->invoiceModel->update($id, $_POST, $rawItems, $userId);

        if ($res['success']) {
            // Re-cache PDF file
            $invoice = $this->invoiceModel->getById($id);
            $items = $this->invoiceModel->getItems($id);
            if ($invoice) {
                $pdfPath = PdfService::saveInvoicePdf($invoice, $items);
                $this->invoiceModel->updatePdfPath($id, $pdfPath);
            }

            $_SESSION['flash_success'] = 'Invoice updated successfully.';
            header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . $id);
            exit;
        }

        $_SESSION['billing_error'] = $res['message'] ?? 'Failed to update invoice.';
        header('Location: ' . BASE_URL . '/index.php?page=billing_edit&id=' . $id);
        exit;
    }

    /**
     * Stream or download PDF
     */
    public function pdf(): void {
        $id = (int)($_GET['id'] ?? 0);
        $download = (isset($_GET['download']) && $_GET['download'] === '1') || (isset($_GET['mode']) && $_GET['mode'] === 'download');

        $invoice = $this->invoiceModel->getById($id);
        if (!$invoice) {
            die('Invoice not found.');
        }

        $items = $this->invoiceModel->getItems($id);
        PdfService::streamInvoicePdf($invoice, $items, $download);
    }

    /**
     * Interactive web print view for invoice
     */
    public function print(): void {
        $id = (int)($_GET['id'] ?? 0);
        $invoice = $this->invoiceModel->getById($id);
        if (!$invoice) {
            die('Invoice not found.');
        }

        $items = $this->invoiceModel->getItems($id);
        echo PdfService::buildInvoiceHtml($invoice, $items, true);
        exit;
    }

    /**
     * Finalize draft invoice
     */
    public function finalize(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . $id);
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $this->invoiceModel->finalize($id, $userId);
        $_SESSION['flash_success'] = 'Invoice finalized and ready for collection.';
        header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . $id);
        exit;
    }

    /**
     * Cancel invoice
     */
    public function cancel(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . $id);
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $this->invoiceModel->cancel($id, $userId);
        $_SESSION['flash_success'] = 'Invoice marked as cancelled.';
        header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . $id);
        exit;
    }

    /**
     * Trigger generation of recurring invoices for all due subscriptions
     */
    public function generateRecurring(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['billing_error'] = 'Invalid security token.';
            header('Location: ' . BASE_URL . '/index.php?page=billing');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->invoiceModel->generateRecurringObligations($userId);
        $count = $res['count'] ?? 0;

        if ($count > 0) {
            $_SESSION['flash_success'] = "Generated {$count} recurring invoice(s) for due subscriptions.";
        } else {
            $_SESSION['flash_info'] = "All active subscriptions are currently up to date. No new invoices were needed.";
        }

        $redirect = !empty($_POST['redirect_to']) && $_POST['redirect_to'] === 'reminders' ? 'reminders' : 'billing';
        header('Location: ' . BASE_URL . '/index.php?page=' . $redirect);
        exit;
    }
}
