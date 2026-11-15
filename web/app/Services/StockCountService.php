<?php

namespace App\Services;

use App\Models\StockBalance;
use App\Models\StockCount;
use App\Models\StockCountItem;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockCountService
{
    public function __construct(
        private StockService $stock,
        private DocumentNumberService $numbers,
    ) {}

    public function create(array $data, ?User $actor = null): StockCount
    {
        return DB::transaction(function () use ($data, $actor) {
            $warehouse = Warehouse::findOrFail($data['warehouse_id']);

            $count = StockCount::create([
                'warehouse_id' => $warehouse->id,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('stock_count'),
                'status' => 'draft',
                'note' => $data['note'] ?? null,
            ]);

            $balances = StockBalance::where('warehouse_id', $warehouse->id)
                ->where('qty', '!=', 0)
                ->with('product')
                ->get();

            foreach ($balances as $balance) {
                StockCountItem::create([
                    'stock_count_id' => $count->id,
                    'product_id' => $balance->product_id,
                    'system_qty' => $balance->qty,
                ]);
            }

            return $count->refresh();
        });
    }

    public function setCounted(StockCount $count, array $countedItems): StockCount
    {
        return DB::transaction(function () use ($count, $countedItems) {
            if ($count->status !== 'draft') {
                throw new \DomainException('هذا الجرد تمت تسويته بالفعل');
            }

            foreach ($countedItems as $item) {
                $countItem = StockCountItem::where('stock_count_id', $count->id)
                    ->where('product_id', $item['product_id'])
                    ->firstOrFail();

                $countedQty = (float) $item['counted_qty'];
                $countItem->update([
                    'counted_qty' => $countedQty,
                    'difference' => $countedQty - (float) $countItem->system_qty,
                ]);
            }

            return $count->refresh();
        });
    }

    public function adjust(StockCount $count, ?User $actor = null): StockCount
    {
        return DB::transaction(function () use ($count, $actor) {
            if ($count->status !== 'draft') {
                throw new \DomainException('هذا الجرد تمت تسويته بالفعل');
            }

            $count->load('items');

            foreach ($count->items as $item) {
                if ($item->counted_qty === null) {
                    continue;
                }

                $diff = (float) $item->difference;
                if (abs($diff) < 0.001) {
                    continue;
                }

                $this->stock->move(
                    warehouse: $count->warehouse,
                    productId: $item->product_id,
                    qty: $diff,
                    refType: 'stock_count',
                    refId: $count->id,
                    uuid: $count->uuid.'-adj-'.$item->product_id,
                    userId: $actor?->id,
                    note: 'تسوية جرد '.$count->number,
                );
            }

            $count->update(['status' => 'adjusted', 'adjusted_at' => now()]);

            return $count->refresh();
        });
    }
}
