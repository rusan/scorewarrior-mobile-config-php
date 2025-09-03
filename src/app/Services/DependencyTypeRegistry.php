<?php
declare(strict_types=1);

namespace App\Services;

use App\Services\DependencyTypes\AssetsType;
use App\Services\DependencyTypes\DefinitionsType;

class DependencyTypeRegistry
{
    /** @var array<string, DependencyTypeInterface> */
    private array $types = [];
    
    public function __construct()
    {
        $this->registerDefaultTypes();
    }
    
    private function registerDefaultTypes(): void
    {
        $this->register(new AssetsType());
        $this->register(new DefinitionsType());
    }
    
    public function register(DependencyTypeInterface $type): void
    {
        $this->types[$type->getName()] = $type;
    }
    
    public function get(string $name): ?DependencyTypeInterface
    {
        return $this->types[$name] ?? null;
    }
    
    public function getAll(): array
    {
        return $this->types;
    }
    
    public function getNames(): array
    {
        return array_keys($this->types);
    }
}
