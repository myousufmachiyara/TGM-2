@extends('layouts.app')

@section('title', 'Accounts | Vendors')

@section('content')
<div class="row">
    <div class="col">
        <section class="card">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show m-3" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <header class="card-header">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    <h2 class="card-title">Vendors</h2>
                    @can('vendors.create')
                        <button type="button" class="modal-with-form btn btn-primary" href="#addModal">
                            <i class="fas fa-plus"></i> Add Vendor
                        </button>
                    @endcan
                </div>
            </header>

            <div class="card-body">

                {{-- ── Filter / Search ────────────────────────────────────── --}}
                <form method="GET" action="{{ route('vendors.index') }}" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label>Search</label>
                            <input type="text" name="search" class="form-control"
                                   placeholder="Name / Phone…"
                                   value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label>Status</label>
                            <select name="status" class="form-control" onchange="this.form.submit()">
                                <option value="">All</option>
                                <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-secondary w-100">
                                <i class="fa fa-search"></i> Search
                            </button>
                        </div>
                        @if(request()->anyFilled(['search', 'status']))
                            <div class="col-md-2">
                                <a href="{{ route('vendors.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fa fa-times"></i> Clear
                                </a>
                            </div>
                        @endif
                    </div>
                </form>

                {{-- ── Table ──────────────────────────────────────────────── --}}
                <div class="modal-wrapper table-scroll">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Vendor Name</th>
                                <th>Phone</th>
                                <th>Address</th>
                                <th>Opening Payables</th>
                                <th>Opening Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($vendors as $vendor)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $vendor->name }}</strong></td>
                                <td>{{ $vendor->contact_no ?? '—' }}</td>
                                <td>{{ $vendor->address ?? '—' }}</td>
                                <td>{{ number_format($vendor->opening_payables, 2) }}</td>
                                <td>{{ $vendor->opening_date ? $vendor->opening_date->format('d-m-Y') : '—' }}</td>
                                <td>
                                    @if($vendor->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td style="white-space:nowrap;">
                                    @can('vendors.edit')
                                        <a href="#" class="text-primary me-1"
                                           onclick="editVendor({{ $vendor->id }})" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('vendors.delete')
                                        <form action="{{ route('vendors.destroy', $vendor->id) }}"
                                              method="POST" style="display:inline;"
                                              onsubmit="return confirm('Delete vendor \'{{ addslashes($vendor->name) }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger" title="Delete">
                                                <i class="fa fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No vendors found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </section>


        {{-- ================================================================ --}}
        {{-- ADD MODAL                                                          --}}
        {{-- ================================================================ --}}
        @can('vendors.create')
        <div id="addModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" id="addForm" action="{{ route('vendors.store') }}"
                      enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
                    @csrf
                    <header class="card-header">
                        <h2 class="card-title">Add New Vendor</h2>
                    </header>
                    <div class="card-body">
                        <div class="row form-group">

                            {{-- Row 1: Name + Phone --}}
                            <div class="col-lg-6 mb-3">
                                <label>Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                       placeholder="Vendor / Supplier name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Phone No.</label>
                                <input type="text" class="form-control" name="contact_no"
                                       placeholder="e.g. 0300-1234567">
                            </div>

                            {{-- Row 2: Opening Payables + Opening Date --}}
                            <div class="col-lg-6 mb-3">
                                <label>Opening Payables <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="opening_payables"
                                       value="0" step="any" min="0" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Opening Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="opening_date"
                                       value="{{ date('Y-m-d') }}" required>
                            </div>

                            {{-- Row 3: Address + Remarks --}}
                            <div class="col-lg-6 mb-3">
                                <label>Address</label>
                                <textarea class="form-control" rows="2" name="address"
                                          placeholder="Optional address"></textarea>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Remarks</label>
                                <textarea class="form-control" rows="2" name="remarks"
                                          placeholder="Optional remarks"></textarea>
                            </div>

                        </div>
                    </div>
                    <footer class="card-footer">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary">Add Vendor</button>
                            <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                        </div>
                    </footer>
                </form>
            </section>
        </div>
        @endcan


        {{-- ================================================================ --}}
        {{-- EDIT MODAL                                                         --}}
        {{-- ================================================================ --}}
        @can('vendors.edit')
        <div id="editModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" id="editForm" action=""
                      enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title">Edit Vendor</h2>
                    </header>
                    <div class="card-body">
                        <div class="row form-group">

                            {{-- Row 1: Name + Phone --}}
                            <div class="col-lg-6 mb-3">
                                <label>Vendor Name <span class="text-danger">*</span></label>
                                <input type="text" id="edit_name" class="form-control"
                                       name="name" placeholder="Vendor / Supplier name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Phone No.</label>
                                <input type="text" id="edit_contact_no" class="form-control"
                                       name="contact_no" placeholder="e.g. 0300-1234567">
                            </div>

                            {{-- Row 2: Opening Payables + Opening Date --}}
                            <div class="col-lg-6 mb-3">
                                <label>Opening Payables <span class="text-danger">*</span></label>
                                <input type="number" id="edit_opening_payables" class="form-control"
                                       name="opening_payables" step="any" min="0" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Opening Date <span class="text-danger">*</span></label>
                                <input type="date" id="edit_opening_date" class="form-control"
                                       name="opening_date" required>
                            </div>

                            {{-- Row 3: Address + Status --}}
                            <div class="col-lg-6 mb-3">
                                <label>Address</label>
                                <textarea id="edit_address" class="form-control" rows="2"
                                          name="address" placeholder="Optional address"></textarea>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Status</label>
                                <select id="edit_is_active" class="form-control" name="is_active">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>

                            {{-- Row 4: Remarks --}}
                            <div class="col-lg-12 mb-3">
                                <label>Remarks</label>
                                <textarea id="edit_remarks" class="form-control" rows="2"
                                          name="remarks" placeholder="Optional remarks"></textarea>
                            </div>

                        </div>
                    </div>
                    <footer class="card-footer">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary">Update Vendor</button>
                            <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                        </div>
                    </footer>
                </form>
            </section>
        </div>
        @endcan

    </div>
</div>

<script>
function editVendor(id) {
    fetch('/vendors/' + id + '/edit')
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            $('#editForm').attr('action', '/vendors/' + id);

            $('#edit_name').val(data.name);
            $('#edit_contact_no').val(data.contact_no ?? '');
            $('#edit_opening_payables').val(data.opening_payables);
            $('#edit_opening_date').val(data.opening_date);
            $('#edit_address').val(data.address ?? '');
            $('#edit_remarks').val(data.remarks ?? '');
            $('#edit_is_active').val(data.is_active ? '1' : '0');

            $.magnificPopup.open({
                items: { src: '#editModal' },
                type: 'inline'
            });
        })
        .catch(err => {
            console.error('Failed to load vendor:', err);
            alert('Could not load vendor data. Please try again.');
        });
}
</script>

@endsection