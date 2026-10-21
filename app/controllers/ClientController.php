<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Client Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/Subscription.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class ClientController {
    private Client $clientModel;
    private Subscription $subscriptionModel;
    private ActivityLog $activityLog;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->clientModel = new Client();
        $this->subscriptionModel = new Subscription();
        $this->activityLog = new ActivityLog();
    }

    /**
     * List clients with search, filter, and pagination
     */
    public function index(): void {
        $page = max(1, (int)($_GET['p'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;

        $search = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $archived = isset($_GET['archived']) && $_GET['archived'] === '1';

        $result = $this->clientModel->getList(
            $limit, 
            $offset, 
            $search ?: null, 
            $status ?: null, 
            $archived
        );

        $clients = $result['data'];
        $totalClients = $result['total'];
        $totalPages = $result['total_pages'];
        $currentPage = $page;

        $pageTitle = $archived ? 'Archived Clients' : 'Clients';
        $activeNav = 'clients';

        require __DIR__ . '/../views/clients/index.php';
    }

    /**
     * Show create form
     */
    public function create(): void {
        $nextCode = $this->clientModel->getNextClientCode();
        $pageTitle = 'Add New Client';
        $activeNav = 'clients';

        $error = $_SESSION['client_error'] ?? null;
        unset($_SESSION['client_error']);

        require __DIR__ . '/../views/clients/create.php';
    }

    /**
     * Handle client store submission
     */
    public function store(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['client_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=client_create');
            exit;
        }

        $companyName = trim((string)($_POST['company_name'] ?? ''));
        $contactPerson = trim((string)($_POST['contact_person'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));

        if ($companyName === '' || $contactPerson === '' || $phone === '') {
            $_SESSION['client_error'] = 'Company name, contact person, and phone number are required.';
            header('Location: ' . BASE_URL . '/index.php?page=client_create');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->clientModel->create($_POST, $userId);

        if ($res['success']) {
            $_SESSION['flash_success'] = "Client '{$companyName}' ({$res['code']}) created successfully.";
            header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $res['id']);
            exit;
        }

        $_SESSION['client_error'] = 'Failed to create client.';
        header('Location: ' . BASE_URL . '/index.php?page=client_create');
        exit;
    }

    /**
     * Show client profile with tabs
     */
    public function show(): void {
        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->getById($id);

        if (!$client) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        $tab = trim((string)($_GET['tab'] ?? 'overview'));
        if (!in_array($tab, ['overview', 'subscriptions', 'payments', 'receipts', 'activity'], true)) {
            $tab = 'overview';
        }

        $stats = $this->clientModel->getProfileStats($id);
        $subscriptions = $this->subscriptionModel->getByClientId($id);
        $payments = $this->clientModel->getPayments($id, 20);
        $invoices = $this->clientModel->getInvoices($id, 20);
        $activities = $this->activityLog->getByClientId($id, 25);

        $flashSuccess = $_SESSION['flash_success'] ?? null;
        $flashError = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_success'], $_SESSION['flash_error']);

        $pageTitle = $client['company_name'];
        $activeNav = 'clients';

        require __DIR__ . '/../views/clients/show.php';
    }

    /**
     * Show edit form
     */
    public function edit(): void {
        $id = (int)($_GET['id'] ?? 0);
        $client = $this->clientModel->getById($id);

        if (!$client) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        $error = $_SESSION['client_error'] ?? null;
        unset($_SESSION['client_error']);

        $pageTitle = 'Edit Client | ' . $client['company_name'];
        $activeNav = 'clients';

        require __DIR__ . '/../views/clients/edit.php';
    }

    /**
     * Handle client update
     */
    public function update(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['client_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=client_edit&id=' . $id);
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $updated = $this->clientModel->update($id, $_POST, $userId);

        if ($updated) {
            $_SESSION['flash_success'] = 'Client updated successfully.';
            header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $id);
            exit;
        }

        $_SESSION['client_error'] = 'Failed to update client.';
        header('Location: ' . BASE_URL . '/index.php?page=client_edit&id=' . $id);
        exit;
    }

    /**
     * Handle archive / restore
     */
    public function archive(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $this->clientModel->toggleArchive($id, $userId);
        $_SESSION['flash_success'] = 'Client status updated.';
        header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $id);
        exit;
    }
}
