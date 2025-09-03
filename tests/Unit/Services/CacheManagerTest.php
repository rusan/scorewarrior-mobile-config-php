<?php
declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Config\ConfigInterface;
use App\Services\CacheManager;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

class CacheManagerTest extends TestCase
{
    private CacheManager $cacheManager;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheManager = new CacheManager(null, 3600, 3);
    }

    public function testLruEviction(): void
    {
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');
        $this->cacheManager->set('key3', 'value3');
        
        $this->assertEquals(3, $this->cacheManager->getLocalCount());
        $this->assertSame(true, $this->cacheManager->hasLocal('key1'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key2'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key3'));
        
        $this->cacheManager->set('key4', 'value4');
        
        $this->assertEquals(3, $this->cacheManager->getLocalCount());
        $this->assertSame(false, $this->cacheManager->hasLocal('key1'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key2'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key3'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key4'));
    }

    public function testLruAccessUpdatesOrder(): void
    {
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');
        $this->cacheManager->set('key3', 'value3');
        
        $this->cacheManager->get('key1');
        
        $this->cacheManager->set('key4', 'value4');
        
        $this->assertEquals(3, $this->cacheManager->getLocalCount());
        $this->assertSame(true, $this->cacheManager->hasLocal('key1'));
        $this->assertSame(false, $this->cacheManager->hasLocal('key2'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key3'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key4'));
    }

    public function testCacheSizeLimit(): void
    {
        $cacheManager = new CacheManager(null, 3600, 2);
        
        $cacheManager->set('key1', 'value1');
        $cacheManager->set('key2', 'value2');
        $cacheManager->set('key3', 'value3');
        
        $this->assertEquals(2, $cacheManager->getLocalCount());
        $this->assertSame(false, $cacheManager->hasLocal('key1'));
        $this->assertSame(true, $cacheManager->hasLocal('key2'));
        $this->assertSame(true, $cacheManager->hasLocal('key3'));
    }

    public function testClearLocalCache(): void
    {
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');
        
        $this->assertEquals(2, $this->cacheManager->getLocalCount());
        
        $this->cacheManager->clearLocal();
        
        $this->assertEquals(0, $this->cacheManager->getLocalCount());
        $this->assertSame(false, $this->cacheManager->hasLocal('key1'));
        $this->assertSame(false, $this->cacheManager->hasLocal('key2'));
    }

    public function testDeleteRemovesFromBothArrays(): void
    {
        $this->cacheManager->set('key1', 'value1');
        $this->cacheManager->set('key2', 'value2');
        
        $this->assertEquals(2, $this->cacheManager->getLocalCount());
        
        $this->cacheManager->delete('key1');
        
        $this->assertEquals(1, $this->cacheManager->getLocalCount());
        $this->assertSame(false, $this->cacheManager->hasLocal('key1'));
        $this->assertSame(true, $this->cacheManager->hasLocal('key2'));
    }
}
