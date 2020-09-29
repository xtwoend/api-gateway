<?php

namespace Api\Gateway\Presenters;

use Api\Gateway\Exceptions\DataFormatException;
use Illuminate\Http\Response;

/**
 * Class JSONPresenter
 * @package Api\Gateway\Presenters
 */
class JSONPresenter implements PresenterContract
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
     * @param $input
     * @return array
     */
    public static function safeDecode($input) {
        // Fix for PHP's issue with empty objects
        $input = preg_replace('/{\s*}/', "{\"EMPTY_OBJECT\":true}", $input);

        return json_decode($input, true);
    }

    /**
     * @param array|object $input
     * @return string
     */
    public static function safeEncode($input) {
        return preg_replace('/{"EMPTY_OBJECT"\s*:\s*true}/', '{}', json_encode($input, JSON_UNESCAPED_SLASHES));
    }

    /**
     * @param array|string $input
     * @param $code
     * @return Response
     */
    public function format($input, $code)
    {
        if (empty($input) && ! is_array($input)) return new Response(null, $code);

        $serialized = is_array($input) ? $this->formatArray($input) : $this->formatString($input);

        return new Response($serialized, $code, array_merge([
            'Content-Type' => 'application/json',
            'X-Gateway-Version' => config('apigateway.version', 'v1'),
            'Connection' => 'close'
        ], $this->headers));
    }

    /**
     * @param $input
     * @return string
     * @throws DataFormatException
     */
    private function formatString($input)
    {
        $decoded = self::safeDecode($input);
        if ($decoded === null) throw new DataFormatException('Unable to decode input');

        return $this->formatArray($decoded);
    }

    /**
     * @param array|mixed $input
     * @return string
     */
    private function formatArray($input)
    {
        return self::safeEncode($input);
    }
}