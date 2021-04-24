<?php

declare(strict_types=1);

namespace PHPMate\Domain\Caching;

interface RepositoryCache
{
    public function isCached(): bool;

    public function restoreFromCache(): void;

    public function saveToCache();
}
