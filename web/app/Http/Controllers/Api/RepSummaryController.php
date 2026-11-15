<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\SalesInvoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepSummaryController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $dayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $invoicesToday = SalesInvoice::where('rep_user_id', $user->id)->where('issued_at', '>=', $dayStart);
        $invoicesMonth = SalesInvoice::where('rep_user_id', $user->id)->where('issued_at', '>=', $monthStart);

        return response()->json([
            'today' => [
                'sales_total' => (float) (clone $invoicesToday)->sum('total'),
                'invoices_count' => (clone $invoicesToday)->count(),
                'collections' => (float) Payment::where('rep_user_id', $user->id)->where('direction', 'in')->where('issued_at', '>=', $dayStart)->sum('amount'),
                'expenses' => (float) Expense::where('rep_user_id', $user->id)->where('issued_at', '>=', $dayStart)->sum('amount'),
            ],
            'month' => [
                'sales_total' => (float) (clone $invoicesMonth)->sum('total'),
                'invoices_count' => (clone $invoicesMonth)->count(),
                'collections' => (float) Payment::where('rep_user_id', $user->id)->where('direction', 'in')->where('issued_at', '>=', $monthStart)->sum('amount'),
                'expenses' => (float) Expense::where('rep_user_id', $user->id)->where('issued_at', '>=', $monthStart)->sum('amount'),
            ],
        ]);
    }
}
