<?php

namespace Canary\Tests\Repositories;

use Canary\Config\Database;
use Canary\Repositories\CheckRepository;
use Canary\Repositories\SiteRepository;
use PHPUnit\Framework\TestCase;
use PDO;

class CheckRepositoryTest extends TestCase
{
    private CheckRepository $repository;
    private SiteRepository $siteRepository;
    private PDO $pdo;
    private int $testSiteId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pdo = new PDO("sqlite::memory:");
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("PRAGMA foreign_keys = ON");
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

            CREATE TABLE checks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                site_id INTEGER NOT NULL,
                is_success BOOLEAN NOT NULL,
                http_code INTEGER NULL,
                response_time_ms INTEGER NULL,
                checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
            );
        ");

        $this->siteRepository = new SiteRepository($this->pdo);
        $this->repository = new CheckRepository($this->pdo);

        $this->testSiteId = $this->siteRepository->create([
            "url" => "https://test.com",
            "threshold_ms" => 500,
        ]);
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
        $data = [
            "site_id" => $this->testSiteId,
            "is_success" => true,
            "http_code" => 200,
            "response_time_ms" => 150,
        ];

        $id = $this->repository->create($data);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $checks = $this->repository->getLastBySite($this->testSiteId, 1);
        $this->assertCount(1, $checks);
        $this->assertEquals($this->testSiteId, $checks[0]["site_id"]);
        $this->assertEquals(1, $checks[0]["is_success"]);
        $this->assertEquals(200, $checks[0]["http_code"]);
        $this->assertEquals(150, $checks[0]["response_time_ms"]);
    }

    public function testCreateWithNullValues(): void
    {
        $data = [
            "site_id" => $this->testSiteId,
            "is_success" => false,
            "http_code" => null,
            "response_time_ms" => null,
        ];

        $id = $this->repository->create($data);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $checks = $this->repository->getLastBySite($this->testSiteId, 1);
        $this->assertCount(1, $checks);
        $this->assertEquals(0, $checks[0]["is_success"]);
        $this->assertNull($checks[0]["http_code"]);
        $this->assertNull($checks[0]["response_time_ms"]);
    }

    public function testGetLastBySite(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repository->create([
                "site_id" => $this->testSiteId,
                "is_success" => true,
                "http_code" => 200,
                "response_time_ms" => $i * 50,
            ]);
        }

        $checks = $this->repository->getLastBySite($this->testSiteId, 3);

        $this->assertCount(3, $checks);
        $this->assertGreaterThan($checks[1]["id"], $checks[0]["id"]);
        $this->assertGreaterThan($checks[2]["id"], $checks[1]["id"]);
    }

    public function testGetLastBySiteLimit(): void
    {
        for ($i = 1; $i <= 10; $i++) {
            $this->repository->create([
                "site_id" => $this->testSiteId,
                "is_success" => true,
                "http_code" => 200,
                "response_time_ms" => $i * 10,
            ]);
        }

        $checks = $this->repository->getLastBySite($this->testSiteId, 5);
        $this->assertCount(5, $checks);

        $checks2 = $this->repository->getLastBySite($this->testSiteId, 3);
        $this->assertCount(3, $checks2);
    }

    public function testGetLastBySiteEmpty(): void
    {
        $newSiteId = $this->siteRepository->create([
            "url" => "https://empty.com",
            "threshold_ms" => 300,
        ]);

        $checks = $this->repository->getLastBySite($newSiteId);
        $this->assertIsArray($checks);
        $this->assertEmpty($checks);
    }

    public function testCheckHasSiteRelation(): void
    {
        $this->repository->create([
            "site_id" => $this->testSiteId,
            "is_success" => true,
            "http_code" => 200,
            "response_time_ms" => 100,
        ]);

        $checks = $this->repository->getLastBySite($this->testSiteId);
        $this->assertCount(1, $checks);

        $this->siteRepository->delete($this->testSiteId);

        $checks = $this->repository->getLastBySite($this->testSiteId);
        $this->assertEmpty($checks);
    }
}
