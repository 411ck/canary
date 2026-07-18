<?php

namespace Canary\Repositories;

use PDO;
class NotificationRepository implements NotificationRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(array $data): int
    {
        $sql =
            "INSERT INTO notifications (site_id, type, message) VALUES (:site_id, :type, :message)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "site_id" => $data["site_id"],
            "type" => $data["type"],
            "message" => $data["message"],
        ]);
        return $this->pdo->lastInsertId();
    }

    public function getLastBySite(
        int $siteId,
        ?string $type = null,
        ?int $limit = null,
    ): array {
        $sql = "SELECT * FROM notifications WHERE site_id = :site_id";
        $params = ["site_id" => $siteId];

        if ($type !== null) {
            $sql .= " AND type = :type";
            $params["type"] = $type;
        }

        $sql .= " ORDER BY id DESC";

        if ($limit !== null && $limit > 0) {
            $sql .= " LIMIT :limit";
            $params["limit"] = $limit;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
