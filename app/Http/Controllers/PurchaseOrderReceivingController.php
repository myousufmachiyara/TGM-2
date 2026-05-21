<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceiving;
use App\Models\PurchaseOrderReceivingItem;
use App\Traits\PostsAccountingEntries;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PurchaseOrderReceivingController extends Controller
{
    use PostsAccountingEntries;

    // ── Index ─────────────────────────────────────────────────────────
    public function index()
    {
        $receivings = PurchaseOrderReceiving::with([
            'purchaseOrder.vendor',
            'receivingItems',
        ])->latest()->get();

        return view('purchase_order_receivings.index', compact('receivings'));
    }

    // ── Create form (launched from PO list) ───────────────────────────
    public function create($purchaseOrderId)
    {
        $order = PurchaseOrder::with('items.product')->findOrFail($purchaseOrderId);

        $alreadyReceived = PurchaseOrderReceivingItem::whereHas(
            'receiving',
            fn($q) => $q->where('purchase_order_id', $purchaseOrderId)
        )
        ->selectRaw('product_id, SUM(quantity) as total')
        ->groupBy('product_id')
        ->pluck('total', 'product_id');

        foreach ($order->items as $item) {
            $item->already_received = (float) ($alreadyReceived[$item->product_id] ?? 0);
            $item->remaining        = max(0, (float) $item->quantity - $item->already_received);
        }

        return view('purchase_order_receivings.create', compact('order'));
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'received_date'     => 'required|date',
            'remarks'           => 'nullable|string|max:1000',
            'quantities'        => 'required|array',
            'quantities.*'      => 'nullable|numeric|min:0',
            'prices'            => 'required|array',
            'prices.*'          => 'nullable|numeric|min:0',
        ]);

        $hasItems = collect($request->quantities)->filter(fn($q) => (float)$q > 0)->isNotEmpty();
        if (!$hasItems) {
            return back()->with('error', 'Enter at least one receiving quantity.');
        }

        DB::beginTransaction();
        try {
            $receiving = PurchaseOrderReceiving::create([
                'purchase_order_id' => $request->purchase_order_id,
                'received_date'     => $request->received_date,
                'remarks'           => $request->remarks,
                'created_by'        => Auth::id(),
                'updated_by'        => Auth::id(),
            ]);

            foreach ($request->quantities as $itemId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) continue;

                $orderItem = PurchaseOrderItem::findOrFail($itemId);
                $receiving->receivingItems()->create([
                    'product_id' => $orderItem->product_id,
                    'quantity'   => $qty,
                    'unit_price' => (float) ($request->prices[$itemId] ?? $orderItem->unit_price),
                ]);
            }

            // ── Post accounting entries (same pattern as PurchaseInvoice) ──
            $receiving->load('receivingItems');
            $this->postGrnEntries($receiving);

            DB::commit();
            Log::info('[GRN] Created', [
                'id'  => $receiving->id,
                'grn' => $receiving->grn_number,
                'by'  => Auth::id(),
            ]);

            return redirect()->route('purchase_order_receivings.index')
                ->with('success', 'Receiving ' . $receiving->grn_number . ' recorded successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[GRN] Store failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Receiving failed: ' . $e->getMessage());
        }
    }

    // ── Edit ──────────────────────────────────────────────────────────
    public function edit($id)
    {
        $receiving = PurchaseOrderReceiving::with('receivingItems')->findOrFail($id);
        $order     = PurchaseOrder::with('items.product')->findOrFail($receiving->purchase_order_id);

        $alreadyReceived = PurchaseOrderReceivingItem::whereHas(
            'receiving',
            fn($q) => $q->where('purchase_order_id', $order->id)->where('id', '!=', $id)
        )
        ->selectRaw('product_id, SUM(quantity) as total')
        ->groupBy('product_id')
        ->pluck('total', 'product_id');

        foreach ($order->items as $item) {
            $thisItem = $receiving->receivingItems->firstWhere('product_id', $item->product_id);

            $item->already_received = (float) ($alreadyReceived[$item->product_id] ?? 0);
            $item->remaining        = max(0, (float) $item->quantity - $item->already_received);
            $item->received_in_this = (float) ($thisItem->quantity   ?? 0);
            $item->price_in_this    = (float) ($thisItem->unit_price ?? $item->unit_price);
        }

        return view('purchase_order_receivings.edit', compact('receiving', 'order'));
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $request->validate([
            'received_date' => 'required|date',
            'remarks'       => 'nullable|string|max:1000',
            'quantities.*'  => 'nullable|numeric|min:0',
            'prices.*'      => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $receiving = PurchaseOrderReceiving::findOrFail($id);

            $receiving->update([
                'received_date' => $request->received_date,
                'remarks'       => $request->remarks,
                'updated_by'    => Auth::id(),
            ]);

            $receiving->receivingItems()->delete();

            $saved = false;
            foreach ($request->quantities as $itemId => $qty) {
                $qty = (float) $qty;
                if ($qty <= 0) continue;

                $orderItem = PurchaseOrderItem::findOrFail($itemId);
                $receiving->receivingItems()->create([
                    'product_id' => $orderItem->product_id,
                    'quantity'   => $qty,
                    'unit_price' => (float) ($request->prices[$itemId] ?? $orderItem->unit_price),
                ]);
                $saved = true;
            }

            if (!$saved) {
                throw new \Exception('No items with quantity > 0 to save.');
            }

            // ── Sync re-posts (wipes old, writes new — same as invoice) ──
            $receiving->load('receivingItems');
            $this->postGrnEntries($receiving);

            DB::commit();
            Log::info('[GRN] Updated', ['id' => $id, 'by' => Auth::id()]);

            return redirect()->route('purchase_order_receivings.index')
                ->with('success', 'Receiving updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[GRN] Update failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $receiving = PurchaseOrderReceiving::findOrFail($id);

            // Wipe accounting entries before deleting (same as invoice destroy)
            $this->deleteVoucherEntries($receiving);

            $receiving->delete();

            DB::commit();
            Log::info('[GRN] Deleted', ['id' => $id, 'by' => Auth::id()]);

            return redirect()->route('purchase_order_receivings.index')
                ->with('success', 'Receiving deleted and accounting entries reversed.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[GRN] Delete failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    // ── Print (TCPDF) ─────────────────────────────────────────────────
    public function print($id)
    {
        $receiving = PurchaseOrderReceiving::with([
            'receivingItems.product.category',
            'receivingItems.product.measurementUnit',
            'purchaseOrder.vendor',
        ])->findOrFail($id);

        $order = $receiving->purchaseOrder;

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetAuthor('BillTrix');
        $pdf->SetTitle($receiving->grn_number);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        // ── Logo ──────────────────────────────────────────────────────
        $logoPath = public_path('assets/img/tgm-logo.webp');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 12, 30);
        }

        // ── Info box (top right) ──────────────────────────────────────
        $pdf->SetXY(130, 12);
        $pdf->writeHTML('
            <table border="1" cellpadding="4" style="font-size:10px;line-height:14px;border-collapse:collapse;">
                <tr><td><b>GRN #</b></td><td>'   . $receiving->grn_number . '</td></tr>
                <tr><td><b>PO #</b></td><td>'    . $order->po_number . '</td></tr>
                <tr><td><b>Date</b></td><td>'    . Carbon::parse($receiving->received_date)->format('d/m/Y') . '</td></tr>
                <tr><td><b>Vendor</b></td><td>'  . ($order->vendor->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        // ── GRN Badge ─────────────────────────────────────────────────
        $pdf->Line(60, 52.25, 200, 52.25);
        $pdf->SetXY(10, 48);
        $pdf->SetFillColor(23, 54, 93);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(65, 8, 'Goods Receiving Note (GRN)', 0, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Ln(5);

        // ── Items table ───────────────────────────────────────────────
        $html = '
        <table border="0.3" cellpadding="4" style="text-align:center;font-size:10px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="5%">#</th>
                <th width="30%">Item Name</th>
                <th width="18%">Category</th>
                <th width="17%">Qty</th>
                <th width="13%">Rate</th>
                <th width="17%">Amount</th>
            </tr>';

        $count = 0; $totalQty = 0; $grandTotal = 0;

        foreach ($receiving->receivingItems as $item) {
            $count++;
            $amount      = (float)$item->quantity * (float)$item->unit_price;
            $grandTotal += $amount;
            $totalQty   += (float)$item->quantity;

            $html .= '
            <tr>
                <td>' . $count . '</td>
                <td align="left">' . ($item->product->name ?? '—') . '</td>
                <td>' . ($item->product->category->name ?? '—') . '</td>
                <td>' . number_format($item->quantity, 2) . ' ' . ($item->product?->measurementUnit?->shortcode ?? '') . '</td>
                <td align="right">' . number_format($item->unit_price, 2) . '</td>
                <td align="right">' . number_format($amount, 2) . '</td>
            </tr>';
        }

        $html .= '
            <tr style="background-color:#f5f5f5;">
                <td colspan="3" align="right"><b>Totals</b></td>
                <td><b>' . number_format($totalQty, 2) . '</b></td>
                <td></td>
                <td align="right"><b>' . number_format($grandTotal, 2) . '</b></td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // ── Remarks ───────────────────────────────────────────────────
        if (!empty($receiving->remarks)) {
            $pdf->Ln(3);
            $pdf->writeHTML(
                '<b>Remarks:</b><br><span style="font-size:10px;">' . nl2br(htmlspecialchars($receiving->remarks)) . '</span>',
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

        return $pdf->Output($receiving->grn_number . '.pdf', 'I');
    }

    // ══════════════════════════════════════════════════════════════════
    //  PRIVATE — Accounting
    // ══════════════════════════════════════════════════════════════════

    /**
     * Post GRN entries using syncVoucherEntries (same as postPurchaseEntries in invoice):
     *
     *   DR  Stock in Hand  104001  ← inventory value increases
     *   CR  Vendor account         ← party payable increases
     *
     * syncVoucherEntries() wipes old rows before writing, so calling this
     * on update automatically replaces the old entries — no separate
     * reverse step needed, identical to how PurchaseInvoiceController works.
     */
    private function postGrnEntries(PurchaseOrderReceiving $receiving): void
    {
        $order      = $receiving->purchaseOrder;
        $vendorId   = $order->vendor_id;
        $totalValue = $receiving->receivingItems->sum(fn($i) => $i->quantity * $i->unit_price);

        $this->syncVoucherEntries(
            $receiving,
            'purchase_grn',
            $receiving->received_date,
            [
                [
                    'dr'      => '104001',   // DR Stock in Hand
                    'cr_id'   => $vendorId,  // CR Vendor (dynamic)
                    'amount'  => $totalValue,
                    'remarks' => 'Goods received — ' . $receiving->grn_number . ' against ' . $order->po_number,
                ],
            ]
        );

        Log::info('[GRN] Accounting synced', [
            'grn'       => $receiving->grn_number,
            'vendor_id' => $vendorId,
            'amount'    => $totalValue,
        ]);
    }
}