<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Front Controller / Router
 */

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';
require_once __DIR__ . '/app/helpers/AuthHelper.php';
require_once __DIR__ . '/app/helpers/FormatHelper.php';
require_once __DIR__ . '/app/controllers/AuthController.php';
require_once __DIR__ . '/app/controllers/DashboardController.php';
require_once __DIR__ . '/app/controllers/ClientController.php';
require_once __DIR__ . '/app/controllers/SubscriptionController.php';
require_once __DIR__ . '/app/controllers/BillingController.php';
require_once __DIR__ . '/app/controllers/PaymentController.php';
require_once __DIR__ . '/app/controllers/ReminderController.php';
require_once __DIR__ . '/app/controllers/ReportController.php';
require_once __DIR__ . '/app/controllers/SettingsController.php';

$page = $_GET['page'] ?? 'dashboard';

switch ($page) {
    // Public Unauthenticated Routes
    case 'pay':
        require __DIR__ . '/app/views/public/pay.php';
        break;

    // Authentication Routes
    case 'login':
        $authController = new AuthController();
        $authController->showLogin();
        break;

    case 'login_process':
        $authController = new AuthController();
        $authController->processLogin();
        break;

    case 'login_pin_process':
        $authController = new AuthController();
        $authController->processPinLogin();
        break;

    case 'logout':
        $authController = new AuthController();
        $authController->logout();
        break;

    // Dashboard Route
    case 'dashboard':
        $dashboardController = new DashboardController();
        $dashboardController->index();
        break;

    // Client Management Routes
    case 'clients':
        $clientController = new ClientController();
        $clientController->index();
        break;

    case 'client_create':
        $clientController = new ClientController();
        $clientController->create();
        break;

    case 'client_store':
        $clientController = new ClientController();
        $clientController->store();
        break;

    case 'client_view':
        $clientController = new ClientController();
        $clientController->show();
        break;

    case 'client_edit':
        $clientController = new ClientController();
        $clientController->edit();
        break;

    case 'client_update':
        $clientController = new ClientController();
        $clientController->update();
        break;

    case 'client_archive':
        $clientController = new ClientController();
        $clientController->archive();
        break;

    // Subscription Management Routes
    case 'subscription_store':
        $subscriptionController = new SubscriptionController();
        $subscriptionController->store();
        break;

    case 'subscription_update':
        $subscriptionController = new SubscriptionController();
        $subscriptionController->update();
        break;

    case 'subscription_renew':
        $subscriptionController = new SubscriptionController();
        $subscriptionController->renew();
        break;

    // Billing & Invoice Routes
    case 'billing':
        $billingController = new BillingController();
        $billingController->index();
        break;

    case 'billing_create':
        $billingController = new BillingController();
        $billingController->create();
        break;

    case 'billing_store':
        $billingController = new BillingController();
        $billingController->store();
        break;

    case 'billing_view':
        $billingController = new BillingController();
        $billingController->show();
        break;

    case 'billing_edit':
        $billingController = new BillingController();
        $billingController->edit();
        break;

    case 'billing_update':
        $billingController = new BillingController();
        $billingController->update();
        break;

    case 'billing_finalize':
        $billingController = new BillingController();
        $billingController->finalize();
        break;

    case 'billing_cancel':
        $billingController = new BillingController();
        $billingController->cancel();
        break;

    case 'invoice_pdf':
        $billingController = new BillingController();
        $billingController->pdf();
        break;

    case 'invoice_print':
        $billingController = new BillingController();
        $billingController->print();
        break;

    case 'billing_generate_recurring':
        $billingController = new BillingController();
        $billingController->generateRecurring();
        break;

    // Payment & Receipt Routes
    case 'payments':
        $paymentController = new PaymentController();
        $paymentController->index();
        break;

    case 'receipts':
        header('Location: ' . BASE_URL . '/index.php?page=payments');
        exit;

    case 'payment_create':
        $paymentController = new PaymentController();
        $paymentController->create();
        break;

    case 'payment_store':
        $paymentController = new PaymentController();
        $paymentController->store();
        break;

    case 'payment_view':
        $paymentController = new PaymentController();
        $paymentController->show();
        break;

    case 'payment_cancel':
        $paymentController = new PaymentController();
        $paymentController->cancel();
        break;

    case 'receipt_pdf':
        $paymentController = new PaymentController();
        $paymentController->pdf();
        break;

    // Due Date & Reminders Routes
    case 'reminders':
        $reminderController = new ReminderController();
        $reminderController->index();
        break;

    case 'reminder_send':
        $reminderController = new ReminderController();
        $reminderController->send();
        break;

    // Reports Routes
    case 'reports':
        $reportController = new ReportController();
        $reportController->index();
        break;

    case 'report_export_csv':
        $reportController = new ReportController();
        $reportController->exportCsv();
        break;

    case 'report_export_pdf':
        $reportController = new ReportController();
        $reportController->exportPdf();
        break;

    // Settings Routes
    case 'settings':
        $settingsController = new SettingsController();
        $settingsController->index();
        break;

    case 'settings_company':
        $settingsController = new SettingsController();
        $settingsController->updateCompany();
        break;

    case 'settings_numbering':
        $settingsController = new SettingsController();
        $settingsController->updateNumbering();
        break;

    case 'settings_restore_db':
        $settingsController = new SettingsController();
        $settingsController->restoreDb();
        break;

    case 'settings_password':
        $settingsController = new SettingsController();
        $settingsController->updatePassword();
        break;

    case 'settings_pin':
        $settingsController = new SettingsController();
        $settingsController->updatePin();
        break;

    case 'settings_bank_add':
        $settingsController = new SettingsController();
        $settingsController->addBank();
        break;

    case 'settings_bank_primary':
        $settingsController = new SettingsController();
        $settingsController->setPrimaryBank();
        break;

    case 'settings_bank_delete':
        $settingsController = new SettingsController();
        $settingsController->deleteBank();
        break;

    case 'backup_db':
        $settingsController = new SettingsController();
        $settingsController->backupDb();
        break;

    default:
        header('Location: ' . BASE_URL . '/index.php?page=dashboard');
        exit;
}
