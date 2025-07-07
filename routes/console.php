<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Programmer l'archivage automatique des conversations tous les jours à 2h du matin
Schedule::command('conversations:archive-old')->dailyAt('02:00');

// Mettre à jour les statuts des annonces toutes les 15 minutes
Schedule::command('announcements:update-statuses')->everyFifteenMinutes();
