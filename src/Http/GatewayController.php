<?php

namespace Api\Gateway\Http;

use Api\Gateway\Exceptions\DataFormatException;
use Api\Gateway\Exceptions\NotImplementedException;
use Api\Gateway\Request;
use Api\Gateway\Services\RestClient;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;

class GatewayController extends Controller
{
    /**
     * [$route description]
     * @var [type]
     */
    protected $route;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var PresenterContract
     */
    protected $presenter;

    /**
     * [$service description]
     * @var [type]
     */
    protected $services;

    /**
     * GatewayController constructor.
     * @param Request $request
     * @throws DataFormatException
     * @throws NotImplementedException
     */
    public function __construct(Request $request)
    {
        if (empty($request->getRoute())) throw new DataFormatException('Unable to find original URI pattern');
        $this->config = $request
            ->getRoute()
            ->getConfig();
        $this->route = $request->getRoute();
        $this->services = $request->getRoute()->getServices();
        $this->presenter = $request
            ->getRoute()
            ->getPresenter();
    }

    /**
     * @param Request $request
     * @param RestClient $client
     * @return Response
     */
    public function get(Request $request, RestClient $client)
    {
        return $this->simpleRequest($request, $client);
    }

    /**
     * @param Request $request
     * @param RestClient $client
     * @return Response
     */
    public function delete(Request $request, RestClient $client)
    {
        return $this->simpleRequest($request, $client);
    }

    /**
     * @param Request $request
     * @param RestClient $client
     * @return Response
     */
    public function post(Request $request, RestClient $client)
    {
        return $this->simpleRequest($request, $client);
    }

    /**
     * @param Request $request
     * @param RestClient $client
     * @return Response
     */
    public function put(Request $request, RestClient $client)
    {
        return $this->simpleRequest($request, $client);
    }

    /**
     * @param Request $request
     * @param RestClient $client
     * @return Response
     * @throws NotImplementedException
     */
    private function simpleRequest(Request $request, RestClient $client)
    {
        $start = microtime(true);

        if($this->route->getType() === 'echo'){
            return $this->echoRequest($request);
        }

        if($this->route->getType() === 'mock'){
            return $this->mockRequest($request);
        }

        $client->setBody($request->getContent());
        if (count($request->allFiles()) !== 0) {
            $client->setFiles($request->allFiles());
        }

        $parametersJar = array_merge($request->getRouteParams(), ['query_string' => $request->getQueryString()]);
        $response = $client->syncRequest($this->route, $parametersJar);

        $execution = microtime(true) - $start;
        
        if(is_null($response)){
            return $this->presenter
                ->setHeaders(['Time-Execution' => $execution, 'X-Mode' => $this->route->getType()])
                ->format("", 500);
        }

        return $this->presenter
            ->setHeaders(['Time-Execution' => $execution, 'X-Mode' => $this->route->getType()])
            ->format((string) $response->getBody(), $response->getStatusCode());
    }

    private function echoRequest(Request $request)
    {
        $start = microtime(true);

        $parametersJar = array_merge($request->getRouteParams(), ['query_string' => $request->getQueryString()]);

        $execution = microtime(true) - $start;
        
        return $this->presenter
            ->setHeaders(['Time-Execution' => $execution, 'X-Mode' => $this->route->getType()])
            ->format((string) $request->getContent(), 200);
    }

    private function mockRequest(Request $request)
    {   
        $start = microtime(true);

        $parametersJar = array_merge($request->getRouteParams(), ['query_string' => $request->getQueryString()]);

        return $this->presenter
            ->setHeaders(['Time-Execution' => $execution, 'X-Mode' => $this->route->getType()])
            ->format((string) $this->route->getContent(), 200);
    }
}