<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\AfterTime;

class StoreAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('parent');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Étape 1: Date et horaires
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|string|regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/',
            'end_time' => [
                'required',
                'string',
                'regex:/^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/',
                new AfterTime($this->input('start_time'))
            ],
            
            // Étape 2: Enfants
            'children' => 'required|array|min:1|max:10',
            'children.*.nom' => 'required|string|max:50|regex:/^[a-zA-ZÀ-ÿ\s\-\']+$/u',
            'children.*.age' => 'required|string|max:3',
            'children.*.unite' => 'required|in:ans,mois',
            
            // Étape 3: Lieu
            'address' => 'required|string|max:500|min:10',
            'postal_code' => 'required|string|max:10|min:4',
            'country' => 'required|string|max:100|min:2',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            
            // Étape 4: Détails (optionnel)
            'additional_info' => 'nullable|string|max:2000',
            
            // Étape 5: Tarif
            'hourly_rate' => 'required|numeric|min:5|max:100',
            'estimated_duration' => 'nullable|numeric|min:0.5|max:24',
            'estimated_total' => 'nullable|numeric|min:0|max:2400',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            // Messages pour la date et horaires
            'date.required' => 'La date est obligatoire.',
            'date.date' => 'Veuillez saisir une date valide.',
            'date.after_or_equal' => 'La date ne peut pas être dans le passé.',
            
            'start_time.required' => 'L\'heure de début est obligatoire.',
            'start_time.regex' => 'L\'heure de début doit être au format HH:MM.',
            
            'end_time.required' => 'L\'heure de fin est obligatoire.',
            'end_time.regex' => 'L\'heure de fin doit être au format HH:MM.',
            
            // Messages pour les enfants
            'children.required' => 'Vous devez renseigner au moins un enfant.',
            'children.min' => 'Vous devez renseigner au moins un enfant.',
            'children.max' => 'Vous ne pouvez pas renseigner plus de 10 enfants.',
            
            'children.*.nom.required' => 'Le prénom de l\'enfant est obligatoire.',
            'children.*.nom.max' => 'Le prénom ne peut pas dépasser 50 caractères.',
            'children.*.nom.regex' => 'Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes.',
            
            'children.*.age.required' => 'L\'âge de l\'enfant est obligatoire.',
            'children.*.age.max' => 'L\'âge ne peut pas dépasser 3 caractères.',
            
            'children.*.unite.required' => 'L\'unité d\'âge est obligatoire.',
            'children.*.unite.in' => 'L\'unité d\'âge doit être "ans" ou "mois".',
            
            // Messages pour l'adresse
            'address.required' => 'L\'adresse est obligatoire.',
            'address.min' => 'L\'adresse doit contenir au moins 10 caractères.',
            'address.max' => 'L\'adresse ne peut pas dépasser 500 caractères.',
            
            'postal_code.required' => 'Le code postal est obligatoire.',
            'postal_code.min' => 'Le code postal doit contenir au moins 4 caractères.',
            'postal_code.max' => 'Le code postal ne peut pas dépasser 10 caractères.',
            
            'country.required' => 'Le pays est obligatoire.',
            'country.min' => 'Le nom du pays doit contenir au moins 2 caractères.',
            'country.max' => 'Le nom du pays ne peut pas dépasser 100 caractères.',
            
            'latitude.required' => 'Les coordonnées de l\'adresse sont manquantes. Veuillez sélectionner une adresse dans la liste proposée.',
            'latitude.numeric' => 'Les coordonnées de latitude ne sont pas valides.',
            'latitude.between' => 'Les coordonnées de latitude ne sont pas valides.',
            
            'longitude.required' => 'Les coordonnées de l\'adresse sont manquantes. Veuillez sélectionner une adresse dans la liste proposée.',
            'longitude.numeric' => 'Les coordonnées de longitude ne sont pas valides.',
            'longitude.between' => 'Les coordonnées de longitude ne sont pas valides.',
            
            // Messages pour les détails
            'additional_info.max' => 'Les informations complémentaires ne peuvent pas dépasser 2000 caractères.',
            
            // Messages pour le tarif
            'hourly_rate.required' => 'Le tarif horaire est obligatoire.',
            'hourly_rate.numeric' => 'Le tarif horaire doit être un nombre.',
            'hourly_rate.min' => 'Le tarif horaire doit être d\'au moins 5€/h.',
            'hourly_rate.max' => 'Le tarif horaire ne peut pas dépasser 100€/h.',
            
            'estimated_duration.numeric' => 'La durée estimée doit être un nombre.',
            'estimated_duration.min' => 'La durée estimée doit être d\'au moins 30 minutes.',
            'estimated_duration.max' => 'La durée estimée ne peut pas dépasser 24 heures.',
            
            'estimated_total.numeric' => 'Le coût total estimé doit être un nombre.',
            'estimated_total.min' => 'Le coût total estimé ne peut pas être négatif.',
            'estimated_total.max' => 'Le coût total estimé ne peut pas dépasser 2400€.',
        ];
    }

    /**
     * Get custom attribute names for error messages.
     */
    public function attributes(): array
    {
        return [
            'date' => 'date',
            'start_time' => 'heure de début',
            'end_time' => 'heure de fin',
            'children' => 'enfants',
            'children.*.nom' => 'prénom de l\'enfant',
            'children.*.age' => 'âge de l\'enfant',
            'children.*.unite' => 'unité d\'âge',
            'address' => 'adresse',
            'postal_code' => 'code postal',
            'country' => 'pays',
            'latitude' => 'coordonnées',
            'longitude' => 'coordonnées',
            'additional_info' => 'informations complémentaires',
            'hourly_rate' => 'tarif horaire',
            'estimated_duration' => 'durée estimée',
            'estimated_total' => 'coût total estimé',
        ];
    }

    /**
     * Validation additionnelle personnalisée pour les âges des enfants
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validation des âges des enfants
            if ($this->children && is_array($this->children)) {
                foreach ($this->children as $index => $child) {
                    if (isset($child['age']) && isset($child['unite'])) {
                        $age = (int) $child['age'];
                        $unite = $child['unite'];
                        
                        if ($unite === 'mois') {
                            if ($age < 1 || $age > 36) {
                                $validator->errors()->add("children.{$index}.age", 'L\'âge en mois doit être entre 1 et 36 mois.');
                            }
                        } elseif ($unite === 'ans') {
                            if ($age < 1 || $age > 17) {
                                $validator->errors()->add("children.{$index}.age", 'L\'âge en années doit être entre 1 et 17 ans.');
                            }
                        }
                    }
                }
            }
        });
    }
} 