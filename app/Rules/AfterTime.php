<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AfterTime implements ValidationRule
{
    protected $startTime;

    public function __construct($startTime)
    {
        $this->startTime = $startTime;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!$this->startTime || !$value) {
            return;
        }

        // Convertir les heures en minutes depuis minuit
        $startMinutes = $this->timeToMinutes($this->startTime);
        $endMinutes = $this->timeToMinutes($value);

        // Si l'heure de fin est plus petite que l'heure de début,
        // cela signifie que ça se termine le lendemain
        if ($endMinutes <= $startMinutes) {
            // Ajouter 24 heures (1440 minutes) pour passer au lendemain
            $endMinutes += 24 * 60;
        }

        $durationMinutes = $endMinutes - $startMinutes;

        // Vérifier que la durée est d'au moins 30 minutes
        if ($durationMinutes < 30) {
            $fail('La garde doit durer au moins 30 minutes.');
            return;
        }

        // Vérifier que la durée ne dépasse pas 24 heures
        if ($durationMinutes > 24 * 60) {
            $fail('La garde ne peut pas durer plus de 24 heures.');
            return;
        }

        // Avertissement si la garde dure plus de 12 heures (garde de nuit)
        $durationHours = $durationMinutes / 60;
        if ($durationHours > 12) {
            // Note: On pourrait ajouter un warning ici, mais pour l'instant on accepte
            // les gardes de nuit longues
        }
    }

    /**
     * Convertit une heure au format HH:MM en minutes depuis minuit
     */
    private function timeToMinutes($time): int
    {
        $parts = explode(':', $time);
        $hours = (int) $parts[0];
        $minutes = (int) ($parts[1] ?? 0);
        
        return $hours * 60 + $minutes;
    }
}
