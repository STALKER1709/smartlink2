@php
    $breezeStatusTokens = ['profile-updated', 'password-updated', 'verification-link-sent'];
    $flashStatus = session('status');
@endphp

@if ($flashStatus && ! in_array($flashStatus, $breezeStatusTokens, true))
    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop pt-4">
        <div class="rounded-lg bg-primary-container/15 border border-outline-variant px-4 py-3 text-label-md text-primary">
            {{ $flashStatus }}
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop pt-4">
        <div class="rounded-lg bg-error-container border border-outline-variant px-4 py-3 text-label-md text-on-error-container">
            <p class="font-medium">{{ __("Veuillez corriger les erreurs suivantes :") }}</p>
            <ul class="mt-1 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
