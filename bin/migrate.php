<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Canary\Config\Database;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

Database::connect();

$pdo = Database::getPdo();

$tableExists = $pdo
    ->query(
        "SELECT name FROM sqlite_master WHERE type='table' AND name='sites'",
    )
    ->fetch();

if ($tableExists) {
    echo "✅ Tables already exist. Nothing to do.\n";
    exit(0);
}

$schema = "
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

CREATE TABLE notifications (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    site_id INTEGER NOT NULL,
    type VARCHAR(20) NOT NULL,
    message TEXT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (site_id) REFERENCES sites(id) ON DELETE CASCADE
);

CREATE TABLE migrations (
    version INTEGER PRIMARY KEY,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO migrations (version) VALUES (1);
";

try {
    $pdo->exec($schema);
    echo "✅ Tables created successfully!\n";
} catch (PDOException $e) {
    echo "❌ Error while creating tables: " . $e->getMessage() . "\n";
    exit(1);
}
