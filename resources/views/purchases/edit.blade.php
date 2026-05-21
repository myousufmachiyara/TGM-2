@extends('layouts.app')
@section('title', 'Purchase Invoices | Edit ' . $invoice->invoice_no)

@section('content')
<div class="row">
    <div class="col">
        <form action="{{ route('purchase_invoices.update', $invoice->id) }}" method="POST"
              enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            {{-- ── Header ── --}}
            <section class="card">
                <header class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0">Edit {{ $invoice->invoice_no }}</h2>
                    <a href="{{ route('purchase_invoices.index') }}" class="btn btn-danger btn-sm">Discard</a>
                </header>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>Invoice No</label>
                            <input class="form-control" value="{{ $invoice->invoice_no }}" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>Invoice Date <span class="text-danger">*</span></label>
                            <input type="date" name="invoice_date" class="form-control"
                                   value="{{ old('invoice_date', \Carbon\Carbon::parse($invoice->invoice_date)->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label>Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" class="form-control select2-js" required>
                                <option value="">— Select Vendor —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}"
                                        {{ old('vendor_id', $invoice->vendor_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Bill No</label>
                            <input type="text" name="bill_no" class="form-control"
                                   value="{{ old('bill_no', $invoice->bill_no) }}">
                        </div>
                        <div class="col-md-2">
                            <label>Ref No</label>
                            <input type="text" name="ref_no" class="form-control"
                                   value="{{ old('ref_no', $invoice->ref_no) }}">
                        </div>
                        <div class="col-md-2">
                            <label>Payment Terms</label>
                            <input type="text" name="payment_terms" class="form-control"
                                   value="{{ old('payment_terms', $invoice->payment_terms) }}">
                        </div>
                        <div class="col-md-3">
                            <label>Add Attachments</label>
                            <input type="file" name="attachments[]" class="form-control"
                                   multiple accept=".jpg,.jpeg,.png,.pdf,.zip">
                        </div>
                        <div class="col-md-5">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $invoice->remarks) }}</textarea>
                        </div>

                        @if($invoice->attachments->count())
                        <div class="col-md-12">
                            <label>Existing Attachments</label>
                            <div class="d-flex gap-2 flex-wrap mt-1">
                                @foreach($invoice->attachments as $att)
                                    <a href="{{ asset('storage/' . $att->file_path) }}" target="_blank"
                                       class="btn btn-outline-secondary btn-sm">
                                        <i class="fa fa-paperclip"></i> Attachment {{ $loop->iteration }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </section>

            {{-- ── Items ── --}}
            <section class="card mt-3">
                <header class="card-header">
                    <h2 class="card-title mb-0">Invoice Items</h2>
                </header>
                <div class="card-body" style="overflow-x:auto;">
                    <table class="table table-bordered table-sm" id="itemsTable">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:220px">Product <span class="text-danger">*</span></th>
                                <th style="min-width:180px">Variation</th>
                                <th style="min-width:110px">Unit <span class="text-danger">*</span></th>
                                <th style="min-width:100px">Qty <span class="text-danger">*</span></th>
                                <th style="min-width:100px">Rate <span class="text-danger">*</span></th>
                                <th style="min-width:110px">Amount</th>
                                <th style="min-width:160px">Remarks</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            @foreach($invoice->items as $i => $item)
                            <tr>
                                <td>
                                    <select name="items[{{ $i }}][item_id]"
                                            class="form-control select2-js product-select" required>
                                        <option value="">— Select Product —</option>
                                        @foreach($products as $p)
                                            <option value="{{ $p->id }}"
                                                    data-unit="{{ $p->measurementUnit->id ?? '' }}"
                                                    {{ $item->item_id == $p->id ? 'selected' : '' }}>
                                                {{ $p->sku ?? '' }} — {{ $p->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="items[{{ $i }}][variation_id]"
                                            class="form-control select2-js variation-select">
                                        <option value="">— No Variation —</option>
                                        @foreach($item->product->variations ?? [] as $var)
                                            <option value="{{ $var->id }}"
                                                {{ $item->variation_id == $var->id ? 'selected' : '' }}>
                                                {{ $var->sku }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="items[{{ $i }}][unit]"
                                            class="form-control select2-js unit-select" required>
                                        <option value="">—</option>
                                        @foreach($units as $u)
                                            <option value="{{ $u->id }}"
                                                {{ $item->unit == $u->id ? 'selected' : '' }}>
                                                {{ $u->shortcode }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $i }}][quantity]"
                                           class="form-control qty" step="any" min="0.01"
                                           value="{{ $item->quantity }}" required>
                                </td>
                                <td>
                                    <input type="number" name="items[{{ $i }}][price]"
                                           class="form-control price" step="any" min="0"
                                           value="{{ $item->price }}" required>
                                </td>
                                <td>
                                    <input type="number" class="form-control row-amount" step="any" disabled
                                           value="{{ number_format($item->quantity * $item->price, 2) }}">
                                </td>
                                <td>
                                    <input type="text" name="items[{{ $i }}][item_remarks]"
                                           class="form-control" value="{{ $item->remarks }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addItemBtn">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>

                <footer class="card-footer">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label>Items Total</label>
                            <input type="number" id="itemsTotal" class="form-control" disabled value="0.00">
                        </div>
                        <div class="col-md-2">
                            <label>Conveyance</label>
                            <input type="number" name="convance_charges" id="conveyance"
                                   class="form-control" step="any" min="0"
                                   value="{{ old('convance_charges', $invoice->convance_charges) }}">
                        </div>
                        <div class="col-md-2">
                            <label>Labour</label>
                            <input type="number" name="labour_charges" id="labour"
                                   class="form-control" step="any" min="0"
                                   value="{{ old('labour_charges', $invoice->labour_charges) }}">
                        </div>
                        <div class="col-md-2">
                            <label>Discount</label>
                            <input type="number" name="bill_discount" id="discount"
                                   class="form-control" step="any" min="0"
                                   value="{{ old('bill_discount', $invoice->bill_discount) }}">
                        </div>
                        <div class="col-md-4 text-end">
                            <h5 class="text-primary mb-0">
                                Net Total: PKR <span id="netTotal" class="text-danger fw-bold">0.00</span>
                            </h5>
                        </div>
                    </div>
                </footer>
                <footer class="card-footer text-end">
                    <a href="{{ route('purchase_invoices.index') }}" class="btn btn-danger">Discard</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Invoice
                    </button>
                </footer>
            </section>
        </form>
    </div>
</div>

@push('scripts')
<script>
const allProducts    = @php echo json_encode($productData); @endphp;
const vendorProducts = @php echo json_encode($vendorProducts); @endphp;
const allUnits       = @php echo json_encode($units->map(fn($u) => ['id' => $u->id, 'name' => $u->shortcode])->values()); @endphp;

let rowIdx = {{ $invoice->items->count() }};

function getFilteredProducts(vendorId) {
    if (!vendorId || !vendorProducts[vendorId]) return allProducts;
    const ids = vendorProducts[vendorId];
    return allProducts.filter(p => ids.includes(p.id));
}

function buildProductOptions(selectedId = '') {
    const vendorId = $('select[name="vendor_id"]').val();
    const filtered = getFilteredProducts(vendorId);
    return '<option value="">— Select Product —</option>'
        + filtered.map(p =>
            `<option value="${p.id}" data-unit="${p.unit_id}" ${p.id == selectedId ? 'selected' : ''}>
                ${p.sku} — ${p.name}
            </option>`
        ).join('');
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

function buildUnitOptions(selectedId = '') {
    return '<option value="">—</option>'
        + allUnits.map(u =>
            `<option value="${u.id}" ${u.id == selectedId ? 'selected' : ''}>${u.name}</option>`
        ).join('');
}

function addRow() {
    const row = `
        <tr>
            <td>
                <select name="items[${rowIdx}][item_id]" class="form-control select2-js product-select" required>
                    ${buildProductOptions()}
                </select>
            </td>
            <td>
                <select name="items[${rowIdx}][variation_id]" class="form-control select2-js variation-select">
                    <option value="">— No Variation —</option>
                </select>
            </td>
            <td>
                <select name="items[${rowIdx}][unit]" class="form-control select2-js unit-select" required>
                    ${buildUnitOptions()}
                </select>
            </td>
            <td><input type="number" name="items[${rowIdx}][quantity]"     class="form-control qty"   step="any" min="0.01" placeholder="0.00" required></td>
            <td><input type="number" name="items[${rowIdx}][price]"        class="form-control price" step="any" min="0"    placeholder="0.00" required></td>
            <td><input type="number" class="form-control row-amount" step="any" disabled value="0.00"></td>
            <td><input type="text"   name="items[${rowIdx}][item_remarks]" class="form-control" placeholder="Optional"></td>
            <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
        </tr>`;
    $('#itemsBody').append(row);
    rowIdx++;
    rebindSelect2();
    recalculate();
}

function recalculate() {
    let subTotal = 0;
    $('#itemsBody tr').each(function () {
        const qty   = parseFloat($(this).find('.qty').val())   || 0;
        const price = parseFloat($(this).find('.price').val()) || 0;
        const amt   = qty * price;
        $(this).find('.row-amount').val(amt.toFixed(2));
        subTotal += amt;
    });
    const conv     = parseFloat($('#conveyance').val()) || 0;
    const labour   = parseFloat($('#labour').val())     || 0;
    const discount = parseFloat($('#discount').val())   || 0;
    const net      = subTotal + conv + labour - discount;
    $('#itemsTotal').val(subTotal.toFixed(2));
    $('#netTotal').text(net.toLocaleString('en-PK', { minimumFractionDigits: 2 }));
}

function loadVariations(row, productId, preselectId = null) {
    const $sel = row.find('.variation-select');
    $sel.html('<option>Loading…</option>').prop('disabled', true);
    $.get(`/product/${productId}/variations`, function (data) {
        const vars = data.variation || [];
        let html = '<option value="">— No Variation —</option>';
        if (vars.length > 0) {
            vars.forEach(v => {
                html += `<option value="${v.id}" ${v.id == preselectId ? 'selected' : ''}>${v.sku}</option>`;
            });
            $sel.prop('disabled', false);
        } else {
            html = '<option value="" disabled selected>No Variations</option>';
        }
        $sel.html(html);
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
        $sel.select2({ width: '100%' });
    });
}

function rebindSelect2() {
    $('.select2-js').each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) {
            $(this).select2({ width: '100%' });
        }
    });
}

$('select[name="vendor_id"]').on('change', function () {
    refreshAllProductSelects();
});

$(document).on('change', '.product-select', function () {
    const row       = $(this).closest('tr');
    const productId = $(this).val();
    const unitId    = $(this).find('option:selected').data('unit');
    if (unitId) row.find('.unit-select').val(unitId).trigger('change.select2');
    if (productId) {
        loadVariations(row, productId);
    } else {
        row.find('.variation-select')
           .html('<option value="">— No Variation —</option>')
           .prop('disabled', false);
    }
    recalculate();
});

$('#addItemBtn').on('click', addRow);

$(document).on('click', '.remove-row', function () {
    if ($('#itemsBody tr').length > 1) {
        $(this).closest('tr').remove();
        recalculate();
    }
});

$(document).on('input', '.qty, .price', recalculate);
$('#conveyance, #labour, #discount').on('input', recalculate);

$(document).ready(function () {
    rebindSelect2();
    recalculate();
    // Vendor already selected on edit — apply filter on load
    if ($('select[name="vendor_id"]').val()) {
        refreshAllProductSelects();
    }
});
</script>
@endpush
@endsection