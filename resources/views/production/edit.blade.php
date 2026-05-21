@extends('layouts.app')
@section('title', 'Production | Edit #' . $production->id)

@section('content')
<div class="row">
    <div class="col">
        <form action="{{ route('production.update', $production->id) }}" method="POST"
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
                    <h2 class="card-title mb-0">Edit Production Order #{{ $production->id }}</h2>
                    <a href="{{ route('production.index') }}" class="btn btn-danger btn-sm">Discard</a>
                </header>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" id="vendorSelect" class="form-control select2-js" required>
                                <option value="">— Select Vendor —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}"
                                        {{ old('vendor_id', $production->vendor_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Production Type <span class="text-danger">*</span></label>
                            <select name="production_type" id="productionType" class="form-control" required>
                                <option value="cmt"      {{ old('production_type', $production->production_type) == 'cmt'      ? 'selected' : '' }}>CMT (Give raw → get FG)</option>
                                <option value="sell_raw" {{ old('production_type', $production->production_type) == 'sell_raw' ? 'selected' : '' }}>Sell Raw to Manufacturer</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Category</label>
                            <select name="category_id" class="form-control select2-js">
                                <option value="">— None —</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ old('category_id', $production->category_id) == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Order Date <span class="text-danger">*</span></label>
                            <input type="date" name="order_date" class="form-control"
                                   value="{{ old('order_date', \Carbon\Carbon::parse($production->order_date)->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-2">
                            <label>Add Attachments</label>
                            <input type="file" name="attachments[]" class="form-control"
                                   multiple accept=".jpg,.jpeg,.png,.webp,.pdf">
                        </div>
                        <div class="col-md-4">
                            <label>Remarks</label>
                            <textarea name="remarks" class="form-control" rows="2">{{ old('remarks', $production->remarks) }}</textarea>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ── Raw Material Table ── --}}
            <section class="card mt-3">
                <header class="card-header">
                    <h2 class="card-title mb-0">
                        <i class="fas fa-boxes me-1 text-warning"></i> Raw Material Issued to Vendor
                    </h2>
                </header>
                <div class="card-body" style="overflow-x:auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:200px">Raw Product <span class="text-danger">*</span></th>
                                <th style="min-width:150px">Variation</th>
                                <th style="min-width:170px">Purchase Invoice</th>
                                <th style="min-width:90px">Unit <span class="text-danger">*</span></th>
                                <th style="min-width:110px">Available Stock</th>
                                <th style="min-width:100px">Qty <span class="text-danger">*</span></th>
                                <th style="min-width:100px">Rate <span class="text-danger">*</span></th>
                                <th style="min-width:100px">Amount</th>
                                <th style="min-width:140px">Description</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="rawBody">
                            @foreach($production->rawDetails as $i => $detail)
                            <tr>
                                <td>
                                    <select name="raw_items[{{ $i }}][product_id]" class="form-control select2-js raw-product-select" required>
                                        <option value="">— Select Raw —</option>
                                        @foreach(json_decode(json_encode($rawProducts), true) as $p)
                                            <option value="{{ $p['id'] }}" data-unit="{{ $p['unit_id'] }}"
                                                {{ $detail->product_id == $p['id'] ? 'selected' : '' }}>
                                                {{ $p['sku'] ? $p['sku'].' — ' : '' }}{{ $p['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="raw_items[{{ $i }}][variation_id]" class="form-control select2-js raw-variation-select">
                                        <option value="">— None —</option>
                                        @foreach($detail->product->variations ?? [] as $var)
                                            <option value="{{ $var->id }}" {{ $detail->variation_id == $var->id ? 'selected' : '' }}>
                                                {{ $var->sku }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="raw_items[{{ $i }}][invoice_id]" class="form-control select2-js raw-invoice-select">
                                        <option value="">— Optional —</option>
                                        @if($detail->invoice_id)
                                            <option value="{{ $detail->invoice_id }}" selected>
                                                {{ $detail->invoice ? $detail->invoice->invoice_no : 'Invoice #'.$detail->invoice_id }}
                                            </option>
                                        @endif
                                    </select>
                                </td>
                                <td>
                                    <select name="raw_items[{{ $i }}][unit]" class="form-control select2-js raw-unit-select" required>
                                        <option value="">—</option>
                                        @foreach($units as $u)
                                            <option value="{{ $u->id }}" {{ $detail->unit == $u->id ? 'selected' : '' }}>
                                                {{ $u->shortcode }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><span class="badge bg-info raw-stock-badge">—</span></td>
                                <td><input type="number" name="raw_items[{{ $i }}][qty]"  class="form-control raw-qty"  step="0.01" min="0.01" value="{{ $detail->qty }}"  required></td>
                                <td><input type="number" name="raw_items[{{ $i }}][rate]" class="form-control raw-rate" step="0.01" min="0"    value="{{ $detail->rate }}" required></td>
                                <td><input type="number" class="form-control raw-amount" step="0.01" disabled value="{{ number_format($detail->qty * $detail->rate, 2) }}"></td>
                                <td><input type="text"   name="raw_items[{{ $i }}][desc]" class="form-control" value="{{ $detail->desc }}"></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-raw-row">✕</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-outline-warning btn-sm mt-2" id="addRawBtn">
                        <i class="fas fa-plus"></i> Add Raw Item
                    </button>
                </div>
                <footer class="card-footer">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label>Total Raw Qty</label>
                            <input type="number" id="rawTotalQty" class="form-control" disabled value="0.00">
                        </div>
                        <div class="col-md-3 offset-md-7 text-end">
                            <h6 class="text-primary mb-0">Raw Total: PKR <span id="rawTotalAmt" class="fw-bold text-warning">0.00</span></h6>
                        </div>
                    </div>
                </footer>
            </section>

            {{-- ── FG Items Table ── --}}
            <section class="card mt-3" id="fgSection">
                <header class="card-header">
                    <h2 class="card-title mb-0">
                        <i class="fas fa-box me-1 text-success"></i> Finished Goods Ordered
                        <small class="text-muted ms-2" style="font-size:12px;">(CMT only)</small>
                    </h2>
                </header>
                <div class="card-body" style="overflow-x:auto;">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:220px">FG Product <span class="text-danger">*</span></th>
                                <th style="min-width:160px">Variation</th>
                                <th style="min-width:110px">Current Stock</th>
                                <th style="min-width:100px">Qty Ordered</th>
                                <th style="min-width:120px">Mfg Rate/pc</th>
                                <th style="min-width:100px">Total CMT</th>
                                <th style="min-width:140px">Description</th>
                                <th style="width:50px"></th>
                            </tr>
                        </thead>
                        <tbody id="fgBody">
                            @foreach($production->fgItems as $i => $fg)
                            <tr>
                                <td>
                                    <select name="fg_items[{{ $i }}][product_id]" class="form-control select2-js fg-product-select">
                                        <option value="">— Select FG Product —</option>
                                        @php
                                            $vendorFgList = $vendorFgProducts[$production->vendor_id] ?? [];
                                        @endphp
                                        @foreach($vendorFgList as $p)
                                            <option value="{{ $p['id'] }}"
                                                    data-variations="{{ json_encode($p['variations']) }}"
                                                    {{ $fg->product_id == $p['id'] ? 'selected' : '' }}>
                                                {{ $p['sku'] ? $p['sku'].' — ' : '' }}{{ $p['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="fg_items[{{ $i }}][variation_id]" class="form-control select2-js fg-variation-select">
                                        <option value="">— None —</option>
                                        @foreach($fg->product->variations ?? [] as $var)
                                            <option value="{{ $var->id }}" {{ $fg->variation_id == $var->id ? 'selected' : '' }}>
                                                {{ $var->sku }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><span class="badge bg-secondary fg-stock-badge">—</span></td>
                                <td><input type="number" name="fg_items[{{ $i }}][qty]"                class="form-control fg-qty"  step="0.01" min="0.01" value="{{ $fg->qty }}"></td>
                                <td><input type="number" name="fg_items[{{ $i }}][manufacturing_rate]" class="form-control fg-rate" step="0.01" min="0"    value="{{ $fg->manufacturing_rate }}"></td>
                                <td><input type="number" class="form-control fg-amount" step="0.01" disabled value="{{ number_format($fg->qty * $fg->manufacturing_rate, 2) }}"></td>
                                <td><input type="text"   name="fg_items[{{ $i }}][desc]" class="form-control" value="{{ $fg->desc }}"></td>
                                <td><button type="button" class="btn btn-danger btn-sm remove-fg-row">✕</button></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-outline-success btn-sm mt-2" id="addFgBtn">
                        <i class="fas fa-plus"></i> Add FG Item
                    </button>
                </div>
                <footer class="card-footer">
                    <div class="row align-items-end">
                        <div class="col-md-2">
                            <label>Total FG Ordered</label>
                            <input type="number" id="fgTotalQty" class="form-control" disabled value="0.00">
                        </div>
                        <div class="col-md-3 offset-md-7 text-end">
                            <h6 class="text-primary mb-0">CMT Total: PKR <span id="fgTotalAmt" class="fw-bold text-success">0.00</span></h6>
                        </div>
                    </div>
                </footer>
            </section>

            <div class="card mt-3">
                <footer class="card-footer text-end">
                    <a href="{{ route('production.index') }}" class="btn btn-danger">Discard</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Update Production Order
                    </button>
                </footer>
            </div>

        </form>
    </div>
</div>

@push('scripts')
<script>
const rawProducts = @php echo json_encode($rawProducts); @endphp;
const vendorFgMap = @php echo json_encode($vendorFgProducts); @endphp;
const allUnits    = @php echo json_encode($units->map(fn($u) => ['id' => $u->id, 'name' => $u->shortcode])->values()); @endphp;

let rawRowIdx = {{ $production->rawDetails->count() }};
let fgRowIdx  = {{ $production->fgItems->count() }};

function toggleFgSection() {
    $('#productionType').val() === 'cmt' ? $('#fgSection').show() : $('#fgSection').hide();
}
$('#productionType').on('change', toggleFgSection);
$('#vendorSelect').on('change', refreshFgProductSelects);

function buildRawOptions(selectedId = '') {
    return '<option value="">— Select Raw —</option>'
        + rawProducts.map(p => `<option value="${p.id}" data-unit="${p.unit_id}" ${p.id == selectedId ? 'selected' : ''}>${p.sku ? p.sku+' — ' : ''}${p.name}</option>`).join('');
}

function buildFgOptions(selectedId = '') {
    const vendorId = $('#vendorSelect').val();
    const fgList   = vendorId && vendorFgMap[vendorId] ? vendorFgMap[vendorId] : [];
    return '<option value="">— Select FG Product —</option>'
        + fgList.map(p => `<option value="${p.id}" data-variations='${JSON.stringify(p.variations)}' ${p.id == selectedId ? 'selected' : ''}>${p.sku ? p.sku+' — ' : ''}${p.name}</option>`).join('');
}

function buildUnitOptions(selectedId = '') {
    return '<option value="">—</option>' + allUnits.map(u => `<option value="${u.id}" ${u.id == selectedId ? 'selected' : ''}>${u.name}</option>`).join('');
}

function refreshFgProductSelects() {
    $('#fgBody tr').each(function () {
        const sel = $(this).find('.fg-product-select');
        sel.html(buildFgOptions(sel.val()));
        sel.trigger('change.select2');
        rebindSelect2();
    });
}

function addRawRow() {
    const row = `<tr>
        <td><select name="raw_items[${rawRowIdx}][product_id]" class="form-control select2-js raw-product-select" required>${buildRawOptions()}</select></td>
        <td><select name="raw_items[${rawRowIdx}][variation_id]" class="form-control select2-js raw-variation-select"><option value="">— None —</option></select></td>
        <td><select name="raw_items[${rawRowIdx}][invoice_id]"   class="form-control select2-js raw-invoice-select"><option value="">— Optional —</option></select></td>
        <td><select name="raw_items[${rawRowIdx}][unit]" class="form-control select2-js raw-unit-select" required>${buildUnitOptions()}</select></td>
        <td><span class="badge bg-info raw-stock-badge">—</span></td>
        <td><input type="number" name="raw_items[${rawRowIdx}][qty]"  class="form-control raw-qty"  step="0.01" min="0.01" placeholder="0.00" required></td>
        <td><input type="number" name="raw_items[${rawRowIdx}][rate]" class="form-control raw-rate" step="0.01" min="0"    placeholder="0.00" required></td>
        <td><input type="number" class="form-control raw-amount" step="0.01" disabled value="0.00"></td>
        <td><input type="text"   name="raw_items[${rawRowIdx}][desc]" class="form-control" placeholder="Optional"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-raw-row">✕</button></td>
    </tr>`;
    $('#rawBody').append(row); rawRowIdx++; rebindSelect2(); recalcRaw();
}

function addFgRow() {
    const row = `<tr>
        <td><select name="fg_items[${fgRowIdx}][product_id]" class="form-control select2-js fg-product-select">${buildFgOptions()}</select></td>
        <td><select name="fg_items[${fgRowIdx}][variation_id]" class="form-control select2-js fg-variation-select"><option value="">— None —</option></select></td>
        <td><span class="badge bg-secondary fg-stock-badge">—</span></td>
        <td><input type="number" name="fg_items[${fgRowIdx}][qty]"                class="form-control fg-qty"  step="0.01" min="0.01" placeholder="0.00"></td>
        <td><input type="number" name="fg_items[${fgRowIdx}][manufacturing_rate]" class="form-control fg-rate" step="0.01" min="0"    placeholder="0.00"></td>
        <td><input type="number" class="form-control fg-amount" step="0.01" disabled value="0.00"></td>
        <td><input type="text"   name="fg_items[${fgRowIdx}][desc]" class="form-control" placeholder="Optional"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-fg-row">✕</button></td>
    </tr>`;
    $('#fgBody').append(row); fgRowIdx++; rebindSelect2(); recalcFg();
}

function recalcRaw() {
    let qty = 0, amt = 0;
    $('#rawBody tr').each(function () {
        const q = parseFloat($(this).find('.raw-qty').val()) || 0;
        const r = parseFloat($(this).find('.raw-rate').val()) || 0;
        const a = q * r;
        $(this).find('.raw-amount').val(a.toFixed(2));
        qty += q; amt += a;
    });
    $('#rawTotalQty').val(qty.toFixed(2));
    $('#rawTotalAmt').text(amt.toLocaleString('en-PK', { minimumFractionDigits: 2 }));
}

function recalcFg() {
    let qty = 0, amt = 0;
    $('#fgBody tr').each(function () {
        const q = parseFloat($(this).find('.fg-qty').val()) || 0;
        const r = parseFloat($(this).find('.fg-rate').val()) || 0;
        const a = q * r;
        $(this).find('.fg-amount').val(a.toFixed(2));
        qty += q; amt += a;
    });
    $('#fgTotalQty').val(qty.toFixed(2));
    $('#fgTotalAmt').text(amt.toLocaleString('en-PK', { minimumFractionDigits: 2 }));
}

function loadRawStock(row, productId, variationId = null) {
    const $b = row.find('.raw-stock-badge');
    $b.text('…').removeClass('bg-success bg-danger').addClass('bg-secondary');
    $.get('/production/raw-stock', { product_id: productId, variation_id: variationId }, function (res) {
        const s = parseFloat(res.stock) || 0;
        $b.text(s.toFixed(2)).removeClass('bg-secondary').addClass(s > 0 ? 'bg-success' : 'bg-danger');
    });
}

function loadFgStock(row, productId, variationId = null) {
    const $b = row.find('.fg-stock-badge');
    $b.text('…').addClass('bg-secondary');
    $.get('/production/fg-stock', { product_id: productId, variation_id: variationId }, function (res) {
        const s = parseFloat(res.stock) || 0;
        $b.text(s.toFixed(2)).removeClass('bg-secondary bg-success bg-warning').addClass(s > 0 ? 'bg-success' : 'bg-warning');
    });
}

function loadRawVariations(row, productId) {
    const $sel = row.find('.raw-variation-select');
    $sel.html('<option value="">Loading…</option>').prop('disabled', true);
    $.get(`/product/${productId}/variations`, function (data) {
        const vars = data.variation || [];
        let html = '<option value="">— None —</option>';
        vars.forEach(v => { html += `<option value="${v.id}">${v.sku}</option>`; });
        $sel.html(html).prop('disabled', false);
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
        $sel.select2({ width: '100%' });
    });
}

function loadRawInvoices(row, productId) {
    const $sel = row.find('.raw-invoice-select');
    $sel.html('<option value="">Loading…</option>');
    $.get(`/product/${productId}/invoices`, function (data) {
        let html = '<option value="">— Optional —</option>';
        (Array.isArray(data) ? data : []).forEach(inv => {
            html += `<option value="${inv.id}" data-rate="${inv.rate}">${inv.invoice_no ?? inv.number} — ${inv.vendor}</option>`;
        });
        $sel.html(html);
        if ($sel.hasClass('select2-hidden-accessible')) $sel.select2('destroy');
        $sel.select2({ width: '100%' });
    });
    row.find('.raw-invoice-select').off('change.inv').on('change.inv', function () {
        const rate = $(this).find(':selected').data('rate') || 0;
        row.find('.raw-rate').val(rate);
        recalcRaw();
    });
}

$(document).on('change', '.raw-product-select', function () {
    const row = $(this).closest('tr');
    const pid = $(this).val();
    const uid = $(this).find('option:selected').data('unit');
    if (uid) row.find('.raw-unit-select').val(uid).trigger('change.select2');
    if (pid) { loadRawVariations(row, pid); loadRawInvoices(row, pid); loadRawStock(row, pid); }
    recalcRaw();
});

$(document).on('change', '.raw-variation-select', function () {
    const row = $(this).closest('tr');
    const pid = row.find('.raw-product-select').val();
    if (pid) loadRawStock(row, pid, $(this).val() || null);
});

$(document).on('change', '.fg-product-select', function () {
    const row  = $(this).closest('tr');
    const pid  = $(this).val();
    const vars = $(this).find('option:selected').data('variations') || [];
    const $var = row.find('.fg-variation-select');
    let html = '<option value="">— None —</option>';
    vars.forEach(v => { html += `<option value="${v.id}">${v.sku}</option>`; });
    $var.html(html);
    if ($var.hasClass('select2-hidden-accessible')) $var.select2('destroy');
    $var.select2({ width: '100%' });
    if (pid) loadFgStock(row, pid);
    recalcFg();
});

$(document).on('change', '.fg-variation-select', function () {
    const row = $(this).closest('tr');
    const pid = row.find('.fg-product-select').val();
    if (pid) loadFgStock(row, pid, $(this).val() || null);
});

$('#addRawBtn').on('click', addRawRow);
$('#addFgBtn').on('click', addFgRow);

$(document).on('click', '.remove-raw-row', function () {
    if ($('#rawBody tr').length > 1) { $(this).closest('tr').remove(); recalcRaw(); }
});
$(document).on('click', '.remove-fg-row', function () {
    if ($('#fgBody tr').length > 1) { $(this).closest('tr').remove(); recalcFg(); }
});

$(document).on('input', '.raw-qty, .raw-rate', recalcRaw);
$(document).on('input', '.fg-qty, .fg-rate',   recalcFg);

function rebindSelect2() {
    $('.select2-js').each(function () {
        if (!$(this).hasClass('select2-hidden-accessible')) $(this).select2({ width: '100%' });
    });
}

$(document).ready(function () {
    rebindSelect2();
    toggleFgSection();
    recalcRaw();
    recalcFg();
    // Load stock for existing raw rows
    $('#rawBody tr').each(function () {
        const row = $(this);
        const pid = row.find('.raw-product-select').val();
        const vid = row.find('.raw-variation-select').val();
        if (pid) {
            loadRawStock(row, pid, vid || null);
            loadRawInvoices(row, pid);
        }
    });
    // Load stock for existing FG rows
    $('#fgBody tr').each(function () {
        const row = $(this);
        const pid = row.find('.fg-product-select').val();
        const vid = row.find('.fg-variation-select').val();
        if (pid) loadFgStock(row, pid, vid || null);
    });
});
</script>
@endpush
@endsection