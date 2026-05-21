@extends('layouts.app')
@section('title', 'Production Receiving | New')

@section('content')
<div class="row">
    <div class="col">
        <form action="{{ route('production_receiving.store') }}" method="POST"
              onkeydown="return event.key !== 'Enter';">
            @csrf

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
                    <h2 class="card-title mb-0">New Production Receiving</h2>
                    <a href="{{ route('production_receiving.index') }}" class="btn btn-danger btn-sm">Discard</a>
                </header>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>GRN No</label>
                            <input class="form-control" value="Auto-generated" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>Receiving Date <span class="text-danger">*</span></label>
                            <input type="date" name="rec_date" class="form-control"
                                   value="{{ old('rec_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label>Production Order</label>
                            <select name="production_id" id="productionSelect" class="form-control select2-js">
                                <option value="">— No Production Order —</option>
                                @foreach($productions as $prod)
                                    <option value="{{ $prod->id }}"
                                        {{ old('production_id', $selectedProductionId) == $prod->id ? 'selected' : '' }}>
                                        #{{ $prod->id }} — {{ $prod->vendor->name ?? '' }}
                                        ({{ $prod->production_type === 'cmt' ? 'CMT' : 'Sell Raw' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Selecting a PO auto-loads its FG items</small>
                        </div>
                        <div class="col-md-3">
                            <label>Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" id="vendorSelect" class="form-control select2-js" required>
                                <option value="">— Select Vendor —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}" {{ old('vendor_id') == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional">
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Items Table ── --}}
            <section class="card mt-3">
                <header class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0">
                        <i class="fas fa-box-open me-1 text-success"></i> Finished Goods Received
                    </h2>
                    <button type="button" class="btn btn-outline-success btn-sm" id="addRowBtn">
                        <i class="fas fa-plus"></i> Add Row
                    </button>
                </header>
                <div class="card-body" style="overflow-x:auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:220px">Product <span class="text-danger">*</span></th>
                                <th style="min-width:160px">Variation</th>
                                <th style="min-width:100px">Ordered Qty</th>
                                <th style="min-width:110px">Mfg Cost/pc <span class="text-danger">*</span></th>
                                <th style="min-width:110px">Received Qty <span class="text-danger">*</span></th>
                                <th style="min-width:110px">Total CMT</th>
                                <th style="min-width:150px">Remarks</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="receivingBody">
                            {{-- Rows are pre-filled via JS when a production order is selected --}}
                        </tbody>
                    </table>
                    <div id="emptyMsg" class="text-center text-muted py-3">
                        Select a Production Order above to auto-load items, or add rows manually.
                    </div>
                </div>

                <footer class="card-footer">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label>Total Pcs</label>
                            <input type="number" id="totalPcs" class="form-control" disabled value="0.00">
                        </div>
                        <div class="col-md-2">
                            <label>Sub Total</label>
                            <input type="number" id="subTotal" class="form-control" disabled value="0.00">
                        </div>
                        <div class="col-md-2">
                            <label>Conveyance</label>
                            <input type="number" name="convance_charges" id="conveyance"
                                   class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-2">
                            <label>Discount</label>
                            <input type="number" name="bill_discount" id="discount"
                                   class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4 text-end">
                            <h5 class="text-primary mb-0">
                                Net Total: PKR <span id="netTotal" class="text-danger fw-bold">0.00</span>
                            </h5>
                        </div>
                    </div>
                </footer>
                <footer class="card-footer text-end">
                    <a href="{{ route('production_receiving.index') }}" class="btn btn-danger">Discard</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Save Receiving
                    </button>
                </footer>
            </section>
        </form>
    </div>
</div>

@push('scripts')
<script>
let rowIdx = 0;

// ── Build a single receiving row ──────────────────────────────────────
function buildRow(idx, productId = '', productName = '', variationId = '', variationSku = '',
                  orderedQty = '', mfgRate = 0, variations = []) {

    const varOptions = variations.map(v =>
        `<option value="${v.id}" ${v.id == variationId ? 'selected' : ''}>${v.sku}</option>`
    ).join('');

    return `
    <tr>
        <td>
            <input type="hidden" name="item_details[${idx}][product_id]" class="product-id-input" value="${productId}">
            <span class="fw-bold">${productName || '—'}</span>
            <small class="text-muted d-block" style="font-size:11px;">ID: ${productId}</small>
        </td>
        <td>
            <select name="item_details[${idx}][variation_id]" class="form-control select2-js variation-select">
                <option value="">— None —</option>
                ${varOptions}
            </select>
        </td>
        <td>
            <input type="number" class="form-control ordered-qty" step="0.01" disabled
                   value="${orderedQty || '—'}" style="background:#f8f9fa;">
        </td>
        <td>
            <input type="number" name="item_details[${idx}][manufacturing_cost]"
                   class="form-control mfg-cost" step="0.01" min="0" value="${mfgRate}" required>
        </td>
        <td>
            <input type="number" name="item_details[${idx}][received_qty]"
                   class="form-control recv-qty" step="0.01" min="0.01" placeholder="0.00" required>
        </td>
        <td>
            <input type="number" class="form-control row-total" step="0.01" disabled value="0.00">
        </td>
        <td>
            <input type="text" name="item_details[${idx}][remarks]" class="form-control" placeholder="Optional">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
        </td>
    </tr>`;
}

