<?php

namespace Xtwoend\ApiGateway\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ServiceGovernance\DriverManager;
use Hyperf\Framework\Event\MainWorkerStart;
use Hyperf\Server\Event\MainCoroutineServerStart;
use Hyperf\Consul\Exception\ServerException;
use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\ServiceGovernanceConsul\ConsulAgent;
use Hyperf\DbConnection\Db;
use Hyperf\Utils\Str;


class RegisterServiceListener implements ListenerInterface
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var DriverManager
     */
    protected $governanceManager;

    protected $publishTo = 'consul';

    public function __construct(ContainerInterface $container)
    {
        $this->governanceManager = $container->get(DriverManager::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
        $this->client = $container->get(ConsulAgent::class);
        $this->config = $container->get(ConfigInterface::class);

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
    public function process(object $event): void
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
                            $this->register($serviceName, $address, (int) $port, $service);
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

    public function register(string $name, string $host, int $port, array $metadata): void
    {
        $nextId = empty($metadata['id']) ? $this->generateId($this->getLastServiceId($name)) : $metadata['id'];
        $protocol = $metadata['protocol'];
        $deregisterCriticalServiceAfter = $this->config->get('services.drivers.consul.check.deregister_critical_service_after') ?? '90m';
        $interval = $this->config->get('services.drivers.consul.check.interval') ?? '1s';
        $requestBody = [
            'Name' => $name,
            'ID' => $nextId,
            'Address' => $host,
            'Port' => $port,
            'Meta' => [
                'Protocol' => $protocol,
            ],
        ];
        if ($protocol === 'http') {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => $deregisterCriticalServiceAfter,
                'HTTP' => "http://{$host}:{$port}/ping",
                'Interval' => $interval,
            ];
        }
        
        $response = $this->client->registerService($requestBody);
        if ($response->getStatusCode() === 200) {
            $this->logger->info(sprintf('Service %s:%s register to the consul successfully.', $name, $nextId));
        } else {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $name));
        }
    }

    protected function getLastServiceId(string $name)
    {
        $maxId = -1;
        $lastService = $name;
        $services = $this->client->services()->json();
        foreach ($services ?? [] as $id => $service) {
            if (isset($service['Service']) && $service['Service'] === $name) {
                $exploded = explode('-', (string) $id);
                $length = count($exploded);
                if ($length > 1 && is_numeric($exploded[$length - 1]) && $maxId < $exploded[$length - 1]) {
                    $maxId = $exploded[$length - 1];
                    $lastService = $service;
                }
            }
        }
        return $lastService['ID'] ?? $name;
    }

    protected function generateId(string $name)
    {
        $exploded = explode('-', $name);
        $length = count($exploded);
        $end = -1;
        if ($length > 1 && is_numeric($exploded[$length - 1])) {
            $end = $exploded[$length - 1];
            unset($exploded[$length - 1]);
        }
        $end = intval($end);
        ++$end;
        $exploded[] = $end;
        return implode('-', $exploded);
    }
}