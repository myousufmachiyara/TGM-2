@extends('layouts.app')
@section('title', 'Production Receivings')

@section('content')
<div class="row">
    <div class="col">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Production Receivings (GRN)</h2>
                @can('production_receiving.create')
                    <a href="{{ route('production_receiving.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> New Receiving
                    </a>
                @endcan
            </header>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm align-middle" id="cust-datatable-default">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>GRN No</th>
                                <th>Date</th>
                                <th>Production #</th>
                                <th>Vendor</th>
                                <th class="text-end">Total Pcs</th>
                                <th class="text-end">CMT Total</th>
                                <th class="text-end">Conveyance</th>
                                <th class="text-end">Net Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receivings as $i => $rec)
                            @php
                                $cmtTotal = $rec->details->sum(fn($d) => $d->manufacturing_cost * $d->received_qty);
                                $net      = $cmtTotal + (float)$rec->convance_charges - (float)$rec->bill_discount;
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $rec->grn_no }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($rec->rec_date)->format('d-m-Y') }}</td>
                                <td>
                                    @if($rec->production_id)
                                        <a href="{{ route('production.show', $rec->production_id) }}"
                                           class="text-primary">#{{ $rec->production_id }}</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td>{{ $rec->vendor->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format($rec->details->sum('received_qty'), 2) }}</td>
                                <td class="text-end">{{ number_format($cmtTotal, 2) }}</td>
                                <td class="text-end">{{ number_format($rec->convance_charges, 2) }}</td>
                                <td class="text-end fw-bold">{{ number_format($net, 2) }}</td>
                                <td style="white-space:nowrap;">
                                    @can('production_receiving.print')
                                        <a href="{{ route('production_receiving.print', $rec->id) }}"
                                           target="_blank" class="text-success me-1" title="Print">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    @endcan
                                    @can('production_receiving.edit')
                                        <a href="{{ route('production_receiving.edit', $rec->id) }}"
                                           class="text-warning me-1" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('production_receiving.delete')
                                        <form action="{{ route('production_receiving.destroy', $rec->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete {{ $rec->grn_no }}?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-link p-0 text-danger" title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection