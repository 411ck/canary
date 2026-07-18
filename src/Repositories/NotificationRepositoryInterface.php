<?php

namespace Canary\Repositories;

interface NotificationRepositoryInterface
{
    public function create(array $data): int;
    public function getLastBySite(
        int $siteId,
        string $type = null,
        int $limit = null,
    ): array;
}
