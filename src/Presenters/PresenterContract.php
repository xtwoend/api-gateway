<?php

namespace Api\Gateway\Presenters;
use Illuminate\Http\Response;

/**
 * Interface PresenterContract
 * @package App
 */
interface PresenterContract
{
	/**
	 * [setHeaders description]
	 * @param array $headers [description]
	 */
	public function setHeaders(array $headers = []);

    /**
     * @param array|string $input
     * @param $code
     * @return Response
     */
    public function format($input, $code);
}