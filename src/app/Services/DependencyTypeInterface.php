<?php
declare(strict_types=1);

namespace App\Services;

interface DependencyTypeInterface
{
    public function getName(): string;
    
    public function getFileName(): string;
    
    public function isCompatible(string $appVersion, string $candidate): bool;
}
