@extends('layouts.app')
@section('title', 'Purchase Invoices')

@section('content')
<div class="row">
    <div class="col">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">Purchase Invoices</h2>
                @can('purchase_invoices.create')
                    <a href="{{ route('purchase_invoices.create') }}" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> New Invoice
                    </a>
                @endcan
            </header>

            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped table-sm align-middle" id="cust-datatable-default">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Invoice No</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Bill No</th>
                                <th>Ref No</th>
                                <th class="text-end">Items Total</th>
                                <th class="text-end">Conveyance</th>
                                <th class="text-end">Labour</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Net Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoices as $i => $invoice)
                            @php
                                $itemsTotal = $invoice->items->sum(fn($r) => $r->quantity * $r->price);
                                $net = $itemsTotal
                                     + (float)$invoice->convance_charges
                                     + (float)$invoice->labour_charges
                                     - (float)$invoice->bill_discount;
                            @endphp
                            <tr>
                                <td>{{ $i + 1 }}</td>
                                <td><strong>{{ $invoice->invoice_no }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') }}</td>
                                <td>{{ $invoice->vendor->name ?? '—' }}</td>
                                <td>{{ $invoice->bill_no ?? '—' }}</td>
                                <td>{{ $invoice->ref_no  ?? '—' }}</td>
                                <td class="text-end">{{ number_format($itemsTotal, 2) }}</td>
                                <td class="text-end">{{ number_format($invoice->convance_charges, 2) }}</td>
                                <td class="text-end">{{ number_format($invoice->labour_charges, 2) }}</td>
                                <td class="text-end text-danger">
                                    {{ $invoice->bill_discount > 0 ? '(' . number_format($invoice->bill_discount, 2) . ')' : '—' }}
                                </td>
                                <td class="text-end fw-bold">{{ number_format($net, 2) }}</td>
                                <td style="white-space:nowrap;">
                                    @can('purchase_invoices.print')
                                        <a href="{{ route('purchase_invoices.print', $invoice->id) }}"
                                           target="_blank" class="text-success me-1" title="Print">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    @endcan
                                    @can('purchase_invoices.edit')
                                        <a href="{{ route('purchase_invoices.edit', $invoice->id) }}"
                                           class="text-warning me-1" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('purchase_invoices.delete')
                                        <form action="{{ route('purchase_invoices.destroy', $invoice->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete {{ $invoice->invoice_no }}?');">
                                            @csrf
                                            @method('DELETE')
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