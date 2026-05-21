@extends('layouts.app')

@section('title', 'Products | Attributes')

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
                <h2 class="card-title mb-0">All Attributes</h2>
                @can('attributes.create')
                    <button type="button" class="modal-with-form btn btn-primary btn-sm" href="#addAttributeModal">
                        <i class="fas fa-plus"></i> Add Attribute
                    </button>
                @endcan
            </header>

            <div class="card-body">
                <div class="modal-wrapper table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="datatable-attributes">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Slug</th>
                                <th>Values</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($attributes as $attribute)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td><strong>{{ $attribute->name }}</strong></td>
                                <td><code>{{ $attribute->slug }}</code></td>
                                <td>
                                    @foreach($attribute->values as $value)
                                        <span class="badge bg-secondary me-1">{{ $value->value }}</span>
                                    @endforeach
                                </td>
                                <td style="white-space:nowrap;">
                                    @can('attributes.edit')
                                        {{-- FIX: single modal via AJAX fetch --}}
                                        <a href="#" class="text-primary me-1"
                                           onclick="editAttribute({{ $attribute->id }})">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('attributes.delete')
                                        <form action="{{ route('attributes.destroy', $attribute->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete attribute \'{{ addslashes($attribute->name) }}\'? All its values will also be deleted.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link p-0 m-0 text-danger">
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


        {{-- ── Add Modal ──────────────────────────────────────────────────── --}}
        @can('attributes.create')
        <div id="addAttributeModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" action="{{ route('attributes.store') }}"
                      onkeydown="return event.key !== 'Enter';">
                    @csrf
                    <header class="card-header">
                        <h2 class="card-title">New Attribute</h2>
                    </header>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Attribute Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                   placeholder="e.g. Size" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="slug" id="add_slug"
                                   placeholder="e.g. size" required>
                            <small class="text-muted">Unique identifier, lowercase, no spaces.</small>
                        </div>
                        <div class="form-group mb-3">
                            <label>Values <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="values"
                                   placeholder="Small, Medium, Large" required>
                            <small class="text-muted">Comma-separated list of values.</small>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-primary">Create</button>
                            <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                        </div>
                    </footer>
                </form>
            </section>
        </div>
        @endcan


        {{-- ── Edit Modal (single shared instance, populated via AJAX) ────── --}}
        @can('attributes.edit')
        <div id="editAttributeModal" class="modal-block modal-block-warning mfp-hide">
            <section class="card">
                <form method="POST" id="editAttributeForm" action=""
                      onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title">Edit Attribute</h2>
                    </header>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Attribute Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_attr_name"
                                   name="name" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Slug <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_attr_slug"
                                   name="slug" required>
                        </div>
                        <div class="form-group mb-3">
                            <label>Values <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_attr_values"
                                   name="values" required>
                            <small class="text-muted">Comma-separated. Removing a value will delete it.</small>
                        </div>
                    </div>
                    <footer class="card-footer">
                        <div class="col-md-12 text-end">
                            <button type="submit" class="btn btn-warning">Update</button>
                            <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                        </div>
                    </footer>
                </form>
            </section>
        </div>
        @endcan

    </div>
</div>

@push('scripts')
<script>
// Auto-generate slug from name in add modal
document.querySelector('[name="name"]')?.addEventListener('input', function () {
    const slug = this.value.toLowerCase().trim().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
    document.getElementById('add_slug').value = slug;
});

function editAttribute(id) {
    fetch('/attributes/' + id + '/edit')
        .then(res => { if (!res.ok) throw new Error('Not ok'); return res.json(); })
        .then(data => {
            document.getElementById('editAttributeForm').action = '/attributes/' + data.id;
            document.getElementById('edit_attr_name').value     = data.name;
            document.getElementById('edit_attr_slug').value     = data.slug;
            document.getElementById('edit_attr_values').value   = data.values;

            $.magnificPopup.open({
                items: { src: '#editAttributeModal' },
                type: 'inline'
            });
        })
        .catch(() => alert('Could not load attribute data. Please try again.'));
}
</script>
@endpush

@endsection