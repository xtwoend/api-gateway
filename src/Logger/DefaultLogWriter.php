<?php

namespace Api\Gateway\Logger;

use Api\Gateway\Logger\DBLogger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DefaultLogWriter implements LogWriter
{
    public function logRequest(Request $request, $response)
    {
        $method = strtoupper($request->getMethod());

        $uri = $request->getRequestUri();

        $bodyAsJson = json_encode($request->except(config('apigateway.logger.except')));

        $headersAsJson = json_encode($request->headers->all());

        $files = (new Collection(iterator_to_array($request->files)))
            ->map([$this, 'flatFiles'])
            ->flatten()
            ->implode(',');

        $user = json_encode($request->user());

        $this->dbLogger($method, $uri, $bodyAsJson, $files, $headersAsJson, $user, $response->status(), $response->content());
    }

    public function dbLogger(...$args)
    {
        list($method, $uri, $body, $files, $headers, $user, $status, $response) = $args;

        try {
            DBLogger::create([
                'uri'       => $uri,
                'method'    => $method,
                'user'      => $user,
                'headers'   => $headers,
                'body'      => $body,
                'files'     => $files,
                'response'  => $response,
                'status'    => $status
            ]);
        } catch (\Exception $e) {
            $message = "[ {$method} {$uri} ] - User: {$user} - Body: {$body} - Headers: {$headers} - Files: {$files} - Response: [{$status}] {$response}";
            Log::channel(config('apigateway.logger.channel'))->info($message);
        }
    }

    public function flatFiles($file)
    {
        if ($file instanceof UploadedFile) {
            return $file->getClientOriginalName();
        }
        if (is_array($file)) {
            return array_map([$this, 'flatFiles'], $file);
        }

        return (string) $file;
    }
}