<?php

namespace App\Http\Controllers;

use App\Log\LogReader;
use Illuminate\Http\Request;

class LogController extends Controller
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

    public function index(Request $request)
    {
        return view('logger');
    }

    /**
     * [logs description]
     * @param  Request $request [description]
     * @return [type]           [description]
     */
    public function logs(Request $request)
    {
        if ($request->has('date')) {
            return (new LogReader(['date' => $request->get('date')]))->get();
        } else {
            return (new LogReader())->get();
        }
    }
}
