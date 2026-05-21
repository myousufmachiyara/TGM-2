<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccounts;
use App\Models\PurFGPO;
use App\Models\FgpoBill;
use App\Models\FgpoBillDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class POBillsController extends Controller
{
    public function index()
    {
    $bills = FgpoBill::with([
        'vendor',
        'details.production'
    ])->get();

    foreach ($bills as $bill) {
        // calculate total
        $bill->calculated_total = $bill->details->sum(function ($d) {
            return ($d->rate ?? 0) * ($d->received_qty ?? 0);
        });

        // collect PO numbers from productions
        $bill->po_numbers = $bill->details
            ->map(fn($d) => $d->production_id ?? null)
            ->filter()
            ->unique()
            ->implode(', ');
    }

    return view('purchasing.fgpo-billing.index', compact('bills'));
    }

    public function create()
    {
        $coa = ChartOfAccounts::all();  // Get all product categories
        $fgpo = PurFGPO::all();  // Get all product categories

        return view('purchasing.fgpo-billing.create', compact('coa', 'fgpo'));
    }

    public function store(Request $request)
    {

        $request->validate([
            'vendor_id' => 'required|exists:chart_of_accounts,id',
            'bill_date' => 'required|date',
            'ref_bill'  => 'nullable|string|max:255',
            'details'   => 'required|array',
        ]);

        try {
            DB::beginTransaction();

            Log::info('Creating new FGPO Bill', [
                'vendor_id' => $request->vendor_id,
                'bill_date' => $request->bill_date,
                'ref_bill'  => $request->ref_bill,
            ]);

            $bill = FgpoBill::create([
                'vendor_id'    => $request->vendor_id,
                'bill_date'    => $request->bill_date,
                'ref_bill_no'  => $request->ref_bill, // make sure matches migration/DB
            ]);

            $totalAmount = 0;

            foreach ($request->details as $poDetail) {
                $productionId   = $poDetail['production_id'];
                $adjustedAmount = $poDetail['adjusted_amount'] ?? 0;

                Log::info('Processing PO detail', [
                    'production_id'   => $productionId,
                    'adjusted_amount' => $adjustedAmount,
                ]);

                if (!empty($poDetail['products'])) {
                    foreach ($poDetail['products'] as $product) {
                        $rate = $product['rate'] ?? 0;
                        $receivedQty  = $product['received_qty'] ?? 0;

                        FgpoBillDetail::create([
                            'bill_id'        => $bill->id,
                            'production_id'  => $productionId,
                            'product_id'     => $product['product_id'],
                            'rate'           => $rate,
                            'received_qty'   => $receivedQty,
                            'adjusted_amount'=> $adjustedAmount, // per PO applied to all rows
                        ]);

                        $totalAmount += ($rate * $receivedQty);

                        Log::debug('Added Bill Detail', [
                            'bill_id'    => $bill->id,
                            'product_id' => $product['product_id'],
                            'rate'       => $rate,
                        ]);
                    }
                }

                // Add adjustment once per PO
                $totalAmount += $adjustedAmount;
            }

            $bill->update([
                'total_amount' => $totalAmount,
            ]);

            Log::info('FGPO Bill stored successfully', [
                'bill_id'      => $bill->id,
                'total_amount' => $totalAmount,
            ]);

            DB::commit();

            return redirect()->route('fgpo-bills.index')->with('success', 'Bill added successfully!');

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error saving FGPO Bill', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return back()->withInput()->withErrors(['error' => 'Error saving Bill: ' . $e->getMessage()]);
        }
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
