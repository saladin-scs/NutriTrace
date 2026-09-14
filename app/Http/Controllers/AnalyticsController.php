<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class AnalyticsController extends Controller
{
    /**
     * Analytics is embedded in Control Tower (Intelligence tab).
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('control-tower.index', ['tab' => 'intelligence']);
    }
}
