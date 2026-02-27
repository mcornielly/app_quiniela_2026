<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class RankingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Rankings/Index');
    }
}
