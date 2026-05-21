<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Vendor::latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name',       'like', "%$s%")
                  ->orWhere('contact_no', 'like', "%$s%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $vendors = $query->get();

        return view('accounts.vendor', compact('vendors'));
    }

    // ── Store ─────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name'             => ['required', 'string', 'max:255',
                                       Rule::unique('vendors')->whereNull('deleted_at')],
                'contact_no'       => 'nullable|string|max:30',
                'address'          => 'nullable|string|max:500',
                'opening_payables' => 'required|numeric|min:0',
                'opening_date'     => 'required|date',
                'remarks'          => 'nullable|string|max:800',
            ]);

            Vendor::create(array_merge($validated, [
                'is_active'  => true,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]));

            return redirect()->route('vendors.index')
                ->with('success', 'Vendor "' . $validated['name'] . '" created successfully.');

        } catch (\Exception $e) {
            Log::error('[Vendor] Store error', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    // ── Edit (AJAX — returns JSON for modal) ──────────────────────────

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);

        return response()->json([
            'id'               => $vendor->id,
            'name'             => $vendor->name,
            'contact_no'       => $vendor->contact_no,
            'address'          => $vendor->address,
            'opening_payables' => $vendor->opening_payables,
            'opening_date'     => $vendor->opening_date?->format('Y-m-d'),
            'remarks'          => $vendor->remarks,
            'is_active'        => $vendor->is_active,
        ]);
    }

    // ── Update ────────────────────────────────────────────────────────

    public function update(Request $request, $id)
    {
        try {
            $vendor = Vendor::findOrFail($id);

            $validated = $request->validate([
                'name'             => ['required', 'string', 'max:255',
                                       Rule::unique('vendors')->ignore($id)->whereNull('deleted_at')],
                'contact_no'       => 'nullable|string|max:30',
                'address'          => 'nullable|string|max:500',
                'opening_payables' => 'required|numeric|min:0',
                'opening_date'     => 'required|date',
                'remarks'          => 'nullable|string|max:800',
                'is_active'        => 'nullable|boolean',
            ]);

            $vendor->update(array_merge($validated, [
                'is_active'  => $request->boolean('is_active', $vendor->is_active),
                'updated_by' => auth()->id(),
            ]));

            return redirect()->route('vendors.index')
                ->with('success', 'Vendor updated successfully.');

        } catch (\Exception $e) {
            Log::error('[Vendor] Update error', ['message' => $e->getMessage()]);
            return redirect()->back()->withInput()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────

    public function destroy($id)
    {
        try {
            Vendor::findOrFail($id)->delete();

            return redirect()->route('vendors.index')
                ->with('success', 'Vendor deleted successfully.');

        } catch (\Exception $e) {
            Log::error('[Vendor] Destroy error', ['message' => $e->getMessage()]);
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
}