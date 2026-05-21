@extends('layouts.app')
@section('title', 'Production Receiving | Edit ' . $receiving->grn_no)

@section('content')
<div class="row">
    <div class="col">
        <form action="{{ route('production_receiving.update', $receiving->id) }}" method="POST"
              onkeydown="return event.key !== 'Enter';">
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
                    <h2 class="card-title mb-0">Edit {{ $receiving->grn_no }}</h2>
                    <a href="{{ route('production_receiving.index') }}" class="btn btn-danger btn-sm">Discard</a>
                </header>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label>GRN No</label>
                            <input class="form-control" value="{{ $receiving->grn_no }}" disabled>
                        </div>
                        <div class="col-md-2">
                            <label>Receiving Date <span class="text-danger">*</span></label>
                            <input type="date" name="rec_date" class="form-control"
                                   value="{{ old('rec_date', \Carbon\Carbon::parse($receiving->rec_date)->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label>Production Order</label>
                            <select name="production_id" id="productionSelect" class="form-control select2-js">
                                <option value="">— No Production Order —</option>
                                @foreach($productions as $prod)
                                    <option value="{{ $prod->id }}"
                                        {{ old('production_id', $receiving->production_id) == $prod->id ? 'selected' : '' }}>
                                        #{{ $prod->id }} — {{ $prod->vendor->name ?? '' }}
                                        ({{ $prod->production_type === 'cmt' ? 'CMT' : 'Sell Raw' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>Vendor <span class="text-danger">*</span></label>
                            <select name="vendor_id" id="vendorSelect" class="form-control select2-js" required>
                                <option value="">— Select Vendor —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}"
                                        {{ old('vendor_id', $receiving->vendor_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
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
                            @foreach($receiving->details as $i => $detail)
                            <tr>
                                <td>
                                    <input type="hidden" name="item_details[{{ $i }}][product_id]" value="{{ $detail->product_id }}">
                                    <span class="fw-bold">{{ $detail->product->name ?? '—' }}</span>
                                    <small class="text-muted d-block" style="font-size:11px;">ID: {{ $detail->product_id }}</small>
                                </td>
                                <td>
                                    <select name="item_details[{{ $i }}][variation_id]" class="form-control select2-js variation-select">
                                        <option value="">— None —</option>
                                        @foreach($detail->product->variations ?? [] as $var)
                                            <option value="{{ $var->id }}" {{ $detail->variation_id == $var->id ? 'selected' : '' }}>
                                                {{ $var->sku }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    @php
                                        $orderedQty = $selectedProduction?->fgItems
                                            ->firstWhere('product_id', $detail->product_id)?->qty ?? '—';
                                    @endphp
                                    <input type="number" class="form-control ordered-qty" step="0.01" disabled
                                           value="{{ $orderedQty }}" style="background:#f8f9fa;">
                                </td>
                                <td>
                                    <input type="number" name="item_details[{{ $i }}][manufacturing_cost]"
                                           class="form-control mfg-cost" step="0.01" min="0"
                                           value="{{ $detail->manufacturing_cost }}" required>
                                </td>
                                <td>
                                    <input type="number" name="item_details[{{ $i }}][received_qty]"
                                           class="form-control recv-qty" step="0.01" min="0.01"
                                           value="{{ $detail->received_qty }}" required>
                                </td>
                                <td>
                                    <input type="number" class="form-control row-total" step="0.01" disabled
                                           value="{{ number_format($detail->manufacturing_cost * $detail->received_qty, 2) }}">
                                </td>
                                <td>
                                    <input type="text" name="item_details[{{ $i }}][remarks]"
                                           class="form-control" value="{{ $detail->remarks }}">
                                </td>
                                <td>
                                    <button type="button" class="btn btn-danger btn-sm remove-row">✕</button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
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
                                   class="form-control" step="0.01" min="0"
                                   value="{{ old('convance_charges', $receiving->convance_charges) }}">
                        </div>
                        <div class="col-md-2">
                            <label>Discount</label>
                            <input type="number" name="bill_discount" id="discount"
                                   class="form-control" step="0.01" min="0"
                                   value="{{ old('bill_discount', $receiving->bill_discount) }}">
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
                        <i class="fas fa-save me-1"></i> Update Receiving
                    </button>
                </footer>
            </section>
        </form>
    </div>
</div>

@push('scripts')
<script>
let rowIdx = {{ $receiving->details->count() }};

function buildManualRow(idx) {
    return `
    <tr>
        <td>
            <select name="item_details[${idx}][product_id]" class="form-control select2-js product-select" required>
                <option value="">— Select Product —</option>
            </select>
        </td>
        <td><select name="item_details[${idx}][variation_id]" class="form-control select2-js variation-select"><option value="">— None —</option></select></td>
        <td><input type="number" class="form-control ordered-qty" step="0.01" disabled value="—" style="background:#f8f9fa;"></td>
        <td><input type="number" name="item_details[${idx}][manufacturing_cost]" class="form-control mfg-cost" step="0.01" min="0" value="0" required></td>
        <td><input type="number" name="item_details[${idx}][received_qty]" class="form-control recv-qty" step="0.01" min="0.01" placeholder="0.00" required></td>
        <td><input type="number" class="form-control row-total" step="0.01" disabled value="0.00"></td>
        <td><input type="text" name="item_details[${idx}][remarks]" class="form-control" placeholder="Optional"></td>
        <td><button type="button" class="btn btn-danger btn-sm remove-row">✕</button></td>
    </tr>`;
}

$('#addRowBtn').on('click', function () {
    $('#receivingBody').append(buildManualRow(rowIdx));
    rowIdx++;
    rebindSelect2();
    recalcSummary();
});

$(document).on('click', '.remove-row', function () {
    if ($('#receivingBody tr').length > 1) { $(this).closest('tr').remove(); recalcSummary(); }
});

$(document).on('change', '.product-select', function () {
    const row = $(this).closest('tr');
    const pid = $(this).val();
    if (!pid) return;
    const $var = row.find('.variation-select');
    $var.html('<option value="">Loading…</option>').prop('disabled', true);
    $.get(`/product/${pid}/variations`, function (data) {
        let html = '<option value="">— None —</option>';
        (data.variation || []).forEach(v => { html += `<option value="${v.id}">${v.sku}</option>`; });
        $var.html(html).prop('disabled', false);
        if ($var.hasClass('select2-hidden-accessible')) $var.select2('destroy');
        $var.select2({ width: '100%' });
    });
});

function recalcSummary() {
    let pcs = 0, sub = 0;
    $('#receivingBody tr').each(function () {
        const qty  = parseFloat($(this).find('.recv-qty').val())  || 0;
        const cost = parseFloat($(this).find('.mfg-cost').val())  || 0;
        const tot  = qty * cost;
        $(this).find('.row-total').val(tot.toFixed(2));
        pcs += qty; sub += tot;
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
    recalcSummary();
});
</script>
@endpush
@endsection