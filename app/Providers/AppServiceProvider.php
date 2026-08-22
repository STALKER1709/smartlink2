<?php

namespace App\Providers;

use Anthropic\Client;
use App\Contracts\ChatbotProvider;
use App\Services\Chatbot\ClaudeChatbotProvider;
use App\Services\Chatbot\RuleBasedChatbotProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            Client::class,
            fn () => new Client(apiKey: (string) config('ai.api_key')),
        );

        // Le pilote choisit l'implémentation ; AiGate décide ensuite, appel par
        // appel, si le message part effectivement vers l'IA.
        $this->app->bind(ChatbotProvider::class, fn ($app) => config('ai.driver') === 'claude'
            ? $app->make(ClaudeChatbotProvider::class)
            : $app->make(RuleBasedChatbotProvider::class));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
