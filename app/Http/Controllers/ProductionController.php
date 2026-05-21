<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\ProductionFgItem;
use App\Models\ProductCategory;
use App\Models\Vendor;
use App\Models\Product;
use App\Models\MeasurementUnit;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseOrderReceivingItem;
use App\Traits\PostsAccountingEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProductionController extends Controller
{
    use PostsAccountingEntries;

    // ── Index ─────────────────────────────────────────────────────────
    public function index()
    {
        $productions = Production::with([
            'vendor',
            'category',
            'rawDetails',
            'fgItems',
            'receivings.details',
        ])->latest()->get();

        return view('production.index', compact('productions'));
    }

    // ── Create ────────────────────────────────────────────────────────
    public function create()
    {
        [$vendors, $categories, $units, $rawProducts, $vendorFgProducts] = $this->formData();

        return view('production.create', compact(
            'vendors', 'categories', 'units', 'rawProducts', 'vendorFgProducts'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->validateProduction($request);

        DB::beginTransaction();
        try {
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $file->store('productions', 'public');
                }
            }

            $production = Production::create([
                'vendor_id'       => $request->vendor_id,
                'category_id'     => $request->category_id,
                'order_date'      => $request->order_date,
                'production_type' => $request->production_type,
                'remarks'         => $request->remarks,
                'attachments'     => $attachments,
                'created_by'      => Auth::id(),
            ]);

            // ── Save raw material rows ────────────────────────────────
            foreach ($request->raw_items ?? [] as $row) {
                if (empty($row['product_id'])) continue;
                $production->rawDetails()->create([
                    'product_id'   => $row['product_id'],
                    'variation_id' => $row['variation_id'] ?? null,
                    'invoice_id'   => $row['invoice_id']   ?? null,
                    'unit'         => $row['unit'],
                    'qty'          => $row['qty'],
                    'rate'         => $row['rate'],
                    'desc'         => $row['desc']         ?? null,
                ]);
            }

            // ── Save FG items we are ordering ─────────────────────────
            foreach ($request->fg_items ?? [] as $row) {
                if (empty($row['product_id'])) continue;
                $production->fgItems()->create([
                    'product_id'          => $row['product_id'],
                    'variation_id'        => $row['variation_id'] ?? null,
                    'qty'                 => $row['qty'],
                    'manufacturing_rate'  => $row['manufacturing_rate'],
                    'desc'                => $row['desc'] ?? null,
                ]);
            }

            // ── Post accounting entries ───────────────────────────────
            $production->loadMissing(['rawDetails', 'fgItems']);
            $this->postProductionEntries($production);

            DB::commit();
            Log::info('[Production] Created', ['id' => $production->id]);

            return redirect()->route('production.index')
                ->with('success', 'Production order #' . $production->id . ' created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Production] Store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to create: ' . $e->getMessage());
        }
    }

    // ── Edit ──────────────────────────────────────────────────────────
    public function edit($id)
    {
        $production = Production::with([
            'rawDetails.product',
            'rawDetails.variation',
            'rawDetails.measurementUnit',
            'fgItems.product.variations',
            'fgItems.variation',
        ])->findOrFail($id);

        [$vendors, $categories, $units, $rawProducts, $vendorFgProducts] = $this->formData();

        return view('production.edit', compact(
            'production', 'vendors', 'categories', 'units', 'rawProducts', 'vendorFgProducts'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $this->validateProduction($request);

        DB::beginTransaction();
        try {
            $production  = Production::findOrFail($id);
            $attachments = $production->attachments ?? [];

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $attachments[] = $file->store('productions', 'public');
                }
            }

            $production->update([
                'vendor_id'       => $request->vendor_id,
                'category_id'     => $request->category_id,
                'order_date'      => $request->order_date,
                'production_type' => $request->production_type,
                'remarks'         => $request->remarks,
                'attachments'     => $attachments,
            ]);

            $production->rawDetails()->delete();
            foreach ($request->raw_items ?? [] as $row) {
                if (empty($row['product_id'])) continue;
                $production->rawDetails()->create([
                    'product_id'   => $row['product_id'],
                    'variation_id' => $row['variation_id'] ?? null,
                    'invoice_id'   => $row['invoice_id']   ?? null,
                    'unit'         => $row['unit'],
                    'qty'          => $row['qty'],
                    'rate'         => $row['rate'],
                    'desc'         => $row['desc']         ?? null,
                ]);
            }

            $production->fgItems()->delete();
            foreach ($request->fg_items ?? [] as $row) {
                if (empty($row['product_id'])) continue;
                $production->fgItems()->create([
                    'product_id'         => $row['product_id'],
                    'variation_id'       => $row['variation_id'] ?? null,
                    'qty'                => $row['qty'],
                    'manufacturing_rate' => $row['manufacturing_rate'],
                    'desc'               => $row['desc'] ?? null,
                ]);
            }

            $production->loadMissing(['rawDetails', 'fgItems']);
            $this->postProductionEntries($production); // syncVoucherEntries auto-wipes old

            DB::commit();
            Log::info('[Production] Updated', ['id' => $id]);

            return redirect()->route('production.index')
                ->with('success', 'Production order #' . $production->id . ' updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Production] Update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update: ' . $e->getMessage());
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $production = Production::findOrFail($id);
            $this->deleteVoucherEntries($production);
            $production->rawDetails()->delete();
            $production->fgItems()->delete();
            $production->delete();
            DB::commit();

            return redirect()->route('production.index')
                ->with('success', 'Production order deleted and entries reversed.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Production] Destroy failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    // ── Print (TCPDF) ─────────────────────────────────────────────────
    public function print($id)
    {
        $production = Production::with([
            'vendor',
            'category',
            'rawDetails.product.measurementUnit',
            'rawDetails.variation',
            'fgItems.product',
            'fgItems.variation',
        ])->findOrFail($id);

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetAuthor('BillTrix');
        $pdf->SetTitle('Production #' . $production->id);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        $logoPath = public_path('assets/img/tgm-logo.webp');
        if (file_exists($logoPath)) $pdf->Image($logoPath, 10, 12, 30);

        $pdf->SetXY(130, 12);
        $pdf->writeHTML('
            <table border="1" cellpadding="4" style="font-size:10px;line-height:14px;border-collapse:collapse;">
                <tr><td><b>Order #</b></td><td>#' . $production->id . '</td></tr>
                <tr><td><b>Date</b></td><td>' . Carbon::parse($production->order_date)->format('d/m/Y') . '</td></tr>
                <tr><td><b>Type</b></td><td>' . ucwords(str_replace('_', ' ', $production->production_type)) . '</td></tr>
                <tr><td><b>Vendor</b></td><td>' . ($production->vendor->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        $pdf->Line(60, 52.25, 200, 52.25);
        $pdf->SetXY(10, 48);
        $pdf->SetFillColor(23, 54, 93);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(55, 8, 'Production Order', 0, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(4);

        // ── Raw Material section ──────────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'Raw Material Issued', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 8);

        $rawHtml = '
        <table border="0.3" cellpadding="3" style="text-align:center;font-size:9px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="5%">#</th><th width="28%">Item</th><th width="18%">Variation</th>
                <th width="14%">Invoice</th><th width="15%">Qty</th>
                <th width="10%">Rate</th><th width="10%">Total</th>
            </tr>';
        $rawTotal = 0;
        foreach ($production->rawDetails as $i => $d) {
            $amt = $d->qty * $d->rate; $rawTotal += $amt;
            $rawHtml .= '<tr>
                <td>' . ($i+1) . '</td>
                <td align="left">' . ($d->product->name ?? '—') . '</td>
                <td>' . ($d->variation->sku ?? '—') . '</td>
                <td>' . ($d->invoice_id ? 'PUR-' . str_pad($d->invoice_id, 5, '0', STR_PAD_LEFT) : '—') . '</td>
                <td>' . number_format($d->qty, 2) . ' ' . ($d->measurementUnit->shortcode ?? '') . '</td>
                <td align="right">' . number_format($d->rate, 2) . '</td>
                <td align="right">' . number_format($amt, 2) . '</td>
            </tr>';
        }
        $rawHtml .= '<tr style="background-color:#f5f5f5;font-weight:bold;">
            <td colspan="6" align="right">Total Raw Value</td>
            <td align="right">PKR ' . number_format($rawTotal, 2) . '</td>
        </tr></table>';
        $pdf->writeHTML($rawHtml, true, false, true, false, '');
        $pdf->Ln(4);

        // ── FG items ordered ──────────────────────────────────────────
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(0, 5, 'Finished Goods Ordered', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 8);

        $fgHtml = '
        <table border="0.3" cellpadding="3" style="text-align:center;font-size:9px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="5%">#</th><th width="35%">Product</th><th width="20%">Variation</th>
                <th width="15%">Qty</th><th width="13%">Mfg Rate</th><th width="12%">Total</th>
            </tr>';
        $fgTotal = 0;
        foreach ($production->fgItems as $i => $f) {
            $amt = $f->qty * $f->manufacturing_rate; $fgTotal += $amt;
            $fgHtml .= '<tr>
                <td>' . ($i+1) . '</td>
                <td align="left">' . ($f->product->name ?? '—') . '</td>
                <td>' . ($f->variation->sku ?? '—') . '</td>
                <td>' . number_format($f->qty, 2) . '</td>
                <td align="right">' . number_format($f->manufacturing_rate, 2) . '</td>
                <td align="right">' . number_format($amt, 2) . '</td>
            </tr>';
        }
        $fgHtml .= '<tr style="background-color:#f5f5f5;font-weight:bold;">
            <td colspan="5" align="right">Total CMT Value</td>
            <td align="right">PKR ' . number_format($fgTotal, 2) . '</td>
        </tr></table>';
        $pdf->writeHTML($fgHtml, true, false, true, false, '');

        if (!empty($production->remarks)) {
            $pdf->Ln(3);
            $pdf->writeHTML('<b>Remarks:</b> ' . htmlspecialchars($production->remarks), true, false, true, false, '');
        }

        $pdf->Ln(20);
        $y = $pdf->GetY();
        $pdf->Line(28, $y, 68, $y); $pdf->Line(130, $y, 170, $y);
        $pdf->SetXY(28,  $y+2); $pdf->Cell(40, 6, 'Issued By',     0, 0, 'C');
        $pdf->SetXY(130, $y+2); $pdf->Cell(40, 6, 'Authorized By', 0, 0, 'C');

        return $pdf->Output('production_' . $production->id . '.pdf', 'I');
    }

    // ── Gate Pass ─────────────────────────────────────────────────────
    public function printGatepass($id)
    {
        $production = Production::with([
            'vendor',
            'rawDetails.product.measurementUnit',
            'rawDetails.variation',
        ])->findOrFail($id);

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetTitle('Gate Pass #' . $production->id);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        $logoPath = public_path('assets/img/tgm-logo.webp');
        if (file_exists($logoPath)) $pdf->Image($logoPath, 10, 12, 30);

        $pdf->SetXY(130, 12);
        $pdf->writeHTML('
            <table border="1" cellpadding="4" style="font-size:10px;border-collapse:collapse;">
                <tr><td><b>Gate Pass #</b></td><td>#' . $production->id . '</td></tr>
                <tr><td><b>Date</b></td><td>' . Carbon::parse($production->order_date)->format('d/m/Y') . '</td></tr>
                <tr><td><b>Vendor</b></td><td>' . ($production->vendor->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        $pdf->Line(60, 52.25, 200, 52.25);
        $pdf->SetXY(10, 48);
        $pdf->SetFillColor(23, 54, 93);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(55, 8, 'Gate Pass', 0, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);

        $html = '
        <table border="0.3" cellpadding="4" style="text-align:center;font-size:10px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="6%">#</th><th width="35%">Item</th>
                <th width="23%">Variation</th><th width="18%">Qty</th><th width="18%">Desc</th>
            </tr>';
        foreach ($production->rawDetails as $i => $d) {
            $html .= '<tr>
                <td>' . ($i+1) . '</td>
                <td align="left">' . ($d->product->name ?? '—') . '</td>
                <td>' . ($d->variation->sku ?? '—') . '</td>
                <td>' . number_format($d->qty, 2) . ' ' . ($d->measurementUnit->shortcode ?? '') . '</td>
                <td>' . ($d->desc ?? '—') . '</td>
            </tr>';
        }
        $html .= '</table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Ln(20);
        $y = $pdf->GetY();
        $pdf->Line(28, $y, 68, $y); $pdf->Line(130, $y, 170, $y);
        $pdf->SetXY(28,  $y+2); $pdf->Cell(40, 6, 'Issued By',   0, 0, 'C');
        $pdf->SetXY(130, $y+2); $pdf->Cell(40, 6, 'Received By', 0, 0, 'C');

        return $pdf->Output('gatepass_' . $production->id . '.pdf', 'I');
    }

    // ── Summary PDF ───────────────────────────────────────────────────
    public function summary($id)
    {
        $production = Production::with([
            'vendor',
            'rawDetails.product.measurementUnit',
            'fgItems.product',
            'receivings.details.product',
        ])->findOrFail($id);

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetTitle('Summary #' . $production->id);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        $rawTotal    = $production->rawDetails->sum(fn($d) => $d->qty * $d->rate);
        $rawQty      = $production->rawDetails->sum('qty');
        $fgOrdered   = $production->fgItems->sum('qty');
        $fgReceived  = $production->receivings->flatMap->details->sum('received_qty');
        $cmtTotal    = $production->receivings->flatMap->details->sum(fn($d) => $d->received_qty * ($d->manufacturing_rate ?? 0));

        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->Cell(0, 10, 'Production Summary — Order #' . $production->id, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 9);

        $html = '
        <table border="0.3" cellpadding="4" style="font-size:10px;">
            <tr style="background-color:#f0f4f8;"><td width="30%"><b>Vendor</b></td><td>' . ($production->vendor->name ?? '—') . '</td></tr>
            <tr><td><b>Date</b></td><td>' . Carbon::parse($production->order_date)->format('d/m/Y') . '</td></tr>
            <tr style="background-color:#f0f4f8;"><td><b>Type</b></td><td>' . ucwords(str_replace('_', ' ', $production->production_type)) . '</td></tr>
            <tr><td><b>Total Raw Issued</b></td><td>' . number_format($rawQty, 2) . ' (PKR ' . number_format($rawTotal, 2) . ')</td></tr>
            <tr style="background-color:#f0f4f8;"><td><b>FG Ordered</b></td><td>' . number_format($fgOrdered, 2) . ' pcs</td></tr>
            <tr><td><b>FG Received</b></td><td>' . number_format($fgReceived, 2) . ' pcs</td></tr>
            <tr style="background-color:#f0f4f8;"><td><b>Total CMT Cost</b></td><td>PKR ' . number_format($cmtTotal, 2) . '</td></tr>
        </table>';
        $pdf->writeHTML($html, true, false, true, false, '');

        return $pdf->Output('summary_' . $production->id . '.pdf', 'I');
    }

    // ── AJAX: stock for a product ─────────────────────────────────────
    public function getProductProductions(Request $request, $productId)
    {
        try {
            $variationId = $request->get('variation_id');
            $query = ProductionDetail::with('production')->where('product_id', $productId);
            if ($variationId) $query->where('variation_id', $variationId);
            else $query->whereNull('variation_id');

            return response()->json($query->get()->map(fn($d) => [
                'id'   => $d->production_id,
                'rate' => $d->rate,
            ]));
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error'], 500);
        }
    }

    // ── AJAX: available stock for a raw product ───────────────────────
    public function getRawStock(Request $request)
    {
        $productId = $request->product_id;

        $fromInvoice = PurchaseInvoiceItem::where('item_id', $productId)->sum('quantity');
        $fromGrn     = PurchaseOrderReceivingItem::where('product_id', $productId)->sum('quantity');
        $issued      = ProductionDetail::where('product_id', $productId)->sum('qty');

        $stock = ($fromInvoice + $fromGrn) - $issued;

        return response()->json(['stock' => round($stock, 2)]);
    }

    // ── AJAX: available stock for a FG product ────────────────────────
    public function getFgStock(Request $request)
    {
        $productId   = $request->product_id;
        $variationId = $request->variation_id;

        // FG comes in via production receivings
        $received = \App\Models\ProductionReceivingDetail::where('product_id', $productId)
            ->when($variationId, fn($q) => $q->where('variation_id', $variationId))
            ->sum('received_qty');

        // FG goes out via sale invoices (placeholder — 0 until sale module built)
        $sold = 0;

        return response()->json(['stock' => round($received - $sold, 2)]);
    }

    // ══════════════════════════════════════════════════════════════════
    //  PRIVATE
    // ══════════════════════════════════════════════════════════════════

    private function formData(): array
    {
        $vendors    = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();
        $units      = MeasurementUnit::orderBy('name')->get();

        // Raw materials — item_type = 'raw'
        $rawProducts = Product::with(['variations', 'measurementUnit'])
            ->where('is_active', true)
            ->where('item_type', 'raw')
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'      => $p->id,
                'name'    => $p->name,
                'sku'     => $p->sku ?? '',
                'unit_id' => $p->measurementUnit->id ?? null,
                'unit'    => $p->measurementUnit->shortcode ?? '',
            ])->values();

        // FG products linked to each vendor (item_type = 'fg')
        // Keyed by vendor_id → [product array]
        $vendorFgProducts = Vendor::whereHas('products', fn($q) => $q->where('item_type', 'fg'))
            ->with(['products' => fn($q) => $q->where('item_type', 'fg')
                ->where('is_active', true)
                ->with('variations')])
            ->get()
            ->mapWithKeys(fn($v) => [
                $v->id => $v->products->map(fn($p) => [
                    'id'         => $p->id,
                    'name'       => $p->name,
                    'sku'        => $p->sku ?? '',
                    'variations' => $p->variations->map(fn($var) => [
                        'id'  => $var->id,
                        'sku' => $var->sku,
                    ])->values(),
                ])->values(),
            ]);

        return [$vendors, $categories, $units, $rawProducts, $vendorFgProducts];
    }

    private function validateProduction(Request $request): void
    {
        $request->validate([
            'vendor_id'                      => 'required|exists:vendors,id',
            'category_id'                    => 'nullable|exists:product_categories,id',
            'order_date'                     => 'required|date',
            'production_type'                => 'required|in:cmt,sell_raw',
            'remarks'                        => 'nullable|string|max:2000',
            'attachments.*'                  => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:4096',
            // Raw items
            'raw_items'                      => 'required|array|min:1',
            'raw_items.*.product_id'         => 'required|exists:products,id',
            'raw_items.*.variation_id'       => 'nullable|exists:product_variations,id',
            'raw_items.*.invoice_id'         => 'nullable|exists:purchase_invoices,id',
            'raw_items.*.unit'               => 'required|exists:measurement_units,id',
            'raw_items.*.qty'                => 'required|numeric|min:0.01',
            'raw_items.*.rate'               => 'required|numeric|min:0',
            'raw_items.*.desc'               => 'nullable|string|max:500',
            // FG items (only for CMT)
            'fg_items.*.product_id'          => 'nullable|exists:products,id',
            'fg_items.*.variation_id'        => 'nullable|exists:product_variations,id',
            'fg_items.*.qty'                 => 'nullable|numeric|min:0.01',
            'fg_items.*.manufacturing_rate'  => 'nullable|numeric|min:0',
            'fg_items.*.desc'                => 'nullable|string|max:500',
        ]);
    }

    /**
     * Accounting entries on production ORDER creation:
     *
     * CMT (give raw for stitching):
     *   DR  WIP Account  104003   ← raw moves to WIP
     *   CR  Raw Stock    104002   ← raw leaves inventory
     *   (vendor ledger NOT hit here — that happens on FG receiving)
     *
     * Sell Raw (sell raw to manufacturer):
     *   DR  Vendor (receivable)   ← vendor owes us money
     *   CR  Raw Stock    104002   ← raw leaves inventory
     */
    private function postProductionEntries(Production $production): void
    {
        $rawTotal = $production->rawDetails->sum(fn($d) => $d->qty * $d->rate);
        if ($rawTotal <= 0) return;

        if ($production->production_type === 'sell_raw') {
            // Sell raw → vendor receivable
            $this->syncVoucherEntries($production, 'production', $production->order_date, [[
                'dr_id'   => $production->vendor_id,
                'cr'      => '104002',
                'amount'  => $rawTotal,
                'remarks' => 'Raw material sold — Production #' . $production->id,
            ]]);
        } else {
            // CMT → WIP
            $this->syncVoucherEntries($production, 'production', $production->order_date, [[
                'dr'      => '104003',
                'cr'      => '104002',
                'amount'  => $rawTotal,
                'remarks' => 'Raw issued for CMT — Production #' . $production->id,
            ]]);
        }

        Log::info('[Production] Accounting synced', [
            'id'    => $production->id,
            'type'  => $production->production_type,
            'total' => $rawTotal,
        ]);
    }
}