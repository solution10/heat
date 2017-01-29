<?php

namespace Solution10\Heat\Tests;

use Doctrine\Common\Cache\ArrayCache;
use PHPUnit\Framework\TestCase;
use Silex\Application;
use Solution10\Heat\HeatTrackerServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class HeatTrackerServiceProviderTest extends TestCase
{
    public function testRegisterDefaults()
    {
        $hs = new HeatTrackerServiceProvider();
        $app = new Application();

        $app->register($hs);
        $this->assertTrue(isset($app['s10.heat.storage']));
        $this->assertInstanceOf(ArrayCache::class, $app['s10.heat.storage']);
    }

    public function testRegisterChangeStorage()
    {
        $hs = new HeatTrackerServiceProvider();
        $app = new Application();
        $c = new ArrayCache();

        $app->register($hs, [
            's10.heat.storage' => $c
        ]);

        $this->assertSame($c, $app['s10.heat.storage']);
    }

    public function testBootBasics()
    {
        $app = new Application();
        $hs = new HeatTrackerServiceProvider();
        $app->register($hs);

        $app->get('/', function () use ($app) {
            $app['s10.heat']->increase(20);
            return '';
        });

        $app->run(new Request([], [], [], [], [], ['REQUEST_URI' => '/', 'REMOTE_ADDR' => '192.168.192.10']));

        /* @var     \Solution10\Heat\Heat   $h  */
        $h = $app['s10.heat'];
        $this->assertEquals(20, $h->getTemperature());
        $this->assertEquals(20, $h->getStorage()->fetch('s10heat_192.168.192.10')['temperature']);
    }

    public function testRespectsStorage()
    {
        $c = new ArrayCache();
        $app = new Application();
        $hs = new HeatTrackerServiceProvider();
        $app->register($hs, [
            's10.heat.storage' => $c,
        ]);

        $app->get('/', function () use ($app) {
            $app['s10.heat']->increase(20);
            return '';
        });

        $app->run(new Request([], [], [], [], [], ['REQUEST_URI' => '/', 'REMOTE_ADDR' => '192.168.192.10']));

        /* @var     \Solution10\Heat\Heat   $h  */
        $h = $app['s10.heat'];
        $this->assertEquals(20, $h->getTemperature());
        $this->assertEquals(20, $h->getStorage()->fetch('s10heat_192.168.192.10')['temperature']);
        $this->assertEquals(20, $c->fetch('s10heat_192.168.192.10')['temperature']);
    }
}
