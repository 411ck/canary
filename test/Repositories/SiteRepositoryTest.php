<?php

namespace Canary\Tests\Repositories;

use Canary\Config\Database;
use Canary\Repositories\SiteRepository;
use PHPUnit\Framework\TestCase;
use PDO;

class SiteRepositoryTest extends TestCase
{
    private SiteRepository $repository;
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO("sqlite::memory:");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty("pdo");
        $property->setAccessible(true);
        $property->setValue(null, $this->pdo);

        $this->pdo->exec("
            CREATE TABLE sites (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                url VARCHAR(255) NOT NULL,
                threshold_ms INTEGER NOT NULL DEFAULT 1000,
                status VARCHAR(20) NOT NULL DEFAULT 'ok',
                last_check_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_notification_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");

        $this->repository = new SiteRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $reflection = new \ReflectionClass(Database::class);
        $property = $reflection->getProperty("pdo");
        $property->setAccessible(true);
        $property->setValue(null, null);
    }

    public function testCreate(): void
    {
        $id = $this->repository->create([
            "url" => "https://example.com",
            "threshold_ms" => 500,
        ]);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $site = $this->repository->findById($id);
        $this->assertEquals("https://example.com", $site["url"]);
        $this->assertEquals(500, $site["threshold_ms"]);
        $this->assertEquals("ok", $site["status"]);
    }

    public function testFindById(): void
    {
        $id = $this->repository->create([
            "url" => "https://test.com",
            "threshold_ms" => 300,
        ]);

        $site = $this->repository->findById($id);

        $this->assertIsArray($site);
        $this->assertArrayHasKey("id", $site);
        $this->assertArrayHasKey("url", $site);
        $this->assertArrayHasKey("status", $site);
        $this->assertEquals("https://test.com", $site["url"]);
    }

    public function testFindByIdNotFound(): void
    {
        $site = $this->repository->findById(9999);
        $this->assertNull($site);
    }

    public function testGetAll(): void
    {
        $this->repository->create([
            "url" => "https://site1.com",
            "threshold_ms" => 100,
        ]);
        $this->repository->create([
            "url" => "https://site2.com",
            "threshold_ms" => 200,
        ]);

        $sites = $this->repository->getAll();

        $this->assertIsArray($sites);
        $this->assertCount(2, $sites);
        $this->assertEquals("https://site1.com", $sites[0]["url"]);
        $this->assertEquals("https://site2.com", $sites[1]["url"]);
    }

    public function testGetAllEmpty(): void
    {
        $sites = $this->repository->getAll();
        $this->assertIsArray($sites);
        $this->assertEmpty($sites);
    }

    public function testUpdate(): void
    {
        $id = $this->repository->create([
            "url" => "https://update.com",
            "threshold_ms" => 100,
        ]);

        $updated = $this->repository->update($id, ["threshold_ms" => 999]);

        $this->assertTrue($updated);

        $site = $this->repository->findById($id);
        $this->assertEquals(999, $site["threshold_ms"]);
        $this->assertEquals("ok", $site["status"]);
    }

    public function testUpdateStatus(): void
    {
        $id = $this->repository->create([
            "url" => "https://status.com",
            "threshold_ms" => 100,
        ]);

        $updated = $this->repository->updateStatus($id, "down");

        $this->assertTrue($updated);

        $site = $this->repository->findById($id);
        $this->assertEquals("down", $site["status"]);
        $this->assertNotNull($site["last_check_at"]);
    }

    public function testDelete(): void
    {
        $id = $this->repository->create([
            "url" => "https://delete.com",
            "threshold_ms" => 100,
        ]);

        $deleted = $this->repository->delete($id);
        $this->assertTrue($deleted);

        $site = $this->repository->findById($id);
        $this->assertNull($site);
    }

    public function testDeleteNotFound(): void
    {
        $deleted = $this->repository->delete(9999);
        $this->assertFalse($deleted);
    }
}
