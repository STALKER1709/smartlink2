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
            // L'exigence « l'un des deux » ne s'écrit qu'une fois. Posée sur
            // les deux champs, elle produisait deux erreurs qui se renvoyaient
            // l'une à l'autre quand aucun des deux n'était fourni.
            'provider_id' => [
                'nullable',
                Rule::exists('users', 'id')->where('role', User::ROLE_PROVIDER),
            ],
            'message' => ['required', 'string', 'max:2000'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'action' => ['required', Rule::in(['draft', 'send'])],
        ];
    }

    /**
     * Le formulaire ne montre ni « service » ni « prestataire » : ils viennent
     * de la page d'où l'on arrive. Quand la règle tombe quand même — un service
     * désactivé entre l'ouverture de la page et l'envoi, par exemple — le
     * message par défaut disait « Le champ service est obligatoire lorsque
     * provider id n'est pas présent », deux noms de colonnes à propos de deux
     * champs invisibles.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $destinataire = "Cette demande n'a plus de destinataire : le service ou le prestataire n'est plus disponible. Repartez de sa page pour la renvoyer.";

        return [
            'service_id.required_without' => $destinataire,
            'service_id.exists' => $destinataire,
            'provider_id.exists' => $destinataire,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'service_id' => 'service',
            'provider_id' => 'prestataire',
            'message' => 'message',
            'preferred_date' => 'date souhaitée',
        ];
    }
}
