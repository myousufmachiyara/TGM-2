@extends('layouts.app')

@section('title', 'Products | Barcode Selection')

@section('content')
<div class="row">
    <div class="col">

        @if($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
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
                <h2 class="card-title mb-0">Product Barcoding</h2>
                <input type="text" id="searchBox" class="form-control w-25"
                       placeholder="Search product…">
            </header>

            <div class="card-body">
                <form action="{{ route('products.generateBarcodes') }}" method="POST">
                    @csrf
                    <div class="text-end mb-3">
                        {{-- FIX: fa-barcode instead of bi-upc-scan (Bootstrap Icons not loaded) --}}
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-barcode"></i> Generate Barcodes
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0"
                               id="productsTable">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">
                                        <input type="checkbox" id="selectAll" title="Select all">
                                    </th>
                                    <th>Product</th>
                                    <th>Variation SKU</th>
                                    <th>Selling Price</th>
                                    <th width="120">Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($variations as $variation)
                                    @if($variation->product)
                                    <tr>
                                        <td>
                                            <input type="checkbox"
                                                   name="selected_variations[]"
                                                   value="{{ $variation->id }}">
                                        </td>
                                        <td class="product-name">{{ $variation->product->name }}</td>
                                        <td><code>{{ $variation->sku }}</code></td>
                                        <td>{{ number_format($variation->product->selling_price, 2) }}</td>
                                        <td>
                                            <input type="number"
                                                   name="quantity[{{ $variation->id }}]"
                                                   value="1" min="1"
                                                   class="form-control form-control-sm">
                                        </td>
                                    </tr>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No variations found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
// Search filter
document.getElementById('searchBox').addEventListener('keyup', function () {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#productsTable tbody tr').forEach(row => {
        const name = row.querySelector('.product-name')?.textContent.toLowerCase() ?? '';
        row.style.display = name.includes(filter) ? '' : 'none';
    });
});

// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function () {
    document.querySelectorAll('input[name="selected_variations[]"]')
        .forEach(cb => cb.checked = this.checked);
});
</script>
@endpush

@endsection