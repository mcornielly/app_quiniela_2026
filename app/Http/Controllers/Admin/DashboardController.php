<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard page.
     *
     * This route is protected by auth/verified middleware in web.php.
     */
    public function index()
    {
        return Inertia::render('Dashboard');
    }
}
