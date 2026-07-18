<?php

namespace Canary\Tests\Repositories;

use Canary\Config\Database;
use Canary\Repositories\NotificationRepository;
use Canary\Repositories\SiteRepository;
use PHPUnit\Framework\TestCase;
use PDO;

class NotificationRepositoryTest extends TestCase
{
    private NotificationRepository $repository;
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

            CREATE TABLE notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                site_id INTEGER NOT NULL,
                type VARCHAR(20) NOT NULL,
                message TEXT NOT NULL,
                sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
            );
        ");

        $this->siteRepository = new SiteRepository($this->pdo);
        $this->repository = new NotificationRepository($this->pdo);

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
            "type" => "down",
            "message" => "🚨 Site is down!",
        ];

        $id = $this->repository->create($data);

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        $notifications = $this->repository->getLastBySite(
            $this->testSiteId,
            "down",
            1,
        );
        $this->assertCount(1, $notifications);
        $this->assertEquals($this->testSiteId, $notifications[0]["site_id"]);
        $this->assertEquals("down", $notifications[0]["type"]);
        $this->assertEquals("🚨 Site is down!", $notifications[0]["message"]);
    }

    public function testGetLastBySiteAndType(): void
    {
        $this->repository->create([
            "site_id" => $this->testSiteId,
            "type" => "down",
            "message" => "Down #1",
        ]);

        $this->repository->create([
            "site_id" => $this->testSiteId,
            "type" => "recovered",
            "message" => "Recovered #1",
        ]);

        $this->repository->create([
            "site_id" => $this->testSiteId,
            "type" => "down",
            "message" => "Down #2",
        ]);

        $notifications = $this->repository->getLastBySite(
            $this->testSiteId,
            "down",
            1,
        );
        $downNotifications = $this->repository->getLastBySite(
            $this->testSiteId,
            "down",
            1,
        );
        $this->assertCount(1, $downNotifications);
        $this->assertEquals("Down #2", $downNotifications[0]["message"]);

        $allDown = $this->repository->getLastBySite($this->testSiteId, "down");
        $this->assertCount(2, $allDown);
    }

    public function testGetLastBySiteWithoutType(): void
    {
        $this->repository->create([
            "site_id" => $this->testSiteId,
            "type" => "down",
            "message" => "Down #1",
        ]);

        $this->repository->create([
            "site_id" => $this->testSiteId,
            "type" => "recovered",
            "message" => "Recovered #1",
        ]);

        $this->repository->create([
            "site_id" => $this->testSiteId,
            "type" => "down",
            "message" => "Down #2",
        ]);

        $all = $this->repository->getLastBySite($this->testSiteId, null, 3);
        $this->assertCount(3, $all);
        $this->assertEquals("Down #2", $all[0]["message"]);
        $this->assertEquals("Recovered #1", $all[1]["message"]);
        $this->assertEquals("Down #1", $all[2]["message"]);
    }

    public function testGetLastBySiteEmpty(): void
    {
        $newSiteId = $this->siteRepository->create([
            "url" => "https://empty.com",
            "threshold_ms" => 300,
        ]);

        $notifications = $this->repository->getLastBySite($newSiteId);
        $this->assertIsArray($notifications);
        $this->assertEmpty($notifications);
    }

    public function testCreateWithDifferentTypes(): void
    {
        $types = ["down", "recovered", "critical", "slow"];

        foreach ($types as $type) {
            $id = $this->repository->create([
                "site_id" => $this->testSiteId,
                "type" => $type,
                "message" => "Test {$type}",
            ]);
            $this->assertGreaterThan(0, $id);
        }

        $all = $this->repository->getLastBySite($this->testSiteId, null, 4);
        $this->assertCount(4, $all);
    }

    public function testNotificationHasSiteRelation(): void
    {
        $this->repository->create([
            "site_id" => $this->testSiteId,
            "type" => "down",
            "message" => "Test cascade",
        ]);

        $notifications = $this->repository->getLastBySite($this->testSiteId);
        $this->assertCount(1, $notifications);

        $this->siteRepository->delete($this->testSiteId);

        $notifications = $this->repository->getLastBySite($this->testSiteId);
        $this->assertEmpty($notifications);
    }
}
