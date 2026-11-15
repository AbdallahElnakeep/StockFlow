<?php

namespace App\Services;

use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public function post(
        string $refType,
        ?int $refId,
        array $lines,
        ?\DateTimeInterface $date = null
    ): void {
        DB::transaction(function () use ($refType, $refId, $lines, $date) {
            $alreadyPosted = LedgerEntry::where('ref_type', $refType)
                ->where('ref_id', $refId)
                ->exists();

            if ($alreadyPosted || empty($lines)) {
                return;
            }

            foreach ($lines as $line) {
                LedgerEntry::create([
                    'ledger_account_id' => $line['account_id'],
                    'ref_type' => $refType,
                    'ref_id' => $refId,
                    'debit' => $line['debit'] ?? 0,
                    'credit' => $line['credit'] ?? 0,
                    'entry_date' => $date ?? now(),
                ]);
            }
        });
    }

    public function accountIdByCode(string $code): int
    {
        $account = LedgerAccount::where('code', $code)->firstOrFail();

        return $account->id;
    }
}
