<?php

namespace App\Services;

use App\Models\RepLoading;
use App\Models\RepLoadingItem;
use App\Models\StockBalance;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class RepLoadingService
{
    public function __construct(
        private StockService $stock,
        private DocumentNumberService $numbers,
    ) {}

    public function create(array $data, ?User $actor = null): RepLoading
    {
        return DB::transaction(function () use ($data, $actor) {
            $existing = RepLoading::where('uuid', $data['uuid'])->first();
            if ($existing) {
                return $existing;
            }

            $rep = User::findOrFail($data['rep_user_id']);
            $custody = Warehouse::where('rep_user_id', $rep->id)->where('type', 'rep')->firstOrFail();
            $from = Warehouse::findOrFail($data['from_warehouse_id']);

            $loading = RepLoading::create([
                'rep_user_id' => $rep->id,
                'from_warehouse_id' => $from->id,
                'to_warehouse_id' => $custody->id,
                'created_by' => $actor?->id,
                'uuid' => $data['uuid'],
                'number' => $this->numbers->next('rep_loading'),
                'note' => $data['note'] ?? null,
            ]);

            $totalCost = 0;
            foreach ($data['items'] as $item) {
                $avgCost = (float) (StockBalance::where('warehouse_id', $from->id)
                    ->where('product_id', $item['product_id'])
                    ->value('avg_cost') ?? 0);
                $totalCost += round($avgCost * $item['qty'], 2);

                $this->stock->move($from, $item['product_id'], -(float) $item['qty'], 'rep_loading', $loading->id, $data['uuid'].'-out-'.$item['product_id'], $avgCost, $actor?->id);
                $this->stock->move($custody, $item['product_id'], (float) $item['qty'], 'rep_loading', $loading->id, $data['uuid'].'-in-'.$item['product_id'], $avgCost, $actor?->id);

                RepLoadingItem::create([
                    'rep_loading_id' => $loading->id,
                    'product_id' => $item['product_id'],
                    'qty' => $item['qty'],
                    'unit_cost' => $avgCost,
                ]);
            }

            $loading->update(['total_cost' => $totalCost]);

            return $loading->refresh();
        });
    }
}
