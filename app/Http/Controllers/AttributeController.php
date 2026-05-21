<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Validation\Rule;

class AttributeController extends Controller
{
    public function index()
    {
        $attributes = Attribute::with('values')->orderBy('name')->get();
        return view('products.attributes', compact('attributes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => 'required|string|max:255|unique:attributes,slug',
            'values' => 'required|string',
        ]);

        $attribute = Attribute::create($request->only('name', 'slug'));

        $this->syncValues($attribute, $request->input('values'));

        return redirect()->route('attributes.index')
            ->with('success', 'Attribute "' . $attribute->name . '" created successfully.');
    }

    // Returns JSON for edit modal
    public function edit($id)
    {
        $attribute = Attribute::with('values')->findOrFail($id);

        return response()->json([
            'id'     => $attribute->id,
            'name'   => $attribute->name,
            'slug'   => $attribute->slug,
            'values' => $attribute->values->pluck('value')->implode(', '),
        ]);
    }

    public function update(Request $request, $id)
    {
        // FIX: use $id not route-model binding — routes pass {id}
        $attribute = Attribute::findOrFail($id);

        $request->validate([
            'name'   => 'required|string|max:255',
            'slug'   => ['required', 'string', 'max:255', Rule::unique('attributes', 'slug')->ignore($attribute->id)],
            'values' => 'required|string',
        ]);

        $attribute->update($request->only('name', 'slug'));

        $this->syncValues($attribute, $request->input('values'));

        return redirect()->route('attributes.index')
            ->with('success', 'Attribute updated successfully.');
    }

    public function destroy($id)
    {
        // FIX: use $id — route-model binding with {id} won't resolve Attribute $attribute
        $attribute = Attribute::findOrFail($id);

        $attribute->values()->delete(); // soft-deletes all child values first
        $attribute->delete();

        return redirect()->route('attributes.index')
            ->with('success', 'Attribute deleted successfully.');
    }

    // ── Private helper ────────────────────────────────────────────────
    private function syncValues(Attribute $attribute, string $rawValues): void
    {
        $incoming = collect(explode(',', $rawValues))
            ->map(fn($v) => trim($v))
            ->filter()
            ->unique()
            ->values();

        $existing = $attribute->values()->get();

        // Add new values
        foreach ($incoming as $value) {
            $exists = $existing->contains(
                fn($v) => strtolower($v->value) === strtolower($value)
            );
            if (!$exists) {
                $attribute->values()->create(['value' => $value]);
            }
        }

        // Remove values no longer in the list
        foreach ($existing as $val) {
            $stillPresent = $incoming->contains(
                fn($v) => strtolower($v) === strtolower($val->value)
            );
            if (!$stillPresent) {
                $val->delete();
            }
        }
    }
}