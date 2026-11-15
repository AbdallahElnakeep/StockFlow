<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LedgerAccount;
use Illuminate\Support\Facades\DB;

class OpeningBalanceService
{
    public function postCustomerOpening(Customer $customer, float $amount): void
    {
        DB::transaction(function () use ($customer, $amount) {
            $opening = LedgerAccount::firstOrCreate(
                ['company_id' => $customer->company_id, 'code' => '3000'],
                ['name' => 'الأرصدة الافتتاحية', 'type' => 'equity', 'is_system' => true]
            );

            app(LedgerService::class)->post('opening_balance', $customer->id, [
                ['account_id' => app(LedgerService::class)->accountIdByCode('1100'), 'debit' => $amount],
                ['account_id' => $opening->id, 'credit' => $amount],
            ]);
        });
    }
}
