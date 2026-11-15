<?php

namespace App\Services;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    private const PREFIXES = [
        'purchase' => 'PUR',
        'sales_invoice' => 'INV',
        'sales_return' => 'RET',
        'payment' => 'PAY',
        'rep_loading' => 'LOD',
        'rep_settlement' => 'STL',
        'stock_transfer' => 'TRF',
        'stock_count' => 'CNT',
    ];

    public function next(string $docType, ?int $companyId = null): string
    {
        $prefix = self::PREFIXES[$docType] ?? strtoupper(substr($docType, 0, 3));
        $companyId = $companyId ?? BelongsToCompany::currentCompanyId();

        if ($companyId === null) {
            throw new \DomainException('لا يمكن توليد رقم مستند خارج سياق شركة');
        }

        return DB::transaction(function () use ($docType, $prefix, $companyId) {
            $row = DB::table('document_sequences')
                ->where('company_id', $companyId)
                ->where('doc_type', $docType)
                ->lockForUpdate()
                ->first();

            if (!$row) {
                DB::table('document_sequences')->insert([
                    'company_id' => $companyId,
                    'doc_type' => $docType,
                    'next_number' => 2,
                ]);

                return $prefix.'-'.str_pad('1', 6, '0', STR_PAD_LEFT);
            }

            DB::table('document_sequences')
                ->where('id', $row->id)
                ->update(['next_number' => $row->next_number + 1]);

            return $prefix.'-'.str_pad((string) $row->next_number, 6, '0', STR_PAD_LEFT);
        });
    }
}
