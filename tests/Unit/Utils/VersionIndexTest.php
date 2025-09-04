<?php
declare(strict_types=1);

namespace Tests\Unit\Utils;

use App\Utils\VersionIndex;
use PHPUnit\Framework\TestCase;

final class VersionIndexTest extends TestCase
{
    public function testBuildAndPickAssets(): void
    {
        $list = [
            ['version' => '1.0.0', 'hash' => 'h100'],
            ['version' => '1.1.0', 'hash' => 'h110'],
            ['version' => '2.0.0', 'hash' => 'h200'],
        ];
        $idx = VersionIndex::build($list);
        $this->assertSame('1.1.0', VersionIndex::pickBest('1.1.5', $idx, 'assets'));
        $this->assertSame('1.1.0', VersionIndex::pickBest('1.1.0', $idx, 'assets'));
        $this->assertSame('1.0.0', VersionIndex::pickBest('1.0.1', $idx, 'assets'));
        $this->assertSame('2.0.0', VersionIndex::pickBest('2.0.0', $idx, 'assets'));
        $this->assertNull(VersionIndex::pickBest('3.0.0', $idx, 'assets'));
    }

    public function testBuildAndPickDefinitions(): void
    {
        $list = [
            ['version' => '1.0.0', 'hash' => 'h100'],
            ['version' => '1.0.1', 'hash' => 'h101'],
            ['version' => '1.1.0', 'hash' => 'h110'],
        ];
        $idx = VersionIndex::build($list);
        $this->assertSame('1.0.1', VersionIndex::pickBest('1.0.5', $idx, 'definitions'));
        $this->assertSame('1.0.1', VersionIndex::pickBest('1.0.1', $idx, 'definitions'));
        $this->assertSame('1.0.0', VersionIndex::pickBest('1.0.0', $idx, 'definitions'));
        $this->assertSame('1.1.0', VersionIndex::pickBest('1.1.9', $idx, 'definitions'));
        $this->assertNull(VersionIndex::pickBest('2.0.0', $idx, 'definitions'));
    }
}


