<?php

namespace App\Http\Controllers\Kitchen;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Inertia\Inertia;
use Inertia\Response;

class KitchenController extends Controller
{
    public function index(Team $current_team): Response
    {
        return Inertia::render('kitchen/Index');
    }
}
