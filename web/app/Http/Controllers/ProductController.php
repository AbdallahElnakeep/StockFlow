<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBalance;
use App\Models\Unit;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['category', 'unit'])
            ->when($request->q, fn ($q, $v) => $q->where(fn ($w) => $w->where('name', 'like', "%{$v}%")->orWhere('barcode', 'like', "%{$v}%")->orWhere('sku', 'like', "%{$v}%")))
            ->when($request->boolean('inactive'), fn ($q) => $q->where('is_active', false), fn ($q) => $q->where('is_active', true))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $stockMap = StockBalance::selectRaw('product_id, SUM(qty) as qty')
            ->groupBy('product_id')
            ->pluck('qty', 'product_id');

        return view('products.index', [
            'products' => $products,
            'stockMap' => $stockMap,
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
            'q' => $request->q,
        ]);
    }

    public function create()
    {
        return view('products.form', [
            'product' => new Product(),
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Product::create($data);

        return redirect()->route('products.index')->with('success', 'تم إضافة الصنف');
    }

    public function edit(Product $product)
    {
        return view('products.form', [
            'product' => $product,
            'categories' => Category::orderBy('name')->get(),
            'units' => Unit::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $product->update($this->validated($request, $product->id));

        return redirect()->route('products.index')->with('success', 'تم تحديث الصنف');
    }

    public function destroy(Product $product)
    {
        $hasMovements = StockBalance::where('product_id', $product->id)->where('qty', '!=', 0)->exists();
        if ($hasMovements) {
            return back()->with('error', 'لا يمكن حذف صنف له رصيد قائم — يمكن إيقافه بدلًا من ذلك');
        }

        $product->update(['is_active' => false]);

        return redirect()->route('products.index')->with('success', 'تم إيقاف الصنف');
    }

    private function validated(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'brand' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'sku' => ['nullable', 'string', 'max:50'],
            'barcode' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'min_stock' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
