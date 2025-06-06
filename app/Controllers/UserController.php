<?php

namespace App\Controllers;

use Portfolion\Http\Request;
use Portfolion\Http\Response;

class UserController
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request): Response
    {
        return view('user/index');
    }
}