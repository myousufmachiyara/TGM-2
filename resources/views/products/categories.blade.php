@extends('layouts.app')

@section('title', 'Products | Categories')

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
                <h2 class="card-title mb-0">All Categories</h2>
                @can('product_categories.create')
                    <button type="button" class="modal-with-form btn btn-primary btn-sm" href="#addCategoryModal">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                @endcan
            </header>

            <div class="card-body">
                <div class="modal-wrapper table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="datatable-categories">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Code</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $category->name }}</td>
                                <td><code>{{ $category->code }}</code></td>
                                <td style="white-space:nowrap;">
                                    @can('product_categories.edit')
                                        {{-- FIX: single shared modal, populated via JS --}}
                                        <a href="#" class="text-primary me-1"
                                           onclick="editCategory({{ $category->id }}, '{{ addslashes($category->name) }}')">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('product_categories.delete')
                                        <form action="{{ route('product_categories.destroy', $category->id) }}"
                                              method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete category \'{{ addslashes($category->name) }}\'?');">
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
        @can('product_categories.create')
        <div id="addCategoryModal" class="modal-block modal-block-primary mfp-hide">
            <section class="card">
                <form method="POST" action="{{ route('product_categories.store') }}"
                      onkeydown="return event.key !== 'Enter';">
                    @csrf
                    <header class="card-header">
                        <h2 class="card-title">New Category</h2>
                    </header>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                   placeholder="e.g. Kameez Shalwar Plain" required>
                            <small class="text-muted">Code is generated automatically from the name.</small>
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


        {{-- ── Edit Modal (single shared instance) ───────────────────────── --}}
        @can('product_categories.edit')
        <div id="editCategoryModal" class="modal-block modal-block-warning mfp-hide">
            <section class="card">
                <form method="POST" id="editCategoryForm" action=""
                      onkeydown="return event.key !== 'Enter';">
                    @csrf
                    @method('PUT')
                    <header class="card-header">
                        <h2 class="card-title">Edit Category</h2>
                    </header>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label>Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_category_name"
                                   name="name" required>
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
function editCategory(id, name) {
    document.getElementById('editCategoryForm').action = '/product_categories/' + id;
    document.getElementById('edit_category_name').value = name;

    $.magnificPopup.open({
        items: { src: '#editCategoryModal' },
        type: 'inline'
    });
}
</script>
@endpush

@endsection