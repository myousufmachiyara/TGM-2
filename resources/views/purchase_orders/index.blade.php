@extends('layouts.app')

@section('title', 'Purchase Orders')

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
                <h2 class="card-title mb-0">Purchase Orders</h2>
                <div class="d-flex gap-2 align-items-center">
                    {{-- Status filter --}}
                    <form method="GET" action="{{ route('purchase_orders.index') }}" class="d-flex gap-2 mb-0">
                        <select name="status" class="form-control form-control-sm" onchange="this.form.submit()">
                            <option value="all"                {{ ($status ?? '') === 'all'                 ? 'selected' : '' }}>All</option>
                            <option value="Pending"            {{ ($status ?? '') === 'Pending'            ? 'selected' : '' }}>Pending</option>
                            <option value="Partially Received" {{ ($status ?? '') === 'Partially Received' ? 'selected' : '' }}>Partially Received</option>
                            <option value="Completed"          {{ ($status ?? '') === 'Completed'          ? 'selected' : '' }}>Completed</option>
                        </select>
                    </form>
                    @can('purchase_orders.create')
                        <a href="{{ route('purchase_orders.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> New PO
                        </a>
                    @endcan
                </div>
            </header>

            <div class="card-body">
                <div class="table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="cust-datatable-default">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>PO Number</th>
                                <th>Date</th>
                                <th>Vendor</th>
                                <th>Category</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $order->po_number }}</strong></td>
                                <td>{{ $order->order_date->format('d-m-Y') }}</td>
                                <td>{{ $order->vendor->name ?? '—' }}</td>
                                <td>{{ $order->category->name ?? '—' }}</td>
                                <td>
                                    {{ $order->items->map(fn($i) => optional($i->product)->name)->filter()->implode(', ') }}
                                </td>
                                <td>{{ number_format($order->items->sum('subtotal'), 2) }}</td>
                                <td>
                                    <span class="{{ $order->status_badge }}">{{ $order->status }}</span>
                                </td>
                                <td style="white-space:nowrap;">
                                    @can('purchase_orders.print')
                                        <a href="{{ route('purchase_orders.print', $order->id) }}"
                                           class="text-success me-1" target="_blank" title="Print PO">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    @endcan
                                    @can('purchase_invoices.create')
                                        <a href="{{ route('purchase_order_receivings.create', $order->id) }}"
                                           class="text-primary me-1" title="Receive against this PO">
                                            <i class="fa fa-arrow-down"></i>
                                        </a>
                                    @endcan
                                    @can('purchase_orders.edit')
                                        <a href="{{ route('purchase_orders.edit', $order->id) }}"
                                           class="text-warning me-1" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('purchase_orders.delete')
                                        <form action="{{ route('purchase_orders.destroy', $order->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete PO {{ $order->po_number }}?');">
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