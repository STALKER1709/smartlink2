<?php

namespace Tests\Feature\Ai;

use App\Contracts\ChatbotProvider;
use App\Models\Plan;
use App\Models\ProviderProfile;
use App\Models\ServiceCategory;
use App\Services\Ai\SmartLinkContext;
use App\Services\Chatbot\ClaudeChatbotProvider;
use App\Services\Chatbot\RuleBasedChatbotProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartLinkContextTest extends TestCase
{
    use RefreshDatabase;

    private SmartLinkContext $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = $this->app->make(SmartLinkContext::class);
        $this->context->forget();
    }

    public function test_the_context_states_the_real_economic_model(): void
    {
        $prompt = $this->context->systemPrompt();

        // C'est exactement ce que l'ancien chatbot affirmait à tort : le
        // contexte doit énoncer le modèle réel, sinon l'IA propage l'erreur.
        $this->assertStringContainsString('ne prélève RIEN', $prompt);
        $this->assertStringContainsString('hors de la plateforme', $prompt);
        $this->assertStringContainsString('Seuls les prestataires paient', $prompt);
        $this->assertStringContainsString('aucun prélèvement automatique', $prompt);
    }

    public function test_the_context_carries_the_real_plans_and_prices(): void
    {
        Plan::factory()->create();
        Plan::factory()->pro()->create();
        $this->context->forget();

        $prompt = $this->context->systemPrompt();

        $this->assertStringContainsString('2 500 FCFA', $prompt);
        $this->assertStringContainsString('7 500 FCFA', $prompt);
        $this->assertStringContainsString('20 demandes lisibles par mois', $prompt);
        $this->assertStringContainsString('services illimités', $prompt);
    }

    public function test_the_context_carries_the_real_catalogue(): void
    {
        ServiceCategory::factory()->create(['name' => 'Soudure & ferronnerie']);
        ProviderProfile::factory()->create(['city' => 'Bonabéri']);
        $this->context->forget();

        $prompt = $this->context->systemPrompt();

        $this->assertStringContainsString('Soudure & ferronnerie', $prompt);
        $this->assertStringContainsString('Bonabéri', $prompt);
    }

    public function test_a_hidden_provider_city_is_not_advertised(): void
    {
        ProviderProfile::factory()->unlisted()->create(['city' => 'Villefantome']);
        $this->context->forget();

        $this->assertStringNotContainsString('Villefantome', $this->context->systemPrompt());
    }

    public function test_the_context_forbids_claiming_access_to_personal_data(): void
    {
        $prompt = $this->context->systemPrompt();

        $this->assertStringContainsString('aucune donnée personnelle', $prompt);
        $this->assertStringContainsString('N\'invente jamais', $prompt);
    }

    public function test_changing_a_plan_rebuilds_the_context(): void
    {
        $plan = Plan::factory()->create();
        $this->context->forget();
        $this->assertStringContainsString('2 500 FCFA', $this->context->systemPrompt());

        $plan->update(['price_xaf' => 3000]);

        $this->assertStringContainsString('3 000 FCFA', $this->context->systemPrompt());
        $this->assertStringNotContainsString('2 500 FCFA', $this->context->systemPrompt());
    }

    public function test_the_driver_selects_which_provider_the_container_builds(): void
    {
        config()->set('ai.driver', 'rule');
        $this->assertInstanceOf(RuleBasedChatbotProvider::class, $this->app->make(ChatbotProvider::class));

        config()->set('ai.driver', 'claude');
        config()->set('ai.api_key', 'cle-de-test');
        $this->assertInstanceOf(ClaudeChatbotProvider::class, $this->app->make(ChatbotProvider::class));
    }
}
