<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::with('parent')->orderBy('name')->paginate(20);

        return view('categories.index', ['categories' => $categories]);
    }

    public function create()
    {
        return view('categories.form', [
            'category' => new Category(),
            'parents' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        Category::create($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id'],
        ]));

        return redirect()->route('categories.index')->with('success', 'تم إضافة التصنيف');
    }

    public function edit(Category $category)
    {
        return view('categories.form', [
            'category' => $category,
            'parents' => Category::where('id', '!=', $category->id)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $category->update($request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:categories,id', 'not_in:'.$category->id],
        ]));

        return redirect()->route('categories.index')->with('success', 'تم تحديث التصنيف');
    }

    public function destroy(Category $category)
    {
        if ($category->children()->exists()) {
            return back()->with('error', 'التصنيف يحتوي تصنيفات فرعية');
        }

        if ($category->products()->exists()) {
            return back()->with('error', 'التصنيف مرتبط بأصناف');
        }

        $category->delete();

        return redirect()->route('categories.index')->with('success', 'تم حذف التصنيف');
    }
}
