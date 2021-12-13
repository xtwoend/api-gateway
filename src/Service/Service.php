<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Xtwoend\ApiGateway\Service;


class Service implements ServiceContract
{
    private $id;

    private $name;

    private $host;

    private $prefix;

    private $healthCheckPath;

    private $limit;

    private $weight;

    private $down;

    private $hit;

    private $version;

    private $default;

    public function __construct(
        int $id,
        string $name,
        string $host,
        ?string $healthCheckPath = null,
        ?string $prefix = null,
        int $limit = -1,
        int $weight = 1,
        $down = false,
        int $hit = 0,
        $version = null,
        $default = null
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->host = $host;
        $this->healthCheckPath = $healthCheckPath;
        $this->prefix = $prefix;
        $this->limit = $limit;
        $this->weight = $weight;
        $this->down = $down;
        $this->hit = $hit;
        $this->version = $version;
        $this->default = $default ?? 0;
    }

    public static function createFromArray(array $service): self
    {
        array_key_exists('id', $service);
        array_key_exists('name', $service);
        array_key_exists('host', $service);
        array_key_exists('health_check_path', $service);

        $prefix = null;
        $limit = -1;
        $weight = 1;
        $down = false;
        $hit = 0;
        $version = 'v1.0';
        $default = false;

        if (array_key_exists('prefix', $service)) {
            $prefix = $service['prefix'];
        }

        if (array_key_exists('limit', $service)) {
            $limit = $service['limit'];
        }

        if (array_key_exists('weight', $service)) {
            $weight = $service['weight'];
        }

        if (array_key_exists('down', $service)) {
            $down = $service['down'];
        }

        if (array_key_exists('hit', $service)) {
            $hit = $service['hit'];
        }

        if (array_key_exists('version', $service)) {
            $version = $service['version'];
        }

        if (array_key_exists('default', $service)) {
            $default = $service['default'];
        }

        return new self(
            $service['id'],
            $service['name'],
            $service['host'],
            $service['health_check_path'],
            $prefix,
            $limit,
            $weight,
            $down,
            $hit,
            $version,
            $default
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'host' => $this->getHost(),
            'health_check_path' => $this->getHealthCheckPath(),
            'prefix' => $this->getPrefix(),
            'limit' => $this->getLimit(),
            'weight' => $this->getWeight(),
            'enabled' => $this->isEnabled(),
            'down' => $this->isDown(),
            'hit' => $this->getHit(),
            'version' => $this->getVersion(),
            'default' => (bool) $this->getDefault(),
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    public function getHealthCheckPath(): ?string
    {
        return $this->healthCheckPath;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function getHit(): int
    {
        return $this->hit;
    }

    public function isEnabled(): bool
    {
        if ($limit = $this->isLimit()) {
            $this->resetHit();
        }

        if (! $limit) {
            return ! $this->isDown();
        }

        return (bool) $limit;
    }

    public function isDown(): bool
    {
        return (bool) $this->down;
    }

    public function isUp(): bool
    {
        return ! $this->isDown();
    }

    public function down(): void
    {
        $this->down = true;
    }

    public function up(): void
    {
        $this->down = false;
    }

    public function hit(): void
    {
        ++$this->hit;
    }

    public function isLimit(): bool
    {
        return $this->limit !== -1 && $this->limit <= $this->hit;
    }

    public function resetHit(): void
    {
        $this->hit = 0;
    }

    public function getUrl(): string
    {
        return sprintf(
            '%s%s',
            $this->getHost(),
            ! $this->getPrefix() ? $this->getPrefix() : sprintf('/%s', $this->getPrefix())
        );
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function getDefault(): bool
    {
        return (bool) $this->default ?? false;
    }
}
