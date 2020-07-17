<?php

namespace Api\Gateway\Logger;

use Illuminate\Http\Request;

interface LogProfile
{
    public function shouldLogRequest(Request $request): bool;
}