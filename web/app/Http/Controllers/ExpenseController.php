<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::with('rep')
            ->when($request->category, fn ($q, $v) => $q->where('category', $v))
            ->latest('issued_at')
            ->paginate(20)
            ->withQueryString();

        $categories = ['بنزين', 'مواصلات', 'ضيافة', 'صيانة', 'أخرى'];

        return view('expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'category' => $request->category,
        ]);
    }

    public function create()
    {
        return view('expenses.form', [
            'reps' => User::whereHas('custodyWarehouse')->orderBy('name')->get(),
            'categories' => ['بنزين', 'مواصلات', 'ضيافة', 'صيانة', 'أخرى'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category' => ['required', 'string', 'max:60'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'rep_user_id' => ['nullable', 'exists:users,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        app(\App\Services\ExpenseService::class)->createExpense($data, $request->user());

        return redirect()->route('expenses.index')->with('success', 'تم تسجيل المصروف');
    }
}
