@props(['conversation', 'plein' => false])

{{--
    Le fil de discussion.

    `plein` sur l'écran de conversation : les messages suivent le fil de la
    page et la zone de saisie **colle au bas de l'écran**, au-dessus de la
    barre d'onglets. La boîte de 26 rem flottait au milieu de la page, la
    saisie loin du pouce, et le pied de page défilait sous une messagerie.

    Le collage plutôt qu'une hauteur en `dvh` : une hauteur calculée doit
    deviner celle de l'en-tête, qui change avec le bouton d'action — un
    premier essai en `calc(100dvh - 16rem)` posait la zone de saisie sous la
    barre d'onglets, mesure à l'appui.

    Sans `plein`, il s'insère dans une autre page (la fiche d'une demande) et
    prend la hauteur de son contenu, dans une limite.

    Le nom de l'expéditeur ne se répète plus dans chaque bulle : à deux, le
    côté et la couleur le disent déjà, et le titre de la page nomme l'autre.
--}}
<div @class([
    'flex flex-col overflow-hidden border-outline-variant bg-surface-container-lowest',
    // Sur l'écran dédié : à fond perdu sur mobile, encadré à partir de md.
    '-mx-margin-mobile border-y md:mx-0 md:rounded-xl md:border' => $plein,
    'rounded-xl border' => ! $plein,
])>
    <div @class([
        'flex-1 space-y-3 bg-surface p-4',
        'min-h-[50vh]' => $plein,
        'max-h-96 overflow-y-auto' => ! $plein,
    ])>
        @forelse ($conversation->messages as $message)
            @php $moi = $message->sender_id === Auth::id(); @endphp
            <div class="flex {{ $moi ? 'justify-end' : 'justify-start' }}">
                <div @class([
                    'max-w-[80%] rounded-2xl px-4 py-2.5 text-sm',
                    'rounded-tr-sm bg-primary text-on-primary' => $moi,
                    'rounded-tl-sm border border-outline-variant bg-surface-container-lowest text-on-surface' => ! $moi,
                ])>
                    <p class="whitespace-pre-line">{{ $message->body }}</p>
                    <p @class([
                        'mt-1 font-label-numeric text-[10px]',
                        'text-on-primary/70' => $moi,
                        'text-on-surface-variant' => ! $moi,
                    ])>{{ $message->created_at->format('d/m H:i') }}</p>
                </div>
            </div>
        @empty
            {{-- Vingt-six rem de vide pour dire « rien » : le bloc prend la
                 place de la phrase, et rien de plus. --}}
            <p class="py-6 text-center text-sm text-on-surface-variant">
                Aucun message pour le moment. Écrivez le premier.
            </p>
        @endforelse
    </div>

    <form action="{{ route('conversations.messages.store', $conversation) }}" method="POST"
          @class([
              'flex gap-2 border-t border-outline-variant bg-surface-container-lowest p-3',
              'sticky bottom-[4.75rem] md:bottom-0' => $plein,
          ])>
        @csrf
        <input
            type="text"
            name="body"
            required
            maxlength="5000"
            placeholder="Écrivez votre message…"
            aria-label="Votre message"
            class="flex-1 rounded-full border-outline-variant text-sm focus:border-primary focus:ring-primary"
        >
        <button type="submit"
                class="flex shrink-0 items-center justify-center rounded-full bg-primary px-4 py-2 font-button-text text-sm font-semibold text-on-primary transition-colors hover:bg-primary-container"
                aria-label="Envoyer">
            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">send</span>
        </button>
    </form>
</div>
