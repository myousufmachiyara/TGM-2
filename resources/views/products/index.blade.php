@extends('layouts.app')

@section('title', 'Products | All Products')

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
                <h2 class="card-title mb-0">All Products</h2>
                <div class="d-flex gap-2">
                    @can('products.print')
                        <a href="{{ route('products.bulk-export') }}" class="btn btn-warning btn-sm">
                            <i class="fas fa-download"></i> Export
                        </a>
                    @endcan
                    @can('products.create')
                        <a href="#bulkImportModal" class="modal-with-form btn btn-success btn-sm">
                            <i class="fas fa-file-import"></i> Bulk Import
                        </a>
                        <a href="{{ route('products.barcode.selection') }}" class="btn btn-danger btn-sm">
                            <i class="fas fa-barcode"></i> Barcodes
                        </a>
                        <a href="{{ route('products.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Add Product
                        </a>
                    @endcan
                </div>
            </header>

            <div class="card-body">
                <div class="modal-wrapper table-scroll">
                    <table class="table table-bordered table-striped mb-0" id="cust-datatable-default">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $index => $product)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    @if($product->images->first())
                                        <img src="{{ asset('storage/' . $product->images->first()->image_path) }}"
                                             width="50" height="50"
                                             style="object-fit:cover;border-radius:4px;">
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td><strong>{{ $product->name }}</strong></td>
                                <td><code>{{ $product->sku }}</code></td>
                                <td>{{ $product->category->name ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $product->item_type === 'fg' ? 'primary' : ($product->item_type === 'raw' ? 'secondary' : 'info') }}">
                                        {{ strtoupper($product->item_type) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $product->is_active ? 'success' : 'secondary' }}">
                                        {{ $product->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td style="white-space:nowrap;">
                                    @can('products.edit')
                                        <a href="{{ route('products.edit', $product->id) }}" class="text-primary me-1">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                    @endcan
                                    @can('products.delete')
                                        <form method="POST" action="{{ route('products.destroy', $product->id) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete \'{{ addslashes($product->name) }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-link p-0 m-0 text-danger">
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


        {{-- ── Bulk Import Modal ──────────────────────────────────────────── --}}
        @can('products.create')
        <div id="bulkImportModal" class="modal-block mfp-hide">
            <section class="card">
                <form action="{{ route('products.bulk-import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <header class="card-header">
                        <h2 class="card-title">Bulk Import / Update Products</h2>
                    </header>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Choose file <span class="text-danger">*</span></label>
                            <input type="file" name="file" class="form-control" accept=".csv,.xlsx" required>
                            <small class="text-muted">Upload the exported file after editing. Allowed: CSV, XLSX.</small>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input mt-0" name="delete_missing"
                                       value="1" id="delete_missing">
                                <label class="form-check-label text-danger" for="delete_missing">
                                    Delete products and variations <strong>not present</strong> in this file
                                </label>
                            </div>
                        </div>
                        <a href="{{ route('products.bulk-upload.template') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-download"></i> Download Template
                        </a>
                    </div>
                    <footer class="card-footer text-end">
                        <button type="button" class="btn btn-default modal-dismiss">Cancel</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload"></i> Import
                        </button>
                    </footer>
                </form>
            </section>
        </div>
        @endcan

    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('#cust-datatable-default').DataTable({ pageLength: 100 });
});
</script>
@endpush

@endsection