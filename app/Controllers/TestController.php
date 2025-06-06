<?php

namespace App\Controllers;

use Portfolion\Http\Request;
use Portfolion\Http\Response;

class TestController
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('test/index');
    }
}