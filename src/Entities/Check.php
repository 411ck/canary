<?php
declare(strict_types=1);

namespace Canary\Entities;

class Check
{
    public function __construct(
        private int $id,
        private int $site_id,
        private int $is_success,
        private ?int $http_code,
        private ?int $response_time_ms,
        private string $checked_at,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getSiteId(): int
    {
        return $this->site_id;
    }

    public function getIsSuccess(): int
    {
        return $this->is_success;
    }

    public function getHttpCode(): ?int
    {
        return $this->http_code;
    }

    public function getResponseTimeMs(): ?int
    {
        return $this->response_time_ms;
    }

    public function getCheckedAt(): string
    {
        return $this->checked_at;
    }
}
