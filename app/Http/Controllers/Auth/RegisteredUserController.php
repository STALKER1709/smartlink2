<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\ClientProfile;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptions) {}

    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        if ($user->isProvider()) {
            ProviderProfile::create([
                'user_id' => $user->id,
                'business_name' => $validated['business_name'],
            ]);

            // Un prestataire entre par 30 jours d'essai au palier le plus
            // complet : le catalogue se remplit sans barrière à l'entrée.
            $this->subscriptions->startTrial($user);
        } else {
            ClientProfile::create([
                'user_id' => $user->id,
            ]);
        }

        event(new Registered($user));

        Auth::login($user);

        // Un compte tout neuf passe par l'accueil : c'est le seul moment où
        // l'on peut dire en trois écrans ce que la plateforme fait, et
        // surtout ce qu'elle ne fait pas.
        return redirect(route('onboarding.show', absolute: false));
    }
}
