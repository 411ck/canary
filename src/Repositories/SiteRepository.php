<?php

namespace Canary\Repositories;

use PDO;

class SiteRepository implements SiteRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function getAll(): array
    {
        $sql = "SELECT * FROM sites";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array
    {
        $sql = "SELECT * FROM sites WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int
    {
        $sql =
            "INSERT INTO sites (url, threshold_ms) VALUES (:url, :threshold_ms)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            "url" => $data["url"],
            "threshold_ms" => $data["threshold_ms"],
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ["id" => $id];

        if (isset($data["threshold_ms"])) {
            $fields[] = "threshold_ms = :threshold_ms";
            $params["threshold_ms"] = $data["threshold_ms"];
        }

        if (isset($data["status"])) {
            $fields[] = "status = :status";
            $params["status"] = $data["status"];
        }

        if (isset($data["last_check_at"])) {
            $fields[] = "last_check_at = :last_check_at";
            $params["last_check_at"] = $data["last_check_at"];
        }

        if (empty($fields)) {
            return false; // Nothing to update
        }

        $sql = "UPDATE sites SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $sql = "DELETE FROM sites WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["id" => $id]);
        return $stmt->rowCount() > 0;
    }

    public function updateStatus(int $id, string $status): bool
    {
        $sql = "UPDATE sites SET status = :status WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(["status" => $status, "id" => $id]);
        return $stmt->rowCount() > 0;
    }
}
