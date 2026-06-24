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

    public function test_payment_keyword_returns_no_online_payment_reply(): void
    {
        $reply = $this->provider->respond('Quel est le prix et comment payer ?');

        $this->assertStringContainsString('ne gère aucun paiement en ligne', $reply);
    }

    public function test_unknown_message_returns_fallback_reply(): void
    {
        $reply = $this->provider->respond('xyzzy plugh qwerty');

        $this->assertStringContainsString("Je n'ai pas bien compris", $reply);
    }
}
