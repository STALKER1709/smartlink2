<?php

namespace App\Http\Requests\Provider;

use App\Models\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServiceRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:service_categories,id'],
            'description' => ['required', 'string', 'max:3000'],
            'price_amount' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'price_unit' => ['nullable', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'is_available' => ['nullable', 'boolean'],
            'availability_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in([Service::STATUS_ACTIVE, Service::STATUS_INACTIVE])],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['image', 'max:4096'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'exists:service_images,id'],
        ];
    }
}
