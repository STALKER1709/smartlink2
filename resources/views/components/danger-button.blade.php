<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-error border border-transparent rounded-full font-button-text text-button-text text-on-error hover:opacity-90 active:scale-95 focus:outline-none focus:ring-2 focus:ring-error focus:ring-offset-2 transition-all duration-150']) }}>
    {{ $slot }}
</button>
