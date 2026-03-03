<?php

namespace App\Http\Controllers\Admin;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;

class GroupController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Groups/Index');
    }
}
