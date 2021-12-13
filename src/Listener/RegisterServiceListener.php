<?php

namespace Xtwoend\ApiGateway\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ServiceGovernance\DriverManager;
use Hyperf\Framework\Event\MainWorkerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Hyperf\Consul\Exception\ServerException;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Str;


class RegisterServiceListener implements ListenerInterface
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var DriverManager
     */
    protected $governanceManager;

    protected $publishTo = 'consul';

    public function __construct(ContainerInterface $container)
    {
        $this->governanceManager = $container->get(DriverManager::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function listen(): array
    {
        return [
            MainWorkerStart::class,
            MainCoroutineServerStart::class,
        ];
    }

    /**
     * All official rpc protocols should register in here,
     * and the others non-official protocols should register in their own component via listener.
     *
     * @param MainWorkerStart|MainCoroutineServerStart $event
     */
    public function process(object $event)
    {
        $continue = true;
        while ($continue) {
            try {
                $servers = $this->getServers();
                foreach ($servers as $server) {
                    if($server->down) {
                        continue;
                    }
                    if ($governance = $this->governanceManager->get($this->publishTo)) {
                        $serviceName = $server->name;
                        $address = $server->host;
                        $port = $server->port;
                        $service = (array) $server;
                        $service['protocol'] = 'http';
                        unset($service['id']);
                        if (! $governance->isRegistered($serviceName, $address, (int) $port, $service)) {
                            $governance->register($serviceName, $address, (int) $port, $service);
                        }
                    }
                }
                $continue = false;
            } catch (ServerException $throwable) {
                if (strpos($throwable->getMessage(), 'Connection failed') !== false) {
                    $this->logger->warning('Cannot register service, connection of service center failed, re-register after 10 seconds.');
                    sleep(10);
                } else {
                    throw $throwable;
                }
            }
        } 
    }

    private function getServers()
    {
        return Db::table('services')->select('*')->get();
    }
}