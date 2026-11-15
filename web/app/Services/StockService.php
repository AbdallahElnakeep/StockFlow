<?php

namespace App\Services;

use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class StockService
{
    public function move(
        Warehouse $warehouse,
        int $productId,
        float $qty,
        string $refType,
        ?int $refId,
        string $uuid,
        ?float $unitCost = null,
        ?int $userId = null,
        ?string $note = null
    ): StockMovement {
        return DB::transaction(function () use ($warehouse, $productId, $qty, $refType, $refId, $uuid, $unitCost, $userId, $note) {
            if (StockMovement::where('uuid', $uuid)->exists()) {
                return StockMovement::where('uuid', $uuid)->first();
            }

            $balance = StockBalance::query()
                ->where('warehouse_id', $warehouse->id)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                $balance = StockBalance::create([
                    'warehouse_id' => $warehouse->id,
                    'product_id' => $productId,
                    'qty' => 0,
                    'avg_cost' => 0,
                ]);
                $balance = $balance->refresh();
            }

            $oldQty = (float) $balance->qty;
            $oldCost = (float) $balance->avg_cost;
            $newQty = $oldQty + $qty;

            if ($newQty < -0.001) {
                throw new \DomainException('الكمية المطلوبة غير متوفرة في المخزن');
            }

            $balance->qty = $newQty;
            $balance->avg_cost = $this->recalculateAvgCost($oldQty, $oldCost, $qty, $unitCost, $newQty);
            $balance->save();

            return StockMovement::create([
                'warehouse_id' => $warehouse->id,
                'product_id' => $productId,
                'created_by' => $userId,
                'uuid' => $uuid,
                'ref_type' => $refType,
                'ref_id' => $refId,
                'qty' => $qty,
                'unit_cost' => $unitCost ?? $balance->avg_cost,
                'balance_after' => $newQty,
                'note' => $note,
            ]);
        }, attempts: 2);
    }

    private function recalculateAvgCost(float $oldQty, float $oldCost, float $qty, ?float $unitCost, float $newQty): float
    {
        if ($qty <= 0 || $unitCost === null || $unitCost <= 0) {
            return $oldCost;
        }

        if ($oldQty + $qty <= 0) {
            return $unitCost;
        }

        return ($oldQty * $oldCost + $qty * $unitCost) / ($oldQty + $qty);
    }

    public function balanceFor(int $warehouseId, int $productId): float
    {
        return (float) (StockBalance::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('qty') ?? 0);
    }
}
