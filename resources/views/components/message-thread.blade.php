@props(['conversation', 'plein' => false])

{{--
    Le fil de discussion, d'après les maquettes : bulles à 85 % de la largeur,
    coin rentrant du côté de l'expéditeur, et l'heure **sous** la bulle plutôt
    que dedans — à l'intérieur, elle mangeait la dernière ligne du message.

    Un séparateur de date ouvre chaque journée, et une ligne d'ouverture
    rappelle de quelle demande on parle : un fil retrouvé trois semaines plus
    tard ne dit sinon plus à quoi il se rapporte.

    `plein` sur l'écran de conversation : les messages suivent le fil de la
    page et la zone de saisie colle au bas de l'écran, au-dessus de la barre
    d'onglets. Une hauteur en `dvh` devrait deviner celle de l'en-tête, qui
    change avec le bouton d'action — un premier essai en `calc(100dvh - 16rem)`
    posait la saisie sous la barre d'onglets, mesure à l'appui.
--}}
<div @class([
    'flex flex-col overflow-hidden border-outline-variant bg-surface-container-lowest',
    '-mx-margin-mobile border-y md:mx-0 md:rounded-xl md:border' => $plein,
    'rounded-xl border' => ! $plein,
])>
    <div @class([
        'flex flex-1 flex-col gap-4 bg-surface p-4',
        'min-h-[50vh]' => $plein,
        'max-h-96 overflow-y-auto' => ! $plein,
    ])>
        @if ($conversation->request?->service)
            <p class="text-center font-body-md text-label-md text-on-surface-variant">
                La discussion a été ouverte au sujet de « {{ $conversation->request->service->title }} ».
            </p>
        @endif

        @php $jourPrecedent = null; @endphp

        @forelse ($conversation->messages as $message)
            @php
                $jour = $message->created_at->toDateString();
                $moi = $message->sender_id === Auth::id();
            @endphp

            @if ($jour !== $jourPrecedent)
                <div class="my-2 flex items-center justify-center">
                    <span class="rounded-full border border-outline-variant bg-surface-container-low px-3 py-1 font-label-sm text-label-sm text-on-surface-variant">
                        {{ $message->created_at->isToday()
                            ? "Aujourd'hui"
                            : ($message->created_at->isYesterday() ? 'Hier' : $message->created_at->translatedFormat('j F Y')) }}
                    </span>
                </div>
                @php $jourPrecedent = $jour; @endphp
            @endif

            <div @class([
                'flex max-w-[85%] flex-col gap-1',
                'items-end self-end' => $moi,
                'items-start self-start' => ! $moi,
            ])>
                <div @class([
                    'rounded-2xl px-4 py-3 shadow-elevation-1',
                    'rounded-tr-sm bg-primary text-on-primary' => $moi,
                    'rounded-tl-sm border border-outline-variant bg-surface text-on-surface' => ! $moi,
                ])>
                    <p class="whitespace-pre-line font-body-md">{{ $message->body }}</p>
                </div>
                <span @class([
                    'font-label-numeric text-label-sm text-on-surface-variant',
                    'mr-1' => $moi,
                    'ml-1' => ! $moi,
                ])>{{ $message->created_at->format('H:i') }}</span>
            </div>
        @empty
            <p class="py-6 text-center text-label-md text-on-surface-variant">
                {{ __("Aucun message pour le moment. Écrivez le premier.") }}
            </p>
        @endforelse
    </div>

    <form action="{{ route('conversations.messages.store', $conversation) }}" method="POST"
          @class([
              'flex items-end gap-2 border-t border-outline-variant bg-surface p-3',
              'sticky bottom-[4.75rem] md:bottom-0' => $plein,
          ])>
        @csrf
        <div class="flex min-h-[48px] flex-1 items-end overflow-hidden rounded-xl border border-outline-variant shadow-elevation-1 bg-surface-container-low transition-all focus-within:border-primary focus-within:ring-1 focus-within:ring-primary">
            <label for="body-{{ $conversation->id }}" class="sr-only">{{ __("Votre message") }}</label>
            <textarea
                id="body-{{ $conversation->id }}"
                name="body"
                rows="1"
                required
                maxlength="5000"
                placeholder="{{ __('Tapez votre message…') }}"
                oninput="this.style.height = ''; this.style.height = this.scrollHeight + 'px'"
                class="max-h-32 w-full resize-none border-none bg-transparent px-4 py-3 font-body-md text-on-surface placeholder:text-on-surface-variant focus:ring-0"
            ></textarea>
        </div>
        <button type="submit"
                class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary text-on-primary transition-colors hover:bg-primary-container"
                aria-label="{{ __('Envoyer') }}">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;" aria-hidden="true">send</span>
        </button>
    </form>
</div>
