<?php
declare(strict_types=1);

namespace App\Config;

final class MtimeTtlSettings
{
    public function __construct(
        public readonly int $fixtures,
        public readonly int $urls,
        public readonly int $general
    ) {}
}


