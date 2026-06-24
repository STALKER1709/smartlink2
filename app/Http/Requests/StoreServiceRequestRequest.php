<?php

namespace App\Http\Requests;

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
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
            'service_id' => [
                'nullable',
                'required_without:provider_id',
                Rule::exists('services', 'id')->where('status', Service::STATUS_ACTIVE),
            ],
            'provider_id' => [
                'nullable',
                'required_without:service_id',
                Rule::exists('users', 'id')->where('role', User::ROLE_PROVIDER),
            ],
            'message' => ['required', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'action' => ['required', Rule::in(['draft', 'send'])],
        ];
    }
}
