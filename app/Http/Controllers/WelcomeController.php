<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

final class WelcomeController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('welcome');
    }
}
