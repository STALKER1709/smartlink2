@php
    $breezeStatusTokens = ['profile-updated', 'password-updated', 'verification-link-sent'];
    $flashStatus = session('status');
@endphp

@if ($flashStatus && ! in_array($flashStatus, $breezeStatusTokens, true))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div class="rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
            {{ $flashStatus }}
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        <div class="rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
            <p class="font-medium">Veuillez corriger les erreurs suivantes :</p>
            <ul class="mt-1 list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif
