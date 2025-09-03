<?php
declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Semver;
use App\Services\DependencyTypes\AssetsType;
use App\Services\DependencyTypes\DefinitionsType;

function assertTrue(bool $cond, string $msg): void {
    if (!$cond) {
        fwrite(STDERR, "FAIL: {$msg}\n");
        exit(1);
    }
}

function assertEq($a, $b, string $msg): void {
    if ($a !== $b) {
        fwrite(STDERR, "FAIL: {$msg}  Got: " . var_export($a, true) . "  Expected: " . var_export($b, true) . "\n");
        exit(1);
    }
}


$p = Semver::parse('14.2.123');
assertEq($p['major'], 14, 'parse major');
assertEq($p['minor'], 2,  'parse minor');
assertEq($p['patch'], 123,'parse patch');


assertEq(Semver::compare('1.2.3', '1.2.3'), 0,  'compare eq');
assertTrue(Semver::compare('1.2.3', '1.2.4') < 0, 'compare lt patch');
assertTrue(Semver::compare('1.3.0', '1.2.999') > 0, 'compare gt minor');
assertTrue(Semver::compare('2.0.0', '1.999.999') > 0, 'compare gt major');


$assetsType = new AssetsType();
$definitionsType = new DefinitionsType();

assertTrue($assetsType->isCompatible('14.2.123', '14.9.24'), 'assets compat (major only)');
assertTrue(!$assetsType->isCompatible('14.2.123', '13.7.697'), 'assets incompat (major mismatch)');
assertTrue($definitionsType->isCompatible('14.2.123', '14.2.392'), 'definitions compat (major+minor)');
assertTrue(!$definitionsType->isCompatible('14.2.123', '14.1.487'), 'definitions incompat (minor mismatch)');


assertEq(Semver::pickBest(['1.2.3','1.2.10','1.3.0','2.0.0']), '2.0.0', 'pickBest overall');

$assets = ['14.9.24','14.1.487','14.4.459','13.7.697'];
$compatibleAssets = array_filter($assets, fn($v) => $assetsType->isCompatible('14.2.123', $v));
$bestAssets = Semver::pickBest($compatibleAssets);
assertEq($bestAssets, '14.9.24', 'best assets within major 14');

$defs = ['14.2.392','14.2.181','14.2.123','14.1.893'];
$compatibleDefs = array_filter($defs, fn($v) => $definitionsType->isCompatible('14.2.50', $v));
$bestDefs = Semver::pickBest($compatibleDefs);
assertEq($bestDefs, '14.2.392', 'best definitions within 14.2');

echo "OK: SemVer sanity passed\n";
