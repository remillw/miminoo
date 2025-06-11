<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class ErrorController extends Controller
{
    public function notFound(): Response
    {
        return Inertia::render('Errors/404');
    }

    public function serverError(): Response
    {
        return Inertia::render('Errors/500');
    }

    public function forbidden(): Response
    {
        return Inertia::render('Errors/403');
    }
}
