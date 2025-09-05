<?php
declare(strict_types=1);

namespace App\Services\DependencyTypes;

use App\Config\DataFileNames;
use App\Config\RequestParameterNames;
use App\Services\DependencyTypeInterface;
use App\Utils\Semver;

class DefinitionsType implements DependencyTypeInterface
{
    public function getName(): string
    {
        return RequestParameterNames::DEFINITIONS_VERSION;
    }
    
    public function getFileName(): string
    {
        return DataFileNames::DEFINITIONS_FIXTURES;
    }
    
    public function isCompatible(string $appVersion, string $candidate): bool
    {
        $app = Semver::parse($appVersion);
        $cand = Semver::parse($candidate);
        return $app['major'] === $cand['major'] && $app['minor'] === $cand['minor'];
    }
}
