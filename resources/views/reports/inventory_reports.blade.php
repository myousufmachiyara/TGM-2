@extends('layouts.app')
@section('title', 'Inventory Reports')

@section('content')
<div class="tabs">

    <ul class="nav nav-tabs">
        <li class="nav-item">
            <a class="nav-link {{ $tab=='IL' ? 'active' : '' }}" data-bs-toggle="tab" href="#IL">
                <i class="fas fa-book me-1"></i> Item Ledger
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab=='SR' ? 'active' : '' }}" data-bs-toggle="tab" href="#SR">
                <i class="fas fa-boxes me-1"></i> Stock In Hand
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab=='NMI' ? 'active' : '' }}" data-bs-toggle="tab" href="#NMI">
                <i class="fas fa-hourglass-half me-1"></i> Non-Moving Items
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab=='ROL' ? 'active' : '' }}" data-bs-toggle="tab" href="#ROL">
                <i class="fas fa-exclamation-triangle me-1"></i> Reorder Level
            </a>
        </li>
    </ul>

    <div class="tab-content mt-3">

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- ITEM LEDGER                                               --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <div id="IL" class="tab-pane fade {{ $tab=='IL' ? 'show active' : '' }}">

            <form method="GET" action="{{ route('reports.inventory') }}" class="mb-3">
                <input type="hidden" name="tab" value="IL">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Product / Variation</label>
                        <select name="item_id" class="form-control select2-js">
                            <option value="">— Select Product —</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ request('item_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                                @foreach($product->variations as $var)
                                    <option value="{{ $product->id }}-{{ $var->id }}"
                                        {{ request('item_id') == $product->id.'-'.$var->id ? 'selected' : '' }}>
                                        &nbsp;&nbsp;↳ {{ $product->name }} ({{ $var->sku }})
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>From Date</label>
                        <input type="date" name="from_date" class="form-control"
                               value="{{ request('from_date', $from) }}">
                    </div>
                    <div class="col-md-2">
                        <label>To Date</label>
                        <input type="date" name="to_date" class="form-control"
                               value="{{ request('to_date', $to) }}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="{{ route('reports.inventory') }}?tab=IL" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>

            @php
                $totalIn  = collect($itemLedger)->sum('qty_in');
                $totalOut = collect($itemLedger)->sum('qty_out');
                $balance  = $totalIn - $totalOut;
            @endphp

            @if(count($itemLedger))
            <div class="row mb-3">
                <div class="col text-end">
                    <span class="me-3">Total In: <strong class="text-success">{{ number_format($totalIn, 2) }}</strong></span>
                    <span class="me-3">Total Out: <strong class="text-danger">{{ number_format($totalOut, 2) }}</strong></span>
                    <span>Closing Balance: <strong class="text-primary fs-5">{{ number_format($balance, 2) }}</strong></span>
                </div>
            </div>
            @endif

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Variation</th>
                            <th class="text-end">Rate</th>
                            <th class="text-end text-success">Qty In</th>
                            <th class="text-end text-danger">Qty Out</th>
                            <th class="text-end">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $runningBalance = 0; @endphp
                        @forelse($itemLedger as $i => $row)
                            @php
                                $runningBalance += ($row['qty_in'] - $row['qty_out']);
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}</td>
                                <td>
                                    @php
                                        $typeColor = match($row['type']) {
                                            'Purchase Invoice', 'GRN', 'Production Receiving' => 'success',
                                            'Purchase Return', 'Production Issue'             => 'danger',
                                            default => 'secondary'
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $typeColor }}">{{ $row['type'] }}</span>
                                </td>
                                <td>{{ $row['description'] }}</td>
                                <td>{{ $row['variation'] ?? '—' }}</td>
                                <td class="text-end">{{ $row['rate'] > 0 ? number_format($row['rate'], 2) : '—' }}</td>
                                <td class="text-end text-success fw-bold">
                                    {{ $row['qty_in'] > 0 ? number_format($row['qty_in'], 2) : '' }}
                                </td>
                                <td class="text-end text-danger fw-bold">
                                    {{ $row['qty_out'] > 0 ? number_format($row['qty_out'], 2) : '' }}
                                </td>
                                <td class="text-end {{ $runningBalance < 0 ? 'text-danger' : 'text-primary' }}">
                                    {{ number_format($runningBalance, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    Select a product and date range to view the item ledger.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($itemLedger))
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <th colspan="6" class="text-end">Totals</th>
                            <th class="text-end text-success">{{ number_format($totalIn, 2) }}</th>
                            <th class="text-end text-danger">{{ number_format($totalOut, 2) }}</th>
                            <th class="text-end text-primary">{{ number_format($balance, 2) }}</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- STOCK IN HAND                                             --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <div id="SR" class="tab-pane fade {{ $tab=='SR' ? 'show active' : '' }}">

            <form method="GET" action="{{ route('reports.inventory') }}" class="mb-3">
                <input type="hidden" name="tab" value="SR">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Product / Variation</label>
                        <select name="item_id" class="form-control select2-js">
                            <option value="">— All Products —</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ request('item_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                                @foreach($product->variations as $var)
                                    <option value="{{ $product->id }}-{{ $var->id }}"
                                        {{ request('item_id') == $product->id.'-'.$var->id ? 'selected' : '' }}>
                                        &nbsp;&nbsp;↳ {{ $product->name }} ({{ $var->sku }})
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>Costing Method</label>
                        <select name="costing_method" class="form-control">
                            <option value="avg"    {{ request('costing_method','avg') == 'avg'    ? 'selected' : '' }}>Average Rate</option>
                            <option value="max"    {{ request('costing_method','avg') == 'max'    ? 'selected' : '' }}>Max Rate</option>
                            <option value="min"    {{ request('costing_method','avg') == 'min'    ? 'selected' : '' }}>Min Rate</option>
                            <option value="latest" {{ request('costing_method','avg') == 'latest' ? 'selected' : '' }}>Latest Rate</option>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <a href="{{ route('reports.inventory') }}?tab=SR" class="btn btn-secondary w-100">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </div>
            </form>

            @php
                $grandTotal = collect($stockInHand)->sum('total');
                $grandQty   = collect($stockInHand)->sum('quantity');
            @endphp

            <div class="row mb-3">
                <div class="col text-end">
                    <span class="me-3">Total Qty: <strong class="text-primary">{{ number_format($grandQty, 2) }}</strong></span>
                    <span>Total Value: <strong class="text-danger fs-5">{{ number_format($grandTotal, 2) }}</strong></span>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>SKU</th>
                            <th>Variation</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Raw Cost/Unit</th>
                            <th class="text-end">MFG Cost/Unit</th>
                            <th class="text-end">Unit Cost</th>
                            <th class="text-end">Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($stockInHand as $i => $stock)
                            <tr class="{{ $stock['quantity'] <= 0 ? 'table-warning' : '' }}">
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $stock['product'] }}</td>
                                <td><code>{{ $stock['sku'] ?? '—' }}</code></td>
                                <td>{{ $stock['variation'] ?? '—' }}</td>
                                <td class="text-end {{ $stock['quantity'] <= 0 ? 'text-danger' : '' }}">
                                    {{ number_format($stock['quantity'], 2) }}
                                </td>
                                <td class="text-end">{{ number_format($stock['raw_cost'], 2) }}</td>
                                <td class="text-end">{{ number_format($stock['mfg_cost'], 2) }}</td>
                                <td class="text-end">{{ number_format($stock['price'], 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($stock['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    No stock data found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(count($stockInHand))
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <th colspan="4" class="text-end">Grand Total</th>
                            <th class="text-end">{{ number_format($grandQty, 2) }}</th>
                            <th colspan="3" class="text-end">—</th>
                            <th class="text-end">{{ number_format($grandTotal, 2) }}</th>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- NON-MOVING ITEMS                                          --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <div id="NMI" class="tab-pane fade {{ $tab=='NMI' ? 'show active' : '' }}">
            <form method="GET" action="{{ route('reports.inventory') }}" class="mb-3">
                <input type="hidden" name="tab" value="NMI">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Product</label>
                        <select name="item_id" class="form-control select2-js">
                            <option value="">— All Products —</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ request('item_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>Inactive for (months)</label>
                        <input type="number" name="months" class="form-control"
                               value="{{ request('months', 3) }}" min="1">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Variation</th>
                            <th>Last Movement</th>
                            <th>Days Inactive</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($nonMovingItems as $i => $nmi)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $nmi['product'] }}</td>
                                <td>{{ $nmi['variation'] ? '('.$nmi['variation'].')' : '—' }}</td>
                                <td>{{ $nmi['last_date'] }}</td>
                                <td><span class="badge bg-warning text-dark">{{ $nmi['days_inactive'] }} days</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No non-moving items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ════════════════════════════════════════════════════════ --}}
        {{-- REORDER LEVEL                                             --}}
        {{-- ════════════════════════════════════════════════════════ --}}
        <div id="ROL" class="tab-pane fade {{ $tab=='ROL' ? 'show active' : '' }}">
            <form method="GET" action="{{ route('reports.inventory') }}" class="mb-3">
                <input type="hidden" name="tab" value="ROL">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label>Product</label>
                        <select name="item_id" class="form-control select2-js">
                            <option value="">— All Products —</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}"
                                    {{ request('item_id') == $product->id ? 'selected' : '' }}>
                                    {{ $product->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-1"></i> Filter
                        </button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Variation</th>
                            <th class="text-end">Stock In Hand</th>
                            <th class="text-end">Reorder Level</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reorderLevel as $i => $rl)
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td>{{ $rl['product'] }}</td>
                                <td>{{ $rl['variation'] ? '('.$rl['variation'].')' : '—' }}</td>
                                <td class="text-end">{{ number_format($rl['stock_inhand'], 2) }}</td>
                                <td class="text-end">{{ number_format($rl['reorder_level'], 2) }}</td>
                                <td>
                                    @if($rl['stock_inhand'] <= $rl['reorder_level'])
                                        <span class="badge bg-danger">Reorder Required</span>
                                    @else
                                        <span class="badge bg-success">Sufficient</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No items found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>{{-- tab-content --}}
</div>{{-- tabs --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    try {
        const tab = new URLSearchParams(window.location.search).get('tab')
                    || window.location.hash.replace('#', '');
        if (tab) {
            const el = document.querySelector(`.nav-link[href="#${tab}"]`);
            if (el && typeof bootstrap !== 'undefined') {
                new bootstrap.Tab(el).show();
            }
        }
    } catch(e) { console.error('Tab error', e); }
});
</script>
@endsection