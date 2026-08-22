<?php

namespace Tests\Unit\Payment;

use App\Services\Payment\HrSkills\HrSkillsCore;
use Tests\TestCase;

class HrSkillsCoreTest extends TestCase
{
    /**
     * C'est toute la raison d'être de l'en-tête : une clé tirée au hasard à
     * chaque tentative n'empêcherait aucun double débit.
     */
    public function test_the_idempotency_key_is_the_same_for_the_same_payment(): void
    {
        $first = HrSkillsCore::idempotencyKey('SL-ABC123DEF456');
        $second = HrSkillsCore::idempotencyKey('SL-ABC123DEF456');

        $this->assertSame($first, $second);
        $this->assertTrue(HrSkillsCore::isUuidV4($first), "« {$first} » doit être un UUID v4 valide");
    }

    public function test_two_payments_get_two_different_keys(): void
    {
        $this->assertNotSame(
            HrSkillsCore::idempotencyKey('SL-AAAAAAAAAAAA'),
            HrSkillsCore::idempotencyKey('SL-BBBBBBBBBBBB'),
        );
    }

    public function test_a_reference_that_is_already_a_uuid_is_used_as_is(): void
    {
        $uuid = '3f2504e0-4f89-41d3-9a0c-0305e82c3301';

        $this->assertSame($uuid, HrSkillsCore::idempotencyKey(strtoupper($uuid)));
    }

    public function test_operators_are_normalised_and_checked(): void
    {
        $this->assertSame('orange', HrSkillsCore::normalizeOperator('  ORANGE '));
        $this->assertTrue(HrSkillsCore::isValidOperator('MTN'));
        $this->assertFalse(HrSkillsCore::isValidOperator('camtel'));
    }

    public function test_phone_numbers_get_the_country_code_without_a_plus(): void
    {
        $this->assertSame('237677123456', HrSkillsCore::normalizePhone('677 12 34 56'));
        $this->assertSame('237677123456', HrSkillsCore::normalizePhone('+237 677123456'));
        $this->assertSame('237677123456', HrSkillsCore::normalizePhone('237677123456'));
        $this->assertNull(HrSkillsCore::normalizePhone('12345'));
    }

    public function test_only_success_and_definitive_failures_settle_a_payment(): void
    {
        $this->assertSame('success', HrSkillsCore::mapStatus('SUCCESS'));
        $this->assertSame('failed', HrSkillsCore::mapStatus('FAILED'));
        $this->assertSame('failed', HrSkillsCore::mapStatus('REFUNDED'));
    }

    /**
     * HOLD signifie « en revue anti-blanchiment ». Le traiter comme un succès
     * ouvrirait un abonnement encore refusable.
     */
    public function test_pending_and_hold_settle_nothing(): void
    {
        $this->assertNull(HrSkillsCore::mapStatus('PENDING'));
        $this->assertNull(HrSkillsCore::mapStatus('HOLD'));
        $this->assertNull(HrSkillsCore::mapStatus('quelque chose de neuf'));
    }

    public function test_the_environment_is_read_from_the_key_prefix(): void
    {
        $this->assertSame('test', HrSkillsCore::keyEnvironment('hrsk_pk_test_abc'));
        $this->assertSame('live', HrSkillsCore::keyEnvironment('hrsk_sk_live_abc'));
    }

    /**
     * Une clé illisible n'est jamais traitée comme « production » : partir en
     * live sur une clé qu'on ne sait pas lire est le pire des deux échecs.
     */
    public function test_an_unreadable_key_is_never_taken_for_production(): void
    {
        $this->assertNull(HrSkillsCore::keyEnvironment('cle-sans-marqueur'));
        $this->assertFalse(HrSkillsCore::isTestKey('cle-sans-marqueur'));
        $this->assertFalse(HrSkillsCore::keysAreCoherent('cle-sans-marqueur', 'hrsk_sk_live_abc'));
    }

    public function test_mixing_a_live_key_with_a_test_key_is_refused(): void
    {
        $this->assertTrue(HrSkillsCore::keysAreCoherent('hrsk_pk_live_a', 'hrsk_sk_live_b'));
        $this->assertTrue(HrSkillsCore::keysAreCoherent('hrsk_pk_test_a', 'hrsk_sk_test_b'));
        $this->assertFalse(HrSkillsCore::keysAreCoherent('hrsk_pk_live_a', 'hrsk_sk_test_b'));
    }

