<?php

namespace Solution10\Heat\Tests;

use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use Solution10\Heat\Heat;

class HeatTest extends TestCase
{
    public function testSetGetIdentifier()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals('127.0.0.1', $h->getIdentifier());
        $this->assertEquals($h, $h->setIdentifier('192.168.192.10'));
        $this->assertEquals('192.168.192.10', $h->getIdentifier());
    }

    public function testSetGetStorage()
    {
        $c1 = new ArrayCache();
        $c2 = new ArrayCache();
        $h = new Heat('127.0.0.1', $c1);
        $this->assertSame($c1, $h->getStorage());
        $this->assertEquals($h, $h->setStorage($c2));
        $this->assertSame($c2, $h->getStorage());
    }

    public function testSetGetStoragePrefix()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals('s10heat_', $h->getStoragePrefix());
        $this->assertEquals($h, $h->setStoragePrefix('myheat_'));
        $this->assertEquals('myheat_', $h->getStoragePrefix());
    }

    public function testSetGetLifetime()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals(3600, $h->getLifetime());
        $this->assertEquals($h, $h->setLifetime(60));
        $this->assertEquals(60, $h->getLifetime());
    }

    public function testSetGetMaxTemperature()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals(100, $h->getMaxTemperature());
        $this->assertEquals($h, $h->setMaxTemperature(1000));
        $this->assertEquals(1000, $h->getMaxTemperature());
    }

    public function testSetGetMinTemperature()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals(0, $h->getMinTemperature());
        $this->assertEquals($h, $h->setMinTemperature(10));
        $this->assertEquals(10, $h->getMinTemperature());
    }

    public function testSetGetSafeThreshold()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals(0.6, $h->getSafeThreshold());
        $this->assertEquals($h, $h->setSafeThreshold(0.9));
        $this->assertEquals(0.9, $h->getSafeThreshold());
    }

    public function testReadingTemperature()
    {
        $c = new ArrayCache();
        $c->save('s10heat_127.0.0.1', ['temperature' => 63], 3600);
        $h = new Heat('127.0.0.1', $c);
        $this->assertEquals(63, $h->getTemperature());
    }

    public function testReadBorkedCache()
    {
        $c = new ArrayCache();
        $c->save('s10heat_127.0.0.1', 63, 3600);
        $h = new Heat('127.0.0.1', $c);
        $this->assertEquals(0, $h->getTemperature());
    }

    public function testWrite()
    {
        $c = new ArrayCache();
        $h = new Heat('127.0.0.1', $c);
        $h->increase(20);
        $this->assertEquals($h, $h->write());
        $this->assertEquals(
            ['temperature' => 20],
            $c->fetch('s10heat_127.0.0.1')
        );
    }

    public function testWriteCustomPrefix()
    {
        $c = new ArrayCache();
        $h = new Heat('127.0.0.1', $c);
        $h->setStoragePrefix('custom_');
        $h->increase(20);
        $this->assertEquals($h, $h->write());
        $this->assertEquals(
            ['temperature' => 20],
            $c->fetch('custom_127.0.0.1')
        );
    }

    public function testIncreasingTemperature()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals($h, $h->increase(20));
        $this->assertEquals(20, $h->getTemperature());
    }

    public function testTemperatureCaps()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals($h, $h->increase(120));
        $this->assertEquals(100, $h->getTemperature());

        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals($h, $h->increase(20));
        $this->assertEquals($h, $h->increase(100));
        $this->assertEquals(100, $h->getTemperature());
    }

    public function testIncreaseToMaximum()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals($h, $h->increaseToMaximum());
        $this->assertEquals(100, $h->getTemperature());

        $h = new Heat('127.0.0.1', new ArrayCache());
        $h->increase(40);
        $this->assertEquals($h, $h->increaseToMaximum());
        $this->assertEquals(100, $h->getTemperature());
    }

    public function testDecreasingTemperature()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $h->increase(60);
        $this->assertEquals($h, $h->decrease(20));
        $this->assertEquals(40, $h->getTemperature());
    }

    public function testMinTemperatureCaps()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals($h, $h->decrease(120));
        $this->assertEquals(0, $h->getTemperature());

        $h = new Heat('127.0.0.1', new ArrayCache());
        $h->increase(60);
        $this->assertEquals($h, $h->decrease(100));
        $this->assertEquals(0, $h->getTemperature());
    }

    public function testDecreaseToMinimum()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $this->assertEquals($h, $h->decreaseToMinimum());
        $this->assertEquals(0, $h->getTemperature());

        $h = new Heat('127.0.0.1', new ArrayCache());
        $h->increase(40);
        $this->assertEquals($h, $h->decreaseToMinimum());
        $this->assertEquals(0, $h->getTemperature());
    }

    public function testStatesSafe()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $h->increase(20);
        $this->assertTrue($h->isSafe());
        $this->assertFalse($h->isWarning());
        $this->assertFalse($h->isCritical());
        $this->assertEquals(Heat::SAFE, $h->getState());
    }

    public function testStatesWarning()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $h->increase(60);
        $this->assertFalse($h->isSafe());
        $this->assertTrue($h->isWarning());
        $this->assertFalse($h->isCritical());
        $this->assertEquals(Heat::WARNING, $h->getState());
    }

    public function testStatesCritical()
    {
        $h = new Heat('127.0.0.1', new ArrayCache());
        $h->increase(100);
        $this->assertFalse($h->isSafe());
        $this->assertFalse($h->isWarning());
        $this->assertTrue($h->isCritical());
        $this->assertEquals(Heat::CRITICAL, $h->getState());
    }
}
