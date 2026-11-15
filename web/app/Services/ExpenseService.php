<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function __construct(private LedgerService $ledger) {}

    public function createExpense(array $data, ?User $actor = null): Expense
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = !empty($data['uuid']) ? Expense::where('uuid', $data['uuid'])->first() : null;
            if ($existing) {
                return $existing;
            }

            $expense = Expense::create([
                'rep_user_id' => $data['rep_user_id'] ?? null,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'] ?? (string) \Illuminate\Support\Str::uuid(),
                'category' => $data['category'],
                'amount' => $data['amount'],
                'note' => $data['note'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
            ]);

            $cashAccount = !empty($data['rep_user_id'])
                ? $this->ledger->accountIdByCode('1020')
                : $this->ledger->accountIdByCode('1010');

            $this->ledger->post('expense', $expense->id, [
                ['account_id' => $this->ledger->accountIdByCode('5200'), 'debit' => $expense->amount],
                ['account_id' => $cashAccount, 'credit' => $expense->amount],
            ], $expense->issued_at);

            return $expense->refresh();
        });
    }
}
