<?php

namespace Tests\Feature;

use App\Models\BillingRecord;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommercialOperationsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::where('email', 'admin@codescaletech.test')->firstOrFail();
    }

    public function test_commercial_seeder_creates_contracts_billing_invoices_and_payments(): void
    {
        $this->seed();

        $this->assertSame(3, Contract::count());
        $this->assertSame(5, BillingRecord::count());
        $this->assertSame(4, Invoice::count());
        $this->assertSame(2, InvoicePayment::count());
        $this->assertDatabaseHas('contracts', ['code' => 'CON-AURECON-2026', 'status' => 'renewing']);
        $this->assertDatabaseHas('invoices', ['number' => 'INV-2026-0003', 'status' => 'overdue']);
    }

    public function test_admin_can_list_contracts(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/contracts')
            ->assertOk()
            ->assertJsonFragment(['code' => 'CON-NALA-2026'])
            ->assertJsonPath('options.statuses.0.value', 'draft')
            ->assertJsonPath('meta.total', 3);
    }

    public function test_admin_can_create_contract(): void
    {
        $this->seed();

        $client = Client::where('code', 'AURECON-PMO')->firstOrFail();
        $package = Package::where('code', 'SCALELENS-ENTERPRISE')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/contracts', [
                'client_id' => $client->id,
                'package_id' => $package->id,
                'code' => 'CON-AURECON-2027',
                'name' => 'ScaleLens Enterprise renewal - Aurecon PMO',
                'total_value' => 780000,
                'monthly_value' => 65000,
                'starts_on' => '2026-08-01',
                'renewal_date' => '2027-07-31',
                'status' => 'draft',
            ])
            ->assertCreated()
            ->assertJsonFragment(['code' => 'CON-AURECON-2027']);

        $this->assertDatabaseHas('contracts', ['code' => 'CON-AURECON-2027']);
    }

    public function test_admin_can_list_billing_records(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/billing-records')
            ->assertOk()
            ->assertJsonPath('options.cadences.0.value', 'once_off')
            ->assertJsonPath('meta.total', 5);
    }

    public function test_admin_can_create_billing_record(): void
    {
        $this->seed();

        $client = Client::where('code', 'KOPANO-CONSULTING')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/billing-records', [
                'client_id' => $client->id,
                'type' => 'enhancement',
                'cadence' => 'once_off',
                'description' => 'Custom report build-out',
                'amount' => 45000,
                'starts_on' => '2026-07-01',
                'is_active' => true,
            ])
            ->assertCreated()
            ->assertJsonFragment(['description' => 'Custom report build-out']);

        $this->assertDatabaseHas('billing_records', ['description' => 'Custom report build-out', 'cadence' => 'once_off']);
    }

    public function test_admin_can_list_invoices(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/invoices')
            ->assertOk()
            ->assertJsonFragment(['number' => 'INV-2026-0003'])
            ->assertJsonPath('options.statuses.0.value', 'draft')
            ->assertJsonPath('meta.total', 4);
    }

    public function test_admin_can_create_invoice(): void
    {
        $this->seed();

        $client = Client::where('code', 'KOPANO-CONSULTING')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson('/api/v1/invoices', [
                'client_id' => $client->id,
                'number' => 'INV-2026-0099',
                'status' => 'sent',
                'amount' => 12000,
                'issued_on' => '2026-06-18',
                'due_on' => '2026-07-02',
            ])
            ->assertCreated()
            ->assertJsonFragment(['number' => 'INV-2026-0099', 'outstanding' => '12000.00']);

        $this->assertDatabaseHas('invoices', ['number' => 'INV-2026-0099', 'amount_paid' => 0]);
    }

    public function test_recording_payments_reduces_outstanding_and_settles_invoice(): void
    {
        $this->seed();

        $invoice = Invoice::where('number', 'INV-2026-0004')->firstOrFail();

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'amount' => 10000,
                'method' => 'eft',
                'paid_on' => '2026-06-18',
            ])
            ->assertCreated();

        $invoice->refresh();
        $this->assertSame('14000.00', (string) $invoice->outstanding);
        $this->assertSame('partially_paid', $invoice->status->value);

        $this->actingAs($this->admin(), 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'amount' => 14000,
                'method' => 'eft',
                'paid_on' => '2026-06-20',
            ])
            ->assertCreated();

        $invoice->refresh();
        $this->assertSame('0.00', (string) $invoice->outstanding);
        $this->assertSame('paid', $invoice->status->value);
        $this->assertSame(2, $invoice->payments()->count());
    }

    public function test_overdue_invoice_is_flagged(): void
    {
        $this->seed();

        $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/invoices?status=overdue')
            ->assertOk()
            ->assertJsonFragment(['number' => 'INV-2026-0003', 'is_overdue' => true]);
    }

    public function test_finance_dashboard_reports_aggregates(): void
    {
        $this->seed();

        $response = $this->actingAs($this->admin(), 'sanctum')
            ->getJson('/api/v1/dashboard/finance')
            ->assertOk();

        // MRR = 35000 + 60000 + 6000 monthly + 24000 annual / 12 (2000) = 103000
        $response->assertJsonPath('data.mrr', 103000)
            ->assertJsonPath('data.arr', 1236000)
            ->assertJsonPath('data.overdue_count', 1);

        $this->assertEquals(66000, $response->json('data.overdue_total'));
    }

    public function test_user_without_payment_permission_cannot_record_payment(): void
    {
        $this->seed();

        $invoice = Invoice::where('number', 'INV-2026-0004')->firstOrFail();
        $sales = User::where('email', 'sales@codescaletech.test')->firstOrFail();

        $this->actingAs($sales, 'sanctum')
            ->postJson("/api/v1/invoices/{$invoice->id}/payments", [
                'amount' => 1000,
                'method' => 'eft',
                'paid_on' => '2026-06-18',
            ])
            ->assertForbidden();
    }

    public function test_user_without_contract_create_permission_cannot_create_contract(): void
    {
        $this->seed();

        $client = Client::where('code', 'NALA-PROJECTS')->firstOrFail();
        $support = User::where('email', 'support@codescaletech.test')->firstOrFail();

        $this->actingAs($support, 'sanctum')
            ->postJson('/api/v1/contracts', [
                'client_id' => $client->id,
                'code' => 'CON-FORBIDDEN',
                'name' => 'Should not be created',
                'status' => 'draft',
            ])
            ->assertForbidden();
    }
}
