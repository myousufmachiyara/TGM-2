<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAttachment;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    // ── Index ─────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $orders = PurchaseOrder::with([
            'vendor',
            'category',
            'items.product',
            'receivings.receivingItems',
        ])->latest()->get();

        // Filter by status (computed attribute — done in PHP since it's calculated)
        $status = $request->input('status');
        if ($status && strtolower($status) !== 'all') {
            $orders = $orders->filter(
                fn($o) => strtolower($o->status) === strtolower($status)
            )->values();
        }

        return view('purchase_orders.index', compact('orders', 'status'));
    }

    // ── Create ────────────────────────────────────────────────────────
    public function create()
    {
        $vendors    = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();
        $products   = Product::where('is_active', true)->orderBy('name')->get();

        return view('purchase_orders.create', compact('vendors', 'categories', 'products'));
    }

    // ── Store ─────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'vendor_id'              => 'required|exists:vendors,id',
            'category_id'            => 'nullable|exists:product_categories,id',
            'order_date'             => 'required|date',
            'ordered_by'             => 'nullable|string|max:255',
            'remarks'                => 'nullable|string|max:1000',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.width'          => 'nullable|numeric|min:0',
            'items.*.description'    => 'nullable|string|max:500',
            'attachments.*'          => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:4096',
        ]);

        DB::beginTransaction();
        try {
            $order = PurchaseOrder::create([
                'vendor_id'   => $request->vendor_id,
                'category_id' => $request->category_id,
                'order_date'  => $request->order_date,
                'ordered_by'  => $request->ordered_by,
                'remarks'     => $request->remarks,
                'created_by'  => Auth::id(),
                'updated_by'  => Auth::id(),
            ]);

            foreach ($request->items as $item) {
                $order->items()->create([
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'width'       => $item['width'] ?? 0,
                    'description' => $item['description'] ?? null,
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('purchase_orders', 'public');
                    $order->attachments()->create(['file_path' => $path]);
                }
            }

            DB::commit();
            Log::info('[PurchaseOrder] Created', ['id' => $order->id, 'by' => Auth::id()]);

            return redirect()->route('purchase_orders.index')
                ->with('success', 'Purchase Order ' . $order->po_number . ' created successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseOrder] Store failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Failed to create PO: ' . $e->getMessage());
        }
    }

    // ── Edit ──────────────────────────────────────────────────────────
    public function edit($id)
    {
        $order      = PurchaseOrder::with(['items.product.measurementUnit', 'attachments'])->findOrFail($id);
        $vendors    = Vendor::where('is_active', true)->orderBy('name')->get();
        $categories = ProductCategory::orderBy('name')->get();
        $products   = Product::with('measurementUnit')->where('is_active', true)->orderBy('name')->get();

        $productData = $products->map(fn($p) => [
            'id'   => $p->id,
            'sku'  => $p->sku,
            'name' => $p->name,
            'unit' => $p->measurementUnit->shortcode ?? '',
        ])->values();

        $vendorProducts = Vendor::whereHas('products')
            ->with('products:id,vendor_id')
            ->get()
            ->mapWithKeys(fn($v) => [
                $v->id => $v->products->pluck('id')->toArray()
            ]);

        return view('purchase_orders.edit', compact(
            'order', 'vendors', 'categories', 'products', 'productData', 'vendorProducts'
        ));
    }

    // ── Update ────────────────────────────────────────────────────────
    public function update(Request $request, $id)
    {
        $request->validate([
            'vendor_id'              => 'required|exists:vendors,id',
            'category_id'            => 'nullable|exists:product_categories,id',
            'order_date'             => 'required|date',
            'ordered_by'             => 'nullable|string|max:255',
            'remarks'                => 'nullable|string|max:1000',
            'items'                  => 'required|array|min:1',
            'items.*.product_id'     => 'required|exists:products,id',
            'items.*.quantity'       => 'required|numeric|min:0.01',
            'items.*.unit_price'     => 'required|numeric|min:0',
            'items.*.width'          => 'nullable|numeric|min:0',
            'items.*.description'    => 'nullable|string|max:500',
            'attachments.*'          => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:4096',
        ]);

        DB::beginTransaction();
        try {
            $order = PurchaseOrder::findOrFail($id);

            $order->update([
                'vendor_id'   => $request->vendor_id,
                'category_id' => $request->category_id,
                'order_date'  => $request->order_date,
                'ordered_by'  => $request->ordered_by,
                'remarks'     => $request->remarks,
                'updated_by'  => Auth::id(),
            ]);

            // Replace all items
            $order->items()->delete();
            foreach ($request->items as $item) {
                $order->items()->create([
                    'product_id'  => $item['product_id'],
                    'quantity'    => $item['quantity'],
                    'unit_price'  => $item['unit_price'],
                    'width'       => $item['width'] ?? 0,
                    'description' => $item['description'] ?? null,
                ]);
            }

            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('purchase_orders', 'public');
                    $order->attachments()->create(['file_path' => $path]);
                }
            }

            DB::commit();
            Log::info('[PurchaseOrder] Updated', ['id' => $order->id, 'by' => Auth::id()]);

            return redirect()->route('purchase_orders.index')
                ->with('success', 'Purchase Order updated successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PurchaseOrder] Update failed', ['error' => $e->getMessage()]);
            return back()->withInput()->with('error', 'Update failed: ' . $e->getMessage());
        }
    }

    // ── Destroy ───────────────────────────────────────────────────────
    public function destroy($id)
    {
        $order = PurchaseOrder::findOrFail($id);

        if ($order->receivings()->count() > 0) {
            return back()->with('error', 'Cannot delete — this PO has receiving entries against it.');
        }

        // Delete stored attachment files
        foreach ($order->attachments as $att) {
            Storage::disk('public')->delete($att->file_path);
        }

        $order->delete();

        return redirect()->route('purchase_orders.index')
            ->with('success', 'Purchase Order deleted successfully.');
    }

    // ── Print (TCPDF) ─────────────────────────────────────────────────
    public function print($id)
    {
        $order = PurchaseOrder::with([
            'vendor',
            'category',
            'items.product.measurementUnit',
        ])->findOrFail($id);

        $pdf = new \TCPDF();
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetCreator('BillTrix');
        $pdf->SetAuthor('BillTrix');
        $pdf->SetTitle($order->po_number);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $pdf->setCellPadding(1.5);

        // ── Logo ─────────────────────────────────────────────────────
        $logoPath = public_path('assets/img/tgm-logo.webp');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 12, 30);
        }

        // ── PO Info box (top right) ───────────────────────────────────
        $pdf->SetXY(130, 12);
        $pdf->writeHTML('
            <table border="1" cellpadding="4" style="font-size:10px;line-height:14px;border-collapse:collapse;">
                <tr><td><b>PO #</b></td><td>' . $order->po_number . '</td></tr>
                <tr><td><b>Date</b></td><td>' . $order->order_date->format('d/m/Y') . '</td></tr>
                <tr><td><b>Ordered By</b></td><td>' . ($order->ordered_by ?? '—') . '</td></tr>
                <tr><td><b>Category</b></td><td>' . ($order->category?->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        // ── PO Badge (bottom-left of header area) ─────────────────────
        $pdf->SetXY(10, 48);
        $pdf->SetFillColor(23, 54, 93);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(50, 8, 'Purchase Order', 0, 1, 'C', 1);
        $pdf->SetTextColor(0, 0, 0);

        // ── Divider line (right of badge to info box) ─────────────────
        $pdf->Line(60, 52, 200, 52);

        // ── Vendor / Party name ───────────────────────────────────────
        $pdf->SetXY(10, 60);
        $pdf->writeHTML('
            <table cellpadding="2" style="font-size:11px;">
                <tr><td><b>Vendor:</b> ' . ($order->vendor->name ?? '—') . '</td></tr>
            </table>',
        false, false, false, false, '');

        $pdf->SetXY(10, 70);

        // ── Items table ───────────────────────────────────────────────
        $html = '
        <table border="0.3" cellpadding="4" style="text-align:center;font-size:10px;">
            <tr style="background-color:#f5f5f5;font-weight:bold;">
                <th width="5%">#</th>
                <th width="28%">Item Name</th>
                <th width="10%">Width</th>
                <th width="20%">Description</th>
                <th width="15%">Qty</th>
                <th width="10%">Rate</th>
                <th width="12%">Amount</th>
            </tr>';

        $count = 0;
        $grandTotal = 0;

        foreach ($order->items as $item) {
            $count++;
            $amount      = (float)$item->quantity * (float)$item->unit_price;
            $grandTotal += $amount;

            $html .= '
            <tr>
                <td>' . $count . '</td>
                <td align="left">' . ($item->product->name ?? '—') . '</td>
                <td>' . ($item->width > 0 ? number_format($item->width, 2) : '—') . '</td>
                <td align="left">' . ($item->description ?? '—') . '</td>
                <td>' . number_format($item->quantity, 2) . ' ' . ($item->product?->measurementUnit?->shortcode ?? '') . '</td>
                <td align="right">' . number_format($item->unit_price, 2) . '</td>
                <td align="right">' . number_format($amount, 2) . '</td>
            </tr>';
        }

        $html .= '
            <tr style="background-color:#f5f5f5;">
                <td colspan="6" align="right"><b>Grand Total</b></td>
                <td align="right"><b>' . number_format($grandTotal, 2) . '</b></td>
            </tr>
        </table>';

        $pdf->writeHTML($html, true, false, true, false, '');

        // ── Remarks ───────────────────────────────────────────────────
        if (!empty($order->remarks)) {
            $pdf->Ln(5);
            $pdf->writeHTML(
                '<b>Remarks:</b><br><span style="font-size:10px;">' . nl2br(htmlspecialchars($order->remarks)) . '</span>',
                true, false, true, false, ''
            );
        }

        // ── Signature lines ───────────────────────────────────────────
        $pdf->Ln(20);
        $y = $pdf->GetY();
        $pdf->Line(28,  $y, 68,  $y);
        $pdf->Line(130, $y, 170, $y);
        $pdf->SetXY(28,  $y + 2); $pdf->Cell(40, 6, 'Approved By',  0, 0, 'C');
        $pdf->SetXY(130, $y + 2); $pdf->Cell(40, 6, 'Received By',  0, 0, 'C');

        return $pdf->Output($order->po_number . '.pdf', 'I');
    }

    // ── AJAX helpers ──────────────────────────────────────────────────

    /** GET /purchase_orders/{id}/po-numbers — returns PO numbers for a product */
    public function getPoNumbersForProduct(Request $request)
    {
        $productId = $request->input('product_id');

        $orders = PurchaseOrder::whereHas('items', fn($q) => $q->where('product_id', $productId))
            ->get(['id', 'po_number']);

        return response()->json($orders);
    }

    /** GET /purchase_orders/{id}/item-detail — returns width/price for product+PO combo */
    public function getItemDetail(Request $request)
    {
        $item = PurchaseOrderItem::where('purchase_order_id', $request->input('purchase_order_id'))
            ->where('product_id', $request->input('product_id'))
            ->first(['width', 'unit_price']);

        if (!$item) {
            return response()->json(['error' => 'Not found'], 404);
        }

        return response()->json($item);
    }

    // ── Convert PO → Invoice (called from routes) ─────────────────────
    public function convertToInvoice($id)
    {
        // Placeholder — implement when PurchaseInvoice module is ready
        return back()->with('error', 'Convert to Invoice not yet implemented.');
    }
}