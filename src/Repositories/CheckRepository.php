<?php

namespace Canary\Repositories;

use PDO;

class CheckRepository implements CheckRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function create(array $data): int
    {
        $sql =
            "INSERT INTO checks (site_id, is_success, http_code, response_time_ms) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $data["site_id"],
            (int) $data["is_success"],
            $data["http_code"],
            $data["response_time_ms"],
        ]);
        return $this->pdo->lastInsertId();
    }

    public function getLastBySite(int $siteId, int $limit = 10): array
    {
        $sql =
            "SELECT * FROM checks WHERE site_id = ? ORDER BY id DESC LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$siteId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
