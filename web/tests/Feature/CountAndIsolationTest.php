<?php

namespace Tests\Feature;

use App\Models\StockBalance;
use App\Services\StockCountService;
use Illuminate\Support\Str;
use Tests\TestCase;

class CountAndIsolationTest extends TestCase
{
    public function test_stock_count_adjusts_balances(): void
    {
        $ctx = $this->seedCompany();
        $this->actingAs($ctx['owner']);
        $product = $this->makeProduct($ctx['company'], 10, 15);

        StockBalance::create([
            'company_id' => $ctx['company']->id,
            'warehouse_id' => $ctx['main']->id,
            'product_id' => $product->id,
            'qty' => 100,
            'avg_cost' => 10,
        ]);

        $count = app(StockCountService::class)->create([
            'uuid' => (string) Str::uuid(),
            'warehouse_id' => $ctx['main']->id,
        ], $ctx['owner']);

        $this->assertCount(1, $count->items);

        app(StockCountService::class)->setCounted($count, [
            ['product_id' => $product->id, 'counted_qty' => 96],
        ]);

        $this->assertEquals(-4, (float) $count->refresh()->items->first()->difference);

        app(StockCountService::class)->adjust($count, $ctx['owner']);

        $this->assertEquals('adjusted', $count->refresh()->status);
        $this->assertEquals(96, $this->balanceOf($ctx['main'], $product->id));
        $this->expectException(\DomainException::class);
        app(StockCountService::class)->adjust($count, $ctx['owner']);
    }

    public function test_company_data_is_isolated(): void
    {
        $ctxA = $this->seedCompany();
        $ctxB = $this->seedCompany();

        $this->makeProduct($ctxA['company']);
        $this->makeProduct($ctxB['company']);

        app()->instance('current.company.id', $ctxA['company']->id);
        $this->actingAs($ctxA['owner']);
        $visibleToA = \App\Models\Product::count();

        app()->instance('current.company.id', $ctxB['company']->id);
        $this->actingAs($ctxB['owner']);
        $visibleToB = \App\Models\Product::count();

        $this->assertEquals(1, $visibleToA);
        $this->assertEquals(1, $visibleToB);
    }

    public function test_rep_cannot_access_web_admin_routes(): void
    {
        $ctx = $this->seedCompany();
        $repRole = \App\Models\Role::where('name', 'مندوب')->first();
        $repRole->permissions()->sync(
            \App\Models\Permission::where('action', 'view')
                ->whereIn('module', ['customers', 'sales'])
                ->pluck('id')
        );

        $response = $this->actingAs($ctx['rep'])->get('/users');
        $response->assertStatus(403);

        $response = $this->actingAs($ctx['rep'])->get('/dashboard');
        $response->assertStatus(200);
    }
}
