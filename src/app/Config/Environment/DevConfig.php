<?php
declare(strict_types=1);

namespace App\Config\Environment;

class DevConfig extends BaseConfig
{
    
    public function getCacheSettings(): array
    {
        return [
            'adapter' => 'memory',
            'options' => [
                'lifetime' => 60,
            ],
            'prefix' => 'dev_cache_',
        ];
    }
    
    public function isDebugMode(): bool
    {
        return true;
    }
}
