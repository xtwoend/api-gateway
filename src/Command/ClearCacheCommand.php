<?php

declare(strict_types=1);

namespace Xtwoend\ApiGateway\Command;

use Psr\SimpleCache\CacheInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;

/**
 * @Command
 */
#[Command]
class ClearCacheCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var CacheManager
     */
    private $cache;

    public function __construct(ContainerInterface $container, CacheInterface $cache)
    {
        $this->container = $container;
        $this->cache = $cache;

        parent::__construct('route:cache-clear');
    }

    public function configure()
    {
        parent::configure();
        $this->setDescription('Flush the application route cache');
    }

    public function handle()
    {
        if ($this->cache->delete('apigateway.routes')) {
            $this->info('Cache clear successfuly.');
        }
    }
}
