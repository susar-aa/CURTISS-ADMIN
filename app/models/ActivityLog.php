<?php
declare(strict_types=1);

/**
 * Curtiss Client Manager - Activity Log Model
 */

require_once __DIR__ . '/../config/database.php';

class ActivityLog {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Record an audit activity
     */
    public function log(?int $clientId, string $action, string $description, ?int $userId = null): bool {
        $stmt = $this->db->prepare(
            "INSERT INTO activity_logs (client_id, user_id, action, description, created_at)
             VALUES (:client_id, :user_id, :action, :description, NOW())"
        );
        return $stmt->execute([
            'client_id'   => $clientId,
            'user_id'     => $userId,
            'action'      => $action,
            'description' => $description
        ]);
    }

    /**
     * Get recent activities for a client using explicit columns and index
     */
    public function getByClientId(int $clientId, int $limit = 20): array {
        $stmt = $this->db->prepare(
            "SELECT a.id, a.client_id, a.user_id, a.action, a.description, a.created_at, u.full_name AS staff_name
             FROM activity_logs a
             LEFT JOIN users u ON a.user_id = u.id
             WHERE a.client_id = :client_id
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT :limit"
        );
        $stmt->bindValue(':client_id', $clientId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll() ?: [];
    }
}
