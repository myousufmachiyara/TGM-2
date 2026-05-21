<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionReceiving;
use App\Models\ProductionReceivingDetail;
use App\Models\Vendor;
use App\Traits\PostsAccountingEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProductionReceivingController extends Controller
{
    use PostsAccountingEntries;

    // ── Index ─────────────────────────────────────────────────────────
    public function index()
    {
        $receivings = ProductionReceiving::with([
            'vendor',
            'production',
            'details.product',
            'details.variation',
        ])->latest()->get();

        return view('production_receiving.index', compact('receivings'));
    }

    // ── Create ────────────────────────────────────────────────────────
    public function create(Request $request)
    {
        $selectedProductionId = $request->query('id');
        $vendors     = Vendor::where('is_active', true)->orderBy('name')->get();
        $productions = Production::with([
            'vendor',
            'fgItems.product.variations',
            'fgItems.variation',
        ])->latest()->get();

        // If a production order is pre-selected, load its FG items
        $selectedProduction = $selectedProductionId
            ? $productions->firstWhere('id', $selectedProductionId)
            : null;

        return view('production_receiving.create', compact(
            'vendors', 'productions', 'selectedProductionId', 'selectedProduction'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'production_id'                     => 'nullable|exists:productions,id',
            'vendor_id'                         => 'required|exists:vendors,id',
            'rec_date'                          => 'required|date',
            'item_details'                      => 'required|array|min:1',
            'item_details.*.product_id'         => 'required|exists:products,id',
            'item_details.*.variation_id'       => 'nullable|exists:product_variations,id',
            'item_details.*.received_qty'       => 'required|numeric|min:0.01',
            'item_details.*.manufacturing_cost' => 'required|numeric|min:0',
            'item_details.*.remarks'            => 'nullable|string|max:500',
            'convance_charges'                  => 'nullable|numeric|min:0',
            'bill_discount'                     => 'nullable|numeric|min:0',
        ]);

        $hasItems = collect($request->item_details)
            ->filter(fn($d) => !empty($d['product_id']) && (float)$d['received_qty'] > 0)
            ->isNotEmpty();

        if (!$hasItems) {
            return back()->withInput()->with('error', 'Enter at least one item with quantity > 0.');
        }

        DB::beginTransaction();
        try {
            $receiving = ProductionReceiving::create([
                'production_id'    => $request->production_id ?: null,
                'vendor_id'        => $request->vendor_id,
                'rec_date'         => $request->rec_date,
                'convance_charges' => $request->convance_charges ?? 0,
                'bill_discount'    => $request->bill_discount    ?? 0,
                'received_by'      => Auth::id(),
            ]);

            foreach ($request->item_details as $detail) {
                if (empty($detail['product_id']) || (float)$detail['received_qty'] <= 0) continue;

                $receiving->details()->create([
                    'product_id'         => $detail['product_id'],
                    'variation_id'       => $detail['variation_id'] ?? null,
                    'manufacturing_cost' => $detail['manufacturing_cost'] ?? 0,
                    'received_qty'       => $detail['received_qty'],
                    'remarks'            => $detail['remarks'] ?? null,
                ]);
            }

            $receiving->load('details');
            $this->postReceivingEntries($receiving);

            DB::commit();
            Log::info('[ProdReceiving] Created', ['id' => $receiving->id, 'grn' => $receiving->grn_no]);

            return redirect()->route('production_receiving.index')
                ->with('success', 'Production Receiving ' . $receiving->grn_no . ' saved successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[ProdReceiving] Store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to save: ' . $e->getMessage());
        }
    }

    // ── Edit ──────────────────────────────────────────────────────────
    public function edit($id)
    {
        $receiving = ProductionReceiving::with([
            'details.product.variations',
            'details.variation',
        ])->findOrFail($id);

        $vendors     = Vendor::where('is_active', true)->orderBy('name')->get();
        $productions = Production::with([
            'vendor',
            'fgItems.product.variations',
            'fgItems.variation',
        ])->latest()->get();

        $selectedProduction = $receiving->production_id
            ? $productions->firstWhere('id', $receiving->production_id)
            : null;

        return view('production_receiving.edit', compact(
            'receiving', 'vendors', 'productions', 'selectedProduction'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $request->validate([
            'production_id'                     => 'nullable|exists:productions,id',
            'vendor_id'                         => 'required|exists:vendors,id',
            'rec_date'                          => 'required|date',
            'item_details'                      => 'required|array|min:1',
            'item_details.*.product_id'         => 'required|exists:products,id',
            'item_details.*.variation_id'       => 'nullable|exists:product_variations,id',
            'item_details.*.received_qty'       => 'required|numeric|min:0.01',
            'item_details.*.manufacturing_cost' => 'required|numeric|min:0',
            'item_details.*.remarks'            => 'nullable|string|max:500',
            'convance_charges'                  => 'nullable|numeric|min:0',
            'bill_discount'                     => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $receiving = ProductionReceiving::findOrFail($id);
            $receiving->update([
                'production_id'    => $request->production_id ?: null,
                'vendor_id'        => $request->vendor_id,
                'rec_date'         => $request->rec_date,
                'convance_charges' => $request->convance_charges ?? 0,
                'bill_discount'    => $request->bill_discount    ?? 0,
            ]);

            $receiving->details()->delete();
            foreach ($request->item_details as $detail) {
                if (empty($detail['product_id']) || (float)$detail['received_qty'] <= 0) continue;
                $receiving->details()->create([
                    'product_id'         => $detail['product_id'],
                    'variation_id'       => $detail['variation_id'] ?? null,
                    'manufacturing_cost' => $detail['manufacturing_cost'] ?? 0,
                    'received_qty'       => $detail['received_qty'],
                    'remarks'            => $detail['remarks'] ?? null,
                ]);
            }

            $receiving->load('details');
            $this->postReceivingEntries($receiving); // syncVoucherEntries auto-wipes old

            DB::commit();
            Log::info('[ProdReceiving] Updated', ['id' => $id]);

            return redirect()->route('production_receiving.index')
                ->with('success', 'Production Receiving updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[ProdReceiving] Update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $receiving = ProductionReceiving::findOrFail($id);
            $this->deleteVoucherEntries($receiving);
            $receiving->details()->delete();
            $receiving->delete();
            DB::commit();

            return redirect()->route('production_receiving.index')
                ->with('success', 'Production Receiving deleted and entries reversed.');

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    // ── Print (TCPDF) ─────────────────────────────────────────────────
    public function print($id)
    {
        $receiving = ProductionReceiving::with([
            'vendor',
            'production',
            'details.product.measurementUnit',
            'details.variation',
        ])->findOrFail($id);

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetAuthor('BillTrix');
        $pdf->SetTitle($receiving->grn_no);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        $logoPath = public_path('assets/img/tgm-logo.webp');
        if (file_exists($logoPath)) $pdf->Image($logoPath, 10, 12, 60);

        $pdf->SetXY(130, 12);
        $pdf->writeHTML('
            <table border="1" cellpadding="4" style="font-size:10px;line-height:14px;border-collapse:collapse;">
                <tr><td><b>GRN #</b></td><td>' . $receiving->grn_no . '</td></tr>
                <tr><td><b>Date</b></td><td>' . Carbon::parse($receiving->rec_date)->format('d/m/Y') . '</td></tr>
                <tr><td><b>Production #</b></td><td>' . ($receiving->production_id ? '#'.$receiving->production_id : '—') . '</td></tr>
                <tr><td><b>Vendor</b></td><td>' . ($receiving->vendor->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        $pdf->Line(60, 52.25, 200, 52.25);
        $pdf->SetXY(10, 48);
        $pdf->SetFillColor(23, 54, 93);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(60, 8, 'Production Receiving (GRN)', 0, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);

        $html = '
        <table border="0.3" cellpadding="4" style="text-align:center;font-size:10px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="5%">#</th>
                <th width="28%">Item</th>
                <th width="20%">Variation</th>
                <th width="13%">Mfg Cost</th>
                <th width="14%">Qty</th>
                <th width="12%">Total</th>
                <th width="8%">Remarks</th>
            </tr>';

        $count = 0; $subTotal = 0;
        foreach ($receiving->details as $detail) {
            $count++;
            $rowTotal  = $detail->manufacturing_cost * $detail->received_qty;
            $subTotal += $rowTotal;
            $html .= '<tr>
                <td>' . $count . '</td>
                <td align="left">' . ($detail->product->name ?? '—') . '</td>
                <td>' . ($detail->variation->sku ?? '—') . '</td>
                <td align="right">' . number_format($detail->manufacturing_cost, 2) . '</td>
                <td>' . number_format($detail->received_qty, 2) . ' ' . ($detail->product?->measurementUnit?->shortcode ?? '') . '</td>
                <td align="right">' . number_format($rowTotal, 2) . '</td>
                <td>' . ($detail->remarks ?? '—') . '</td>
            </tr>';
        }

        $conv = (float)($receiving->convance_charges ?? 0);
        $disc = (float)($receiving->bill_discount    ?? 0);
        $net  = $subTotal + $conv - $disc;

        $html .= '<tr><td colspan="5" align="right"><b>Sub Total</b></td><td align="right"><b>' . number_format($subTotal, 2) . '</b></td><td></td></tr>';
        if ($conv > 0) $html .= '<tr><td colspan="5" align="right">Conveyance</td><td align="right">' . number_format($conv, 2) . '</td><td></td></tr>';
        if ($disc > 0) $html .= '<tr><td colspan="5" align="right">Discount</td><td align="right">(' . number_format($disc, 2) . ')</td><td></td></tr>';
        $html .= '<tr style="background-color:#f5f5f5;"><td colspan="5" align="right"><b>Net Total</b></td><td align="right"><b>' . number_format($net, 2) . '</b></td><td></td></tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(20);
        $y = $pdf->GetY();
        $pdf->Line(28, $y, 68, $y); $pdf->Line(130, $y, 170, $y);
        $pdf->SetXY(28,  $y+2); $pdf->Cell(40, 6, 'Received By',   0, 0, 'C');
        $pdf->SetXY(130, $y+2); $pdf->Cell(40, 6, 'Authorized By', 0, 0, 'C');

        return $pdf->Output($receiving->grn_no . '.pdf', 'I');
    }

    // ── AJAX: get FG items for a production order ─────────────────────
    public function getProductionFgItems($productionId)
    {
        $production = Production::with([
            'vendor',
            'fgItems.product.variations',
            'fgItems.variation',
        ])->findOrFail($productionId);

        return response()->json([
            'vendor_id'   => $production->vendor_id,
            'vendor_name' => $production->vendor->name ?? '—',
            'fg_items'    => $production->fgItems->map(fn($f) => [
                'product_id'         => $f->product_id,
                'product_name'       => $f->product->name ?? '—',
                'variation_id'       => $f->variation_id,
                'variation_sku'      => $f->variation->sku ?? null,
                'qty_ordered'        => $f->qty,
                'manufacturing_rate' => $f->manufacturing_rate,
                'variations'         => $f->product->variations->map(fn($v) => [
                    'id'  => $v->id,
                    'sku' => $v->sku,
                ])->values(),
            ])->values(),
        ]);
    }

    // ══════════════════════════════════════════════════════════════════
    //  PRIVATE — Accounting
    // ══════════════════════════════════════════════════════════════════

    /**
     * On CMT receiving:
     *   DR  Finished Goods Stock  104004   ← FG enters inventory
     *   CR  WIP Account           104003   ← clears WIP (raw was moved here on issue)
     *   DR  WIP Account           104003   ← manufacturing cost portion
     *   CR  Vendor (AP)                    ← CMT bill payable to vendor
     *
     * Simplified to two entries:
     *   DR  FG Stock   104004   amount = mfg_cost × qty
     *   CR  Vendor               amount = mfg_cost × qty  ← CMT payable
     *   DR  Conveyance 502001    CR Vendor
     *   DR  Vendor               CR Discount 402001
     */
    private function postReceivingEntries(ProductionReceiving $receiving): void
    {
        $cmtTotal   = $receiving->details->sum(fn($d) => $d->manufacturing_cost * $d->received_qty);
        $conveyance = (float) ($receiving->convance_charges ?? 0);
        $discount   = (float) ($receiving->bill_discount    ?? 0);

        $this->syncVoucherEntries(
            $receiving,
            'production_receiving',
            $receiving->rec_date,
            [
                // FG inventory increase + CMT payable to vendor
                [
                    'dr'      => '104004',
                    'cr_id'   => $receiving->vendor_id,
                    'amount'  => $cmtTotal,
                    'remarks' => 'FG received — ' . $receiving->grn_no,
                ],
                // Conveyance
                [
                    'dr'      => '502001',
                    'cr_id'   => $receiving->vendor_id,
                    'amount'  => $conveyance,
                    'remarks' => 'Conveyance — ' . $receiving->grn_no,
                ],
                // Discount
                [
                    'dr_id'   => $receiving->vendor_id,
                    'cr'      => '402001',
                    'amount'  => $discount,
                    'remarks' => 'Discount — ' . $receiving->grn_no,
                ],
            ]
        );

        Log::info('[ProdReceiving] Accounting synced', [
            'grn'       => $receiving->grn_no,
            'cmt_total' => $cmtTotal,
            'conveyance'=> $conveyance,
            'discount'  => $discount,
        ]);
    }
}