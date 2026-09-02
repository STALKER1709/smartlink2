<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Les champs répétables arrivent avec leurs lignes vides.
     *
     * Le formulaire ouvre toujours une ligne sous « Zones d'intervention » et
     * sous « Moyens de contact », sans quoi il n'y aurait aucun champ où
     * écrire. Le prestataire qui n'en veut pas la laisse vide, et le
     * navigateur poste `service_areas[] = ''`.
     *
     * `ConvertEmptyStringsToNull` — actif par défaut — en fait alors un null,
     * que la règle `string` refuse. Le profil devenait impossible à
     * enregistrer sans remplir deux champs annoncés facultatifs, sur un
     * message qui ne désigne aucun champ visible : « Le champ
     * service_areas.0 doit être une chaîne de caractères. »
     *
     * Ces lignes ne sont pas une saisie invalide : ce sont des cases qu'on n'a
     * pas remplies. On les retire avant de valider, plutôt que de tolérer le
     * null jusqu'en base, où il ressortirait en zone d'intervention sans nom.
     */
    protected function prepareForValidation(): void
    {
        $vider = function (mixed $valeur, bool $garderLesCles = false): ?array {
            if (! is_array($valeur)) {
                return null;
            }

            $reste = array_filter($valeur, fn ($v) => is_string($v) && trim($v) !== '');

            return $garderLesCles ? $reste : array_values($reste);
        };

        $this->merge(array_filter([
            'service_areas' => $vider($this->input('service_areas')),
            'contact_methods' => $vider($this->input('contact_methods')),
            // Les horaires sont indexés par jour : leurs clés portent le sens.
            // Sept jours laissés vides donnaient un tableau de sept nulls, donc
            // un « Horaires » suivi d'une liste vide sur la fiche publique.
            'opening_hours' => $vider($this->input('opening_hours'), garderLesCles: true),
        ], fn (?array $v) => $v !== null));
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'exists:service_categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'service_areas' => ['nullable', 'array'],
            'service_areas.*' => ['string', 'max:120'],
            'opening_hours' => ['nullable', 'array'],
            'contact_methods' => ['nullable', 'array'],
            'contact_methods.*' => ['string', 'max:120'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'quarter' => ['nullable', 'string', 'max:120'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'id_card' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
        ];
    }
}
