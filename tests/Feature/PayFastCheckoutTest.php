<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayFastCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private function financeUser(): User
    {
        return User::where('email', 'finance@codescaletech.test')->firstOrFail();
    }

    private function supportUser(): User
    {
        return User::where('email', 'support@codescaletech.test')->firstOrFail();
    }

    private function payFastConfig(): void
    {
        config([
            'payfast.merchant_id' => '10000100',
            'payfast.merchant_key' => '46f0cd694581a',
            'payfast.pass_phrase' => '',
        ]);
    }

    public function test_unauthenticated_checkout_initiation_is_rejected(): void
    {
        $this->seed();

        $this->postJson('/api/v1/payments/payfast/initiate', [
            'amount' => '499.00',
            'item_name' => 'Test item',
        ])->assertUnauthorized();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_unauthenticated_request_without_an_accept_header_gets_a_clean_401_not_a_500(): void
    {
        $this->seed();

        // Regression: an SPA with no server-rendered login route otherwise
        // 500s here ("Route [login] not defined") for any guest request
        // that doesn't explicitly ask for JSON — see bootstrap/app.php.
        $this->post('/api/v1/payments/payfast/initiate', [
            'amount' => '499.00',
            'item_name' => 'Test item',
        ])->assertUnauthorized();
    }

    public function test_user_without_payments_create_permission_is_forbidden(): void
    {
        $this->seed();

        $this->actingAs($this->supportUser(), 'sanctum')
            ->postJson('/api/v1/payments/payfast/initiate', [
                'amount' => '499.00',
                'item_name' => 'Test item',
            ])->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_authorized_user_can_initiate_a_one_time_payment(): void
    {
        $this->seed();
        $this->payFastConfig();

        $response = $this->actingAs($this->financeUser(), 'sanctum')
            ->post('/api/v1/payments/payfast/initiate', [
                'amount' => '499.00',
                'item_name' => 'Test item',
                'm_payment_id' => 'pf-owned-by-finance',
            ])->assertOk();

        $response->assertSee('name="merchant_id" value="10000100"', false);

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'pf-owned-by-finance',
            'user_id' => $this->financeUser()->id,
            'status' => 'initiated',
        ]);
    }

    public function test_reusing_another_users_reference_is_rejected(): void
    {
        $this->seed();
        $this->payFastConfig();

        Payment::query()->create([
            'user_id' => $this->supportUser()->id,
            'merchant_payment_id' => 'pf-belongs-to-support',
            'provider' => 'payfast',
            'item_name' => 'Someone else\'s payment',
            'amount_requested' => 100,
            'status' => 'initiated',
            'initiated_at' => now(),
        ]);

        $this->actingAs($this->financeUser(), 'sanctum')
            ->postJson('/api/v1/payments/payfast/initiate', [
                'amount' => '1.00',
                'item_name' => 'Hijack attempt',
                'm_payment_id' => 'pf-belongs-to-support',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('m_payment_id');

        $this->assertDatabaseHas('payments', [
            'merchant_payment_id' => 'pf-belongs-to-support',
            'amount_requested' => 100,
        ]);
    }

    public function test_reinitiating_an_already_paid_reference_is_rejected(): void
    {
        $this->seed();
        $this->payFastConfig();

        Payment::query()->create([
            'user_id' => $this->financeUser()->id,
            'merchant_payment_id' => 'pf-already-paid',
            'provider' => 'payfast',
            'item_name' => 'Finalized payment',
            'amount_requested' => 100,
            'status' => 'paid',
            'initiated_at' => now(),
            'paid_at' => now(),
        ]);

        $this->actingAs($this->financeUser(), 'sanctum')
            ->postJson('/api/v1/payments/payfast/initiate', [
                'amount' => '1.00',
                'item_name' => 'Re-open attempt',
                'm_payment_id' => 'pf-already-paid',
            ])->assertUnprocessable()
            ->assertJsonValidationErrors('m_payment_id');

        $this->assertDatabaseHas('payments', ['merchant_payment_id' => 'pf-already-paid', 'amount_requested' => 100, 'status' => 'paid']);
    }

    public function test_a_valid_itn_marks_the_payment_paid(): void
    {
        $this->seed();
        $this->payFastConfig();

        $payment = Payment::query()->create([
            'user_id' => $this->financeUser()->id,
            'merchant_payment_id' => 'pf-itn-test',
            'provider' => 'payfast',
            'item_name' => 'ITN test item',
            'amount_requested' => 499,
            'status' => 'initiated',
            'initiated_at' => now(),
        ]);

        $payload = [
            'm_payment_id' => 'pf-itn-test',
            'pf_payment_id' => 'pf-remote-id',
            'payment_status' => 'COMPLETE',
            'amount_gross' => '499.00',
            'merchant_id' => '10000100',
        ];
        $rawBody = http_build_query($payload);
        $signature = md5($rawBody);

        $this->call('POST', '/payments/payfast/itn', array_merge($payload, ['signature' => $signature]), [], [], [
            'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        ], $rawBody.'&signature='.$signature)
            ->assertOk();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'payfast_payment_id' => 'pf-remote-id',
        ]);
    }

    public function test_browser_return_does_not_mutate_payment_state(): void
    {
        $this->seed();

        $payment = Payment::query()->create([
            'user_id' => $this->financeUser()->id,
            'merchant_payment_id' => 'pf-return-tamper',
            'provider' => 'payfast',
            'item_name' => 'Tamper attempt',
            'amount_requested' => 499,
            'status' => 'initiated',
            'initiated_at' => now(),
        ]);

        $this->get('/payments/payfast/return?m_payment_id=pf-return-tamper')->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'initiated']);
    }

    public function test_browser_cancel_does_not_mutate_payment_state(): void
    {
        $this->seed();

        $payment = Payment::query()->create([
            'user_id' => $this->financeUser()->id,
            'merchant_payment_id' => 'pf-cancel-tamper',
            'provider' => 'payfast',
            'item_name' => 'Tamper attempt',
            'amount_requested' => 499,
            'status' => 'initiated',
            'initiated_at' => now(),
        ]);

        $this->get('/payments/payfast/cancel?m_payment_id=pf-cancel-tamper')->assertOk();

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'initiated']);
    }

    public function test_itn_with_invalid_signature_is_rejected(): void
    {
        $this->seed();
        $this->payFastConfig();

        $payment = Payment::query()->create([
            'user_id' => $this->financeUser()->id,
            'merchant_payment_id' => 'pf-bad-sig',
            'provider' => 'payfast',
            'item_name' => 'Tamper attempt',
            'amount_requested' => 499,
            'status' => 'initiated',
            'initiated_at' => now(),
        ]);

        $this->postJson('/payments/payfast/itn', [
            'm_payment_id' => 'pf-bad-sig',
            'payment_status' => 'COMPLETE',
            'amount_gross' => '499.00',
            'merchant_id' => '10000100',
            'signature' => 'not-a-real-signature',
        ])->assertStatus(400);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'status' => 'initiated']);
    }
}
