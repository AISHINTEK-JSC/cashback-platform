<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TrackingLinkController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.links.index', [
            'links' => $request->user()->trackingLinks()->with('platform')->withCount('orders')->latest()->paginate(20),
        ]);
    }
}
