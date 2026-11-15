<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private LedgerService $ledger,
        private DocumentNumberService $numbers,
    ) {}

    public function createPayment(array $data, ?User $actor = null): Payment
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = Payment::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing;
            }

            $amount = round($data['amount'], 2);
            if ($amount <= 0) {
                throw new \DomainException('قيمة التحصيل يجب أن تكون أكبر من صفر');
            }

            $isByRep = !empty($data['rep_user_id']);

            $payment = Payment::create([
                'rep_user_id' => $data['rep_user_id'] ?? null,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('payment'),
                'payable_type' => $data['payable_type'],
                'payable_id' => $data['payable_id'],
                'direction' => $data['direction'],
                'method' => $data['method'] ?? 'cash',
                'cheque_no' => $data['cheque_no'] ?? null,
                'due_date' => $data['due_date'] ?? null,
                'amount' => $amount,
                'note' => $data['note'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
            ]);

            $cashAccount = $isByRep
                ? $this->ledger->accountIdByCode('1020')
                : $this->ledger->accountIdByCode('1010');

            if ($data['direction'] === 'in') {
                $customer = Customer::findOrFail($data['payable_id']);
                $customer->decrement('balance', $amount);

                $debitAccount = ($data['method'] ?? 'cash') === 'cheque'
                    ? $this->ledger->accountIdByCode('1050')
                    : $cashAccount;

                $lines = [
                    ['account_id' => $debitAccount, 'debit' => $amount],
                    ['account_id' => $this->ledger->accountIdByCode('1100'), 'credit' => $amount],
                ];
            } else {
                $supplier = Supplier::findOrFail($data['payable_id']);
                $supplier->decrement('balance', $amount);

                $lines = [
                    ['account_id' => $this->ledger->accountIdByCode('2100'), 'debit' => $amount],
                    ['account_id' => $cashAccount, 'credit' => $amount],
                ];
            }

            $this->ledger->post('payment', $payment->id, $lines, $payment->issued_at);

            return $payment->refresh();
        });
    }
}
