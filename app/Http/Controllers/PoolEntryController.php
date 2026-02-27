<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class PoolEntryController extends Controller
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
