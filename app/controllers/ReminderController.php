<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Due Date & Reminder Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/Reminder.php';

class ReminderController {
    private Reminder $reminderModel;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->reminderModel = new Reminder();
    }

    /**
     * Display due date tracking timeline & WhatsApp reminder center
     */
    public function index(): void {
        $obligations = $this->reminderModel->getUpcomingObligations();

        $pageTitle = 'Due Dates & Reminders';
        $activeNav = 'reminders';

        require __DIR__ . '/../views/reminders/index.php';
    }

    /**
     * Log reminder sent and redirect to WhatsApp
     */
    public function send(): void {
        $clientId = (int)($_GET['client_id'] ?? 0);
        $type = (string)($_GET['type'] ?? 'custom');
        $invId = !empty($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : null;
        $subId = !empty($_GET['sub_id']) ? (int)$_GET['sub_id'] : null;
        $targetUrl = urldecode((string)($_GET['wa_url'] ?? ''));

        if ($clientId > 0) {
            $user = AuthHelper::getCurrentUser();
            $userId = $user['id'] ? (int)$user['id'] : null;
            $this->reminderModel->log($clientId, $invId, $subId, $type, 'Sent WhatsApp reminder', $userId);
        }

        if (!empty($targetUrl)) {
            header('Location: ' . $targetUrl);
            exit;
        }

        header('Location: ' . BASE_URL . '/index.php?page=reminders');
        exit;
    }
}
