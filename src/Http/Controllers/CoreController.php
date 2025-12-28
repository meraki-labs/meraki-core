<?php

namespace Meraki\Core\Http\Controllers;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;

class CoreController extends Controller
{
    /**
     * @return Factory|View
     */
    public function index(): Factory|View
    {
        return view('meraki::dashboard', [
            'app' => config('meraki.name')
        ]);
    }
}