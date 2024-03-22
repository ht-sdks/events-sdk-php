<?php

declare(strict_types=1);

namespace Hightouch\Test;

use PHPUnit\Framework;
use Hightouch\Hightouch;
use Hightouch\HightouchException;

final class HightouchTest extends Framework\TestCase
{
    protected function setUp(): void
    {
        self::resetHightouch();
    }

    public function testAliasThrowsHightouchExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(HightouchException::class);
        $this->expectExceptionMessage('Hightouch::init() must be called before any other tracking method.');

        Hightouch::alias([]);
    }
    public function testFlushThrowsHightouchExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(HightouchException::class);
        $this->expectExceptionMessage('Hightouch::init() must be called before any other tracking method.');

        Hightouch::flush();
    }
    public function testGroupThrowsHightouchExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(HightouchException::class);
        $this->expectExceptionMessage('Hightouch::init() must be called before any other tracking method.');

        Hightouch::group([]);
    }
    public function testIdentifyThrowsHightouchExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(HightouchException::class);
        $this->expectExceptionMessage('Hightouch::init() must be called before any other tracking method.');

        Hightouch::identify([]);
    }
    public function testPageThrowsHightouchExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(HightouchException::class);
        $this->expectExceptionMessage('Hightouch::init() must be called before any other tracking method.');

        Hightouch::page([]);
    }
    public function testScreenThrowsHightouchExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(HightouchException::class);
        $this->expectExceptionMessage('Hightouch::init() must be called before any other tracking method.');

        Hightouch::screen([]);
    }
    public function testTrackThrowsHightouchExceptionWhenClientHasNotBeenInitialized(): void
    {
        $this->expectException(HightouchException::class);
        $this->expectExceptionMessage('Hightouch::init() must be called before any other tracking method.');

        Hightouch::track([]);
    }

    private static function resetHightouch(): void
    {
        $reflectedClass = new \ReflectionClass('Hightouch\Hightouch');
        $reflectedClass->setStaticPropertyValue('client', null);
    }
}
