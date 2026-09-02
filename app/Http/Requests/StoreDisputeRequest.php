<?php

namespace App\Http\Requests;

use App\Models\Dispute;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDisputeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', Rule::in(array_keys(Dispute::reasons()))],
            'description' => ['required', 'string', 'min:20', 'max:500'],
            'evidence' => ['nullable', 'array', 'max:3'],
            'evidence.*' => ['image', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.min' => 'Décrivez le problème en quelques phrases : une ligne ne permet pas de trancher.',
            'evidence.max' => 'Trois photos au maximum.',
            'evidence.*.image' => 'Les preuves doivent être des images.',
            'evidence.*.max' => 'Chaque image doit peser moins de 4 Mo.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'reason' => 'motif',
            'description' => 'description',
            'evidence' => 'preuves',
        ];
    }
}
