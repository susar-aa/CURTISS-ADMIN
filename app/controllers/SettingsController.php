<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Settings & Administration Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../helpers/FormatHelper.php';
require_once __DIR__ . '/../models/ActivityLog.php';

class SettingsController {
    private PDO $db;
    private ActivityLog $activityLog;

    public function __construct() {
        AuthHelper::requireAuth();
        $this->db = Database::getConnection();
        $this->activityLog = new ActivityLog();
    }

    /**
     * Show settings dashboard
     */
    public function index(): void {
        // Fetch company settings
        $settingsStmt = $this->db->query("SELECT setting_key, setting_value FROM company_settings");
        $settingsRaw = $settingsStmt->fetchAll() ?: [];
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        // Fetch bank accounts
        $bankStmt = $this->db->query("SELECT id, bank_name, branch, account_name, account_number, is_primary, status FROM bank_accounts ORDER BY is_primary DESC, id ASC");
        $bankAccounts = $bankStmt->fetchAll() ?: [];

        $currentUser = AuthHelper::getCurrentUser();

        $flashSuccess = $_SESSION['settings_success'] ?? null;
        $flashError = $_SESSION['settings_error'] ?? null;
        unset($_SESSION['settings_success'], $_SESSION['settings_error']);

        $pageTitle = 'Settings & Configuration';
        $activeNav = 'settings';

        require __DIR__ . '/../views/settings/index.php';
    }

