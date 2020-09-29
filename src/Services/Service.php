<?php

namespace Api\Gateway\Services;

use Illuminate\Support\Arr;

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

    public function __construct(
        int $id,
        string $name,
        string $host,
        ?string $healthCheckPath = null,
        ?string $prefix = null,
        int $limit = -1,
        int $weight = 1,
        bool $down = false,
        int $hit = 0
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

        return new self($service['id'], $service['name'], $service['host'], $service['health_check_path'], $prefix, $limit, $weight, $down, $hit);
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

        if (!$limit) {
            return ! $this->isDown();
        }

        return $limit;
    }

    public function isDown(): bool
    {
        return $this->down;
    }

    public function isUp(): bool
    {
        return !$this->down;
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
        $this->hit++;
    }

    public function isLimit(): bool
    {
        return -1 !== $this->limit && $this->limit <= $this->hit;
    }

    public function resetHit(): void
    {
        $this->hit = 0;
    }

    public function getUrl(): string
    {
        return sprintf('%s%s', $this->getHost(), !$this->getPrefix()? $this->getPrefix(): sprintf('/%s', $this->getPrefix()));
    }
}