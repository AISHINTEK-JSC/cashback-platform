<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckinController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('dashboard.checkins.index', [
            'history' => $request->user()->checkins()->latest('checkin_date')->paginate(30),
        ]);
    }
}
