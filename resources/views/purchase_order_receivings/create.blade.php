@extends('layouts.app')

@section('title', 'Record Receiving | ' . $order->po_number)

@section('content')
<div class="row">
    <div class="col">
        <form action="{{ route('purchase_order_receivings.store') }}" method="POST"
              onkeydown="return event.key !== 'Enter';">
            @csrf

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            <input type="hidden" name="purchase_order_id" value="{{ $order->id }}">

            {{-- ── Header ── --}}
            <section class="card">
                <header class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0">Record Receiving — {{ $order->po_number }}</h2>
                    <a href="{{ route('purchase_orders.index') }}" class="btn btn-danger btn-sm">Discard</a>
                </header>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>GRN Number</label>
                            <input class="form-control" value="Auto-generated" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>PO Number</label>
                            <input class="form-control" value="{{ $order->po_number }}" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>Vendor</label>
                            <input class="form-control" value="{{ $order->vendor->name ?? '—' }}" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>Receiving Date <span class="text-danger">*</span></label>
                            <input type="date" name="received_date" class="form-control"
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="1"></textarea>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Items ── --}}
            <section class="card mt-3">
                <header class="card-header">
                    <h2 class="card-title mb-0">Item Details</h2>
                </header>
                <div class="card-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Ordered Qty</th>
                                <th>Already Received</th>
                                <th>Remaining</th>
                                <th>Receiving Now <span class="text-danger">*</span></th>
                                <th>Unit Price</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                            <tr class="receiving-row">
                                <td>{{ $item->product->name ?? '—' }}</td>
                                <td>{{ number_format($item->quantity, 2) }}</td>
                                <td>{{ number_format($item->already_received, 2) }}</td>
                                <td>
                                    <span class="{{ $item->remaining <= 0 ? 'text-muted' : 'text-danger fw-bold' }}">
                                        {{ number_format($item->remaining, 2) }}
                                    </span>
                                </td>
                                <td>
                                    <input type="number"
                                           name="quantities[{{ $item->id }}]"
                                           class="form-control recv-qty"
                                           step="0.01" min="0"
                                           max="{{ $item->remaining }}"
                                           {{ $item->remaining <= 0 ? 'disabled' : '' }}
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number"
                                           name="prices[{{ $item->id }}]"
                                           class="form-control recv-price"
                                           step="0.01" min="0"
                                           value="{{ $item->unit_price }}"
                                           {{ $item->remaining <= 0 ? 'disabled' : '' }}
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" class="form-control row-subtotal"
                                           step="0.01" disabled value="0.00">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <footer class="card-footer">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label>Total Qty Received</label>
                            <input type="number" id="totalQty" class="form-control" disabled value="0.00">
                        </div>
                        <div class="col-md-2">
                            <label>Total Bill</label>
                            <input type="number" id="totalBill" class="form-control" disabled value="0.00">
                        </div>
                    </div>
                </footer>
                <footer class="card-footer text-end">
                    <a href="{{ route('purchase_orders.index') }}" class="btn btn-danger">Discard</a>
                    <button type="submit" class="btn btn-primary">Record Receiving</button>
                </footer>
            </section>
        </form>
    </div>
</div>

@push('scripts')
<script>
function updateSummary() {
    let totalQty = 0, totalBill = 0;
    document.querySelectorAll('.receiving-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.recv-qty')?.value)   || 0;
        const price = parseFloat(row.querySelector('.recv-price')?.value) || 0;
        const sub   = qty * price;
        row.querySelector('.row-subtotal').value = sub.toFixed(2);
        totalQty  += qty;
        totalBill += sub;
    });
    document.getElementById('totalQty').value  = totalQty.toFixed(2);
    document.getElementById('totalBill').value = totalBill.toFixed(2);
}

document.querySelectorAll('.recv-qty, .recv-price').forEach(el =>
    el.addEventListener('input', updateSummary)
);
updateSummary();
</script>
@endpush

@endsection