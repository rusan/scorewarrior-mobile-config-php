<?php
declare(strict_types=1);

namespace App\Utils;

final class Clock
{
    public static function now(): int 
    { 
        return hrtime(true); 
    }

    public static function sinceMs(int $startNs): float
    {
        return (hrtime(true) - $startNs) / 1_000_000;
    }
}