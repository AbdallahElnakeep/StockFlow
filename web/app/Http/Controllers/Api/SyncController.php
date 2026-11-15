<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerVisit;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\SyncBatch;
use App\Services\PaymentService;
use App\Services\SalesReturnService;
use App\Services\SalesService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SyncController extends Controller
{
    public function __construct(
        private SalesService $salesService,
        private SalesReturnService $returnService,
        private PaymentService $paymentService,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->custodyWarehouse) {
            return response()->json(['message' => 'هذا الحساب ليس حساب مندوب'], 403);
        }

        $data = $request->validate([
            'batch_uuid' => ['required', 'uuid'],
            'device_id' => ['required', 'string', 'max:100'],
            'operations' => ['required', 'array', 'max:100'],
            'operations.*.op_uuid' => ['required', 'uuid'],
            'operations.*.type' => ['required', 'in:invoice,payment,return,expense,visit'],
            'operations.*.data' => ['required', 'array'],
        ]);

        $user = $request->user();

        if (SyncBatch::where('uuid', $data['batch_uuid'])->exists()) {
            $batch = SyncBatch::where('uuid', $data['batch_uuid'])->first();

            return response()->json(['batch_uuid' => $batch->uuid, 'results' => $batch->result]);
        }

        $results = [];

        foreach ($data['operations'] as $op) {
            $results[] = [
                'op_uuid' => $op['op_uuid'],
                'status' => $this->processOperation($user, $op),
            ];
        }

        $batch = SyncBatch::create([
            'user_id' => $user->id,
            'uuid' => $data['batch_uuid'],
            'device_id' => $data['device_id'],
            'result' => $results,
        ]);

        return response()->json(['batch_uuid' => $batch->uuid, 'results' => $results]);
    }

    private function processOperation($user, array $op): array
    {
        if (SalesInvoice::where('uuid', $op['op_uuid'])->exists()
            || Payment::where('uuid', $op['op_uuid'])->exists()
            || SalesReturn::where('uuid', $op['op_uuid'])->exists()
            || Expense::where('uuid', $op['op_uuid'])->exists()
            || CustomerVisit::where('uuid', $op['op_uuid'])->exists()) {
            return ['state' => 'duplicate'];
        }

        try {
            return DB::transaction(function () use ($user, $op) {
                match ($op['type']) {
                    'invoice' => $this->processInvoice($user, $op['op_uuid'], $op['data']),
                    'payment' => $this->processPayment($user, $op['op_uuid'], $op['data']),
                    'return' => $this->processReturn($user, $op['op_uuid'], $op['data']),
                    'expense' => $this->processExpense($user, $op['op_uuid'], $op['data']),
                    'visit' => $this->processVisit($user, $op['op_uuid'], $op['data']),
                };

                return ['state' => 'accepted'];
            });
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ['state' => 'rejected', 'reason' => $e->errors()];
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return ['state' => 'rejected', 'reason' => 'بيانات غير موجودة في نطاق شركتك'];
        } catch (\DomainException $e) {
            return ['state' => 'rejected', 'reason' => $e->getMessage()];
        }
    }

    private function processInvoice($user, string $uuid, array $d): void
    {
        $items = collect($d['items'] ?? [])->map(fn ($i) => [
            'product_id' => (int) $i['product_id'],
            'qty' => (float) $i['qty'],
            'unit_price' => (float) $i['unit_price'],
        ])->all();

        validator([
            'customer_id' => $d['customer_id'] ?? null,
            'type' => $d['type'] ?? null,
            'items' => $items,
        ], [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'type' => ['required', 'in:cash,credit'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ])->validate();

        $this->salesService->createInvoice([
            'uuid' => $uuid,
            'customer_id' => (int) $d['customer_id'],
            'warehouse_id' => $user->custodyWarehouse->id,
            'rep_user_id' => $user->id,
            'type' => $d['type'],
            'channel' => 'mobile',
            'discount' => (float) ($d['discount'] ?? 0),
            'collected_amount' => (float) ($d['collected_amount'] ?? 0),
            'note' => $d['note'] ?? null,
            'issued_at' => !empty($d['issued_at']) ? \Illuminate\Support\Carbon::parse($d['issued_at']) : now(),
            'items' => $items,
        ], $user);
    }

    private function processPayment($user, string $uuid, array $d): void
    {
        validator([
            'customer_id' => $d['customer_id'] ?? null,
            'amount' => $d['amount'] ?? null,
        ], [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ])->validate();

        $this->paymentService->createPayment([
            'uuid' => $uuid,
            'payable_type' => 'customer',
            'payable_id' => (int) $d['customer_id'],
            'direction' => 'in',
            'method' => 'cash',
            'rep_user_id' => $user->id,
            'amount' => (float) $d['amount'],
            'note' => $d['note'] ?? null,
            'issued_at' => !empty($d['issued_at']) ? \Illuminate\Support\Carbon::parse($d['issued_at']) : now(),
        ], $user);
    }

    private function processReturn($user, string $uuid, array $d): void
    {
        $items = collect($d['items'] ?? [])->map(fn ($i) => [
            'product_id' => (int) $i['product_id'],
            'qty' => (float) $i['qty'],
            'unit_price' => (float) $i['unit_price'],
        ])->all();

        validator([
            'customer_id' => $d['customer_id'] ?? null,
            'items' => $items,
        ], [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.qty' => ['required', 'numeric', 'gt:0'],
        ])->validate();

        $this->returnService->createReturn([
            'uuid' => $uuid,
            'customer_id' => (int) $d['customer_id'],
            'warehouse_id' => $user->custodyWarehouse->id,
            'rep_user_id' => $user->id,
            'channel' => 'mobile',
            'restock' => $d['restock'] ?? true,
            'note' => $d['note'] ?? null,
            'issued_at' => !empty($d['issued_at']) ? \Illuminate\Support\Carbon::parse($d['issued_at']) : now(),
            'items' => $items,
        ], $user);
    }

    private function processExpense($user, string $uuid, array $d): void
    {
        validator([
            'category' => $d['category'] ?? null,
            'amount' => $d['amount'] ?? null,
        ], [
            'category' => ['required', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'gt:0'],
        ])->validate();

        app(\App\Services\ExpenseService::class)->createExpense([
            'rep_user_id' => $user->id,
            'uuid' => $uuid,
            'category' => $d['category'],
            'amount' => (float) $d['amount'],
            'note' => $d['note'] ?? null,
            'issued_at' => !empty($d['issued_at']) ? \Illuminate\Support\Carbon::parse($d['issued_at']) : now(),
        ], $user);
    }

    private function processVisit($user, string $uuid, array $d): void
    {
        validator([
            'customer_id' => $d['customer_id'] ?? null,
            'status' => $d['status'] ?? null,
        ], [
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'status' => ['required', 'in:visited,no_answer,postponed'],
        ])->validate();

        CustomerVisit::create([
            'rep_user_id' => $user->id,
            'customer_id' => (int) $d['customer_id'],
            'uuid' => $uuid,
            'status' => $d['status'],
            'note' => $d['note'] ?? null,
            'visited_at' => !empty($d['visited_at']) ? \Illuminate\Support\Carbon::parse($d['visited_at']) : now(),
        ]);
    }
}
