{{-- ============================================================
     resources/views/purchase_order_receivings/index.blade.php
     ============================================================ --}}
@extends('layouts.app')

@section('title', 'Goods Receiving Notes')

@section('content')
<div class="row">
    <div class="col">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Goods Receiving Notes (GRN)</h2>
            </header>
            <div class="card-body">
                <div class="table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="cust-datatable-default">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>GRN Number</th>
                                <th>Date</th>
                                <th>PO Number</th>
                                <th>Vendor</th>
                                <th>Total Qty</th>
                                <th>Total Value</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receivings as $rec)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $rec->grn_number }}</strong></td>
                                <td>{{ $rec->received_date->format('d-m-Y') }}</td>
                                <td>{{ $rec->purchaseOrder->po_number ?? '—' }}</td>
                                <td>{{ $rec->purchaseOrder->vendor->name ?? '—' }}</td>
                                <td>{{ number_format($rec->receivingItems->sum('quantity'), 2) }}</td>
                                <td>{{ number_format($rec->receivingItems->sum('subtotal'), 2) }}</td>
                                <td style="white-space:nowrap;">
                                    @can('purchase_invoices.print')
                                        <a href="{{ route('purchase_order_receivings.print', $rec->id) }}"
                                           class="text-success me-1" target="_blank" title="Print GRN">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    @endcan
                                    @can('purchase_invoices.edit')
                                        <a href="{{ route('purchase_order_receivings.edit', $rec->id) }}"
                                           class="text-warning me-1" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('purchase_invoices.delete')
                                        <form action="{{ route('purchase_order_receivings.destroy', $rec->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete GRN {{ $rec->grn_number }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link p-0 text-danger">
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