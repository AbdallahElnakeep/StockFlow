<?php

namespace App\Services;

use App\Models\StockTransfer;
use App\Models\StockTransferItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class TransferService
{
    public function __construct(
        private StockService $stock,
        private DocumentNumberService $numbers,
    ) {}

    public function create(array $data, ?User $actor = null): StockTransfer
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = StockTransfer::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing;
            }

            $from = Warehouse::findOrFail($data['from_warehouse_id']);
            $to = Warehouse::findOrFail($data['to_warehouse_id']);

            if ($from->id === $to->id) {
                throw new \DomainException('لا يمكن التحويل لنفس المخزن');
            }

            if ($from->type === 'rep' || $to->type === 'rep') {
                throw new \DomainException('عُهد المندوبين تتحرك فقط عبر تحميل العهدة أو تسويتها — استخدم شاشة تحميل العُهد');
            }

            $transfer = StockTransfer::create([
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $to->id,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('stock_transfer'),
                'status' => 'confirmed',
                'note' => $data['note'] ?? null,
                'confirmed_at' => now(),
            ]);

            foreach ($data['items'] as $item) {
                $avgCost = (float) (\App\Models\StockBalance::where('warehouse_id', $from->id)
                    ->where('product_id', $item['product_id'])
                    ->value('avg_cost') ?? 0);

                $this->stock->move($from, $item['product_id'], -(float) $item['qty'], 'stock_transfer', $transfer->id, $data['uuid'].'-out-'.$item['product_id'], $avgCost, $actor?->id);
                $this->stock->move($to, $item['product_id'], (float) $item['qty'], 'stock_transfer', $transfer->id, $data['uuid'].'-in-'.$item['product_id'], $avgCost, $actor?->id);

                StockTransferItem::create([
                    'stock_transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                ]);
            }

            return $transfer->refresh();
        });
    }
}
