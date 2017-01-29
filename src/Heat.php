<?php

namespace Solution10\Heat;

use Doctrine\Common\Cache\Cache;

/**
 * Class Heat
 *
 * This class simply tracks the amount of heat that a given identifier
 * has accumulated.
 *
 * @package     Solution10\Heat
 * @author      Alex Gisby<alex@solution10.com>
 * @license     MIT
 */
class Heat
{
    const
        SAFE = 0,
        WARNING = 10,
        CRITICAL = 100
    ;

    /**
     * @var     string
     */
    protected $identifier;

    /**
     * @var     Cache
     */
    protected $storage;

    /**
     * @var     string
     */
    protected $storagePrefix = 's10heat_';

    /**
     * @var     int
     */
    protected $temperature = 0;

    /**
     * @var     int
     */
    protected $maxTemperature = 100;

    /**
     * @var     int
     */
    protected $minTemperature = 0;

    /**
     * @var     float
     */
    protected $safeThreshold = 0.6;

    /**
     * @var     int
     */
    protected $lifetime = 300;

    /**
     * @param   string  $identifier
     * @param   Cache   $storage
     */
    public function __construct(string $identifier, Cache $storage)
    {
        $this->identifier = $identifier;
        $this->storage = $storage;

        $data = $this->storage->fetch($this->storagePrefix.$this->identifier);
        if ($data && is_array($data) && array_key_exists('temperature', $data)) {
            $this->temperature = $data['temperature'];
        }
    }

    /**
     * Writes out the current temperature into storage.
     *
     * @return  $this
     */
    public function write()
    {
        $this->storage->save(
            $this->storagePrefix.$this->identifier,
            ['temperature' => $this->temperature],
            $this->lifetime
        );
        return $this;
    }

    /**
     * Increments the heat value by a given amount. Will cap to the max temperature
     * set on the class.
     *
     * @param   int     $amount
     * @return  $this
     */
    public function increase(int $amount)
    {
        $this->temperature = min($this->temperature + $amount, $this->maxTemperature);
        return $this;
    }

    /**
     * Sets the temperature at its max value
     *
     * @return  $this
     */
    public function increaseToMaximum()
    {
        $this->temperature = $this->maxTemperature;
        return $this;
    }

    /**
     * Decrements the heat value by a given amount. Will cap to the min temperature
     * set on the class.
     *
     * @param   int     $amount
     * @return  $this
     */
    public function decrease(int $amount)
    {
        $this->temperature = max($this->temperature - $amount, $this->minTemperature);
        return $this;
    }

    /**
     * Sets the temperature at its min value
     *
     * @return  $this
     */
    public function decreaseToMinimum()
    {
        $this->temperature = $this->minTemperature;
        return $this;
    }


    /**
     * Returns the current temperature of this identifier
     *
     * @return  int
     */
    public function getTemperature(): int
    {
        return $this->temperature;
    }

    /**
     * Returns whether the user is in a 'safe' range - as defined by the setSafeThreshold().
     * If this identifier is in warning state, you probably want to start treating them
     * with suspicion - display a CAPTCHA or something.
     *
     * @return  bool
     */
    public function isSafe(): bool
    {
        return $this->temperature < ($this->maxTemperature * $this->safeThreshold);
    }

    /**
     * Returns whether the user is in a 'warning' range - as defined by the setSafeThreshold().
     * If this identifier is in warning state, you probably want to start treating them
     * with suspicion - display a CAPTCHA or something.
     *
     * @return  bool
     */
    public function isWarning(): bool
    {
        return
            $this->temperature !== $this->maxTemperature
            && $this->temperature >= ($this->maxTemperature * $this->safeThreshold)
        ;
    }

    /**
     * Returns whether the user is in critical state. If they are in critical they have performed
     * too many actions incorrectly for too long. Take extreme action - log them out, suspend access
     * and find a way to verify this identifier properly.
     *
     * @return  bool
     */
    public function isCritical(): bool
    {
        return $this->temperature === $this->maxTemperature;
    }

    /**
     * Returns the state, via one of the class constants, that the current identifier is in.
     *
     * @return  int
     */
    public function getState(): int
    {
        if ($this->isCritical()) {
            return self::CRITICAL;
        }

        if ($this->isWarning()) {
            return self::WARNING;
        }

        return self::SAFE;
    }

    /* ------------ Getters and setters ---------------- */

    /**
     * @return  string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param   string $identifier
     * @return  $this
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return  Cache
     */
    public function getStorage(): Cache
    {
        return $this->storage;
    }

    /**
     * @param   Cache $storage
     * @return  $this
     */
    public function setStorage(Cache $storage)
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * @return  string
     */
    public function getStoragePrefix(): string
    {
        return $this->storagePrefix;
    }

    /**
     * @param   string $storagePrefix
     * @return  $this
     */
    public function setStoragePrefix(string $storagePrefix)
    {
        $this->storagePrefix = $storagePrefix;
        return $this;
    }

    /**
     * @return  int
     */
    public function getMaxTemperature(): int
    {
        return $this->maxTemperature;
    }

    /**
     * @param   int $maxTemperature
     * @return  $this
     */
    public function setMaxTemperature(int $maxTemperature)
    {
        $this->maxTemperature = $maxTemperature;
        return $this;
    }

    /**
     * @return  int
     */
    public function getMinTemperature(): int
    {
        return $this->minTemperature;
    }

    /**
     * @param   int $minTemperature
     * @return  $this
     */
    public function setMinTemperature(int $minTemperature)
    {
        $this->minTemperature = $minTemperature;
        return $this;
    }

    /**
     * @return  float
     */
    public function getSafeThreshold(): float
    {
        return $this->safeThreshold;
    }

    /**
     * @param   float $safeThreshold
     * @return  $this
     */
    public function setSafeThreshold(float $safeThreshold)
    {
        $this->safeThreshold = $safeThreshold;
        return $this;
    }

    /**
     * @return  int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * @param   int $lifetime
     * @return  $this
     */
    public function setLifetime(int $lifetime)
    {
        $this->lifetime = $lifetime;
        return $this;
    }
}
