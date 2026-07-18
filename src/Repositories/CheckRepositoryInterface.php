<?php
declare(strict_types=1);

namespace Canary\Repositories;

interface CheckRepositoryInterface
{
    /**
     * Creates a new check for the given site.
     *
     * @param array $data The check data to create.
     *
     * @return int The ID of the created check.
     */
    public function create(array $data): int;

    /**
     * Fetches the last N checks for the given site ID, ordered by created_at in descending order.
     *
     * @param int $siteId The ID of the site to fetch checks for.
     * @param int $limit The number of checks to fetch (default: 10).
     *
     * @return array An array of check data.
     */
    public function getLastBySite(int $siteId, int $limit = 10): array;
}
