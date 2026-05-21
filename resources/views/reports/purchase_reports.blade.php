{{-- resources/views/reports/partials/_purchase_filter.blade.php --}}
{{-- Used by all purchase report tabs. Parent form must set: <input type="hidden" name="tab" value="..."> --}}
<div class="row g-3 mb-3">
    <div class="col-md-3">
        <label>From Date</label>
        <input type="date" name="from_date" class="form-control"
               value="{{ request('from_date', $from) }}">
    </div>
    <div class="col-md-3">
        <label>To Date</label>
        <input type="date" name="to_date" class="form-control"
               value="{{ request('to_date', $to) }}">
    </div>
    <div class="col-md-3">
        <label>Vendor</label>
        <select name="vendor_id" class="form-control select2-js">
            <option value="">— All Vendors —</option>
            @foreach($vendors as $vendor)
                <option value="{{ $vendor->id }}"
                    {{ request('vendor_id') == $vendor->id ? 'selected' : '' }}>
                    {{ $vendor->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-primary w-100">
            <i class="fas fa-filter me-1"></i> Filter
        </button>
    </div>
    <div class="col-md-1 d-flex align-items-end">
        <a href="{{ route('reports.purchase') }}?tab={{ $activeTab }}"
           class="btn btn-secondary w-100" title="Reset">
            <i class="fas fa-times"></i>
        </a>
    </div>
</div>