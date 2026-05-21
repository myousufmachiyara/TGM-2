<?php

namespace App\Http\Controllers;

use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseInvoice;
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

class PurchaseReturnController extends Controller
{
    use PostsAccountingEntries;

    // ── Index ─────────────────────────────────────────────────────────
    public function index()
    {
        $returns = PurchaseReturn::with(['vendor', 'items'])->latest()->get();
        return view('purchase_returns.index', compact('returns'));
    }

    // ── Create ────────────────────────────────────────────────────────
    public function create()
    {
        [$vendors, $products, $units, $productData, $vendorProducts] = $this->formData();
        return view('purchase_returns.create', compact('vendors', 'products', 'units', 'productData', 'vendorProducts'));
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $this->validateReturn($request);

        $hasItems = collect($request->items)->filter(fn($i) => !empty($i['item_id']))->isNotEmpty();
        if (!$hasItems) {
            return back()->withInput()->with('error', 'Add at least one item.');
        }

        DB::beginTransaction();
        try {
            $return = PurchaseReturn::create([
                'vendor_id'        => $request->vendor_id,
                'return_date'      => $request->return_date,
                'bill_no'          => $request->bill_no,
                'ref_no'           => $request->ref_no,
                'remarks'          => $request->remarks,
                'convance_charges' => $request->convance_charges ?? 0,
                'bill_discount'    => $request->bill_discount    ?? 0,
                'created_by'       => Auth::id(),
            ]);

            $this->saveItems($return, $request->items ?? []);
            $this->saveAttachments($return, $request);

            $return->loadMissing('items');
            $this->postReturnEntries($return);

            DB::commit();
            Log::info('[PurchaseReturn] Created', ['id' => $return->id, 'no' => $return->return_no]);

            return redirect()->route('purchase_return.index')
                ->with('success', 'Purchase Return ' . $return->return_no . ' created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseReturn] Store failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->withInput()->with('error', 'Failed to save return: ' . $e->getMessage());
        }
    }

    // ── Edit ──────────────────────────────────────────────────────────
    public function edit($id)
    {
        $return = PurchaseReturn::with([
            'items.product.variations',
            'items.variation',
            'items.measurementUnit',
            'items.purchaseInvoice',
            'attachments',
        ])->findOrFail($id);

        [$vendors, $products, $units, $productData, $vendorProducts] = $this->formData();
        return view('purchase_returns.edit', compact('return', 'vendors', 'products', 'units', 'productData', 'vendorProducts'));
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $this->validateReturn($request);

        DB::beginTransaction();
        try {
            $return = PurchaseReturn::findOrFail($id);
            $return->update([
                'vendor_id'        => $request->vendor_id,
                'return_date'      => $request->return_date,
                'bill_no'          => $request->bill_no,
                'ref_no'           => $request->ref_no,
                'remarks'          => $request->remarks,
                'convance_charges' => $request->convance_charges ?? 0,
                'bill_discount'    => $request->bill_discount    ?? 0,
            ]);

            $return->items()->delete();
            $this->saveItems($return, $request->items ?? []);
            $this->saveAttachments($return, $request);

            $return->loadMissing('items');
            $this->postReturnEntries($return); // syncVoucherEntries auto-wipes old

            DB::commit();
            Log::info('[PurchaseReturn] Updated', ['id' => $id]);

            return redirect()->route('purchase_return.index')
                ->with('success', 'Purchase Return ' . $return->return_no . ' updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseReturn] Update failed', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to update return: ' . $e->getMessage());
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $return = PurchaseReturn::with('attachments')->findOrFail($id);
            $this->deleteVoucherEntries($return);
            foreach ($return->attachments as $att) {
                Storage::disk('public')->delete($att->file_path);
            }
            $return->delete();
            DB::commit();
            Log::info('[PurchaseReturn] Deleted', ['id' => $id]);
            return redirect()->route('purchase_return.index')
                ->with('success', 'Purchase Return deleted and entries reversed.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseReturn] Destroy failed', ['id' => $id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Failed to delete return: ' . $e->getMessage());
        }
    }

    // ── Print (TCPDF) ─────────────────────────────────────────────────
    public function print($id)
    {
        $return = PurchaseReturn::with([
            'vendor', 'items.product', 'items.variation',
            'items.measurementUnit', 'items.purchaseInvoice',
        ])->findOrFail($id);

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetAuthor('BillTrix');
        $pdf->SetTitle($return->return_no);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        $logoPath = public_path('assets/img/tgm-logo.webp');
        if (file_exists($logoPath)) $pdf->Image($logoPath, 10, 12, 30);

        $pdf->SetXY(130, 12);
        $pdf->writeHTML('
            <table border="1" cellpadding="4" style="font-size:10px;line-height:14px;border-collapse:collapse;">
                <tr><td><b>Return #</b></td><td>' . $return->return_no . '</td></tr>
                <tr><td><b>Date</b></td><td>' . Carbon::parse($return->return_date)->format('d/m/Y') . '</td></tr>
                <tr><td><b>Bill No</b></td><td>' . ($return->bill_no ?? '—') . '</td></tr>
                <tr><td><b>Vendor</b></td><td>' . ($return->vendor->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        $pdf->Line(60, 52.25, 200, 52.25);
        $pdf->SetXY(10, 48);
        $pdf->SetFillColor(23, 54, 93);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(50, 8, 'Purchase Return', 0, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);

        $html = '
        <table border="0.3" cellpadding="4" style="text-align:center;font-size:10px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="5%">#</th>
                <th width="22%">Item</th>
                <th width="17%">Variation</th>
                <th width="14%">Invoice #</th>
                <th width="16%">Qty</th>
                <th width="12%">Rate</th>
                <th width="14%">Total</th>
            </tr>';

        $count = 0; $subTotal = 0;
        foreach ($return->items as $item) {
            $count++;
            $amount    = $item->quantity * $item->price;
            $subTotal += $amount;
            $html .= '<tr>
                <td>' . $count . '</td>
                <td align="left">' . ($item->product->name ?? '—') . '</td>
                <td>' . ($item->variation->sku ?? '—') . '</td>
                <td>' . ($item->purchaseInvoice?->invoice_no ?? '—') . '</td>
                <td>' . number_format($item->quantity, 2) . ' ' . ($item->measurementUnit->shortcode ?? '') . '</td>
                <td align="right">' . number_format($item->price, 2) . '</td>
                <td align="right">' . number_format($amount, 2) . '</td>
            </tr>';
        }

        $netTotal = $subTotal;
        $html .= '<tr><td colspan="6" align="right"><b>Sub Total</b></td><td align="right"><b>' . number_format($subTotal, 2) . '</b></td></tr>';
        if ($return->convance_charges > 0) {
            $netTotal += $return->convance_charges;
            $html .= '<tr><td colspan="6" align="right">Conveyance</td><td align="right">' . number_format($return->convance_charges, 2) . '</td></tr>';
        }
        if ($return->bill_discount > 0) {
            $netTotal -= $return->bill_discount;
            $html .= '<tr><td colspan="6" align="right">Discount</td><td align="right">(' . number_format($return->bill_discount, 2) . ')</td></tr>';
        }
        $html .= '<tr style="background-color:#f5f5f5;"><td colspan="6" align="right"><b>Net Total</b></td><td align="right"><b>' . number_format($netTotal, 2) . '</b></td></tr></table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        if (!empty($return->remarks)) {
            $pdf->Ln(3);
            $pdf->writeHTML('<b>Remarks:</b><br><span style="font-size:10px;">' . nl2br(htmlspecialchars($return->remarks)) . '</span>', true, false, true, false, '');
        }

        $pdf->Ln(20);
        $y = $pdf->GetY();
        $pdf->Line(28, $y, 68, $y); $pdf->Line(130, $y, 170, $y);
        $pdf->SetXY(28,  $y + 2); $pdf->Cell(40, 6, 'Returned By',   0, 0, 'C');
        $pdf->SetXY(130, $y + 2); $pdf->Cell(40, 6, 'Authorized By', 0, 0, 'C');

        return $pdf->Output($return->return_no . '.pdf', 'I');
    }

    // ══════════════════════════════════════════════════════════════════
    //  PRIVATE
    // ══════════════════════════════════════════════════════════════════

    private function formData(): array
    {
        $vendors  = Vendor::where('is_active', true)->orderBy('name')->get();
        $products = Product::with(['variations', 'measurementUnit'])->where('is_active', true)->orderBy('name')->get();
        $units    = MeasurementUnit::orderBy('name')->get();

        $productData = $products->map(fn($p) => [
            'id'      => $p->id,
            'sku'     => $p->sku ?? '',
            'name'    => $p->name,
            'unit_id' => $p->measurementUnit->id ?? null,
        ])->values();

        $vendorProducts = Vendor::whereHas('products')
            ->with('products:id,vendor_id')
            ->get()
            ->mapWithKeys(fn($v) => [$v->id => $v->products->pluck('id')->toArray()]);

        return [$vendors, $products, $units, $productData, $vendorProducts];
    }

    private function validateReturn(Request $request): void
    {
        $request->validate([
            'return_date'          => 'required|date',
            'vendor_id'            => 'required|exists:vendors,id',
            'bill_no'              => 'nullable|string|max:100',
            'ref_no'               => 'nullable|string|max:100',
            'remarks'              => 'nullable|string|max:2000',
            'convance_charges'     => 'nullable|numeric|min:0',
            'bill_discount'        => 'nullable|numeric|min:0',
            'attachments.*'        => 'nullable|file|mimes:jpg,jpeg,png,pdf,zip|max:4096',
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|exists:products,id',
            'items.*.variation_id' => 'nullable|exists:product_variations,id',
            'items.*.invoice_id'   => 'nullable|exists:purchase_invoices,id',
            'items.*.quantity'     => 'required|numeric|min:0.01',
            'items.*.unit'         => 'required|exists:measurement_units,id',
            'items.*.price'        => 'required|numeric|min:0',
            'items.*.item_remarks' => 'nullable|string|max:500',
        ]);
    }

    private function postReturnEntries(PurchaseReturn $return): void
    {
        $itemsTotal = $return->items->sum(fn($i) => $i->quantity * $i->price);
        $conveyance = (float) ($return->convance_charges ?? 0);
        $discount   = (float) ($return->bill_discount    ?? 0);

        $this->syncVoucherEntries(
            $return,
            'purchase_return',
            $return->return_date,
            [
                ['dr_id' => $return->vendor_id, 'cr' => '104001', 'amount' => $itemsTotal, 'remarks' => 'Goods returned — ' . $return->return_no],
                ['dr_id' => $return->vendor_id, 'cr' => '502001', 'amount' => $conveyance,  'remarks' => 'Conveyance reversed — ' . $return->return_no],
                ['dr'    => '402001', 'cr_id' => $return->vendor_id, 'amount' => $discount, 'remarks' => 'Discount on return — ' . $return->return_no],
            ]
        );

        Log::info('[PurchaseReturn] Accounting synced', ['return_no' => $return->return_no, 'items' => $itemsTotal]);
    }

    private function saveItems(PurchaseReturn $return, array $items): void
    {
        foreach ($items as $row) {
            if (empty($row['item_id'])) continue;
            $return->items()->create([
                'item_id'             => $row['item_id'],
                'variation_id'        => $row['variation_id']  ?? null,
                'purchase_invoice_id' => $row['invoice_id']    ?? null,
                'item_name'           => Product::find($row['item_id'])?->name,
                'quantity'            => $row['quantity']       ?? 0,
                'unit'                => $row['unit'],
                'price'               => $row['price']          ?? 0,
                'remarks'             => $row['item_remarks']   ?? null,
            ]);
        }
    }

    private function saveAttachments(PurchaseReturn $return, Request $request): void
    {
        if (!$request->hasFile('attachments')) return;
        foreach ($request->file('attachments') as $file) {
            $return->attachments()->create([
                'file_path'     => $file->store('purchase_returns', 'public'),
                'original_name' => $file->getClientOriginalName(),
                'file_type'     => $file->getClientMimeType(),
            ]);
        }
    }
}