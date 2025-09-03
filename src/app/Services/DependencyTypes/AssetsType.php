<?php
declare(strict_types=1);

namespace App\Services\DependencyTypes;

use App\Config\DataFileNames;
use App\Config\DependencyNames;
use App\Services\DependencyTypeInterface;
use App\Utils\Semver;

class AssetsType implements DependencyTypeInterface
{
    public function getName(): string
    {
        return DependencyNames::ASSETS;
    }
    
    public function getFileName(): string
    {
        return DataFileNames::ASSETS_FIXTURES;
    }
    
    public function getUrlsKey(): string
    {
        return 'assets_cdn_urls';
    }
    
    public function isCompatible(string $appVersion, string $candidate): bool
    {
        $app = Semver::parse($appVersion);
        $cand = Semver::parse($candidate);
        return $app['major'] === $cand['major'];
    }
}
