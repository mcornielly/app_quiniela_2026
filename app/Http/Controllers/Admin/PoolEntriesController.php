<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;

class PoolEntriesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Pools/Index');
    }

    public function create(): Response
    {
        return Inertia::render('Pools/Create');
    }
}
