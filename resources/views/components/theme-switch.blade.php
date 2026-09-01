{{--
    Le choix du schéma de couleurs, sur la même forme que le sélecteur FR/EN
    qu'il accompagne : trois segments dans une pilule, l'actif en plein.

    Trois états et non deux. Un simple bascule clair/sombre oblige à choisir,
    et l'on ne peut plus revenir à « ce que fait mon téléphone » — ce qui
    compte pour qui règle son appareil sur un basculement automatique le soir.

    L'état vit dans `localStorage` et dans l'attribut `data-theme` de la
    racine, posé avant le premier pixel par `partials/theme-head`. Ici on ne
    fait que l'écrire ; c'est là-bas qu'il est lu au chargement.
--}}
<div
    x-data="{
        choix: 'systeme',
        init() {
            try { this.choix = localStorage.getItem('smartlink-theme') || 'systeme'; } catch (e) {}
        },
        poser(valeur) {
            this.choix = valeur;
            try {
                if (valeur === 'systeme') {
                    localStorage.removeItem('smartlink-theme');
                    document.documentElement.removeAttribute('data-theme');
                } else {
                    localStorage.setItem('smartlink-theme', valeur);
                    document.documentElement.setAttribute('data-theme', valeur);
                }
            } catch (e) {}
        },
    }"
    {{ $attributes->merge(['class' => 'flex overflow-hidden rounded-full border border-outline-variant']) }}
    role="group"
    aria-label="{{ __('Apparence') }}"
>
    @foreach ([
        ['valeur' => 'light', 'icone' => 'light_mode', 'libelle' => __('Clair')],
        ['valeur' => 'dark', 'icone' => 'dark_mode', 'libelle' => __('Sombre')],
        ['valeur' => 'systeme', 'icone' => 'brightness_auto', 'libelle' => __('Comme mon appareil')],
    ] as $option)
        <button
            type="button"
            @click="poser('{{ $option['valeur'] }}')"
            :aria-pressed="choix === '{{ $option['valeur'] }}' ? 'true' : 'false'"
            :class="choix === '{{ $option['valeur'] }}'
                ? 'bg-primary text-on-primary'
                : 'text-on-surface-variant hover:bg-surface-container-low'"
            class="inline-flex h-11 min-w-11 items-center justify-center px-3 transition-colors"
            title="{{ $option['libelle'] }}"
        >
            <span class="sr-only">{{ $option['libelle'] }}</span>
            <x-icon :name="$option['icone']" size="sm" />
        </button>
    @endforeach
</div>
