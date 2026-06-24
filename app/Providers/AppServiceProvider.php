<?php

namespace App\Providers;

use App\Contracts\ChatbotProvider;
use App\Services\Chatbot\RuleBasedChatbotProvider;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ChatbotProvider::class, RuleBasedChatbotProvider::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
