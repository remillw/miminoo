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

        // Convertir les heures en timestamps pour comparaison
        $startTimestamp = strtotime("1970-01-01 " . $this->startTime);
        $endTimestamp = strtotime("1970-01-01 " . $value);

        // Vérifier que l'heure de fin est après l'heure de début
        if ($endTimestamp <= $startTimestamp) {
            $fail('L\'heure de fin doit être après l\'heure de début.');
            return;
        }

        // Vérifier que la durée est d'au moins 30 minutes
        $durationMinutes = ($endTimestamp - $startTimestamp) / 60;
        if ($durationMinutes < 30) {
            $fail('La garde doit durer au moins 30 minutes.');
            return;
        }

        // Vérifier que la durée ne dépasse pas 12 heures
        $durationHours = $durationMinutes / 60;
        if ($durationHours > 12) {
            $fail('La garde ne peut pas durer plus de 12 heures.');
        }
    }
}
