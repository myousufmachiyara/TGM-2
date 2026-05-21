<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ProductCategory;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ProductCategoryController extends Controller
{
    public function index()
    {
        $categories = ProductCategory::orderBy('name')->get();
        return view('products.categories', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:product_categories,name',
        ]);

        ProductCategory::create([
            'name' => $request->name,
            'code' => strtoupper(Str::slug($request->name, '-')),
        ]);

        return redirect()->route('product_categories.index')
            ->with('success', 'Category "' . $request->name . '" created successfully.');
    }

    public function edit($id)
    {
        $category = ProductCategory::findOrFail($id);
        return response()->json($category);
    }

    public function update(Request $request, $id)
    {
        $category = ProductCategory::findOrFail($id);

        $request->validate([
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('product_categories', 'name')->ignore($id),
            ],
        ]);

        $category->update([
            'name' => $request->name,
            'code' => strtoupper(Str::slug($request->name, '-')),
        ]);

        return redirect()->route('product_categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy($id)
    {
        $category = ProductCategory::findOrFail($id);

        if ($category->products()->count() > 0) {
            return redirect()->back()
                ->with('error', 'Cannot delete "' . $category->name . '" — it has products assigned to it.');
        }

        $category->delete();

        return redirect()->route('product_categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}