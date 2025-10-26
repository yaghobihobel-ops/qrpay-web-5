<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Routing\Controller;

class QueueMonitorController extends Controller
{
    public function index(): Renderable
    {
        $page_title = __('Queue Monitor');

        return view('admin.sections.horizon.dashboard', [
            'page_title' => $page_title,
            'horizonAvailable' => class_exists('Laravel\Horizon\Horizon'),
            'horizonPath' => url(config('horizon.path', 'horizon')),
        ]);
    }
}
