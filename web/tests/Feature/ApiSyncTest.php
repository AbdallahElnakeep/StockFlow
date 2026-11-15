<?php

namespace Tests\Feature;

use App\Models\StockBalance;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiSyncTest extends TestCase
{
    private function seedCustody(array $ctx, $product, int $qty): void
    {
        StockBalance::create([
            'company_id' => $ctx['company']->id,
            'warehouse_id' => $ctx['custody']->id,
            'product_id' => $product->id,
            'qty' => $qty,
            'avg_cost' => 10,
        ]);
    }

    public function test_login_returns_token_for_rep(): void
    {
        $ctx = $this->seedCompany();

        $response = $this->postJson('/api/auth/login', [
            'phone' => $ctx['rep']->phone,
            'password' => 'password',
            'device_name' => 'test-device',
        ]);

        $response->assertOk()->assertJsonStructure(['token', 'user' => ['id', 'name', 'is_rep', 'custody_warehouse_id']]);
    }

    public function test_sync_processes_operations_and_is_idempotent(): void
    {
        $ctx = $this->seedCompany();
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $customer = $this->makeCustomer($ctx['company']);
        $this->seedCustody($ctx, $product, 100);

        Sanctum::actingAs($ctx['rep']);

        $opUuid = (string) Str::uuid();
        $batchUuid = (string) Str::uuid();

        $payload = [
            'batch_uuid' => $batchUuid,
            'device_id' => 'device-1',
            'operations' => [
                [
                    'op_uuid' => $opUuid,
                    'type' => 'invoice',
                    'data' => [
                        'customer_id' => $customer->id,
                        'type' => 'cash',
                        'items' => [['product_id' => $product->id, 'qty' => 10, 'unit_price' => 15]],
                    ],
                ],
            ],
        ];

        $first = $this->postJson('/api/sync', $payload);
        $first->assertOk();
        $this->assertEquals('accepted', $first->json('results.0.status.state'));

        $this->assertEquals(90, $this->balanceOf($ctx['custody'], $product->id));

        $second = $this->postJson('/api/sync', $payload);
        $second->assertOk();
        $this->assertEquals('accepted', $second->json('results.0.status.state'));

        $this->assertEquals(1, \App\Models\SalesInvoice::where('uuid', $opUuid)->count());
        $this->assertEquals(90, $this->balanceOf($ctx['custody'], $product->id));

        $newOp = array_merge($payload, [
            'batch_uuid' => (string) Str::uuid(),
            'operations' => [[
                'op_uuid' => $opUuid,
                'type' => 'invoice',
                'data' => [
                    'customer_id' => $customer->id,
                    'type' => 'cash',
                    'items' => [['product_id' => $product->id, 'qty' => 10, 'unit_price' => 15]],
                ],
            ]],
        ]);
        $third = $this->postJson('/api/sync', $newOp);
        $this->assertEquals('duplicate', $third->json('results.0.status.state'));
    }

    public function test_sync_rejects_operation_exceeding_custody_stock(): void
    {
        $ctx = $this->seedCompany();
        $product = $this->makeProduct($ctx['company'], 10, 15);
        $customer = $this->makeCustomer($ctx['company']);
        $this->seedCustody($ctx, $product, 5);

        Sanctum::actingAs($ctx['rep']);

        $response = $this->postJson('/api/sync', [
            'batch_uuid' => (string) Str::uuid(),
            'device_id' => 'device-1',
            'operations' => [
                [
                    'op_uuid' => (string) Str::uuid(),
                    'type' => 'invoice',
                    'data' => [
                        'customer_id' => $customer->id,
                        'type' => 'cash',
                        'items' => [['product_id' => $product->id, 'qty' => 10, 'unit_price' => 15]],
                    ],
                ],
            ],
        ]);

        $response->assertOk();
        $this->assertEquals('rejected', $response->json('results.0.status.state'));
        $this->assertEquals(5, $this->balanceOf($ctx['custody'], $product->id));
    }

    public function test_non_rep_user_cannot_sync(): void
    {
        $ctx = $this->seedCompany();
        Sanctum::actingAs($ctx['owner']);

        $this->postJson('/api/sync', [
            'batch_uuid' => (string) Str::uuid(),
            'device_id' => 'device-1',
            'operations' => [],
        ])->assertStatus(403);
    }
}
