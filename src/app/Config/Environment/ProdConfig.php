<?php
declare(strict_types=1);

namespace App\Config\Environment;

class ProdConfig extends BaseConfig
{
    public function getCacheSettings(): array
    {
        return [
            'adapter' => 'apcu',
            'options' => [
                'defaultSerializer' => 'Php',
                'lifetime' => 3600,
            ],
            'prefix' => 'prod_cache_',
        ];
    }

    public function isDebugMode(): bool
    {
        return false;
    }
}
