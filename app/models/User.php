<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - User Model
 */

require_once __DIR__ . '/../config/database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Find user by username with explicit column selection
     */
    public function findByUsername(string $username): ?array {
        $stmt = $this->db->prepare(
            "SELECT id, username, email, password_hash, pin_hash, full_name, role, status 
             FROM users 
             WHERE username = :username 
             LIMIT 1"
        );
        $stmt->execute(['username' => $username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    /**
     * Authenticate user credentials with password
     */
    public function authenticate(string $username, string $password): ?array {
        $user = $this->findByUsername($username);
        if (!$user) {
            return null;
        }

        if ($user['status'] !== 'active') {
            return null;
        }

        if (password_verify($password, $user['password_hash'])) {
            unset($user['password_hash'], $user['pin_hash']);
            return $user;
        }

        return null;
    }

    /**
     * Authenticate user with 6-digit PIN
     */
    public function authenticateByPin(string $pin, ?string $username = null): ?array {
        if (!preg_match('/^\d{6}$/', $pin)) {
            return null;
        }

        if ($username !== null && trim($username) !== '') {
            $user = $this->findByUsername(trim($username));
            if ($user && $user['status'] === 'active' && !empty($user['pin_hash'])) {
                if (password_verify($pin, $user['pin_hash'])) {
                    unset($user['password_hash'], $user['pin_hash']);
                    return $user;
                }
            }
            return null;
        }

        // Single-user / match against active users with a PIN
        $stmt = $this->db->query(
            "SELECT id, username, email, password_hash, pin_hash, full_name, role, status 
             FROM users 
             WHERE status = 'active' AND pin_hash IS NOT NULL 
             LIMIT 10"
        );
        $users = $stmt->fetchAll();

        foreach ($users as $u) {
            if (password_verify($pin, $u['pin_hash'])) {
                unset($u['password_hash'], $u['pin_hash']);
                return $u;
            }
        }

        return null;
    }

    /**
     * Update user 6-digit PIN
     */
    public function updatePin(int $userId, string $pin): bool {
        if (!preg_match('/^\d{6}$/', $pin)) {
            return false;
        }

        $pinHash = password_hash($pin, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("UPDATE users SET pin_hash = :pin_hash WHERE id = :id");
        return $stmt->execute(['pin_hash' => $pinHash, 'id' => $userId]);
    }

    /**
     * Check if user has PIN configured
     */
    public function hasPin(int $userId): bool {
        $stmt = $this->db->prepare("SELECT pin_hash FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $userId]);
        $val = $stmt->fetchColumn();
        return !empty($val);
    }
}
