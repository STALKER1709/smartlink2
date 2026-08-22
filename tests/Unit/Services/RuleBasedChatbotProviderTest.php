<?php

namespace Tests\Unit\Services;

use App\Services\Chatbot\RuleBasedChatbotProvider;
use PHPUnit\Framework\TestCase;

class RuleBasedChatbotProviderTest extends TestCase
{
    private RuleBasedChatbotProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new RuleBasedChatbotProvider;
    }

    public function test_greeting_keyword_returns_greeting_reply(): void
    {
        $reply = $this->provider->respond('Bonjour !');

        $this->assertStringContainsString('assistant SmartLink', $reply);
    }

    public function test_matching_is_case_and_accent_insensitive(): void
    {
        $reply = $this->provider->respond('BONJOUR à tous');

        $this->assertStringContainsString('assistant SmartLink', $reply);
    }

    public function test_payment_keyword_explains_smartlink_takes_no_cut(): void
    {
        $reply = $this->provider->respond('Quel est le prix et comment payer ?');

        $this->assertStringContainsString('ne prélève rien sur vos prestations', $reply);
        $this->assertStringContainsString('hors plateforme', $reply);
    }

    public function test_subscription_keyword_describes_the_provider_plans(): void
    {
        $reply = $this->provider->respond('Comment fonctionne l\'abonnement ?');

        $this->assertStringContainsString('30 jours d\'essai gratuit', $reply);
        $this->assertStringContainsString('2 500 FCFA', $reply);
        $this->assertStringContainsString('7 500 FCFA', $reply);
    }

    public function test_unknown_message_returns_fallback_reply(): void
    {
        $reply = $this->provider->respond('xyzzy plugh qwerty');

        $this->assertStringContainsString("Je n'ai pas bien compris", $reply);
    }
}
