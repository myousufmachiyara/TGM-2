{{-- resources/views/purchase_orders/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Purchase Orders | New')

@section('content')
<div class="row">
    <div class="col">
        <form action="{{ route('purchase_orders.store') }}" method="POST" enctype="multipart/form-data"
              onkeydown="return event.key !== 'Enter';">
            @csrf

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif

            {{-- ── Header card ──────────────────────────────────────────── --}}
            <section class="card">
                <header class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0">New Purchase Order</h2>
                    <a href="{{ route('purchase_orders.index') }}" class="btn btn-danger btn-sm">Discard</a>
                </header>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>PO Number</label>
                            <input class="form-control" value="Auto-generated" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" class="form-control select2-js" required>
                                <option value="">— Select Vendor —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Category</label>
                            <select name="category_id" class="form-control select2-js">
                                <option value="">— None —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Order Date <span class="text-danger">*</span></label>
                            <input type="date" name="order_date" class="form-control"
                                   value="{{ old('order_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label>Ordered By</label>
                            <input type="text" name="ordered_by" class="form-control"
                                   value="{{ old('ordered_by') }}" placeholder="Name">
                        </div>
                        <div class="col-md-2">
                            <label>Attachments</label>
                            <input type="file" name="attachments[]" class="form-control"
                                   multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                        <div class="col-md-4">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Items card ───────────────────────────────────────────── --}}
            <section class="card mt-3">
                <header class="card-header">
                    <h2 class="card-title mb-0">Order Items</h2>
                </header>
                <div class="card-body" style="overflow-x:auto;">
                    <table class="table table-bordered" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="min-width:220px">Product <span class="text-danger">*</span></th>
                                <th style="min-width:90px">Width</th>
                                <th style="min-width:160px">Description</th>
                                <th style="min-width:100px">Unit Price <span class="text-danger">*</span></th>
                                <th style="min-width:100px">Quantity <span class="text-danger">*</span></th>
                                <th style="min-width:100px">Unit</th>
                                <th style="min-width:110px">Subtotal</th>
                                <th style="width:80px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr>
                                <td>
                                    <select name="items[0][product_id]" class="form-control select2-js product-select" required>
                                        <option value="">— Select Product —</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}"
                                                    data-unit="{{ $p->measurementUnit->shortcode ?? '' }}">
                                                {{ $p->sku }} — {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input type="number" name="items[0][width]" class="form-control" step="any" min="0" value="0"></td>
                                <td><input type="text"   name="items[0][description]" class="form-control"></td>
                                <td><input type="number" name="items[0][unit_price]" class="form-control unit-price" step="any" min="0" value="0" required></td>
                                <td><input type="number" name="items[0][quantity]"   class="form-control qty" step="any" min="0.01" required></td>
                                <td><input type="text" class="form-control unit-display" disabled></td>
                                <td><input type="number" class="form-control subtotal" step="any" disabled value="0"></td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addItemBtn">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
                <footer class="card-footer">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label>Total Quantity</label>
                            <input type="number" id="totalQty" class="form-control" disabled value="0">
                        </div>
                        <div class="col-md-2">
                            <label>Total Amount</label>
                            <input type="number" id="totalAmt" class="form-control" disabled value="0">
                        </div>
                        <div class="col-md-4 offset-md-4 text-end">
                            <h5 class="text-primary">Net Total: PKR <span id="netTotal" class="text-danger fw-bold">0.00</span></h5>
                        </div>
                    </div>
                </footer>
                <footer class="card-footer text-end">
                    <a href="{{ route('purchase_orders.index') }}" class="btn btn-danger">Discard</a>
                    <button type="submit" class="btn btn-primary">Create Purchase Order</button>
                </footer>
            </section>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const products = @json($productData);

    const vendorProducts = @json($vendorProducts);

let rowIdx = 1;

function getFilteredProducts(vendorId) {
    if (!vendorId || !vendorProducts[vendorId]) return products;
    const ids = vendorProducts[vendorId];
    return products.filter(p => ids.includes(p.id));
}

function buildProductOptions(selectedId = '') {
    const vendorId   = $('select[name="vendor_id"]').val();
    const filtered   = getFilteredProducts(vendorId);
    const placeholder = `<option value="">— Select Product —</option>`;
    const options    = filtered.map(p =>
        `<option value="${p.id}" data-unit="${p.unit}" ${p.id == selectedId ? 'selected' : ''}>
            ${p.sku} — ${p.name}
        </option>`
    ).join('');
    return placeholder + options;
}

function refreshAllProductSelects() {
    $('#itemsBody tr').each(function () {
        const select     = $(this).find('.product-select');
        const selectedId = select.val();
        select.html(buildProductOptions(selectedId));
        select.trigger('change.select2');
    });
    rebindSelect2();
}

function addRow() {
    const row = `
        <tr>
            <td>
                <select name="items[${rowIdx}][product_id]" class="form-control select2-js product-select" required>
                    ${buildProductOptions()}
                </select>
            </td>
            <td><input type="number" name="items[${rowIdx}][width]"       class="form-control" step="any" min="0" value="0"></td>
            <td><input type="text"   name="items[${rowIdx}][description]" class="form-control"></td>
            <td><input type="number" name="items[${rowIdx}][unit_price]"  class="form-control unit-price" step="any" min="0" value="0" required></td>
            <td><input type="number" name="items[${rowIdx}][quantity]"    class="form-control qty" step="any" min="0.01" required></td>
            <td><input type="text" class="form-control unit-display" disabled></td>
            <td><input type="number" class="form-control subtotal" step="any" disabled value="0"></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
        </tr>`;
    $('#itemsBody').append(row);
    rowIdx++;
    rebindSelect2();
    recalculate();
}

function recalculate() {
    let totalQty = 0, totalAmt = 0;
    $('#itemsBody tr').each(function () {
        const price    = parseFloat($(this).find('.unit-price').val()) || 0;
        const qty      = parseFloat($(this).find('.qty').val()) || 0;
        const subtotal = price * qty;
        $(this).find('.subtotal').val(subtotal.toFixed(2));
        totalQty += qty;
        totalAmt += subtotal;
    });
    $('#totalQty').val(totalQty.toFixed(2));
    $('#totalAmt').val(totalAmt.toFixed(2));
    $('#netTotal').text(totalAmt.toLocaleString('en-PK', { minimumFractionDigits: 2 }));
}

function rebindSelect2() { $('.select2-js').select2(); }

// ── Vendor change → refresh product dropdowns ─────────────────────────
$('select[name="vendor_id"]').on('change', function () {
    refreshAllProductSelects();
});

$('#addItemBtn').on('click', addRow);

$(document).on('click', '.remove-row', function () {
    if ($('#itemsBody tr').length > 1) {
        $(this).closest('tr').remove();
        recalculate();
    }
});

$(document).on('change', '.product-select', function () {
    const unit = $(this).find('option:selected').data('unit') || '';
    $(this).closest('tr').find('.unit-display').val(unit);
});

$(document).on('input', '.unit-price, .qty', recalculate);

$(document).ready(function () {
    rebindSelect2();
    recalculate();
    // Trigger on load in case vendor is pre-selected (e.g. old() on validation fail)
    if ($('select[name="vendor_id"]').val()) {
        refreshAllProductSelects();
    }
});
</script>
@endpush

@endsection