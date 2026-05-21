<?php

namespace App\Http\Controllers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseInvoiceItem;
use App\Models\Product;
use App\Models\MeasurementUnit;
use App\Models\Vendor;
use App\Traits\PostsAccountingEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseInvoiceController extends Controller
{
    use PostsAccountingEntries;

    // ── Index ─────────────────────────────────────────────────────────
    public function index()
    {
        $invoices = PurchaseInvoice::with(['vendor', 'attachments', 'items'])
            ->latest()
            ->get();

        return view('purchases.index', compact('invoices'));
    }

    // ── Create ────────────────────────────────────────────────────────
    public function create()
    {
        $vendors  = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::with(['variations', 'measurementUnit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $units = MeasurementUnit::orderBy('name')->get();

        $productData = $products->map(fn($p) => [
            'id'      => $p->id,
            'sku'     => $p->sku ?? '',
            'name'    => $p->name,
            'unit_id' => $p->measurementUnit->id ?? null,
        ])->values();

        $vendorProducts = Vendor::whereHas('products')
            ->with('products:id,vendor_id')
            ->get()
            ->mapWithKeys(fn($v) => [
                $v->id => $v->products->pluck('id')->toArray()
            ]);

        return view('purchases.create', compact(
            'vendors', 'products', 'units', 'productData', 'vendorProducts'
        ));
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'invoice_date'         => 'required|date',
            'vendor_id'            => 'required|exists:vendors,id',
            'payment_terms'        => 'nullable|string|max:255',
            'bill_no'              => 'nullable|string|max:100',
            'ref_no'               => 'nullable|string|max:100',
            'remarks'              => 'nullable|string|max:2000',
            'convance_charges'     => 'nullable|numeric|min:0',
            'labour_charges'       => 'nullable|numeric|min:0',
            'bill_discount'        => 'nullable|numeric|min:0',
            'attachments.*'        => 'nullable|file|mimes:jpg,jpeg,png,pdf,zip|max:4096',
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit'         => 'required|exists:measurement_units,id',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.item_remarks' => 'nullable|string|max:500',
        ]);

        $hasItems = collect($request->items)->filter(fn($i) => !empty($i['item_id']))->isNotEmpty();
        if (!$hasItems) {
            return back()->withInput()->with('error', 'Add at least one item.');
        }

        DB::beginTransaction();
        try {
            $invoice = PurchaseInvoice::create([
                'vendor_id'        => $request->vendor_id,
                'invoice_date'     => $request->invoice_date,
                'payment_terms'    => $request->payment_terms,
                'bill_no'          => $request->bill_no,
                'ref_no'           => $request->ref_no,
                'remarks'          => $request->remarks,
                'convance_charges' => $request->convance_charges ?? 0,
                'labour_charges'   => $request->labour_charges   ?? 0,
                'bill_discount'    => $request->bill_discount    ?? 0,
                'created_by'       => Auth::id(),
            ]);

            $this->saveItems($invoice, $request->items ?? []);
            $this->saveAttachments($invoice, $request);

            $invoice->loadMissing('items');
            $this->postInvoiceEntries($invoice);

            DB::commit();
            Log::info('[PurchaseInvoice] Created', [
                'id'         => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ]);

            return redirect()->route('purchase_invoices.index')
                ->with('success', 'Purchase Invoice ' . $invoice->invoice_no . ' created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseInvoice] Store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->withInput()->with('error', 'Failed to save invoice: ' . $e->getMessage());
        }
    }

    // ── Edit ──────────────────────────────────────────────────────────
    public function edit($id)
    {
        $invoice  = PurchaseInvoice::with(['items.product.variations', 'items.measurementUnit', 'items.variation', 'attachments'])->findOrFail($id);
        $vendors  = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::with(['variations', 'measurementUnit'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $units = MeasurementUnit::orderBy('name')->get();

        $productData = $products->map(fn($p) => [
            'id'      => $p->id,
            'sku'     => $p->sku ?? '',
            'name'    => $p->name,
            'unit_id' => $p->measurementUnit->id ?? null,
        ])->values();

        $vendorProducts = Vendor::whereHas('products')
            ->with('products:id,vendor_id')
            ->get()
            ->mapWithKeys(fn($v) => [
                $v->id => $v->products->pluck('id')->toArray()
            ]);

        return view('purchases.edit', compact(
            'invoice', 'vendors', 'products', 'units', 'productData', 'vendorProducts'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $request->validate([
            'invoice_date'         => 'required|date',
            'vendor_id'            => 'required|exists:vendors,id',
            'payment_terms'        => 'nullable|string|max:255',
            'bill_no'              => 'nullable|string|max:100',
            'ref_no'               => 'nullable|string|max:100',
            'remarks'              => 'nullable|string|max:2000',
            'convance_charges'     => 'nullable|numeric|min:0',
            'labour_charges'       => 'nullable|numeric|min:0',
            'bill_discount'        => 'nullable|numeric|min:0',
            'attachments.*'        => 'nullable|file|mimes:jpg,jpeg,png,pdf,zip|max:4096',
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit'         => 'required|exists:measurement_units,id',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.item_remarks' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $invoice = PurchaseInvoice::findOrFail($id);

            $invoice->update([
                'vendor_id'        => $request->vendor_id,
                'invoice_date'     => $request->invoice_date,
                'payment_terms'    => $request->payment_terms,
                'bill_no'          => $request->bill_no,
                'ref_no'           => $request->ref_no,
                'remarks'          => $request->remarks,
                'convance_charges' => $request->convance_charges ?? 0,
                'labour_charges'   => $request->labour_charges   ?? 0,
                'bill_discount'    => $request->bill_discount    ?? 0,
            ]);

            $invoice->items()->delete();
            $this->saveItems($invoice, $request->items ?? []);
            $this->saveAttachments($invoice, $request);

            // syncVoucherEntries wipes old and rewrites — no manual reverse needed
            $invoice->loadMissing('items');
            $this->postInvoiceEntries($invoice);

            DB::commit();
            Log::info('[PurchaseInvoice] Updated', [
                'id'         => $invoice->id,
                'invoice_no' => $invoice->invoice_no,
            ]);

            return redirect()->route('purchase_invoices.index')
                ->with('success', 'Purchase Invoice ' . $invoice->invoice_no . ' updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseInvoice] Update failed', [
                'id'    => $id,
                'error' => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', 'Failed to update invoice: ' . $e->getMessage());
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $invoice = PurchaseInvoice::with('attachments')->findOrFail($id);

            // Wipe accounting entries first (same as GRN destroy)
            $this->deleteVoucherEntries($invoice);

            foreach ($invoice->attachments as $att) {
                Storage::disk('public')->delete($att->file_path);
            }

            $invoice->delete();
            DB::commit();

            Log::info('[PurchaseInvoice] Deleted', ['id' => $id]);

            return redirect()->route('purchase_invoices.index')
                ->with('success', 'Invoice ' . $invoice->invoice_no . ' deleted and entries reversed.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseInvoice] Delete failed', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete invoice: ' . $e->getMessage());
        }
    }

    // ── Print (TCPDF) ─────────────────────────────────────────────────
    public function print($id)
    {
        $invoice = PurchaseInvoice::with([
            'vendor',
            'items.product',
            'items.variation',
            'items.measurementUnit',
        ])->findOrFail($id);

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetAuthor('BillTrix');
        $pdf->SetTitle($invoice->invoice_no);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        // ── Logo ──────────────────────────────────────────────────────
        $logoPath = public_path('assets/img/tgm-logo.webp');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 12, 30);
        }

        // ── Info box ──────────────────────────────────────────────────
        $pdf->SetXY(130, 12);
        $pdf->writeHTML('
            <table border="1" cellpadding="4" style="font-size:10px;line-height:14px;border-collapse:collapse;">
                <tr><td><b>Invoice #</b></td><td>' . $invoice->invoice_no . '</td></tr>
                <tr><td><b>Date</b></td><td>' . Carbon::parse($invoice->invoice_date)->format('d/m/Y') . '</td></tr>
                <tr><td><b>Bill No</b></td><td>' . ($invoice->bill_no ?? '—') . '</td></tr>
                <tr><td><b>Vendor</b></td><td>' . ($invoice->vendor->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        // ── Badge ─────────────────────────────────────────────────────
        $pdf->Line(60, 52.25, 200, 52.25);
        $pdf->SetXY(10, 48);
        $pdf->SetFillColor(23, 54, 93);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(50, 8, 'Purchase Invoice', 0, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);

        // ── Items table ───────────────────────────────────────────────
        $html = '
        <table border="0.3" cellpadding="4" style="text-align:center;font-size:10px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="5%">#</th>
                <th width="22%">Item</th>
                <th width="20%">Variation</th>
                <th width="18%">Qty</th>
                <th width="12%">Rate</th>
                <th width="13%">Total</th>
            </tr>';

        $count = 0; $subTotal = 0;

        foreach ($invoice->items as $item) {
            $count++;
            $amount    = $item->quantity * $item->price;
            $subTotal += $amount;

            $html .= '
            <tr>
                <td>' . $count . '</td>
                <td align="left">' . ($item->product->name ?? '—') . '</td>
                <td>' . ($item->variation->sku ?? '—') . '</td>
                <td>' . number_format($item->quantity, 2) . ' ' . ($item->measurementUnit->shortcode ?? '') . '</td>
                <td align="right">' . number_format($item->price, 2) . '</td>
                <td align="right">' . number_format($amount, 2) . '</td>
            </tr>';
        }

        $netTotal = $subTotal;

        $html .= '<tr><td colspan="5" align="right"><b>Sub Total</b></td>'
            . '<td align="right"><b>' . number_format($subTotal, 2) . '</b></td></tr>';

        if ($invoice->convance_charges > 0) {
            $netTotal += $invoice->convance_charges;
            $html .= '<tr><td colspan="5" align="right">Conveyance</td>'
                . '<td align="right">' . number_format($invoice->convance_charges, 2) . '</td></tr>';
        }
        if ($invoice->labour_charges > 0) {
            $netTotal += $invoice->labour_charges;
            $html .= '<tr><td colspan="5" align="right">Labour</td>'
                . '<td align="right">' . number_format($invoice->labour_charges, 2) . '</td></tr>';
        }
        if ($invoice->bill_discount > 0) {
            $netTotal -= $invoice->bill_discount;
            $html .= '<tr><td colspan="5" align="right">Discount</td>'
                . '<td align="right">(' . number_format($invoice->bill_discount, 2) . ')</td></tr>';
        }

        $html .= '<tr style="background-color:#f5f5f5;">'
            . '<td colspan="5" align="right"><b>Net Total</b></td>'
            . '<td align="right"><b>' . number_format($netTotal, 2) . '</b></td>'
            . '</tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        if (!empty($invoice->remarks)) {
            $pdf->Ln(3);
            $pdf->writeHTML(
                '<b>Remarks:</b><br><span style="font-size:10px;">' . nl2br(htmlspecialchars($invoice->remarks)) . '</span>',
                true, false, true, false, ''
            );
        }

        // ── Signatures ────────────────────────────────────────────────
        $pdf->Ln(20);
        $y = $pdf->GetY();
        $pdf->Line(28,  $y, 68,  $y);
        $pdf->Line(130, $y, 170, $y);
        $pdf->SetXY(28,  $y + 2); $pdf->Cell(40, 6, 'Received By',   0, 0, 'C');
        $pdf->SetXY(130, $y + 2); $pdf->Cell(40, 6, 'Authorized By', 0, 0, 'C');

        return $pdf->Output('invoice_' . $invoice->invoice_no . '.pdf', 'I');
    }

    // ── AJAX helpers ──────────────────────────────────────────────────

    /**
     * GET /product/{productId}/invoices
     * Called by purchase return form to populate the "Against Invoice" dropdown.
     * Returns all purchase invoices that contain this product, with rate.
     */
    public function getProductInvoices($productId)
    {
        try {
            $invoices = PurchaseInvoice::whereHas('items', fn($q) => $q->where('item_id', $productId))
                ->with([
                    'vendor',
                    'items' => fn($q) => $q->where('item_id', $productId),
                ])
                ->latest()
                ->get();

            return response()->json($invoices->map(function ($inv) {
                $item = $inv->items->first();
                return [
                    'id'         => $inv->id,
                    'invoice_no' => $inv->invoice_no,
                    'number'     => $inv->invoice_no,
                    'vendor'     => $inv->vendor->name ?? '—',
                    'rate'       => $item?->price ?? 0,
                ];
            }));

        } catch (\Throwable $e) {
            Log::error('[PurchaseInvoice] getProductInvoices failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to load invoices'], 500);
        }
    }

    // ══════════════════════════════════════════════════════════════════
    //  PRIVATE — Accounting
    // ══════════════════════════════════════════════════════════════════

    /**
     * Post invoice accounting entries (identical logic to GRN but also
     * handles conveyance, labour and discount lines):
     *
     *   DR  Stock in Hand  104001      ← goods value
     *   DR  Conveyance     502001      ← conveyance charges
     *   DR  Labour         502002      ← labour charges
     *   CR  Vendor (dynamic id)        ← total payable
     *   CR  Purchase Discount 402001   ← discount received (if any)
     *
     * syncVoucherEntries wipes old rows then rewrites — so calling this on
     * update automatically replaces the previous entries without a separate
     * reverse step, identical to the GRN controller.
     */
    private function postInvoiceEntries(PurchaseInvoice $invoice): void
    {
        $itemsTotal = $invoice->items->sum(fn($i) => $i->quantity * $i->price);
        $conveyance = (float) ($invoice->convance_charges ?? 0);
        $labour     = (float) ($invoice->labour_charges   ?? 0);
        $discount   = (float) ($invoice->bill_discount    ?? 0);

        $this->syncVoucherEntries(
            $invoice,
            'purchase_invoice',
            $invoice->invoice_date,
            [
                [
                    'dr'      => '104001',
                    'cr_id'   => $invoice->vendor_id,
                    'amount'  => $itemsTotal,
                    'remarks' => 'Goods received — ' . $invoice->invoice_no,
                ],
                [
                    'dr'      => '502001',
                    'cr_id'   => $invoice->vendor_id,
                    'amount'  => $conveyance,
                    'remarks' => 'Conveyance — ' . $invoice->invoice_no,
                ],
                [
                    'dr'      => '502002',
                    'cr_id'   => $invoice->vendor_id,
                    'amount'  => $labour,
                    'remarks' => 'Labour — ' . $invoice->invoice_no,
                ],
                [
                    'dr_id'   => $invoice->vendor_id,
                    'cr'      => '402001',
                    'amount'  => $discount,
                    'remarks' => 'Purchase discount — ' . $invoice->invoice_no,
                ],
            ]
        );

        Log::info('[PurchaseInvoice] Accounting synced', [
            'invoice_no' => $invoice->invoice_no,
            'items'      => $itemsTotal,
            'conveyance' => $conveyance,
            'labour'     => $labour,
            'discount'   => $discount,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function saveItems(PurchaseInvoice $invoice, array $items): void
    {
        foreach ($items as $row) {
            if (empty($row['item_id'])) continue;

            $invoice->items()->create([
                'item_id'      => $row['item_id'],
                'variation_id' => $row['variation_id'] ?? null,
                'item_name'    => Product::find($row['item_id'])?->name,
                'quantity'     => $row['quantity']     ?? 0,
                'unit'         => $row['unit'],
                'price'        => $row['price']        ?? 0,
                'remarks'      => $row['item_remarks']  ?? null,
            ]);
        }
    }

    private function saveAttachments(PurchaseInvoice $invoice, Request $request): void
    {
        if (!$request->hasFile('attachments')) return;

        foreach ($request->file('attachments') as $file) {
            $invoice->attachments()->create([
                'file_path' => $file->store('purchase_invoices', 'public'),
            ]);
        }
    }
}