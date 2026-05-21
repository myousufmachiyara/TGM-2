@extends('layouts.app')

@section('title', 'Accounts | Chart of Accounts')

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
                    <h2 class="card-title">Chart of Accounts</h2>
                    @can('coa.create')
                        <button type="button" class="modal-with-form btn btn-primary" href="#addModal">
                            <i class="fas fa-plus"></i> Add Account
                        </button>
                    @endcan
                </div>
                @if($errors->has('error'))
                    <strong class="text-danger">{{ $errors->first('error') }}</strong>
                @endif
            </header>

            <div class="card-body">

                {{-- ── Filter ─────────────────────────────────────────────── --}}
                <form method="GET" action="{{ route('coa.index') }}" class="mb-3">
                    <div class="col-md-3">
                        <label>Filter by Sub-head</label>
                        <select name="subhead" class="form-control" onchange="this.form.submit()">
                            <option value="all" {{ !request('subhead') || request('subhead') === 'all' ? 'selected' : '' }}>
                                All
                            </option>
                            @foreach($subHeadOfAccounts as $sub)
                                <option value="{{ $sub->id }}" {{ request('subhead') == $sub->id ? 'selected' : '' }}>
                                    {{ $sub->headOfAccount->name ?? '' }} — {{ $sub->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>

                {{-- ── Table ──────────────────────────────────────────────── --}}
                <div class="modal-wrapper table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="datatable-default">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Code</th>
                                <th>Account Name</th>
                                <th>Sub-head</th>
                                <th>Type</th>
                                <th>TRN</th>
                                <th>Credit Limit</th>
                                <th>Credit Days</th>
                                <th>Phone</th>
                                <th>Date</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($chartOfAccounts as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><code>{{ $item->account_code }}</code></td>
                                <td><strong>{{ $item->name }}</strong></td>
                                <td>{{ $item->subHeadOfAccount->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-secondary">
                                        {{ $accountTypes[$item->account_type] ?? ucfirst($item->account_type ?? '—') }}
                                    </span>
                                </td>
                                <td>{{ $item->trn ?? '—' }}</td>
                                <td>{{ number_format($item->credit_limit, 2) }}</td>
                                <td>{{ $item->credit_days ? $item->credit_days . ' days' : '—' }}</td>
                                <td>{{ $item->contact_no ?? '—' }}</td>
                                <td>{{ $item->opening_date ? \Carbon\Carbon::parse($item->opening_date)->format('d-m-Y') : '—' }}</td>
                                <td>{{ $item->remarks ?? '—' }}</td>
                                <td style="white-space:nowrap;">
                                    @can('coa.edit')
                                        <a href="#" class="text-primary me-1" onclick="editAccount({{ $item->id }})">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('coa.delete')
                                        <form action="{{ route('coa.destroy', $item->id) }}" method="POST"
                                              style="display:inline;"
                                              onsubmit="return confirm('Delete account \'{{ addslashes($item->name) }}\'? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 text-danger">
                                                <i class="fa fa-trash-alt"></i>
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


        {{-- ================================================================ --}}
        {{-- ADD MODAL                                                          --}}
        {{-- ================================================================ --}}
        @can('coa.create')
        <div id="addModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" id="addForm" action="{{ route('coa.store') }}"
                      enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
                    @csrf
                    <header class="card-header">
                        <h2 class="card-title">Add New Account</h2>
                    </header>
                    <div class="card-body">
                        <div class="row form-group">

                            {{-- Row 1: Name + Type --}}
                            <div class="col-lg-6 mb-3">
                                <label>Account Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name"
                                       placeholder="Account Name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Account Type</label>
                                <select data-plugin-selecttwo class="form-control select2-js" name="account_type">
                                    <option value="" disabled selected>Select Type</option>
                                    @foreach($accountTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Row 2: Sub-head + TRN --}}
                            <div class="col-lg-6 mb-3">
                                <label>Sub-head of Account <span class="text-danger">*</span></label>
                                <select data-plugin-selecttwo class="form-control select2-js"
                                        name="shoa_id" required>
                                    <option value="" disabled selected>Select Sub-head</option>
                                    @foreach($subHeadOfAccounts as $row)
                                        <option value="{{ $row->id }}">
                                            {{ $row->headOfAccount->name ?? '' }} — {{ $row->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>TRN <small class="text-muted">(Tax Registration No.)</small></label>
                                <input type="text" class="form-control" name="trn"
                                       placeholder="e.g. 100-234-567890">
                            </div>

                            {{-- Row 3: Receivables + Payables --}}
                            <div class="col-lg-6 mb-3">
                                <label>Opening Receivables <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="receivables"
                                       value="0" step="any" min="0" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Opening Payables <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" name="payables"
                                       value="0" step="any" min="0" required>
                            </div>

                            {{-- Row 4: Credit Limit + Credit Days --}}
                            <div class="col-lg-6 mb-3">
                                <label>Credit Limit <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" class="form-control" name="credit_limit"
                                           value="0" step="any" min="0" required>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Credit Days <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" class="form-control" name="credit_days"
                                           value="0" min="0" max="365" required>
                                    <span class="input-group-text">days</span>
                                </div>
                                <small class="text-muted">Payment due period after invoice date</small>
                            </div>

                            {{-- Row 5: Opening Date + Phone --}}
                            <div class="col-lg-6 mb-3">
                                <label>Opening Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="opening_date"
                                       value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Phone No.</label>
                                <input type="text" class="form-control" name="contact_no"
                                       placeholder="e.g. 0300-1234567">
                            </div>

                            {{-- Row 6: Address + Remarks --}}
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
                            <button type="submit" class="btn btn-primary">Add Account</button>
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
        @can('coa.edit')
        <div id="editModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" id="editForm" action=""
                      enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title">Edit Account</h2>
                        <small id="edit_account_code_display" class="text-muted"></small>
                    </header>
                    <div class="card-body">
                        <div class="row form-group">

                            {{-- Row 1: Name + Type --}}
                            <div class="col-lg-6 mb-3">
                                <label>Account Name <span class="text-danger">*</span></label>
                                <input type="text" id="edit_name" class="form-control"
                                       name="name" placeholder="Account Name" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Account Type</label>
                                <select data-plugin-selecttwo id="edit_account_type"
                                        class="form-control select2-js" name="account_type">
                                    <option value="" disabled>Select Type</option>
                                    @foreach($accountTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Row 2: Sub-head + TRN --}}
                            <div class="col-lg-6 mb-3">
                                <label>Sub-head of Account <span class="text-danger">*</span></label>
                                <select id="edit_shoa_id" class="form-control select2-js"
                                        name="shoa_id" required>
                                    <option value="" disabled>Select Sub-head</option>
                                    @foreach($subHeadOfAccounts as $row)
                                        <option value="{{ $row->id }}">
                                            {{ $row->headOfAccount->name ?? '' }} — {{ $row->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>TRN <small class="text-muted">(Tax Registration No.)</small></label>
                                <input type="text" id="edit_trn" class="form-control"
                                       name="trn" placeholder="e.g. 100-234-567890">
                            </div>

                            {{-- Row 3: Receivables + Payables --}}
                            <div class="col-lg-6 mb-3">
                                <label>Opening Receivables <span class="text-danger">*</span></label>
                                <input type="number" id="edit_receivables" class="form-control"
                                       name="receivables" step="any" min="0" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Opening Payables <span class="text-danger">*</span></label>
                                <input type="number" id="edit_payables" class="form-control"
                                       name="payables" step="any" min="0" required>
                            </div>

                            {{-- Row 4: Credit Limit + Credit Days --}}
                            <div class="col-lg-6 mb-3">
                                <label>Credit Limit <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">PKR</span>
                                    <input type="number" id="edit_credit_limit" class="form-control"
                                           name="credit_limit" step="any" min="0" required>
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Credit Days <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="number" id="edit_credit_days" class="form-control"
                                           name="credit_days" min="0" max="365" required>
                                    <span class="input-group-text">days</span>
                                </div>
                                <small class="text-muted">Payment due period after invoice date</small>
                            </div>

                            {{-- Row 5: Opening Date + Phone --}}
                            <div class="col-lg-6 mb-3">
                                <label>Opening Date <span class="text-danger">*</span></label>
                                <input type="date" id="edit_opening_date" class="form-control"
                                       name="opening_date" required>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Phone No.</label>
                                <input type="text" id="edit_contact_no" class="form-control"
                                       name="contact_no" placeholder="e.g. 0300-1234567">
                            </div>

                            {{-- Row 6: Address + Remarks --}}
                            <div class="col-lg-6 mb-3">
                                <label>Address</label>
                                <textarea id="edit_address" class="form-control" rows="2"
                                          name="address" placeholder="Optional address"></textarea>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label>Remarks</label>
                                <textarea id="edit_remarks" class="form-control" rows="2"
                                          name="remarks" placeholder="Optional remarks"></textarea>
                            </div>

                        </div>
                    </div>
                    <footer class="card-footer">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary">Update Account</button>
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
function editAccount(id) {
    fetch('/coa/' + id + '/edit')
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            // Set form action
            $('#editForm').attr('action', '/coa/' + id);

            // Show account code in header
            $('#edit_account_code_display').text('Code: ' + data.account_code);

            // Populate all fields
            $('#edit_name').val(data.name);
            $('#edit_trn').val(data.trn ?? '');
            $('#edit_receivables').val(data.receivables);
            $('#edit_payables').val(data.payables);
            $('#edit_credit_limit').val(data.credit_limit);
            $('#edit_credit_days').val(data.credit_days ?? 0);
            $('#edit_opening_date').val(data.opening_date);
            $('#edit_remarks').val(data.remarks ?? '');
            $('#edit_address').val(data.address ?? '');
            $('#edit_contact_no').val(data.contact_no ?? '');

            // Select2 fields — trigger('change') updates the visual state
            $('#edit_account_type').val(data.account_type).trigger('change');
            $('#edit_shoa_id').val(data.shoa_id).trigger('change');

            $.magnificPopup.open({
                items: { src: '#editModal' },
                type: 'inline'
            });
        })
        .catch(err => {
            console.error('Failed to load account:', err);
            alert('Could not load account data. Please try again.');
        });
}
</script>

@endsection