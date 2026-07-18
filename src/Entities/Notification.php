<?php
declare(strict_types=1);

namespace Canary\Entities;

class Notification
{
    public function __construct(
        private int $id,
        private int $site_id,
        private string $type,
        private string $message,
        private string $sent_at,
    ) {}

    public function getId(): int
    {
        return $this->id;
    }

    public function getSiteId(): int
    {
        return $this->site_id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getSentAt(): string
    {
        return $this->sent_at;
    }
}
