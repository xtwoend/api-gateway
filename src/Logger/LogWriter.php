<?php

namespace Api\Gateway\Logger;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

interface LogWriter
{
    public function logRequest(Request $request, Response $response);
}