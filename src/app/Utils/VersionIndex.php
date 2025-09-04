<?php
declare(strict_types=1);

namespace App\Utils;

final class VersionIndex
{
    /** @return array{versionToHash: array<string,string>, byMajor: array<int,array<int,string>>, byMajorMinor: array<string,array<int,string>>} */
    public static function build(array $list): array
    {
        $versionToHash = [];
        $byMajor = [];
        $byMajorMinor = [];

        foreach ($list as $row) {
            $v = (string) $row['version'];
            $h = (string) $row['hash'];
            $versionToHash[$v] = $h;

            $parts = Semver::parse($v);
            $maj = (int) $parts['major'];
            $min = (int) $parts['minor'];

            if (!isset($byMajor[$maj])) { $byMajor[$maj] = []; }
            $byMajor[$maj][] = $v;

            $mm = $maj . '.' . $min;
            if (!isset($byMajorMinor[$mm])) { $byMajorMinor[$mm] = []; }
            $byMajorMinor[$mm][] = $v;
        }

        $cmp = fn(string $a, string $b) => Semver::compare($a, $b);
        foreach ($byMajor as $k => $arr) {
            usort($arr, $cmp);
            $byMajor[$k] = $arr;
        }
        foreach ($byMajorMinor as $k => $arr) {
            usort($arr, $cmp);
            $byMajorMinor[$k] = $arr;
        }

        return [
            'versionToHash' => $versionToHash,
            'byMajor' => $byMajor,
            'byMajorMinor' => $byMajorMinor,
        ];
    }

    public static function pickBest(string $appVersion, array $index, string $mode): ?string
    {
        $p = Semver::parse($appVersion);
        $maj = $p['major'];
        $min = $p['minor'];

        if ($mode === 'assets') {
            $arr = $index['byMajor'][$maj] ?? [];
        } elseif ($mode === 'definitions') {
            $key = $maj . '.' . $min;
            $arr = $index['byMajorMinor'][$key] ?? [];
        } else {
            return null;
        }

        if (!$arr) return null;
        // Greatest version <= appVersion
        $left = 0; $right = count($arr) - 1; $best = null;
        while ($left <= $right) {
            $mid = intdiv($left + $right, 2);
            $cmp = Semver::compare($arr[$mid], $appVersion);
            if ($cmp <= 0) {
                $best = $arr[$mid];
                $left = $mid + 1;
            } else {
                $right = $mid - 1;
            }
        }
        return $best;
    }
}


