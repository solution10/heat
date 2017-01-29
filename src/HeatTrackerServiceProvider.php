<?php

namespace Solution10\Heat;

use Doctrine\Common\Cache\ArrayCache;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

class HeatTrackerServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['s10.heat.storage'] = function () {
            return new ArrayCache();
        };
    }

    public function boot(Application $app)
    {
        $app->before(function (Request $request) use ($app) {
            $app['s10.heat'] = new Heat($request->getClientIp(), $app['s10.heat.storage']);
        });

        $app->finish(function () use ($app) {
            $app['s10.heat']->write();
        });
    }
}
