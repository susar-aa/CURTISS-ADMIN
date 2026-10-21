<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Subscription Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/Subscription.php';

class SubscriptionController {
    private Subscription $subscriptionModel;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Store new subscription
     */
    public function store(): void {
        $clientId = (int)($_POST['client_id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $clientId <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $clientId . '&tab=subscriptions');
            exit;
        }

        $planName = trim((string)($_POST['plan_name'] ?? ''));
        $amount = (float)($_POST['amount'] ?? 0);
        $frequency = (string)($_POST['frequency'] ?? 'monthly');
        $startDate = (string)($_POST['start_date'] ?? date('Y-m-d'));

        if ($planName === '' || $amount <= 0) {
            $_SESSION['flash_error'] = 'Please enter a valid plan name and subscription amount.';
            header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $clientId . '&tab=subscriptions');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->subscriptionModel->create($_POST, $userId);

        if ($res['success']) {
            $_SESSION['flash_success'] = "Subscription '{$planName}' added successfully.";
        } else {
            $_SESSION['flash_error'] = $res['message'] ?? 'Failed to add subscription.';
        }

        header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $clientId . '&tab=subscriptions');
        exit;
    }

    /**
     * Update existing subscription
     */
    public function update(): void {
        $id = (int)($_POST['id'] ?? 0);
        $clientId = (int)($_POST['client_id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0 || $clientId <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $clientId . '&tab=subscriptions');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $updated = $this->subscriptionModel->update($id, $_POST, $userId);

        if ($updated) {
            $_SESSION['flash_success'] = 'Subscription updated successfully.';
        } else {
            $_SESSION['flash_error'] = 'Failed to update subscription.';
        }

        header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $clientId . '&tab=subscriptions');
        exit;
    }

    /**
     * Renew subscription / change renewal due date
     */
    public function renew(): void {
        $id = (int)($_POST['id'] ?? 0);
        $clientId = (int)($_POST['client_id'] ?? 0);
        $customDueDate = !empty($_POST['custom_due_date']) ? (string)$_POST['custom_due_date'] : null;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $id <= 0 || $clientId <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=clients');
            exit;
        }

        if (!AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['flash_error'] = 'Security validation failed (CSRF).';
            header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $clientId . '&tab=subscriptions');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = $user['id'] ? (int)$user['id'] : null;

        $res = $this->subscriptionModel->renew($id, $customDueDate, $userId);

        if ($res['success']) {
            $_SESSION['flash_success'] = 'Subscription renewed successfully. Next due: ' . $res['new_due_date'];
        } else {
            $_SESSION['flash_error'] = $res['message'] ?? 'Failed to renew subscription.';
        }

        header('Location: ' . BASE_URL . '/index.php?page=client_view&id=' . $clientId . '&tab=subscriptions');
        exit;
    }
}
