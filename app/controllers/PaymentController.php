<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Payment & Receipt Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/Invoice.php';
require_once __DIR__ . '/../services/PdfService.php';

class PaymentController {
    private Payment $paymentModel;
    private Client $clientModel;
    private Invoice $invoiceModel;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->paymentModel = new Payment();
        $this->clientModel = new Client();
        $this->invoiceModel = new Invoice();
    }

    /**
     * List all payments & receipts
     */
    public function index(): void {
        $page = max(1, (int)($_GET['p'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $search = trim((string)($_GET['q'] ?? ''));
        $method = trim((string)($_GET['method'] ?? ''));
        $clientId = !empty($_GET['client_id']) ? (int)$_GET['client_id'] : null;

        $result = $this->paymentModel->getList($limit, $offset, $search ?: null, $method ?: null, $clientId);

        $payments = $result['data'];
        $totalPayments = $result['total'];
        $totalPages = $result['total_pages'];
        $currentPage = $page;

        $pageTitle = 'Payments & Receipts';
        $activeNav = 'payments';

        require __DIR__ . '/../views/payments/index.php';
    }

    /**
     * Show record payment form
     */
    public function create(): void {
        $nextRec = $this->paymentModel->getNextReceiptNumber();
        $preselectedInvoiceId = !empty($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : null;
        $preselectedClientId = !empty($_GET['client_id']) ? (int)$_GET['client_id'] : null;

        $invoice = null;
        if ($preselectedInvoiceId) {
            $invoice = $this->invoiceModel->getById($preselectedInvoiceId);
            if ($invoice) {
                $preselectedClientId = (int)$invoice['client_id'];
            }
        }

        $clientsResult = $this->clientModel->getList(100, 0, null, 'active', false);
        $clients = $clientsResult['data'];

        $error = $_SESSION['payment_error'] ?? null;
        unset($_SESSION['payment_error']);

        $pageTitle = 'Record Payment';
        $activeNav = 'payments';

        require __DIR__ . '/../views/payments/create.php';
    }

    /**
     * Store payment transaction
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=payments');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['payment_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=payment_create');
            exit;
        }

        $proofPath = null;
        if (isset($_FILES['proof_file']) && $_FILES['proof_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['proof_file'];
            $allowedExts = ['jpg', 'jpeg', 'png', 'pdf'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (in_array($ext, $allowedExts, true) && $file['size'] <= 5 * 1024 * 1024) {
                $uploadDir = __DIR__ . '/../../storage/proofs';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $filename = 'proof_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetFile = $uploadDir . '/' . $filename;
                if (move_uploaded_file($file['tmp_name'], $targetFile)) {
                    $proofPath = $targetFile;
                }
            }
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->paymentModel->record($_POST, $proofPath, $userId);

        if ($res['success']) {
            $paymentId = (int)$res['id'];
            $payment = $this->paymentModel->getById($paymentId);
            if ($payment) {
                $pdfPath = PdfService::saveReceiptPdf($payment);
                $this->paymentModel->updatePdfPath($paymentId, $pdfPath);
            }

            // If payment was recorded against an invoice, update invoice PDF
            if (!empty($_POST['invoice_id'])) {
                $invId = (int)$_POST['invoice_id'];
                $updatedInv = $this->invoiceModel->getById($invId);
                if ($updatedInv) {
                    $invItems = $this->invoiceModel->getItems($invId);
                    $invPdf = PdfService::saveInvoicePdf($updatedInv, $invItems);
                    $this->invoiceModel->updatePdfPath($invId, $invPdf);
                }
            }

            $_SESSION['flash_success'] = "Payment {$res['receipt']} recorded successfully.";

            if (!empty($_POST['redirect_to']) && $_POST['redirect_to'] === 'billing' && !empty($_POST['invoice_id'])) {
                header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . (int)$_POST['invoice_id']);
                exit;
            }

            header('Location: ' . BASE_URL . '/index.php?page=payment_view&id=' . $paymentId);
            exit;
        }

        $_SESSION['payment_error'] = $res['message'] ?? 'Failed to record payment.';

        if (!empty($_POST['redirect_to']) && $_POST['redirect_to'] === 'billing' && !empty($_POST['invoice_id'])) {
            $_SESSION['flash_error'] = $res['message'] ?? 'Failed to record payment.';
            header('Location: ' . BASE_URL . '/index.php?page=billing_view&id=' . (int)$_POST['invoice_id']);
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php?page=payment_create');
        exit;
    }

    /**
     * View payment receipt details
     */
    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $payment = $this->paymentModel->getById($id);

        if (!$payment) {
            header('Location: ' . BASE_URL . '/index.php?page=payments');
            exit;
        }

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $pageTitle = 'Receipt ' . $payment['receipt_number'];
        $activeNav = 'payments';

        require __DIR__ . '/../views/payments/show.php';
    }

    /**
     * Stream or download Receipt PDF
     */
    public function pdf(): void {
        $id = (int)($_GET['id'] ?? 0);
        $download = isset($_GET['download']) && $_GET['download'] === '1';

        $payment = $this->paymentModel->getById($id);
        if (!$payment) {
            die('Payment record not found.');
        }

        PdfService::streamReceiptPdf($payment, $download);
    }

    /**
     * Reverse / Cancel payment transaction
     */
    public function cancel(): void {
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=payments');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=payment_view&id=' . $id);
            exit;
        }

        if ($reason === '') {
            $_SESSION['flash_error'] = 'Cancellation reason is required.';
            header('Location: ' . BASE_URL . '/index.php?page=payment_view&id=' . $id);
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->paymentModel->cancel($id, $reason, $userId);

        if ($res['success']) {
            $_SESSION['flash_success'] = 'Payment reversed successfully. Invoice balance restored.';
        } else {
            $_SESSION['flash_error'] = $res['message'] ?? 'Failed to reverse payment.';
        }

        header('Location: ' . BASE_URL . '/index.php?page=payment_view&id=' . $id);
        exit;
    }
}
