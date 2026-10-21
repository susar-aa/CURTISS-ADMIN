<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Authentication Controller
 */

require_once __DIR__ . '/../helpers/AuthHelper.php';
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function showLogin(): void {
        AuthHelper::requireGuest();
        $error = $_SESSION['login_error'] ?? null;
        unset($_SESSION['login_error']);

        require __DIR__ . '/../views/auth/login.php';
    }

    public function processLogin(): void {
        AuthHelper::requireGuest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? null;
        if (!AuthHelper::verifyCsrfToken($csrfToken)) {
            $_SESSION['login_error'] = 'Security validation failed (CSRF token mismatch). Please try again.';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $_SESSION['login_error'] = 'Please enter both username and password.';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        $user = $this->userModel->authenticate($username, $password);

        if (!$user) {
            $_SESSION['login_error'] = 'Invalid username or password.';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        // Session fixation protection
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        header('Location: ' . BASE_URL . '/index.php?page=dashboard');
        exit;
    }

    public function processPinLogin(): void {
        AuthHelper::requireGuest();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        // Validate CSRF token
        $csrfToken = $_POST['csrf_token'] ?? null;
        if (!AuthHelper::verifyCsrfToken($csrfToken)) {
            $_SESSION['login_error'] = 'Security validation failed (CSRF token mismatch). Please try again.';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        $pin = trim((string)($_POST['pin'] ?? ''));
        $username = trim((string)($_POST['username'] ?? 'admin'));

        if (!preg_match('/^\d{6}$/', $pin)) {
            $_SESSION['login_error'] = 'Please enter a valid 6-digit PIN.';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        $user = $this->userModel->authenticateByPin($pin, $username !== '' ? $username : null);

        if (!$user) {
            $_SESSION['login_error'] = 'Incorrect 6-digit PIN. Please try again.';
            header('Location: ' . BASE_URL . '/index.php?page=login');
            exit;
        }

        // Session fixation protection
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['email'] = $user['email'];

        header('Location: ' . BASE_URL . '/index.php?page=dashboard');
        exit;
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
        header('Location: ' . BASE_URL . '/index.php?page=login');
        exit;
    }
}