// ── Build a blank manual row ──────────────────────────────────────────
function buildManualRow(idx) {
    return `
    <tr>
        <td>
            <select name="item_details[${idx}][product_id]" class="form-control select2-js product-select" required>
                <option value="">— Select Product —</option>
            </select>
        </td>
        <td>
            <select name="item_details[${idx}][variation_id]" class="form-control select2-js variation-select">
                <option value="">— None —</option>
            </select>
        </td>
        <td>
            <input type="number" class="form-control ordered-qty" step="0.01" disabled
                   value="—" style="background:#f8f9fa;">
        </td>
        <td>
            <input type="number" name="item_details[${idx}][manufacturing_cost]"
                   class="form-control mfg-cost" step="0.01" min="0" value="0" required>
        </td>
        <td>
            <input type="number" name="item_details[${idx}][received_qty]"
                   class="form-control recv-qty" step="0.01" min="0.01" placeholder="0.00" required>
        </td>
        <td>
            <input type="number" class="form-control row-total" step="0.01" disabled value="0.00"></td>
        <td>
            <input type="text" name="item_details[${idx}][remarks]" class="form-control" placeholder="Optional">
        </td>
        <td>
            <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
        </td>
    </tr>`;
}

// ── Load FG items from selected production order ───────────────────────
$('#productionSelect').on('change', function () {
    const productionId = $(this).val();

    if (!productionId) {
        $('#receivingBody').empty();
        $('#emptyMsg').show();
        recalcSummary();
        return;
    }

    $.get(`/production/${productionId}/fg-items`, function (data) {
        // Auto-select vendor
        if (data.vendor_id) {
            $('#vendorSelect').val(data.vendor_id).trigger('change.select2');
        }

        $('#receivingBody').empty();
        rowIdx = 0;

        if (!data.fg_items || data.fg_items.length === 0) {
            $('#emptyMsg').show().text('This production order has no FG items. Add rows manually.');
            return;
        }

        $('#emptyMsg').hide();

        data.fg_items.forEach(function (item) {
            const row = buildRow(
                rowIdx,
                item.product_id,
                item.product_name,
                item.variation_id,
                item.variation_sku,
                item.qty_ordered,
                item.manufacturing_rate,
                item.variations
            );
            $('#receivingBody').append(row);
            rowIdx++;
        });

        rebindSelect2();
        recalcSummary();

    }).fail(function () {
        alert('Failed to load production order items.');
    });
});

// ── Add blank row manually ────────────────────────────────────────────
$('#addRowBtn').on('click', function () {
    $('#emptyMsg').hide();
    $('#receivingBody').append(buildManualRow(rowIdx));
    rowIdx++;
    rebindSelect2();
    recalcSummary();
});

// ── Remove row ────────────────────────────────────────────────────────
$(document).on('click', '.remove-row', function () {
    $(this).closest('tr').remove();
    if ($('#receivingBody tr').length === 0) $('#emptyMsg').show();
    recalcSummary();
});

// ── Product select (manual rows) ──────────────────────────────────────
$(document).on('change', '.product-select', function () {
    const row = $(this).closest('tr');
    const pid = $(this).val();
    if (!pid) return;
    const $var = row.find('.variation-select');
    $var.html('<option value="">Loading…</option>').prop('disabled', true);
    $.get(`/product/${pid}/variations`, function (data) {
        const vars = data.variation || [];
        let html = '<option value="">— None —</option>';
        vars.forEach(v => { html += `<option value="${v.id}">${v.sku}</option>`; });
        $var.html(html).prop('disabled', false);
        if ($var.hasClass('select2-hidden-accessible')) $var.select2('destroy');
        $var.select2({ width: '100%' });
    });
});

// ── Recalculate ───────────────────────────────────────────────────────
function recalcSummary() {
    let pcs = 0, sub = 0;
    $('#receivingBody tr').each(function () {
        const qty  = parseFloat($(this).find('.recv-qty').val())  || 0;
        const cost = parseFloat($(this).find('.mfg-cost').val())  || 0;
        const tot  = qty * cost;
        $(this).find('.row-total').val(tot.toFixed(2));
        pcs += qty;
        sub += tot;
    });
    const conv = parseFloat($('#conveyance').val()) || 0;
    const disc = parseFloat($('#discount').val())   || 0;
    const net  = sub + conv - disc;
    $('#totalPcs').val(pcs.toFixed(2));
    $('#subTotal').val(sub.toFixed(2));
    $('#netTotal').text(net.toLocaleString('en-PK', { minimumFractionDigits: 2 }));
}

function rebindSelect2() {
    $('.select2-js').each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) $(this).select2({ width: '100%' });
    });
}

$(document).on('input', '.recv-qty, .mfg-cost', recalcSummary);
$('#conveyance, #discount').on('input', recalcSummary);

$(document).ready(function () {
    rebindSelect2();
    // Auto-load if a production ID is pre-selected from URL
    if ($('#productionSelect').val()) {
        $('#productionSelect').trigger('change');
    }
});
</script>
@endpush
@endsection