<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\ConfigInterface;
use App\Utils\CacheKeyBuilder;
use App\Utils\Log;


class MtimeCacheService
{    
    public function __construct(
        private ConfigInterface $config,
        private CacheManager $cacheManager
    ) {}


    private function getTTLForFile(string $filePath): int
    {
        $pathMap = $this->config->getMtimeCachePathMap();
        return $pathMap[$filePath] ?? 0;
    }

    public function getMtime(string $filePath): int
    {
        $fileTTL = $this->getTTLForFile($filePath);
        
        if ($fileTTL === 0) {
            return (int) @filemtime($filePath);
        }

        $cacheKey = CacheKeyBuilder::fileMtime($filePath);
        

        $cached = $this->cacheManager->get($cacheKey, 'mtime');
        if ($cached !== null) {
            $now = time();
            if (($now - $cached['timestamp']) < $fileTTL) {
                return $cached['mtime'];
            }
        }


        $mtime = (int) @filemtime($filePath);
        $now = time();
        

        $this->cacheManager->set($cacheKey, [
            'mtime' => $mtime,
            'timestamp' => $now
        ], 'mtime', $fileTTL);

        return $mtime;
    }
}
