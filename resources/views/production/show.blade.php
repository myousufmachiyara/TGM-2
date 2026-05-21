@extends('layouts.app')
@section('title', 'Production #' . $production->id)

@section('content')
<div class="row">
    <div class="col">
        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Production Order #{{ $production->id }}</h2>
                <div class="d-flex gap-2">
                    <a href="{{ route('production.print', $production->id) }}" target="_blank" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-print"></i> Print
                    </a>
                    <a href="{{ route('production.gatepass', $production->id) }}" target="_blank" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-file-export"></i> Gate Pass
                    </a>
                    <a href="{{ route('production.summary', $production->id) }}" target="_blank" class="btn btn-outline-dark btn-sm">
                        <i class="fas fa-calculator"></i> Summary
                    </a>
                    @can('production.edit')
                        <a href="{{ route('production.edit', $production->id) }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    @endcan
                    <a href="{{ route('production.index') }}" class="btn btn-danger btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
            </header>

            <div class="card-body">
                {{-- ── Header info ── --}}
                <div class="row mb-4">
                    <div class="col-md-3">
                        <small class="text-muted">Vendor</small>
                        <div class="fw-bold">{{ $production->vendor->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Date</small>
                        <div class="fw-bold">{{ \Carbon\Carbon::parse($production->order_date)->format('d-m-Y') }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Type</small>
                        <div>
                            <span class="badge {{ $production->production_type === 'cmt' ? 'bg-primary' : 'bg-warning text-dark' }}">
                                {{ $production->production_type === 'cmt' ? 'CMT' : 'Sell Raw' }}
                            </span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Category</small>
                        <div>{{ $production->category->name ?? '—' }}</div>
                    </div>
                    <div class="col-md-2">
                        <small class="text-muted">Status</small>
                        <div><span class="{{ $production->status_badge }}">{{ $production->status }}</span></div>
                    </div>
                    @if($production->remarks)
                    <div class="col-md-12 mt-2">
                        <small class="text-muted">Remarks</small>
                        <div>{{ $production->remarks }}</div>
                    </div>
                    @endif
                </div>

                {{-- ── Raw Material ── --}}
                <h5 class="text-warning"><i class="fas fa-boxes me-1"></i> Raw Material Issued</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Product</th><th>Variation</th>
                                <th>Invoice</th><th class="text-end">Qty</th>
                                <th class="text-end">Rate</th><th class="text-end">Amount</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $rawTotal = 0; @endphp
                            @foreach($production->rawDetails as $i => $d)
                            @php $amt = $d->qty * $d->rate; $rawTotal += $amt; @endphp
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $d->product->name ?? '—' }}</td>
                                <td>{{ $d->variation->sku ?? '—' }}</td>
                                <td>{{ $d->invoice_id ? 'PUR-'.str_pad($d->invoice_id,5,'0',STR_PAD_LEFT) : '—' }}</td>
                                <td class="text-end">{{ number_format($d->qty, 2) }} {{ $d->measurementUnit->shortcode ?? '' }}</td>
                                <td class="text-end">{{ number_format($d->rate, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($amt, 2) }}</td>
                                <td>{{ $d->desc ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="6" class="text-end">Raw Total</td>
                                <td class="text-end">{{ number_format($rawTotal, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ── FG Items (CMT only) ── --}}
                @if($production->production_type === 'cmt' && $production->fgItems->count())
                <h5 class="text-success"><i class="fas fa-box me-1"></i> Finished Goods Ordered</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th><th>Product</th><th>Variation</th>
                                <th class="text-end">Qty Ordered</th>
                                <th class="text-end">Mfg Rate</th>
                                <th class="text-end">CMT Total</th>
                                <th>Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $fgTotal = 0; @endphp
                            @foreach($production->fgItems as $i => $f)
                            @php $amt = $f->qty * $f->manufacturing_rate; $fgTotal += $amt; @endphp
                            <tr>
                                <td>{{ $i+1 }}</td>
                                <td>{{ $f->product->name ?? '—' }}</td>
                                <td>{{ $f->variation->sku ?? '—' }}</td>
                                <td class="text-end">{{ number_format($f->qty, 2) }}</td>
                                <td class="text-end">{{ number_format($f->manufacturing_rate, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($amt, 2) }}</td>
                                <td>{{ $f->desc ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td colspan="5" class="text-end">CMT Total</td>
                                <td class="text-end">{{ number_format($fgTotal, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @endif

                {{-- ── Receivings summary ── --}}
                @if($production->receivings->count())
                <h5 class="text-info"><i class="fas fa-truck-loading me-1"></i> FG Received So Far</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr><th>#</th><th>GRN/Ref</th><th>Product</th><th class="text-end">Qty Received</th><th class="text-end">Mfg Rate</th></tr>
                        </thead>
                        <tbody>
                            @foreach($production->receivings as $rec)
                                @foreach($rec->details as $d)
                                <tr>
                                    <td>{{ $loop->parent->iteration }}.{{ $loop->iteration }}</td>
                                    <td>#{{ $rec->id }}</td>
                                    <td>{{ $d->product->name ?? '—' }}</td>
                                    <td class="text-end">{{ number_format($d->received_qty, 2) }}</td>
                                    <td class="text-end">{{ number_format($d->manufacturing_rate ?? 0, 2) }}</td>
                                </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>
        </section>
    </div>
</div>
@endsection