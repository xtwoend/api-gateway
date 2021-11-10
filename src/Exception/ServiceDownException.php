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
namespace Xtwoend\ApiGateway\Exception;

class ServiceDownException extends \Exception
{
    protected $code = 101001;

    protected $message = 'Connection failed';

    public function __construct($message = null)
    {
        $message = $message ?? $this->message;

        parent::__construct($message, $this->code);
    }
}
