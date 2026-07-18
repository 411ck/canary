<?php
declare(strict_types=1);

namespace Canary\Entities;

class Site
{
    public function __construct(
        private int $id,
        private string $url,
        private int $threshold_ms,
        private string $status,
        private string $last_check_at,
        private ?string $last_notification_at,
        private string $created_at,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getThresholdMs(): int
    {
        return $this->threshold_ms;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getLastCheckAt(): string
    {
        return $this->last_check_at;
    }

    public function getLastNotificationAt(): ?string
    {
        return $this->last_notification_at;
    }

    public function getCreatedAt(): string
    {
        return $this->created_at;
    }
}
