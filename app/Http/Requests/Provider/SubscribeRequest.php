<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
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
            // Numéro camerounais, avec ou sans indicatif : 677123456 ou +237677123456.
            'phone' => ['required', 'string', 'regex:/^(\+?237)?6[0-9]{8}$/'],
            'operator' => ['required', 'in:mtn,orange'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('ui.subscription.phone_invalid'),
        ];
    }
}
