<?php

namespace App\Http\Controllers\Api;

use Api\Gateway\Service;
use App\Http\Controllers\Controller;

class ServiceController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * [index description]
     * @return [type] [description]
     */
    public function index()
    {
        return Service::all();
    }
}
