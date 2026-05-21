@extends('layouts.app')

@section('title', 'Edit Receiving | ' . $receiving->grn_number)

@section('content')
<div class="row">
    <div class="col">
        <form action="{{ route('purchase_order_receivings.update', $receiving->id) }}" method="POST"
              onkeydown="return event.key !== 'Enter';">
            @csrf
            @method('PUT')

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

            {{-- ── Header ── --}}
            <section class="card">
                <header class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0">Edit {{ $receiving->grn_number }}</h2>
                    <a href="{{ route('purchase_order_receivings.index') }}" class="btn btn-danger btn-sm">Discard</a>
                </header>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>GRN Number</label>
                            <input class="form-control" value="{{ $receiving->grn_number }}" disabled>
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
                                   value="{{ old('received_date', $receiving->received_date->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="1">{{ old('remarks', $receiving->remarks) }}</textarea>
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
                                <th>Other Receivings</th>
                                <th>Remaining</th>
                                <th>Receiving Now</th>
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
                                <td>{{ number_format($item->remaining, 2) }}</td>
                                <td>
                                    <input type="number"
                                           name="quantities[{{ $item->id }}]"
                                           class="form-control recv-qty"
                                           step="0.01" min="0"
                                           value="{{ old('quantities.'.$item->id, $item->received_in_this) }}"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number"
                                           name="prices[{{ $item->id }}]"
                                           class="form-control recv-price"
                                           step="0.01" min="0"
                                           value="{{ old('prices.'.$item->id, $item->price_in_this) }}"
                                           placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" class="form-control row-subtotal" step="0.01"
                                           disabled value="{{ number_format($item->received_in_this * $item->price_in_this, 2) }}">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <footer class="card-footer">
                    <div class="row">
                        <div class="col-md-2">
                            <label>Total Qty</label>
                            <input type="number" id="totalQty" class="form-control" disabled value="0.00">
                        </div>
                        <div class="col-md-2">
                            <label>Total Bill</label>
                            <input type="number" id="totalBill" class="form-control" disabled value="0.00">
                        </div>
                    </div>
                </footer>
                <footer class="card-footer text-end">
                    <a href="{{ route('purchase_order_receivings.index') }}" class="btn btn-danger">Discard</a>
                    <button type="submit" class="btn btn-primary">Update Receiving</button>
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