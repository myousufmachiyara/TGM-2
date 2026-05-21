<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReceiving;
use App\Models\Vendor;
use Carbon\Carbon;

class PurchaseReportController extends Controller
{
    public function purchaseReports(Request $request)
    {
        $tab  = $request->get('tab', 'PUR');
        $from = $request->get('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $to   = $request->get('to_date',   Carbon::now()->format('Y-m-d'));

        $vendors = Vendor::where('is_active', true)->orderBy('name')->get();

        $purchaseRegister   = collect();
        $purchaseReturns    = collect();
        $vendorWisePurchase = collect();
        $poRegister         = collect();
        $grnRegister        = collect();

        // ── Purchase Invoice Register ─────────────────────────────────
        if ($tab === 'PUR') {
            $purchaseRegister = PurchaseInvoice::with([
                'vendor',
                'items.product',
                'items.variation',
                'items.measurementUnit',
            ])
            ->whereBetween('invoice_date', [$from, $to])
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->vendor_id))
            ->get()
            ->flatMap(function ($invoice) {
                return $invoice->items->map(fn($item) => (object)[
                    'date'        => $invoice->invoice_date,
                    'invoice_no'  => $invoice->invoice_no ?? $invoice->bill_no ?? $invoice->id,
                    'vendor_name' => $invoice->vendor->name ?? '—',
                    'item_name'   => $item->product->name  ?? $item->item_name ?? '—',
                    'variation'   => $item->variation->sku ?? '—',
                    'unit'        => $item->measurementUnit->shortcode ?? '—',
                    'quantity'    => $item->quantity,
                    'rate'        => $item->price,
                    'total'       => $item->quantity * $item->price,
                ]);
            });
        }

        // ── Purchase Returns ──────────────────────────────────────────
        if ($tab === 'PR') {
            $purchaseReturns = PurchaseReturn::with([
                'vendor',
                'items.product',
                'items.variation',
                'items.measurementUnit',
            ])
            ->whereBetween('return_date', [$from, $to])
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->vendor_id))
            ->get()
            ->flatMap(function ($return) {
                return $return->items->map(fn($item) => (object)[
                    'date'        => $return->return_date,
                    'return_no'   => $return->id,
                    'vendor_name' => $return->vendor->name ?? '—',
                    'item_name'   => $item->product->name  ?? $item->item_name ?? '—',
                    'variation'   => $item->variation->sku ?? '—',
                    'unit'        => $item->measurementUnit->shortcode ?? '—',
                    'quantity'    => $item->quantity,
                    'rate'        => $item->price,
                    'total'       => $item->quantity * $item->price,
                ]);
            });
        }

        // ── Vendor-wise Purchases ─────────────────────────────────────
        if ($tab === 'VWP') {
            $vendorWisePurchase = PurchaseInvoice::with([
                'vendor',
                'items.product',
                'items.variation',
                'items.measurementUnit',
            ])
            ->whereBetween('invoice_date', [$from, $to])
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->vendor_id))
            ->get()
            ->groupBy('vendor_id')
            ->map(function ($invoices) {
                $vendor = $invoices->first()->vendor->name ?? 'Unknown Vendor';

                $items = $invoices->flatMap(fn($inv) =>
                    $inv->items->map(fn($item) => (object)[
                        'invoice_date' => $inv->invoice_date,
                        'invoice_no'   => $inv->invoice_no ?? $inv->bill_no ?? $inv->id,
                        'item_name'    => $item->product->name  ?? $item->item_name ?? '—',
                        'variation'    => $item->variation->sku ?? '—',
                        'unit'         => $item->measurementUnit->shortcode ?? '—',
                        'quantity'     => $item->quantity,
                        'rate'         => $item->price,
                        'total'        => $item->quantity * $item->price,
                    ])
                );

                return (object)[
                    'vendor_name'  => $vendor,
                    'items'        => $items,
                    'total_qty'    => $items->sum('quantity'),
                    'total_amount' => $items->sum('total'),
                ];
            })->values();
        }

        // ── PO Register ───────────────────────────────────────────────
        if ($tab === 'PO') {
            $poRegister = PurchaseOrder::with([
                'vendor',
                'category',
                'items.product',
                'receivings.receivingItems',
            ])
            ->whereBetween('order_date', [$from, $to])
            ->when($request->filled('vendor_id'), fn($q) => $q->where('vendor_id', $request->vendor_id))
            ->latest()
            ->get()
            ->map(fn($order) => (object)[
                'po_number'     => $order->po_number,
                'date'          => $order->order_date,
                'vendor_name'   => $order->vendor->name   ?? '—',
                'category_name' => $order->category->name ?? '—',
                'ordered_by'    => $order->ordered_by     ?? '—',
                'ordered_qty'   => $order->items->sum('quantity'),
                'ordered_value' => $order->items->sum(fn($i) => $i->quantity * $i->unit_price),
                'received_qty'  => $order->receivings->flatMap->receivingItems->sum('quantity'),
                'received_value'=> $order->receivings->flatMap->receivingItems->sum(fn($i) => $i->quantity * $i->unit_price),
                'status'        => $order->status,
                'status_badge'  => $order->status_badge,
                'items'         => $order->items->map(fn($i) => (object)[
                    'name'       => $i->product->name ?? '—',
                    'quantity'   => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'subtotal'   => $i->quantity * $i->unit_price,
                ]),
            ]);
        }

        // ── GRN Register ──────────────────────────────────────────────
        if ($tab === 'GRN') {
            $grnRegister = PurchaseOrderReceiving::with([
                'purchaseOrder.vendor',
                'receivingItems.product',
            ])
            ->whereBetween('received_date', [$from, $to])
            ->when($request->filled('vendor_id'), fn($q) => $q->whereHas(
                'purchaseOrder', fn($pq) => $pq->where('vendor_id', $request->vendor_id)
            ))
            ->latest()
            ->get()
            ->flatMap(function ($grn) {
                return $grn->receivingItems->map(fn($item) => (object)[
                    'grn_number'  => $grn->grn_number,
                    'po_number'   => $grn->purchaseOrder->po_number ?? '—',
                    'date'        => $grn->received_date,
                    'vendor_name' => $grn->purchaseOrder->vendor->name ?? '—',
                    'item_name'   => $item->product->name ?? '—',
                    'quantity'    => $item->quantity,
                    'unit_price'  => $item->unit_price,
                    'total'       => $item->quantity * $item->unit_price,
                ]);
            });
        }

        return view('reports.purchase_reports', compact(
            'tab', 'from', 'to', 'vendors',
            'purchaseRegister', 'purchaseReturns', 'vendorWisePurchase',
            'poRegister', 'grnRegister'
        ));
    }
}