@extends('layouts.app')

@section('title', 'Users | All Users')

@section('content')
<div class="row">
    <div class="col">

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <section class="card">
            <header class="card-header d-flex justify-content-between align-items-center">
                <h2 class="card-title mb-0">All Users</h2>
                @can('users.create')
                    <a href="#addModal" class="modal-with-form btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Add User
                    </a>
                @endcan
            </header>

            <div class="card-body">
                <div class="modal-wrapper table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="cust-datatable-default">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role(s)</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($users as $user)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $user->name }}</td>
                                <td><code>{{ $user->username }}</code></td>
                                <td>{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $user->is_active ? 'success' : 'secondary' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="white-space:nowrap;">

                                    @can('users.edit')
                                        <a href="#updateModal" class="text-primary modal-with-form me-1"
                                           onclick="getUser({{ $user->id }})" title="Edit">
                                            <i class="fa fa-edit"></i>
                                        </a>

                                        <a href="#activateModal"
                                           class="text-{{ $user->is_active ? 'warning' : 'success' }} modal-with-form me-1"
                                           onclick="setActivateUser({{ $user->id }}, {{ $user->is_active ? 'false' : 'true' }})"
                                           title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}">
                                            <i class="fa fa-toggle-{{ $user->is_active ? 'on' : 'off' }}"></i>
                                        </a>

                                        <a href="#passwordModal" class="text-info modal-with-form me-1"
                                           onclick="getPasswordUser({{ $user->id }})" title="Change Password">
                                            <i class="fa fa-key"></i>
                                        </a>
                                    @endcan

                                    @can('users.delete')
                                        <form action="{{ route('users.destroy', $user->id) }}" method="POST"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete user \'{{ addslashes($user->name) }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 m-0 text-danger" title="Delete">
                                                <i class="fas fa-trash-alt"></i>
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
        @can('users.create')
        <div id="addModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form action="{{ route('users.store') }}" method="POST"
                      enctype="multipart/form-data" onkeydown="return event.key !== 'Enter';">
                    @csrf
                    <header class="card-header">
                        <h2 class="card-title">Add User</h2>
                    </header>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Password <span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" minlength="6" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Role <span class="text-danger">*</span></label>
                                <select name="role" class="form-control" required>
                                    <option value="">— Select Role —</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <footer class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Create</button>
                        <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                    </footer>
                </form>
            </section>
        </div>
        @endcan


        {{-- ================================================================ --}}
        {{-- EDIT MODAL                                                         --}}
        {{-- ================================================================ --}}
        @can('users.edit')
        <div id="updateModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form id="updateForm" method="POST" enctype="multipart/form-data"
                      onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title">Edit User</h2>
                    </header>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label>Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="edit_name" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Username <span class="text-danger">*</span></label>
                                <input type="text" name="username" id="edit_username" class="form-control" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Role <span class="text-danger">*</span></label>
                                <select name="role" id="edit_role" class="form-control" required>
                                    <option value="">— Select Role —</option>
                                    @foreach($roles as $role)
                                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <footer class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Update</button>
                        <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                    </footer>
                </form>
            </section>
        </div>

        {{-- ── Change Password Modal ── --}}
        <div id="passwordModal" class="modal-block modal-block-warning mfp-hide">
            <section class="card">
                <form id="passwordForm" method="POST" enctype="multipart/form-data"
                      onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title">Change Password</h2>
                    </header>
                    <div class="card-body">
                        <div class="mb-3">
                            <label>New Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label>Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" name="password_confirmation" class="form-control" minlength="6" required>
                        </div>
                    </div>
                    <footer class="card-footer text-end">
                        <button type="submit" class="btn btn-warning">Change Password</button>
                        <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                    </footer>
                </form>
            </section>
        </div>

        {{-- ── Activate / Deactivate Modal ── --}}
        <div id="activateModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form id="activateForm" method="POST" onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title" id="activateModalTitle">Toggle User Status</h2>
                    </header>
                    <div class="card-body">
                        <p id="activateModalMessage">Are you sure you want to change this user's status?</p>
                    </div>
                    <footer class="card-footer text-end">
                        <button type="submit" class="btn btn-primary" id="activateModalBtn">Confirm</button>
                        <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                    </footer>
                </form>
            </section>
        </div>
        @endcan

    </div>
</div>

@push('scripts')
<script>
function getUser(id) {
    fetch(`/users/${id}`)
        .then(res => { if (!res.ok) throw new Error('Not ok'); return res.json(); })
        .then(res => {
            if (!res.status) { alert('User not found.'); return; }
            const u = res.data;
            document.getElementById('updateForm').action = `/users/${u.id}`;
            document.getElementById('edit_name').value     = u.name;
            document.getElementById('edit_username').value = u.username;
            document.getElementById('edit_role').value     = u.roles[0]?.id || '';
        })
        .catch(() => alert('Error loading user details.'));
}

function getPasswordUser(id) {
    document.getElementById('passwordForm').action = `/users/${id}/change-password`;
}

function setActivateUser(userId, activate) {
    document.getElementById('activateForm').action = `/users/${userId}/toggle-active`;

    const isActivating = (activate === true || activate === 'true');
    document.getElementById('activateModalTitle').textContent   = isActivating ? 'Activate User' : 'Deactivate User';
    document.getElementById('activateModalMessage').textContent = isActivating
        ? 'Are you sure you want to activate this user?'
        : 'Are you sure you want to deactivate this user?';

    const btn = document.getElementById('activateModalBtn');
    btn.textContent = isActivating ? 'Activate' : 'Deactivate';
    btn.className   = 'btn ' + (isActivating ? 'btn-success' : 'btn-danger');
}
</script>
@endpush

@endsection