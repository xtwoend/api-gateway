<?php

namespace Api\Gateway\Services;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Exception\StorageException;
use Ackintosh\Ganesha\Storage\AdapterInterface;
use Ackintosh\Ganesha\Storage\Adapter\SlidingTimeWindowInterface;


/**
 * 
 */
class RedisLumenAdapter implements AdapterInterface, SlidingTimeWindowInterface
{
	/**
     * @var \Ackintosh\Ganesha\Storage\Adapter\RedisStore
     */
    private $redis;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @param \Redis|\RedisArray|\RedisCluster|\Predis\Client|\Ackintosh\Ganesha\Storage\Adapter\RedisStore $redis
     */
    public function __construct($redis)
    {
        if (!($redis instanceof Illuminate\Redis\RedisManager)) {
            $redis = app('redis');
        }

        $this->redis = $redis;
    }

    /**
     * @return bool
     */
    public function supportCountStrategy(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function supportRateStrategy(): bool
    {
        return true;
    }

    /**
     * @param Configuration $configuration
     *
     * @return void
     */
    public function setConfiguration(Configuration $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @param string $service
     *
     * @return int
     * @throws StorageException
     */
    public function load(string $service): int
    {
        $expires = microtime(true) - $this->configuration->timeWindow();
       	
        if ($this->redis->zRemRangeByScore($service, '-inf', $expires) === false) {
            throw new StorageException('Failed to remove expired elements. service: ' . $service);
        }

        $r = $this->redis->zCard($service);

        if ($r === false) {
            throw new StorageException('Failed to load cardinality. service: ' . $service);
        }

        return $r;
    }

    /**
     * @param  string $service
     * @param  int    $count
     * @return void
     */
    public function save(string $service, int $count): void
    {
        // Redis adapter does not support Count strategy
    }

    /**
     * @param string $service
     *
     * @throws StorageException
     */
    public function increment(string $service): void
    {
        $t = microtime(true);
        $this->redis->zAdd($service, $t, $t);
    }

    public function decrement(string $service): void
    {
        // Redis adapter does not support Count strategy
    }

    public function saveLastFailureTime(string $service, int $lastFailureTime): void
    {
        // nop
    }

    /**
     * @param $service
     *
     * @return int|null
     * @throws StorageException
     */
    public function loadLastFailureTime(string $service)
    {
        $lastFailure = $this->redis->zRange($service, -1, -1);

        if (!$lastFailure) {
            return null;
        }

        return (int)$lastFailure[0];
    }

    /**
     * @param string $service
     * @param int    $status
     *
     * @throws StorageException
     */
    public function saveStatus(string $service, int $status): void
    {
        $r = $this->redis->set($service, $status);

        if ($r === false) {
            throw new StorageException(sprintf(
                'Failed to save status. service: %s, status: %d',
                $service,
                $status
            ));
        }
    }

    /**
     * @param string $service
     *
     * @return int
     * @throws StorageException
     */
    public function loadStatus(string $service): int
    {
        $r = $this->redis->get($service);

        // \Redis::get() returns FALSE if key didn't exist.
        // @see https://github.com/phpredis/phpredis#get
        if ($r === false) {
            $this->saveStatus($service, Ganesha::STATUS_CALMED_DOWN);
            return Ganesha::STATUS_CALMED_DOWN;
        }

        return (int)$r;
    }

    public function reset(): void
    {
        // TODO: Implement reset() method.
    }
}