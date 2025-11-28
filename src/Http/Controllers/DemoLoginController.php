<?php

namespace Sti\StiAuth\Http\Controllers;

use Illuminate\Routing\Controller;

class DemoLoginController extends Controller
{
    public function index()
    {
        return view('sti-auth::demo');
    }
}
