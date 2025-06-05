<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/annonces', function () {
    return Inertia::render('Annonces');
})->name('annonces');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('profil', function () {
        return Inertia::render('profil');
    })->middleware(['auth', 'verified'])->name('profil');

    Route::get('comment-ca-marche', function () {
        return Inertia::render('comment-ca-marche');
    })->name('comment-ca-marche');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
