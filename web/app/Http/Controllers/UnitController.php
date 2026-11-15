<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index()
    {
        $units = Unit::orderBy('name')->paginate(20);

        return view('units.index', ['units' => $units]);
    }

    public function create()
    {
        return view('units.form', ['unit' => new Unit()]);
    }

    public function store(Request $request)
    {
        Unit::create($request->validate([
            'name' => ['required', 'string', 'max:50'],
            'short_name' => ['required', 'string', 'max:20'],
        ]));

        return redirect()->route('units.index')->with('success', 'تم إضافة الوحدة');
    }

    public function edit(Unit $unit)
    {
        return view('units.form', ['unit' => $unit]);
    }

    public function update(Request $request, Unit $unit)
    {
        $unit->update($request->validate([
            'name' => ['required', 'string', 'max:50'],
            'short_name' => ['required', 'string', 'max:20'],
        ]));

        return redirect()->route('units.index')->with('success', 'تم تحديث الوحدة');
    }

    public function destroy(Unit $unit)
    {
        if ($unit->products()->exists()) {
            return back()->with('error', 'الوحدة مرتبطة بأصناف');
        }

        $unit->delete();

        return redirect()->route('units.index')->with('success', 'تم حذف الوحدة');
    }
}
