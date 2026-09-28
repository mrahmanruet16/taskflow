<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Phase 2.1 stub: proves the authenticated route + Blade + current-user
     * path works end to end. The real aggregation queries (project/task
     * counts) are added once the Project/Task models exist.
     */
    public function index(): View
    {
        return view('dashboard.index', [
            'user' => Auth::user(),
        ]);
    }
}
