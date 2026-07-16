<?php

namespace Database\Seeders;

use App\Enums\BillingCadence;
use App\Enums\BillingRecordType;
use App\Enums\ContractStatus;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\BillingRecord;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CommercialSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::query()->pluck('id', 'code');
        $product = Product::query()->where('code', 'SCALELENS')->value('id');
        $packages = Package::query()->pluck('id', 'code');

        $contracts = [
            ['client_code' => 'NALA-PROJECTS', 'package_code' => 'SCALELENS-GROWTH', 'code' => 'CON-NALA-2026', 'name' => 'ScaleLens Growth - Nala Projects', 'total_value' => 480000, 'monthly_value' => 35000, 'starts_on' => '2026-06-01', 'renewal_date' => '2027-05-31', 'status' => ContractStatus::Active],
            ['client_code' => 'AURECON-PMO', 'package_code' => 'SCALELENS-ENTERPRISE', 'code' => 'CON-AURECON-2026', 'name' => 'ScaleLens Enterprise - Aurecon PMO', 'total_value' => 720000, 'monthly_value' => 60000, 'starts_on' => '2026-05-15', 'renewal_date' => '2026-07-31', 'status' => ContractStatus::Renewing],
            ['client_code' => 'KOPANO-CONSULTING', 'package_code' => 'SCALELENS-STARTER', 'code' => 'CON-KOPANO-2026', 'name' => 'ScaleLens Starter - Kopano Consulting', 'total_value' => 250000, 'monthly_value' => 20000, 'starts_on' => '2026-05-20', 'renewal_date' => '2027-05-19', 'status' => ContractStatus::Active],
        ];

        foreach ($contracts as $contract) {
            $clientCode = $contract['client_code'];
            $packageCode = $contract['package_code'];
            unset($contract['client_code'], $contract['package_code']);

            Contract::updateOrCreate(
                ['code' => $contract['code']],
                [
                    ...$contract,
                    'client_id' => $clients[$clientCode] ?? null,
                    'product_id' => $product,
                    'package_id' => $packages[$packageCode] ?? null,
                    'status' => $contract['status']->value,
                    'notes' => 'Seeded Module 04 commercial contract.',
                ],
            );
        }

        $contractIds = Contract::query()->pluck('id', 'code');

        $billingRecords = [
            ['client_code' => 'NALA-PROJECTS', 'contract_code' => 'CON-NALA-2026', 'type' => BillingRecordType::Implementation, 'cadence' => BillingCadence::OnceOff, 'description' => 'ScaleLens Growth implementation', 'amount' => 360000, 'starts_on' => '2026-06-01'],
            ['client_code' => 'NALA-PROJECTS', 'contract_code' => 'CON-NALA-2026', 'type' => BillingRecordType::Support, 'cadence' => BillingCadence::Monthly, 'description' => 'Priority support retainer', 'amount' => 35000, 'starts_on' => '2026-06-01'],
            ['client_code' => 'AURECON-PMO', 'contract_code' => 'CON-AURECON-2026', 'type' => BillingRecordType::Support, 'cadence' => BillingCadence::Monthly, 'description' => 'Strategic support retainer', 'amount' => 60000, 'starts_on' => '2026-05-15'],
            ['client_code' => 'AURECON-PMO', 'contract_code' => 'CON-AURECON-2026', 'type' => BillingRecordType::Hosting, 'cadence' => BillingCadence::Monthly, 'description' => 'Production hosting', 'amount' => 6000, 'starts_on' => '2026-05-15'],
            ['client_code' => 'KOPANO-CONSULTING', 'contract_code' => 'CON-KOPANO-2026', 'type' => BillingRecordType::Hosting, 'cadence' => BillingCadence::Annual, 'description' => 'Annual hosting & maintenance', 'amount' => 24000, 'starts_on' => '2026-05-20'],
        ];

        foreach ($billingRecords as $record) {
            $clientCode = $record['client_code'];
            $contractCode = $record['contract_code'];
            unset($record['client_code'], $record['contract_code']);

            BillingRecord::updateOrCreate(
                ['client_id' => $clients[$clientCode] ?? null, 'description' => $record['description']],
                [
                    ...$record,
                    'client_id' => $clients[$clientCode] ?? null,
                    'contract_id' => $contractIds[$contractCode] ?? null,
                    'type' => $record['type']->value,
                    'cadence' => $record['cadence']->value,
                    'is_active' => true,
                    'notes' => 'Seeded Module 04 billing commitment.',
                ],
            );
        }

        $invoices = [
            ['client_code' => 'NALA-PROJECTS', 'contract_code' => 'CON-NALA-2026', 'number' => 'INV-2026-0001', 'status' => InvoiceStatus::Paid, 'amount' => 180000, 'amount_paid' => 180000, 'issued_on' => '2026-06-01', 'due_on' => '2026-06-15'],
            ['client_code' => 'NALA-PROJECTS', 'contract_code' => 'CON-NALA-2026', 'number' => 'INV-2026-0002', 'status' => InvoiceStatus::PartiallyPaid, 'amount' => 180000, 'amount_paid' => 90000, 'issued_on' => now()->subDays(15)->toDateString(), 'due_on' => now()->addDays(14)->toDateString()],
            ['client_code' => 'AURECON-PMO', 'contract_code' => 'CON-AURECON-2026', 'number' => 'INV-2026-0003', 'status' => InvoiceStatus::Overdue, 'amount' => 66000, 'amount_paid' => 0, 'issued_on' => now()->subDays(60)->toDateString(), 'due_on' => now()->subDays(30)->toDateString()],
            ['client_code' => 'KOPANO-CONSULTING', 'contract_code' => 'CON-KOPANO-2026', 'number' => 'INV-2026-0004', 'status' => InvoiceStatus::Sent, 'amount' => 24000, 'amount_paid' => 0, 'issued_on' => now()->subDays(5)->toDateString(), 'due_on' => now()->addDays(10)->toDateString()],
        ];

        foreach ($invoices as $invoice) {
            $clientCode = $invoice['client_code'];
            $contractCode = $invoice['contract_code'];
            unset($invoice['client_code'], $invoice['contract_code']);

            $model = Invoice::updateOrCreate(
                ['number' => $invoice['number']],
                [
                    ...$invoice,
                    'client_id' => $clients[$clientCode] ?? null,
                    'contract_id' => $contractIds[$contractCode] ?? null,
                    'status' => $invoice['status']->value,
                    'notes' => 'Seeded Module 04 invoice.',
                ],
            );

            if ($invoice['amount_paid'] > 0) {
                $model->payments()->updateOrCreate(
                    ['reference' => $invoice['number'].'-PMT'],
                    [
                        'client_id' => $clients[$clientCode] ?? null,
                        'amount' => $invoice['amount_paid'],
                        'method' => PaymentMethod::Eft->value,
                        'paid_on' => $invoice['issued_on'],
                        'notes' => 'Seeded Module 04 payment.',
                    ],
                );
            }
        }
    }
}
