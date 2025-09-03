<?php
declare(strict_types=1);

namespace App\Utils;

use InvalidArgumentException;

final class Semver
{
    public const RX = '/^(?<major>\d+)\.(?<minor>\d+)\.(?<patch>\d+)$/';
    public static function parse(string $v): array
    {
        if (!preg_match(self::RX, $v, $m)) {
            throw new InvalidArgumentException("Invalid SemVer: {$v}");
        }
        return [
            'major' => (int)$m['major'],
            'minor' => (int)$m['minor'],
            'patch' => (int)$m['patch'],
        ];
    }

    public static function compare(string $a, string $b): int
    {
        $A = self::parse($a);
        $B = self::parse($b);
        foreach (['major','minor','patch'] as $k) {
            if ($A[$k] < $B[$k]) return -1;
            if ($A[$k] > $B[$k]) return 1;
        }
        return 0;
    }

    public static function pickBest(array $versions): ?string
    {
        if (!$versions) return null;
        usort($versions, fn($x, $y) => - self::compare($x, $y));
        return $versions[0];
    }
}
