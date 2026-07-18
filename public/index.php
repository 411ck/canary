<?php

require_once __DIR__ . "/../vendor/autoload.php";

use Canary\Config\Database;
use Dotenv\Dotenv;

// Load .env
$dotenv = Dotenv::createImmutable(__DIR__ . "/../");
$dotenv->load();

Database::connect();

$method = $_SERVER["REQUEST_METHOD"];
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$path = str_replace("/index.php", "", $path);

header("Content-Type: application/json");

switch ($path) {
    case "/sites":
        if ($method === "GET") {
            echo json_encode(["status" => "success", "data" => []]);
            exit();
        }
        break;
    default:
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Not found"]);
}
