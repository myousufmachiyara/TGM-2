<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChartOfAccounts;
use App\Models\SubHeadOfAccounts;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class COAController extends Controller
{
    // ─────────────────────────────────────────────────────────────────
    // Single source of truth for valid account types.
    // Must stay in sync with the blade $accountTypes array below.
    // ─────────────────────────────────────────────────────────────────
    private const ACCOUNT_TYPES = [
        'customer',
        'vendor',
        'cash',
        'bank',
        'asset',       // generic fixed/current asset
        'inventory',
        'liability',
        'equity',
        'revenue',
        'cogs',
        'expense',     // note: seeder uses 'expense' (not 'expenses')
        'receivable',  // loan given out
        'payable',     // loan taken
    ];

    // ── Shared label map (passed to views) ───────────────────────────
    private function accountTypeLabels(): array
    {
        return [
            'customer'   => 'Customer',
            'vendor'     => 'Vendor',
            'cash'       => 'Cash',
            'bank'       => 'Bank',
            'asset'      => 'Asset (Fixed / Current)',
            'inventory'  => 'Inventory / Stock',
            'liability'  => 'Liability',
            'equity'     => 'Equity',
            'revenue'    => 'Revenue',
            'cogs'       => 'Cost of Goods Sold',
            'expense'    => 'Expense',
            'receivable' => 'Receivable (Loan Given)',
            'payable'    => 'Payable (Loan Taken)',
        ];
    }

    // ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $subHeadOfAccounts = SubHeadOfAccounts::with('headOfAccount')->orderBy('id')->get();

        $query = ChartOfAccounts::with('subHeadOfAccount');

        if ($request->filled('subhead') && $request->subhead !== 'all') {
            $query->where('shoa_id', $request->subhead);
        }

        $chartOfAccounts = $query->latest()->get();
        $accountTypes    = $this->accountTypeLabels();

        return view('accounts.coa', compact('chartOfAccounts', 'subHeadOfAccounts', 'accountTypes'));
    }

    public function store(Request $request)
    {
        try {
            Log::info('[COA] Store called', ['user_id' => auth()->id()]);

            $validated = $request->validate([
                'shoa_id'      => 'required|exists:sub_head_of_accounts,id',
                'name'         => [
                    'required', 'string', 'max:255',
                    Rule::unique('chart_of_accounts')->whereNull('deleted_at'),
                ],
                'trn'          => 'nullable|string|max:50',
                'account_type' => ['nullable', 'string', Rule::in(self::ACCOUNT_TYPES)],
                'receivables'  => 'required|numeric|min:0',
                'payables'     => 'required|numeric|min:0',
                'credit_limit' => 'required|numeric|min:0',
                'credit_days'  => 'required|integer|min:0|max:365',
                'opening_date' => 'required|date',
                'remarks'      => 'nullable|string|max:800',
                'address'      => 'nullable|string|max:500',
                'contact_no'   => 'nullable|string|max:50',
            ]);

            // ── Auto-generate account code ────────────────────────
            $subHead = SubHeadOfAccounts::findOrFail($validated['shoa_id']);
            $prefix  = $subHead->hoa_id . str_pad($subHead->id, 2, '0', STR_PAD_LEFT);

            $nextNumber = ChartOfAccounts::withTrashed()
                ->where('account_code', 'like', $prefix . '%')
                ->pluck('account_code')
                ->map(fn($code) => intval(substr($code, strlen($prefix))))
                ->sort()
                ->last() ?? 0;

            $accountCode = $prefix . str_pad($nextNumber + 1, 3, '0', STR_PAD_LEFT);

            Log::info('[COA] Generated account code', ['code' => $accountCode]);

            $account = ChartOfAccounts::create([
                'shoa_id'      => $validated['shoa_id'],
                'account_code' => $accountCode,
                'name'         => $validated['name'],
                'trn'          => $validated['trn'] ?? null,
                'account_type' => $validated['account_type'] ?? null,
                'receivables'  => $validated['receivables'],
                'payables'     => $validated['payables'],
                'credit_limit' => $validated['credit_limit'],
                'credit_days'  => $validated['credit_days'],
                'opening_date' => $validated['opening_date'],
                'remarks'      => $validated['remarks'] ?? null,
                'address'      => $validated['address'] ?? null,
                'contact_no'   => $validated['contact_no'] ?? null,
                'created_by'   => auth()->id(),
                'updated_by'   => auth()->id(),
            ]);

            Log::info('[COA] Account created', ['id' => $account->id, 'code' => $accountCode]);

            return redirect()->route('coa.index')
                ->with('success', 'Account "' . $account->name . '" created successfully.');

        } catch (\Exception $e) {
            Log::error('[COA] Store error', [
                'message' => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }

    // Returns JSON for the edit modal AJAX call
    public function edit($id)
    {
        $account = ChartOfAccounts::with('subHeadOfAccount')->findOrFail($id);
        return response()->json($account);
    }

    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'shoa_id'      => 'required|exists:sub_head_of_accounts,id',
                'name'         => [
                    'required', 'string', 'max:255',
                    Rule::unique('chart_of_accounts')->ignore($id)->whereNull('deleted_at'),
                ],
                'trn'          => 'nullable|string|max:50',
                'account_type' => ['nullable', 'string', Rule::in(self::ACCOUNT_TYPES)],
                'receivables'  => 'required|numeric|min:0',
                'payables'     => 'required|numeric|min:0',
                'credit_limit' => 'required|numeric|min:0',
                'credit_days'  => 'required|integer|min:0|max:365',
                'opening_date' => 'required|date',
                'remarks'      => 'nullable|string|max:800',
                'address'      => 'nullable|string|max:500',
                'contact_no'   => 'nullable|string|max:50',
            ]);

            $account = ChartOfAccounts::findOrFail($id);

            $account->update([
                'shoa_id'      => $validated['shoa_id'],
                'name'         => $validated['name'],
                'trn'          => $validated['trn'] ?? null,
                'account_type' => $validated['account_type'] ?? null,
                'receivables'  => $validated['receivables'],
                'payables'     => $validated['payables'],
                'credit_limit' => $validated['credit_limit'],
                'credit_days'  => $validated['credit_days'],
                'opening_date' => $validated['opening_date'],
                'remarks'      => $validated['remarks'] ?? null,
                'address'      => $validated['address'] ?? null,
                'contact_no'   => $validated['contact_no'] ?? null,
                'updated_by'   => auth()->id(),
            ]);

            Log::info('[COA] Account updated', ['id' => $id, 'user' => auth()->id()]);

            return redirect()->route('coa.index')
                ->with('success', 'Account updated successfully.');

        } catch (\Exception $e) {
            Log::error('[COA] Update error', ['message' => $e->getMessage()]);
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $account = ChartOfAccounts::with('subHeadOfAccount')->findOrFail($id);
        return response()->json($account);
    }

    public function destroy($id)
    {
        try {
            $account = ChartOfAccounts::findOrFail($id);

            // Guard: block deletion of core system accounts
            $systemCodes = [
                '101001', '101002',         // cash
                '102001', '102002',         // bank
                '104001', '104002',         // stock
                '301001',                   // owner equity
                '401001',                   // sales revenue
                '501001',                   // COGS
            ];

            if (in_array($account->account_code, $systemCodes)) {
                return redirect()->back()
                    ->with('error', 'System account "' . $account->name . '" cannot be deleted.');
            }

            $account->delete();

            return redirect()->route('coa.index')
                ->with('success', 'Account deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }
}