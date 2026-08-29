@props(['value' => null])

{{--
    La recherche en langage naturel — l'entrée principale du produit.

    Elle existait en deux exemplaires, à l'accueil et sur la liste des
    services : mêmes champs, même route, même texte d'aide, à quelques classes
    près. Deux copies d'un même contrôle finissent toujours par diverger — le
    champ de l'accueil avait déjà pris l'alignement centré de son bloc parent,
    pas celui de la liste.

    Une variante `hero` a existé, pour la version posée sur le bandeau vert de
    l'accueil : ombre au lieu de bordure, texte d'aide en blanc. Le bandeau vert
    est parti avec la maquette de l'accueil, et la variante est partie avec lui —
    elle ne portait plus que deux propriétés que la charte interdit.
--}}
<form action="{{ route('services.index') }}" method="GET" {{ $attributes->only('class') }}>
    <div class="flex flex-col gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest p-2 sm:flex-row sm:items-center">
        <label for="q" class="sr-only">{{ __('ui.search.natural_label') }}</label>

        <div class="flex flex-1 items-center gap-2 px-3">
            <span class="material-symbols-outlined text-on-surface-variant" aria-hidden="true">search</span>
            {{-- `text-left` explicite : le bloc du hero est centré et le champ
                 en héritait — le curseur démarrait au milieu et le texte
                 poussait des deux côtés à mesure qu'on écrivait. --}}
            <input
                type="text"
                id="q"
                name="q"
                maxlength="300"
                value="{{ $value }}"
                placeholder="{{ __('ui.search.natural_placeholder') }}"
                class="w-full border-0 bg-transparent py-2.5 text-left text-on-surface placeholder:text-on-surface-variant/70 focus:ring-0"
            >
        </div>

        <button type="submit" class="shrink-0 rounded-xl bg-primary px-6 py-3 font-button-text text-button-text text-on-primary transition-colors hover:bg-primary-container">
            {{ __('ui.search.natural_submit') }}
        </button>
    </div>

    <p class="mt-2 px-1 text-xs text-on-surface-variant">{{ __('ui.search.natural_hint') }}</p>
</form>
