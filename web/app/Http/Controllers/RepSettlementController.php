<?php

namespace App\Http\Controllers;

use App\Models\RepSettlement;
use App\Services\SettlementService;
use Illuminate\Http\Request;

class RepSettlementController extends Controller
{
    public function __construct(private SettlementService $settlementService) {}

    public function index()
    {
        $settlements = RepSettlement::with('rep')->latest()->paginate(20);

        return view('settlements.index', ['settlements' => $settlements]);
    }

    public function show(RepSettlement $settlement)
    {
        $settlement->load('rep');

        return view('settlements.show', ['settlement' => $settlement]);
    }

    public function settle(Request $request, RepSettlement $settlement)
    {
        abort_unless($settlement->status === 'draft', 422, 'التسوية منفذة بالفعل');

        $data = $request->validate([
            'cash_handover' => ['required', 'numeric', 'min:0'],
            'main_warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->settlementService->settle($settlement->rep, (float) $data['cash_handover'], $data + ['uuid' => $settlement->uuid], $request->user());

        return redirect()->route('settlements.show', $settlement->refresh())->with('success', 'تمت تسوية العهدة');
    }
}
