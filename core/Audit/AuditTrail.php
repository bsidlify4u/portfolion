<?php
namespace Portfolion\Audit;

use RuntimeException;

class AuditTrail {
    private string $table = 'audit_logs';
    private \PDO $db;
    
    public function __construct(\PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Log an auditable event.
     *
     * @param string $event Event name/type
     * @param string $module Affected module/component
     * @param int|null $userId User who performed the action
     * @param array<string, mixed> $oldData Previous state
     * @param array<string, mixed> $newData Current state
     * @param string|null $ipAddress IP address (nullable)
     * @throws RuntimeException
     */
    public function log(
        string $event,
        string $module,
        ?int $userId = null,
        array $oldData = [],
        array $newData = [],
        ?string $ipAddress = null
    ): void {
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} (event, module, user_id, old_data, new_data, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        
        if (!$stmt->execute([
            $event,
            $module,
            $userId,
            json_encode($oldData, JSON_THROW_ON_ERROR),
            json_encode($newData, JSON_THROW_ON_ERROR),
            $ipAddress
        ])) {
            throw new RuntimeException('Failed to write audit log');
        }
    }
    
    /**
     * Get audit trail for a specific module.
     *
     * @param string $module
     * @param int|null $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function getTrail(string $module, ?int $limit = null, int $offset = 0): array {
        $sql = "SELECT * FROM {$this->table} WHERE module = ? ORDER BY created_at DESC";
        if ($limit !== null) {
            $sql .= " LIMIT ? OFFSET ?";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($limit !== null) {
            $stmt->execute([$module, $limit, $offset]);
        } else {
            $stmt->execute([$module]);
        }
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
