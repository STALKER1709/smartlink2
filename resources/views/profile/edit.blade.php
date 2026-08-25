<x-app-layout>
    <x-slot name="header">
        <x-page-header :title="__('Profile')" />
    </x-slot>

    <div class="py-12">
        <div class="max-w-container mx-auto px-margin-mobile md:px-margin-desktop space-y-6">
            <div class="p-4 sm:p-8 bg-surface-container-lowest border border-outline-variant sm:rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-surface-container-lowest border border-outline-variant sm:rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-surface-container-lowest border border-outline-variant sm:rounded-xl">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
