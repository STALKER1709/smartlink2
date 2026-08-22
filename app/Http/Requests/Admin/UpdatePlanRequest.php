<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'price_xaf' => ['required', 'integer', 'min:0', 'max:1000000'],
            // Vide signifie « illimité » : c'est ainsi que la limite est
            // stockée, et le formulaire doit pouvoir l'exprimer.
            'max_services' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'max_monthly_requests' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'is_featured' => ['boolean'],
            'has_ai_writing' => ['boolean'],
            'has_stats' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_featured' => $this->boolean('is_featured'),
            'has_ai_writing' => $this->boolean('has_ai_writing'),
            'has_stats' => $this->boolean('has_stats'),
            'is_active' => $this->boolean('is_active'),
            'max_services' => $this->input('max_services') === '' ? null : $this->input('max_services'),
            'max_monthly_requests' => $this->input('max_monthly_requests') === '' ? null : $this->input('max_monthly_requests'),
        ]);
    }
}
