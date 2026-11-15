<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $company = $request->user()->company;

        return view('settings.index', [
            'company' => $company,
            'settings' => Setting::pluck('value', 'key'),
        ]);
    }

    public function update(Request $request)
    {
        $company = $request->user()->company;

        $data = $request->validate([
            'company_name' => ['required', 'string', 'max:150'],
            'company_phone' => ['nullable', 'string', 'max:30'],
            'enforce_credit_limit' => ['nullable', 'boolean'],
            'allow_negative_stock' => ['nullable', 'boolean'],
        ]);

        $company->update([
            'name' => $data['company_name'],
            'phone' => $data['company_phone'],
        ]);

        foreach (['enforce_credit_limit' => (bool) ($data['enforce_credit_limit'] ?? false), 'allow_negative_stock' => (bool) ($data['allow_negative_stock'] ?? false)] as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => $value ? '1' : '0']);
        }

        return redirect()->route('settings.index')->with('success', 'تم حفظ الإعدادات');
    }
}
