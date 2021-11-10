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

interface ServiceContract
{
    public function getName(): string;
    public function getHost(): string;
    public function getPrefix(): ?string;
    public function getHealthCheckPath(): ?string;
    public function getLimit(): int;
    public function getWeight(): int;
    public function getHit(): int;
    public function isEnabled(): bool;
    public function isDown(): bool;
    public function isUp(): bool;
    public function down(): void;
    public function up(): void;
    public function hit(): void;
    public function isLimit(): bool;
    public function resetHit(): void;
    public function getUrl(): string;
    public function getVersion(): ?string;
}
