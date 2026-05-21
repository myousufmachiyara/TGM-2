<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Product;
use App\Models\PurchaseInvoiceItem;
use App\Models\PurchaseReturnItem;
use App\Models\PurchaseOrderReceivingItem;
use App\Models\ProductionDetail;
use App\Models\ProductionReceivingDetail;

class InventoryReportController extends Controller
{
    public function inventoryReports(Request $request)
    {
        $tab      = $request->tab      ?? 'IL';
        $selected = $request->item_id  ?? null;
        $from     = $request->from_date ?? now()->startOfMonth()->toDateString();
        $to       = $request->to_date   ?? now()->toDateString();

        // Parse "productId" or "productId-variationId"
        $productId   = null;
        $variationId = null;
        if ($selected) {
            if (str_contains($selected, '-')) {
                [$p, $v]     = explode('-', $selected, 2);
                $productId   = (int) $p;
                $variationId = $v !== '' ? (int) $v : null;
            } else {
                $productId = (int) $selected;
            }
        }

        $allProducts    = Product::with('variations')->get();
        $itemLedger     = collect();
        $stockInHand    = collect();
        $nonMovingItems = collect();
        $reorderLevel   = collect();

        // ═══════════════════════════════════════════════════════════════
        //  ITEM LEDGER
        //  Shows every stock movement for a selected product/variation.
        //  GRN (PO Receiving) is included as a qty_in source.
        // ═══════════════════════════════════════════════════════════════
        if ($tab === 'IL' && $productId) {
            $product = $allProducts->firstWhere('id', $productId);

            if ($product) {
                $variations = $this->resolveVariations($product, $variationId);

                foreach ($variations as $var) {
                    $ledger = collect();

                    // ── Purchase Invoice (qty in) ─────────────────────
                    $ledger = $ledger->concat(
                        PurchaseInvoiceItem::where('item_id', $product->id)
                            ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                            ->whereHas('invoice', fn($q) => $q->whereBetween('invoice_date', [$from, $to]))
                            ->with('invoice')
                            ->get()
                            ->map(fn($row) => [
                                'date'        => $row->invoice->invoice_date,
                                'type'        => 'Purchase Invoice',
                                'description' => 'Invoice: ' . ($row->invoice->invoice_no ?? $row->invoice->bill_no ?? $row->invoice->id),
                                'qty_in'      => $row->quantity,
                                'qty_out'     => 0,
                                'rate'        => $row->price,
                                'product'     => $product->name,
                                'variation'   => $var->sku ?? null,
                            ])
                    );

                    // ── GRN / PO Receiving (qty in) ───────────────────
                    $ledger = $ledger->concat(
                        PurchaseOrderReceivingItem::where('product_id', $product->id)
                            ->whereHas('receiving', fn($q) => $q->whereBetween('received_date', [$from, $to]))
                            ->with(['receiving.purchaseOrder'])
                            ->get()
                            ->map(fn($row) => [
                                'date'        => $row->receiving->received_date,
                                'type'        => 'GRN',
                                'description' => ($row->receiving->grn_number ?? '—')
                                                 . ' | PO: ' . ($row->receiving->purchaseOrder->po_number ?? '—'),
                                'qty_in'      => $row->quantity,
                                'qty_out'     => 0,
                                'rate'        => $row->unit_price,
                                'product'     => $product->name,
                                'variation'   => $var->sku ?? null,
                            ])
                    );

                    // ── Purchase Return (qty out) ─────────────────────
                    $ledger = $ledger->concat(
                        PurchaseReturnItem::where('item_id', $product->id)
                            ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                            ->whereHas('purchaseReturn', fn($q) => $q->whereBetween('return_date', [$from, $to]))
                            ->with('purchaseReturn')
                            ->get()
                            ->map(fn($row) => [
                                'date'        => $row->purchaseReturn->return_date,
                                'type'        => 'Purchase Return',
                                'description' => 'Ref: ' . ($row->purchaseReturn->reference_no ?? $row->purchaseReturn->id),
                                'qty_in'      => 0,
                                'qty_out'     => $row->quantity,
                                'rate'        => $row->price ?? 0,
                                'product'     => $product->name,
                                'variation'   => $var->sku ?? null,
                            ])
                    );

                    // ── Production Issue (qty out — raw consumed) ─────
                    $ledger = $ledger->concat(
                        ProductionDetail::where('product_id', $product->id)
                            ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                            ->whereHas('production', fn($q) => $q->whereBetween('order_date', [$from, $to]))
                            ->with('production')
                            ->get()
                            ->map(fn($row) => [
                                'date'        => $row->production->order_date,
                                'type'        => 'Production Issue',
                                'description' => 'Raw Material Issued',
                                'qty_in'      => 0,
                                'qty_out'     => $row->qty,
                                'rate'        => 0,
                                'product'     => $product->name,
                                'variation'   => $var->sku ?? null,
                            ])
                    );

                    // ── Production Receiving (qty in — finished goods) ─
                    $ledger = $ledger->concat(
                        ProductionReceivingDetail::where('product_id', $product->id)
                            ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                            ->whereHas('receiving', fn($q) => $q->whereBetween('rec_date', [$from, $to]))
                            ->with('receiving')
                            ->get()
                            ->map(fn($row) => [
                                'date'        => $row->receiving->rec_date,
                                'type'        => 'Production Receiving',
                                'description' => 'Finished Goods Received',
                                'qty_in'      => $row->received_qty,
                                'qty_out'     => 0,
                                'rate'        => $row->manufacturing_cost ?? 0,
                                'product'     => $product->name,
                                'variation'   => $var->sku ?? null,
                            ])
                    );

                    $itemLedger = $itemLedger->concat($ledger->sortBy('date'));
                }
            }
        }

        // ═══════════════════════════════════════════════════════════════
        //  STOCK IN HAND
        //  Calculates net stock for every product/variation.
        //  GRN qty is included as an inflow alongside purchase invoices.
        // ═══════════════════════════════════════════════════════════════
        if ($tab === 'SR') {
            $costingMethod     = $request->costing_method ?? 'avg';
            $productsToProcess = $allProducts;

            if ($selected) {
                if (str_contains($selected, '-')) {
                    [$pid, $vid]       = explode('-', $selected, 2);
                    $productsToProcess = $allProducts->where('id', (int) $pid);
                    $productsToProcess->transform(function ($p) use ($vid) {
                        $p->variations = $p->variations->where('id', (int) $vid);
                        return $p;
                    });
                } else {
                    $productsToProcess = $allProducts->where('id', (int) $selected);
                }
            }

            foreach ($productsToProcess as $product) {
                $variations = $product->variations->isNotEmpty()
                    ? $product->variations
                    : collect([(object)['id' => null, 'sku' => null]]);

                foreach ($variations as $var) {
                    $openingStock = !is_null($var->id)
                        ? ($var->stock_quantity ?? 0)
                        : ($product->opening_stock ?? 0);

                    // ── Inflows ───────────────────────────────────────
                    $fromInvoice = PurchaseInvoiceItem::where('item_id', $product->id)
                        ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                        ->sum('quantity');

                    $fromGrn = PurchaseOrderReceivingItem::where('product_id', $product->id)
                        ->sum('quantity');

                    $fromSaleReturn = 0; // no sale module yet

                    $fromProduction = ProductionReceivingDetail::where('product_id', $product->id)
                        ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                        ->sum('received_qty');

                    // ── Outflows ──────────────────────────────────────
                    $toPurchaseReturn = PurchaseReturnItem::where('item_id', $product->id)
                        ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                        ->sum('quantity');

                    $toSale = 0; // no sale module yet

                    $toProduction = ProductionDetail::where('product_id', $product->id)
                        ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                        ->sum('qty');

                    $stockQty = $openingStock
                        + ($fromInvoice + $fromGrn + $fromSaleReturn + $fromProduction)
                        - ($toPurchaseReturn + $toSale + $toProduction);

                    // ── Cost per unit ─────────────────────────────────
                    $rawCostPerUnit = 0;
                    $mfgCostPerUnit = 0;

                    $pq = PurchaseInvoiceItem::where('item_id', $product->id);
                    $rawCostPerUnit = $this->resolveRate($pq, $costingMethod);

                    $mfgRows = ProductionReceivingDetail::where('product_id', $product->id)
                        ->when(!is_null($var->id), fn($q) => $q->where('variation_id', $var->id))
                        ->get(['manufacturing_cost', 'received_qty']);

                    $totalMfgValue  = $mfgRows->sum(fn($r) => (float)$r->manufacturing_cost * (float)($r->received_qty ?? 0));
                    $totalMfgQty    = $mfgRows->sum('received_qty');
                    $mfgCostPerUnit = $totalMfgQty > 0 ? $totalMfgValue / $totalMfgQty : 0;

                    $costPrice = $rawCostPerUnit + $mfgCostPerUnit;

                    $stockInHand->push([
                        'product'   => $product->name,
                        'sku'       => $product->sku,
                        'variation' => $var->sku ?? null,
                        'quantity'  => round($stockQty, 2),
                        'raw_cost'  => round($rawCostPerUnit, 2),
                        'mfg_cost'  => round($mfgCostPerUnit, 2),
                        'price'     => round($costPrice, 2),
                        'total'     => round($stockQty * $costPrice, 2),
                    ]);
                }
            }
        }

        return view('reports.inventory_reports', [
            'products'       => $allProducts,
            'tab'            => $tab,
            'itemLedger'     => $itemLedger->sortBy('date')->values(),
            'stockInHand'    => $stockInHand,
            'nonMovingItems' => $nonMovingItems,
            'reorderLevel'   => $reorderLevel,
            'from'           => $from,
            'to'             => $to,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────

    private function resolveVariations($product, ?int $variationId): \Illuminate\Support\Collection
    {
        if ($variationId) {
            $var = $product->variations->firstWhere('id', $variationId);
            return $var
                ? collect([$var])
                : collect([(object)['id' => $variationId, 'sku' => null]]);
        }

        return $product->variations->isNotEmpty()
            ? $product->variations
            : collect([(object)['id' => null, 'sku' => null]]);
    }

    private function resolveRate($query, string $method): float
    {
        return match ($method) {
            'max'    => (float) ($query->max('price') ?? 0),
            'min'    => (float) ($query->min('price') ?? 0),
            'latest' => (float) (optional($query->latest('id')->first())->price ?? 0),
            default  => ($agg = $query->selectRaw('SUM(quantity*price) as v, SUM(quantity) as q')->first())
                        && $agg->q > 0 ? $agg->v / $agg->q : 0,
        };
    }
}