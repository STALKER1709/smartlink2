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
                    this.history.push({ role: 'assistant', content: data.reply ?? '{{ __('ui.assistant.error') }}' });
                })
                .catch(() => {
                    this.history.push({ role: 'assistant', content: '{{ __('ui.assistant.error') }}' });
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
    class="fixed bottom-4 right-4 z-50"
>
    <!-- Toggle Button -->
    <button
        @click="open = ! open"
        class="flex items-center justify-center h-14 w-14 rounded-full bg-indigo-600 text-white shadow-lg hover:bg-indigo-700 transition focus:outline-none"
        aria-label="{{ __('ui.assistant.title') }}"
    >
        <svg x-show="! open" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.97-4.03 9-9 9-1.6 0-3.1-.41-4.4-1.13L3 21l1.18-3.54A8.96 8.96 0 013 12c0-4.97 4.03-9 9-9s9 4.03 9 9z" />
        </svg>
        <svg x-show="open" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
    </button>

    <!-- Chat Panel -->
    <div
        x-show="open"
        x-cloak
        x-transition
        class="absolute bottom-16 right-0 w-80 sm:w-96 bg-white rounded-lg shadow-xl border border-gray-200 flex flex-col overflow-hidden"
        style="height: 28rem;"
    >
        <div class="bg-indigo-600 text-white px-4 py-3">
            <h3 class="font-semibold">{{ __('ui.assistant.title') }}</h3>
            <p class="text-xs text-indigo-100">{{ __('ui.assistant.subtitle') }}</p>
        </div>

        <div x-ref="messages" class="flex-1 overflow-y-auto p-3 space-y-2 bg-gray-50">
            <template x-if="history.length === 0">
                <p class="text-sm text-gray-400 text-center mt-4">
                    {{ __('ui.assistant.greeting') }}
                </p>
            </template>

            <template x-for="(entry, index) in history" :key="index">
                <div :class="entry.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="entry.role === 'user' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-800 border border-gray-200'"
                        class="rounded-lg px-3 py-2 text-sm max-w-[85%] whitespace-pre-wrap"
                        x-text="entry.content"
                    ></div>
                </div>
            </template>

            <div x-show="sending" x-cloak class="flex justify-start">
                <div class="bg-white text-gray-400 border border-gray-200 rounded-lg px-3 py-2 text-sm">
                    {{ __('ui.assistant.typing') }}
                </div>
            </div>
        </div>

        <form @submit.prevent="send" class="border-t border-gray-200 p-2 flex gap-2">
            <input
                type="text"
                x-model="message"
                :disabled="sending"
                placeholder="{{ __('ui.assistant.placeholder') }}"
                class="flex-1 rounded-md border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            >
            <button
                type="submit"
                :disabled="sending || ! message.trim()"
                class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-white hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9-7-9-7v6.5L3 12l9 1.5V19z" />
                </svg>
            </button>
        </form>
    </div>
</div>
