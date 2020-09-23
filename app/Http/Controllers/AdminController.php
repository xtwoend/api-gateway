<?php

namespace App\Http\Controllers;

class AdminController extends Controller
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
        return view('index');
    }
}
