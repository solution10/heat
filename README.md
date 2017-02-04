# Solution10\Heat

A simple library for tracking bad behaviour of given users (usually identified by their IP) and locking
them out when they dun goofed too much. Usual uses include preventing brute-force and dictionary attacks
against your login endpoints.

[![Build Status](https://travis-ci.org/Solution10/heat.svg?branch=master)](https://travis-ci.org/Solution10/heat)
[![Latest Stable Version](https://poser.pugx.org/solution10/heat/v/stable.svg)](https://packagist.org/packages/solution10/heat)
[![Total Downloads](https://poser.pugx.org/solution10/heat/downloads.svg)](https://packagist.org/packages/solution10/heat)
[![License](https://poser.pugx.org/solution10/heat/license.svg)](https://packagist.org/packages/solution10/heat)

- [Theory](#theory)
- [Usage](#usage)
    - [Creating Instances](#creating-instances)
    - [Tracking Heat](#tracking-heat)
    - [States](#states)
    - [Lifetime](#lifetime)
    - [Additional Methods](#additional-methods)
    - [Silex Service Provider](#silex-service-provider)
- [PHP Requirements](#php-requirements)
- [Author](#author)
- [License](#license)

## Theory

Users can make mistakes, but those mistakes can also be a signal of an attack. Consider a login form; users
make mistakes entering their passwords and such, but after a few attempts, it could be someone attempting to
brute force their way into the system by guessing passwords.

This library provides a way of tracking "heat" - every time a user does something suspicious, their heat rating
increases until they are required to provide some other action to ensure they are genuine, such as solving a
CAPTCHA. This heat accumulates as they perform actions and will be stored for a given time.

This library provides a simple tracking mechanism only, as well as a Silex service provider to help hook into
that framework.

## Usage

The lib is pretty simple to use:

### Creating Instances

```php
$c = new Doctrine\Common\Cache\RedisCache();
$h = new Solution10\Heat\Heat($_SERVER['REMOTE_ADDR'], $c);
```

You need to provide an identifier for the user as well as a `Doctrine\Common\Cache\Cache` instance to serve as the
storage backend.

In the above example we're using `$_SERVER['REMOTE_ADDR']` but this is a **bad idea**. Make use of something like
`Symfony\Component\HttpFoundation\Request::getClientIp()` instead that takes proxies and the like into account.

### Tracking Heat

```php
$c = new Doctrine\Common\Cache\RedisCache();
$h = new Solution10\Heat\Heat($_SERVER['REMOTE_ADDR'], $c);

if ($user->gotTheirPasswordWrong()) {
    $h->increase(25);
}

if ($user->solvedCAPCTHA()) {
    $h->decrease(50);
}
```

You can increase and decrease the heat of the current identifier using the `increase()` and `decrease()` methods.

If the user does something really good, or really terrible, you can use `increaseToMaximum()` and `decreaseToMinimum()`
to immediately increase/decrease the users heat. **You should do this in extreme cases only**.

Do **not** reset the users heat simply because they logged in. If you do, an attacker simply needs to log in with
their own account every so often to reset the heat! Allow the lifetime to do it's job.

You can adjust the maximum and minimum temperatures using `setMaxTemperature()` and `setMinimumTemperature()`, the
defaults of which are 100 and 0 respectively.

You can read the heat at any time using `$h->getTemperature()`.

### States

An identifier can be in one of three states; `SAFE`, `WARNING` and `CRITICAL`.

**SAFE**: the user hasn't done enough to arouse suspicion yet. Consider them alright, for now.

**WARNING**: the user has performed enough actions to be >= 60% of maximum temperature. Probably time to throw
them a CAPTCHA to check they're legit.

**CRITICAL**: the user has reached maximum temperature and should be considered dangerous. Block their ability to
log in for a while.

You can check the state with the following:

```php
// Boolean checks;
$h->isSafe();
$h->isWarning();
$h->isCritical();

// Checking state against constants;
$h->getState() === Heat::SAFE;
$h->getState() === Heat::WARNING;
$h->getState() === Heat::CRITICAL;
```

You can adjust the threshold between SAFE and CRITICAL using `$h->setSafeThreshold(0.4)`. This value is a float
representing the percentage of the maximum temperature, so setting a value of 0.4 is 40% of maximum.

### Lifetime

The temperature is written into the Cache instance using `write()` which will only update the value if it changed
from load. This is important, since it means that heat accumulates and stays for the lifetime that you define in
the class and can stick around for much longer.

Consider - a user attempts three logins, raising their temperature to 60%. They then do nothing for three minutes, or
log in correctly, and then attempt another brute force. The previous three attempts remain, and the new one, bringing
the user to 80% heat means that the user stays at 80% for a further five minutes.

The default lifetime is 300 seconds or five minutes.

You can get and set the lifetime using:

```php
$h->getLifetime();
$h->setLifetime(3600);
```

### Additional Methods

Everything you would expect has getters and setters.

```php
$h->getIdentifier();
$h->setIdentifier(string $identifier);

$h->getStorage();
$h->setStorage(Cache $storage);

$h->getStoragePrefix();
$h->setStoragePrefix(string $storagePrefix);

$h->getMaxTemperature();
$h->setMaxTemperature(int $maxTemperature);

$h->getMinTemperature();
$h->setMinTemperature(int $minTemperature);

$h->getSafeThreshold();
$h->setSafeThreshold(float $safeThreshold);

$h->getLifetime();
$h->setLifetime(int $lifetime);
```

### Silex Service Provider

The library provides a Service Provider for the [Silex microframework](http://silex.sensiolabs.org) which makes
integration easy.

Register the provider as normal:

```php
$app = new Silex\Application();
$app->register(new \Solution10\Heat\HeatTrackerServiceProvider(), [
    's10.heat.storage' => new RedisCache() // replace as appropriate
]);
```

This provider gives you an instance of `Solution10\Heat\Heat` in the `$app['s10.heat']` DI path and binds onto the
`$app->before()` and `$app->finished()` methods to provide the identifier from the IP address and the `write()`
method call.

The `s10.heat.storage` parameter can be passed to `register()` to use your own cache provider, if you don't provide
one it'll use `ArrayCache` which is totally useless since it doesn't persist!

## PHP Requirements

- PHP >= 7.0

## Author

Alex Gisby: [GitHub](http://github.com/alexgisby), [Twitter](http://twitter.com/alexgisby)

## License

[MIT](http://github.com/solution10/heat/tree/master/LICENSE.md)
