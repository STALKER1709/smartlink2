<div
    x-data="{
        open: false,
        sending: false,
        message: '',
        history: [],
        send() {
            if (! this.message.trim() || this.sending) return;

            const userMessage = this.message.trim();
            this.history.push({ role: 'user', content: userMessage });
            this.message = '';
            this.sending = true;
            this.scrollToBottom();

            fetch('{{ route('chatbot.ask') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({
                    message: userMessage,
                    history: this.history.slice(0, -1),
                }),
            })
                .then(response => response.json())
                .then(data => {
                    this.history.push({ role: 'assistant', content: data.reply ?? @js(__('ui.assistant.error')) });
                })
                .catch(() => {
                    this.history.push({ role: 'assistant', content: @js(__('ui.assistant.error')) });
                })
                .finally(() => {
                    this.sending = false;
                    this.scrollToBottom();
                });
        },
        scrollToBottom() {
            this.$nextTick(() => {
                if (this.$refs.messages) {
                    this.$refs.messages.scrollTop = this.$refs.messages.scrollHeight;
                }
            });
        },
    }"
    {{-- Les fiches posent une barre d'action collée en bas sur mobile ; la
         règle .chat-fab de resources/css/app.css fait remonter la bulle
         au-dessus plutôt que de la laisser la recouvrir. --}}
    class="chat-fab fixed bottom-4 right-4 z-50"
>
    <!-- Toggle Button -->
    <button
        @click="open = ! open"
        class="flex h-14 w-14 items-center justify-center rounded-full border border-outline-variant bg-primary-container text-on-primary transition-colors hover:bg-primary focus:outline-none"
        aria-label="{{ __('ui.assistant.title') }}"
    >
        <span x-show="! open" class="material-symbols-outlined" style="font-size: 28px;">chat</span>
        <span x-show="open" x-cloak class="material-symbols-outlined" style="font-size: 24px;">close</span>
    </button>

    <!-- Chat Panel -->
    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute bottom-16 right-0 w-80 sm:w-96 bg-surface-container-lowest rounded-xl shadow-xl border border-outline-variant flex flex-col overflow-hidden"
        style="height: 28rem;"
    >
        <div class="flex items-center gap-3 bg-primary-container px-4 py-3 text-on-primary">
            <div class="flex size-9 shrink-0 items-center justify-center bg-on-primary/20 rounded-full">
                <span class="material-symbols-outlined text-lg">smart_toy</span>
            </div>
            <div>
                <h3 class="font-headline-sm text-headline-sm leading-tight">{{ __('ui.assistant.title') }}</h3>
                <p class="text-label-md text-on-primary-container">{{ __('ui.assistant.subtitle') }}</p>
            </div>
        </div>

        <div x-ref="messages" class="flex-1 overflow-y-auto p-3 space-y-2 bg-surface">
            <template x-if="history.length === 0">
                <p class="text-label-lg text-on-surface-variant text-center mt-4">
                    {{ __('ui.assistant.greeting') }}
                </p>
            </template>

            <template x-for="(entry, index) in history" :key="index">
                <div :class="entry.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="entry.role === 'user' ? 'rounded-tr-sm bg-primary text-on-primary' : 'rounded-tl-sm bg-surface-container-lowest text-on-surface border border-outline-variant'"
                        class="rounded-2xl px-3 py-2 text-label-lg max-w-[85%] whitespace-pre-wrap shadow-sm"
                        x-text="entry.content"
                    ></div>
                </div>
            </template>

            <div x-show="sending" x-cloak class="flex justify-start">
                <div class="bg-surface-container-lowest text-on-surface-variant border border-outline-variant rounded-2xl rounded-tl-sm px-3 py-2 text-label-lg">
                    {{ __('ui.assistant.typing') }}
                </div>
            </div>
        </div>

        <form @submit.prevent="send" class="border-t border-outline-variant p-2 flex gap-2 bg-surface-container-lowest">
            <input
                type="text"
                x-model="message"
                :disabled="sending"
                placeholder="{{ __('ui.assistant.placeholder') }}"
                class="flex-1 rounded-full border-outline-variant text-label-lg focus:border-primary focus:ring-primary"
            >
            <button
                type="submit"
                :disabled="sending || ! message.trim()"
                class="inline-flex items-center justify-center rounded-full bg-primary px-3 py-2 text-on-primary hover:bg-primary-container transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">send</span>
            </button>
        </form>
    </div>
</div>
