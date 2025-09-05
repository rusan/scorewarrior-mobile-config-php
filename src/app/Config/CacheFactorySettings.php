<?php
declare(strict_types=1);

namespace App\Config;

final class CacheFactorySettings
{
    public function __construct(
        public readonly string $adapter,
        public readonly array $options,
        public readonly string $prefix
    ) {}
}
