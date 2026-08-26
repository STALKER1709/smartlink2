<x-app-layout>
    <x-slot name="header">
        <x-page-header title="Mes services" :subtitle="$services->total().' '.Str::plural('service', $services->total()).' publié'.($services->total() > 1 ? 's' : '')">
            <x-slot name="action">
                <x-publish-cta compact />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div class="mx-auto max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-outline-variant bg-secondary-container/30 px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        @if ($services->isEmpty())
            <x-empty-state
                title="Vous n'avez pas encore publié de service."
                description="Un service bien décrit, avec une photo et un prix indicatif, est ce qui décide un client à vous écrire."
                action-label="Publier mon premier service"
                :action-href="route('provider.services.create')"
            />
        @else
            {{-- Les trois titres se lisaient « Coiffure à domi… », « Peinture
                 intérie… », « Service de dém… » : un prestataire ne
                 distinguait plus ses propres annonces. La vignette, la
                 pastille et deux actions se partageaient la largeur avec le
                 titre, à qui il restait cent trente pixels.

                 Le titre passe seul sur sa ligne, les actions sous lui. Et
                 elles portent leur nom : réduites à deux pictogrammes sur
                 mobile, « Modifier » et « Supprimer » ne se distinguaient que
                 par la couleur, avec un `title=` qu'aucun doigt n'ouvre. --}}
            <x-list-panel>
                @foreach ($services as $service)
                    <x-list-row class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <div class="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-secondary-container/40">
                                <x-service-thumb :service="$service" size="text-xl" />
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                    <a href="{{ route('services.show', $service) }}" class="font-medium text-on-surface hover:text-primary">
                                        {{ $service->title }}
                                    </a>
                                    @if ($service->status !== \App\Models\Service::STATUS_ACTIVE)
                                        <x-status-badge :status="$service->status" />
                                    @endif
                                    @if (! $service->is_available)
                                        <x-status-badge status="inactive" />
                                    @endif
                                </div>
                                <p class="mt-0.5 text-sm text-on-surface-variant">
                                    {{ $service->category?->name }}@if ($service->city) · {{ $service->city }} @endif
                                </p>
                            </div>
                        </div>

                        {{-- Groupées et rangées à droite : `ml-auto` les
                             envoyait aux deux bords opposés de l'écran, où
                             elles ne se lisaient plus comme une paire. --}}
                        <div class="flex shrink-0 items-center justify-end gap-4 sm:pt-0.5">
                            <a href="{{ route('provider.services.edit', $service) }}" class="text-sm font-medium text-primary hover:text-primary-container">
                                Modifier
                            </a>
                            <form action="{{ route('provider.services.destroy', $service) }}" method="POST"
                                  onsubmit="return confirm('Supprimer « {{ $service->title }} » ? Cette action est définitive.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-error hover:opacity-80">
                                    Supprimer
                                </button>
                            </form>
                        </div>
                    </x-list-row>
                @endforeach
            </x-list-panel>

            <div class="mt-6">{{ $services->links() }}</div>
        @endif
    </div>
</x-app-layout>
