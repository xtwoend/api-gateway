<?php

namespace Api\Gateway\Presenters;
use Illuminate\Http\Response;

/**
 * Class RawPresenter
 * @package Api\Gateway\Presenters
 */
class RawPresenter implements PresenterContract
{   
    /**
     * [$headers description]
     * @var array
     */
    protected $headers = [];

    /**
     * [setHeaders description]
     * @param array $headers [description]
     */
    public function setHeaders($headers =[])
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param array|string $input
     * @param $code
     * @return Response
     */
    public function format($input, $code)
    {
        if (is_array($input)) $input = json_encode($input);

        return new Response($input, $code, array_merge([
            'Content-Type' => 'application/json',
            'Gateway-Version' => config('apigateway.version', 'v1'),
        ], $this->headers));
    }
}