<?php

namespace Api\Gateway\Exceptions;

use Illuminate\Http\Response;


class HttpException extends \Exception
{
	/**
     * UnableToExecuteRequestException constructor.
     * @param Response $response
     */
    public function __construct(Response $response = null)
    {
        if ($response) {
            parent::__construct((string) $response->getBody(), $response->getStatusCode());
            return;
        }

        parent::__construct('Unable to finish the request', 200);
    }
}