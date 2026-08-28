<x-app-layout titre="Litiges" :indexable="false">
    <x-slot name="header">
        <x-page-header title="Litiges" subtitle="Les signalements déposés par les clients et les prestataires." />
    </x-slot>

    <div class="mx-auto w-full max-w-container px-margin-mobile py-8 md:px-margin-desktop">
        @if (session('status'))
            <div class="mb-4 rounded-md border border-outline-variant bg-secondary-container/30 px-4 py-3 text-sm text-on-secondary-container">
                {{ session('status') }}
            </div>
        @endif

        <div class="hide-scrollbar -mx-margin-mobile mb-6 flex gap-3 overflow-x-auto px-margin-mobile pb-2 md:mx-0 md:px-0">
            <a href="{{ route('admin.disputes.index') }}"
               @class([
                   'flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 font-button-text text-button-text transition-colors',
                   'border border-transparent bg-primary-container text-on-primary-container' => ! request('status'),
                   'border border-outline-variant bg-surface text-on-surface-variant hover:bg-surface-container-low' => request('status'),
               ])>
                Tous <span class="font-label-numeric">{{ array_sum($comptes) }}</span>
            </a>
            @foreach (\App\Models\Dispute::statuses() as $valeur => $libelle)
                @continue (($comptes[$valeur] ?? 0) === 0)
                <a href="{{ route('admin.disputes.index', ['status' => $valeur]) }}"
                   @class([
                       'flex shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full px-4 py-2 font-button-text text-button-text transition-colors',
                       'border border-transparent bg-primary-container text-on-primary-container' => request('status') === $valeur,
                       'border border-outline-variant bg-surface text-on-surface-variant hover:bg-surface-container-low' => request('status') !== $valeur,
                   ])>
                    {{ $libelle }} <span class="font-label-numeric">{{ $comptes[$valeur] }}</span>
                </a>
            @endforeach
        </div>

        @if ($disputes->isEmpty())
            <x-empty-state title="Aucun litige." description="Les signalements déposés arrivent ici." />
        @else
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-2">
                @foreach ($disputes as $dispute)
                    <article class="relative overflow-hidden rounded-xl border border-outline-variant bg-surface-container-lowest">
                        <div class="absolute left-0 top-0 h-1 w-full {{ $dispute->isOpen() ? 'bg-error' : 'bg-outline-variant' }}" aria-hidden="true"></div>

                        <div class="p-6">
                            <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded bg-surface-container-high px-2 py-1 font-label-numeric text-xs uppercase tracking-wider text-on-surface">
                                        {{ \App\Models\Dispute::reasonLabel($dispute->reason) }}
                                    </span>
                                    <span class="font-label-numeric text-sm text-on-surface-variant">{{ $dispute->created_at->translatedFormat('d M, H:i') }}</span>
                                </div>
                                <span class="font-label-numeric text-sm text-on-surface-variant">n° {{ $dispute->id }}</span>
                            </div>

                            <p class="font-headline-md text-body-lg text-on-surface">
                                {{ $dispute->request?->service?->title ?? 'Demande directe' }}
                            </p>
                            <p class="mt-1 text-sm text-on-surface-variant">
                                Déposé par {{ $dispute->reporter?->name }}
                                @if ($dispute->request)
                                    · <a href="{{ route('requests.show', $dispute->request) }}" class="text-primary hover:text-primary-container">voir la demande</a>
                                @endif
                            </p>

                            <p class="mt-3 font-body-md text-body-md text-on-surface">{{ $dispute->description }}</p>

                            @if ($dispute->evidence_paths)
                                <div class="mt-4 flex flex-wrap gap-2">
                                    @foreach ($dispute->evidence_paths as $chemin)
                                        <a href="{{ media_url($chemin) }}" target="_blank" rel="noopener"
                                           class="block h-20 w-20 overflow-hidden rounded-lg border border-outline-variant bg-surface-container">
                                            <img src="{{ media_url($chemin) }}" alt="Preuve déposée" class="h-full w-full object-cover" onerror="this.remove()">
                                        </a>
                                    @endforeach
                                </div>
                            @endif

                            @if ($dispute->isOpen())
                                <form action="{{ route('admin.disputes.resolve', $dispute) }}" method="POST" class="mt-6 border-t border-outline-variant pt-4">
                                    @csrf
                                    <label for="resolution-{{ $dispute->id }}" class="mb-2 block text-sm font-medium text-on-surface">Ce qui a été décidé</label>
                                    <textarea id="resolution-{{ $dispute->id }}" name="resolution" rows="2" required maxlength="1000"
                                              placeholder="Le déclarant recevra ce texte."
                                              class="w-full resize-none rounded-lg border border-outline-variant bg-surface-container-low px-3 py-2 text-sm text-on-surface focus:border-primary focus:ring-1 focus:ring-primary"></textarea>
                                    <x-input-error :messages="$errors->get('resolution')" class="mt-2" />

                                    <div class="mt-3 flex flex-wrap justify-end gap-3">
                                        <button type="submit" name="status" value="{{ \App\Models\Dispute::STATUS_REJECTED }}"
                                                class="rounded-full border border-outline px-4 py-2 font-button-text text-button-text text-on-surface-variant transition-colors hover:bg-surface-container-low">
                                            Rejeter
                                        </button>
                                        <button type="submit" name="status" value="{{ \App\Models\Dispute::STATUS_RESOLVED }}"
                                                class="rounded-full bg-primary px-6 py-2 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container">
                                            Résolu
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="mt-4 rounded-lg border border-outline-variant bg-surface-container-low p-3">
                                    <p class="mb-1 font-button-text text-button-text text-on-surface">
                                        {{ \App\Models\Dispute::statuses()[$dispute->status] ?? $dispute->status }}
                                        @if ($dispute->reviewer)
                                            <span class="font-body-md text-sm font-normal text-on-surface-variant">par {{ $dispute->reviewer->name }}</span>
                                        @endif
                                    </p>
                                    <p class="text-sm text-on-surface-variant">{{ $dispute->resolution }}</p>
                                </div>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-6">{{ $disputes->links() }}</div>
        @endif
    </div>
</x-app-layout>
