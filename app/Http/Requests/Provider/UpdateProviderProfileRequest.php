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
        ];
    }
}
