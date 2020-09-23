<?php

namespace Api\Gateway\Logger;

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

        $message = "[ {$method} {$uri} ] - User: {$user} - Body: {$bodyAsJson} - Headers: {$headersAsJson} - Files: {$files} - Response: [{$response->status()}] {$response->content()}";

        Log::channel(config('apigateway.logger.channel'))->info($message);
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