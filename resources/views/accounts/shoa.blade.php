@extends('layouts.app')

@section('title', 'Accounts | Sub Head of Accounts')

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

            <header class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h2 class="card-title">Sub Heads of Accounts</h2>
                @can('shoa.create')
                    <button type="button" class="modal-with-form btn btn-primary" href="#addModal">
                        <i class="fas fa-plus"></i> Add New
                    </button>
                @endcan
            </header>

            <div class="card-body">
                <div class="modal-wrapper table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="cust-datatable-default">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Sub-head Name</th>
                                <th>Head of Account</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($subHeadOfAccounts as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->headOfAccount->name ?? '—' }}</td>
                                <td style="white-space:nowrap;">
                                    @can('shoa.edit')
                                        <a href="javascript:void(0);" class="text-primary me-1"
                                           onclick="editSubHead({{ $item->id }})">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('shoa.delete')
                                        {{-- FIX: use a proper submit button, not <a> inside a form --}}
                                        <form action="{{ route('shoa.destroy', $item->id) }}"
                                              method="POST" style="display:inline;"
                                              onsubmit="return confirm('Delete sub-head \'{{ addslashes($item->name) }}\'?');">
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
        @can('shoa.create')
        <div id="addModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" action="{{ route('shoa.store') }}"
                      enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
                    @csrf
                    <header class="card-header">
                        <h2 class="card-title">New Sub Head of Account</h2>
                    </header>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Head of Account <span class="text-danger">*</span></label>
                            <select data-plugin-selecttwo class="form-control select2-js"
                                    name="hoa_id" required>
                                <option value="" selected disabled>Select Head</option>
                                @foreach($HeadOfAccounts as $row)
                                    <option value="{{ $row->id }}">{{ $row->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Sub-head Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                   placeholder="e.g. Accounts Receivable" required>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary">Add</button>
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
        @can('shoa.edit')
        <div id="updateModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" id="updateForm" action=""
                      onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title">Edit Sub Head of Account</h2>
                    </header>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Head of Account <span class="text-danger">*</span></label>
                            <select data-plugin-selecttwo class="form-control select2-js"
                                    name="hoa_id" id="edit_hoa_id" required>
                                <option value="" disabled>Select Head</option>
                                @foreach($HeadOfAccounts as $row)
                                    <option value="{{ $row->id }}">{{ $row->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-3">
                            <label>Sub-head Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                   id="edit_name" placeholder="Sub-head Name" required>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary">Update</button>
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
function editSubHead(id) {
    fetch('/shoa/' + id + '/edit')
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            $('#updateForm').attr('action', '/shoa/' + id);
            $('#edit_name').val(data.name);
            $('#edit_hoa_id').val(data.hoa_id).trigger('change');

            $.magnificPopup.open({
                items: { src: '#updateModal' },
                type: 'inline'
            });
        })
        .catch(err => {
            console.error('Failed to load sub-head:', err);
            alert('Could not load record. Please try again.');
        });
}
</script>

@endsection