    public function test_test_keys_route_through_the_sandbox_prefix(): void
    {
        $base = 'https://api.hrskills-pay.com';

        $this->assertSame($base.'/sandbox', HrSkillsCore::apiRoot($base, 'hrsk_pk_test_a'));
        $this->assertSame($base, HrSkillsCore::apiRoot($base, 'hrsk_pk_live_a'));
    }

    public function test_the_sandbox_prefix_is_never_added_twice(): void
    {
        $this->assertSame(
            'https://api.hrskills-pay.com/sandbox',
            HrSkillsCore::apiRoot('https://api.hrskills-pay.com/sandbox/', 'hrsk_pk_test_a'),
        );
    }

    public function test_a_signature_is_accepted_on_either_header_spelling(): void
    {
        $secret = 'secret-de-rappel';
        $body = '{"data":{"reference":"ref_123"}}';
        $valid = 'sha256='.hash_hmac('sha256', $body, $secret);

        $this->assertTrue(HrSkillsCore::verifySignature($secret, $body, [$valid, null]));
        $this->assertTrue(HrSkillsCore::verifySignature($secret, $body, [null, $valid]));
    }

    public function test_a_forged_or_absent_signature_is_refused(): void
    {
        $body = '{"data":{"reference":"ref_123"}}';

        $this->assertFalse(HrSkillsCore::verifySignature('secret', $body, ['sha256=faux']));
        $this->assertFalse(HrSkillsCore::verifySignature('secret', $body, [null, null]));
        $this->assertFalse(HrSkillsCore::verifySignature('secret', $body, []));
    }

    public function test_a_body_altered_after_signing_is_refused(): void
    {
        $secret = 'secret-de-rappel';
        $signature = 'sha256='.hash_hmac('sha256', '{"amount":2500}', $secret);

        $this->assertFalse(HrSkillsCore::verifySignature($secret, '{"amount":250000}', [$signature]));
    }

    public function test_the_reference_is_found_wherever_the_provider_puts_it(): void
    {
        foreach ([
            ['data' => ['reference' => 'ref_1']],
            ['reference' => 'ref_1'],
            ['data' => ['transaction_reference' => 'ref_1']],
            ['transaction_reference' => 'ref_1'],
        ] as $payload) {
            $read = HrSkillsCore::readWebhookPayload($payload);

            $this->assertNotNull($read, 'structure non reconnue : '.json_encode($payload));
            $this->assertSame('ref_1', $read['reference']);
        }
    }

    public function test_our_own_reference_comes_back_through_the_metadata(): void
    {
        $read = HrSkillsCore::readWebhookPayload([
            'data' => [
                'reference' => 'ref_1',
                'metadata' => ['reference_interne' => 'SL-ABC123DEF456'],
            ],
        ]);

        $this->assertSame('SL-ABC123DEF456', $read['internal_reference']);
    }

    public function test_a_payload_without_any_reference_is_unusable(): void
    {
        $this->assertNull(HrSkillsCore::readWebhookPayload(['data' => ['status' => 'SUCCESS']]));
        $this->assertNull(HrSkillsCore::readWebhookPayload('une chaîne'));
        $this->assertNull(HrSkillsCore::readWebhookPayload(null));
    }

    /**
     * Un corps de rappel transporte un numéro de téléphone et un montant, et
     * un journal se relit et se copie : seules les clés sont restituées.
     */
    public function test_the_diagnostic_shape_never_leaks_values(): void
    {
        $shape = HrSkillsCore::jsonShape([
            'event' => 'payin.completed',
            'data' => ['reference' => 'ref_1', 'phone_number' => '237677123456', 'amount' => 2500],
        ]);

        $this->assertStringContainsString('phone_number', $shape);
        $this->assertStringNotContainsString('237677123456', $shape);
        $this->assertStringNotContainsString('2500', $shape);
        $this->assertStringNotContainsString('payin.completed', $shape);
    }

    public function test_the_body_diagnostic_tells_an_empty_ping_from_a_form_post(): void
    {
        $this->assertStringContainsString('corps VIDE', HrSkillsCore::bodyDiagnostic('', 'application/json'));

        $form = HrSkillsCore::bodyDiagnostic('reference=ref_1&amount=2500', 'application/x-www-form-urlencoded');
        $this->assertStringContainsString('ressemble à un formulaire', $form);
        $this->assertStringContainsString('reference, amount', $form);
        $this->assertStringNotContainsString('ref_1', $form);
    }
}
