<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-primary border border-transparent rounded-full font-button-text text-button-text text-on-primary hover:bg-primary-container active:scale-95 focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-50 disabled:pointer-events-none transition-all duration-150']) }}>
    {{ $slot }}
</button>