    /**
     * Update invoice and receipt numbering prefixes
     */
    public function updateNumbering(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $invPrefix = trim((string)($_POST['invoice_prefix'] ?? 'INV-'));
        $recPrefix = trim((string)($_POST['receipt_prefix'] ?? 'REC-'));

        $stmt = $this->db->prepare("INSERT INTO company_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute(['k' => 'invoice_prefix', 'v' => $invPrefix]);
        $stmt->execute(['k' => 'receipt_prefix', 'v' => $recPrefix]);

        $_SESSION['settings_success'] = 'Numbering settings saved.';
        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Restore database from uploaded SQL file
     */
    public function restoreDb(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            $_SESSION['settings_error'] = 'Invalid request or expired session.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        if (empty($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['settings_error'] = 'Please choose a valid .sql backup file to upload.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $file = $_FILES['sql_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            $_SESSION['settings_error'] = 'Invalid file type. Only .sql files are supported for database restoration.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $sqlContent = file_get_contents($file['tmp_name']);
        if ($sqlContent === false || trim($sqlContent) === '') {
            $_SESSION['settings_error'] = 'The selected SQL backup file is empty.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        try {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 0;");
            
            // Split queries safely by semicolon at end of line
            $queries = preg_split('/;\s*[\r\n]+/', $sqlContent);
            foreach ($queries as $query) {
                $trimmed = trim($query);
                if ($trimmed !== '' && !str_starts_with($trimmed, '--') && !str_starts_with($trimmed, '/*')) {
                    $this->db->exec($trimmed);
                }
            }

            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");

            $user = AuthHelper::getCurrentUser();
            $userId = (int)($user['id'] ?? null);
            $this->activityLog->log(null, 'Database Restored', 'Admin restored system database from backup file: ' . htmlspecialchars($file['name']), $userId);

            $_SESSION['settings_success'] = 'Database restored successfully from backup.';
        } catch (Exception $e) {
            $this->db->exec("SET FOREIGN_KEY_CHECKS = 1;");
            $_SESSION['settings_error'] = 'Failed to restore database: ' . $e->getMessage();
        }

        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Update company details
     */
    public function updateCompany(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $fields = ['company_name', 'company_email', 'company_phone', 'company_address', 'company_website'];
        $stmt = $this->db->prepare("INSERT INTO company_settings (setting_key, setting_value) VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $stmt->execute(['k' => $field, 'v' => trim((string)$_POST[$field])]);
            }
        }

        $_SESSION['settings_success'] = 'Company information updated successfully.';
        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Add new bank account
     */
    public function addBank(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $bankName = trim((string)($_POST['bank_name'] ?? ''));
        $branch = trim((string)($_POST['branch'] ?? ''));
        $accountName = trim((string)($_POST['account_name'] ?? ''));
        $accountNumber = trim((string)($_POST['account_number'] ?? ''));

        if ($bankName === '' || $accountNumber === '') {
            $_SESSION['settings_error'] = 'Bank name and account number are required.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO bank_accounts (bank_name, branch, account_name, account_number, is_primary, status)
             VALUES (:bname, :branch, :aname, :anum, 0, 'active')"
        );
        $stmt->execute([
            'bname'  => $bankName,
            'branch' => $branch,
            'aname'  => $accountName,
            'anum'   => $accountNumber
        ]);

        $_SESSION['settings_success'] = 'Bank account added successfully.';
        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Set primary bank account
     */
    public function setPrimaryBank(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null) || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $this->db->exec("UPDATE bank_accounts SET is_primary = 0");
        $stmt = $this->db->prepare("UPDATE bank_accounts SET is_primary = 1 WHERE id = :id");
        $stmt->execute(['id' => $id]);

        $_SESSION['settings_success'] = 'Primary bank account updated.';
        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Delete bank account
     */
    public function deleteBank(): void {
        $id = (int)($_POST['id'] ?? 0);
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null) || $id <= 0) {
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $stmt = $this->db->prepare("DELETE FROM bank_accounts WHERE id = :id AND is_primary = 0");
        $stmt->execute(['id' => $id]);

        $_SESSION['settings_success'] = 'Bank account removed.';
        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Update admin password
     */
    public function updatePassword(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = (int)($user['id'] ?? 0);
        $currentPass = (string)($_POST['current_password'] ?? '');
        $newPass = (string)($_POST['new_password'] ?? '');
        $confirmPass = (string)($_POST['confirm_password'] ?? '');

        if (strlen($newPass) < 6) {
            $_SESSION['settings_error'] = 'New password must be at least 6 characters.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        if ($newPass !== $confirmPass) {
            $_SESSION['settings_error'] = 'New password and confirmation do not match.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $existingHash = $stmt->fetchColumn();

        if (!password_verify($currentPass, (string)$existingHash)) {
            $_SESSION['settings_error'] = 'Current password is incorrect.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $newHash = password_hash($newPass, PASSWORD_BCRYPT);
        $upStmt = $this->db->prepare("UPDATE users SET password_hash = :hash, updated_at = NOW() WHERE id = :id");
        $upStmt->execute(['hash' => $newHash, 'id' => $userId]);

        $_SESSION['settings_success'] = 'Password changed successfully.';
        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Update user 6-digit login PIN
     */
    public function updatePin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !AuthHelper::verifyCsrfToken($_POST['csrf_token'] ?? null)) {
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $user = AuthHelper::getCurrentUser();
        $userId = (int)($user['id'] ?? 0);
        $currentPass = (string)($_POST['current_password'] ?? '');
        $newPin = trim((string)($_POST['new_pin'] ?? ''));
        $confirmPin = trim((string)($_POST['confirm_pin'] ?? ''));

        if (!preg_match('/^\d{6}$/', $newPin)) {
            $_SESSION['settings_error'] = 'PIN must be exactly 6 digits (0-9).';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        if ($newPin !== $confirmPin) {
            $_SESSION['settings_error'] = 'New 6-digit PIN and confirmation do not match.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $existingHash = $stmt->fetchColumn();

        if (!password_verify($currentPass, (string)$existingHash)) {
            $_SESSION['settings_error'] = 'Current password is incorrect. PIN was not updated.';
            header('Location: ' . BASE_URL . '/index.php?page=settings');
            exit;
        }

        $newPinHash = password_hash($newPin, PASSWORD_DEFAULT);
        $upStmt = $this->db->prepare("UPDATE users SET pin_hash = :hash, updated_at = NOW() WHERE id = :id");
        $upStmt->execute(['hash' => $newPinHash, 'id' => $userId]);

        $_SESSION['settings_success'] = '6-Digit Login PIN updated successfully. You can now log in using your PIN.';
        header('Location: ' . BASE_URL . '/index.php?page=settings');
        exit;
    }

    /**
     * Generate complete database backup SQL dump
     */
    public function backupDb(): void {
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="Curtiss_Backup_' . date('Y-m-d_His') . '.sql"');

        echo "-- Curtiss Client Manager SQL Backup\n";
        echo "-- Exported at: " . date('Y-m-d H:i:s') . "\n\n";

        $tables = ['company_settings', 'users', 'clients', 'subscriptions', 'invoices', 'invoice_items', 'payments', 'bank_accounts', 'reminder_logs', 'activity_logs'];

        foreach ($tables as $table) {
            echo "-- Table structure for `{$table}`\n";
            $createStmt = $this->db->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_NUM);
            echo "DROP TABLE IF EXISTS `{$table}`;\n";
            echo $createRow[1] . ";\n\n";

            $rowsStmt = $this->db->query("SELECT * FROM `{$table}`");
            $rows = $rowsStmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                echo "-- Data for `{$table}`\n";
                foreach ($rows as $row) {
                    $cols = array_keys($row);
                    $vals = array_map(function ($val) {
                        return $val === null ? 'NULL' : Database::getConnection()->quote((string)$val);
                    }, array_values($row));
                    echo "INSERT INTO `{$table}` (`" . implode('`, `', $cols) . "`) VALUES (" . implode(', ', $vals) . ");\n";
                }
                echo "\n";
            }
        }
        exit;
    }
}
