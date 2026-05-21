@extends('layouts.app')

@section('title', 'Products | Edit')

@section('content')
<div class="row">
    <div class="col">
        <form id="productForm" action="{{ route('products.update', $product->id) }}"
              method="POST" enctype="multipart/form-data"
              onkeydown="return event.key !== 'Enter';">
            @csrf
            @method('PUT')

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="card">
                <header class="card-header d-flex justify-content-between align-items-center">
                    <h2 class="card-title mb-0">Edit Product</h2>
                    <a href="{{ route('products.index') }}" class="btn btn-danger btn-sm">Cancel</a>
                </header>

                <div class="card-body">
                    <div class="row pb-3">

                        <div class="col-md-2 mb-3">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required
                                   value="{{ old('name', $product->name) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control select2-js" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}"
                                        {{ $product->category_id == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Vendor / Manufacturer</label>
                            <select name="vendor_id" class="form-control select2-js">
                                <option value="">— None —</option>
                                @foreach($vendors as $v)
                                    <option value="{{ $v->id }}"
                                        {{ old('vendor_id', $product->vendor_id) == $v->id ? 'selected' : '' }}>
                                        {{ $v->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>SKU</label>
                            <input type="text" name="sku" id="sku" class="form-control"
                                   value="{{ old('sku', $product->sku) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Item Type</label>
                            <select name="item_type" class="form-control select2-js">
                                <option value="fg"  {{ $product->item_type === 'fg'  ? 'selected' : '' }}>F.G</option>
                                <option value="raw" {{ $product->item_type === 'raw' ? 'selected' : '' }}>Raw</option>
                                <option value="service" {{ $product->item_type === 'service' ? 'selected' : '' }}>Service</option>
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Measurement Unit <span class="text-danger">*</span></label>
                            <select name="measurement_unit" class="form-control" required>
                                <option value="">— Select —</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}"
                                        {{ $product->measurement_unit == $unit->id ? 'selected' : '' }}>
                                        {{ $unit->name }} ({{ $unit->shortcode }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Consumption</label>
                            <input type="number" step="any" name="consumption" class="form-control"
                                   value="{{ old('consumption', $product->consumption) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>M.Cost</label>
                            <input type="number" step="any" name="manufacturing_cost" class="form-control"
                                   value="{{ old('manufacturing_cost', $product->manufacturing_cost) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Selling Price</label>
                            <input type="number" step="any" name="selling_price" class="form-control"
                                   value="{{ old('selling_price', $product->selling_price) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Opening Stock</label>
                            <input type="number" step="any" name="opening_stock" class="form-control"
                                   value="{{ old('opening_stock', $product->opening_stock) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Reorder Level</label>
                            <input type="number" step="any" name="reorder_level" class="form-control"
                                   value="{{ old('reorder_level', $product->reorder_level) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Max Stock Level</label>
                            <input type="number" step="any" name="max_stock_level" class="form-control"
                                   value="{{ old('max_stock_level', $product->max_stock_level) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Min Order Qty</label>
                            <input type="number" step="any" name="minimum_order_qty" class="form-control"
                                   value="{{ old('minimum_order_qty', $product->minimum_order_qty) }}">
                        </div>

                        <div class="col-md-2 mb-3">
                            <label>Status</label>
                            <select name="is_active" class="form-control">
                                <option value="1" {{ old('is_active', $product->is_active) == 1 ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ old('is_active', $product->is_active) == 0 ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3">{{ old('description', $product->description) }}</textarea>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label>Product Images</label>
                            <input type="file" id="imageUpload" name="prod_att[]" multiple class="form-control">
                            <small class="text-muted">Leave empty to keep existing images.</small>

                            {{-- Existing images --}}
                            <div id="existingImages" class="mt-2 d-flex flex-wrap gap-2">
                                @foreach($product->images as $img)
                                    <div class="existing-image-wrapper position-relative"
                                         data-img-id="{{ $img->id }}">
                                        <img src="{{ asset('storage/' . $img->image_path) }}"
                                             width="100" height="100"
                                             style="object-fit:cover;border-radius:5px;"
                                             class="img-thumbnail">
                                        <button type="button"
                                                class="btn btn-sm btn-danger position-absolute top-0 end-0 remove-existing-image"
                                                data-id="{{ $img->id }}">&times;</button>
                                        <input type="hidden" name="keep_images[]" value="{{ $img->id }}">
                                    </div>
                                @endforeach
                            </div>

                            {{-- New image previews --}}
                            <div id="previewContainer" class="mt-2 d-flex flex-wrap gap-2"></div>
                        </div>

                    </div>

                    {{-- ── Existing Variations ──────────────────────────────── --}}
                    <h5 class="mt-3">Existing Variations</h5>
                    <div id="variation-section">
                        @foreach($product->variations as $i => $variation)
                            {{-- FIX: data-variation-id on the block itself for undo targeting --}}
                            <div class="variation-block border rounded p-3 mb-3 existing-variation"
                                 data-variation-id="{{ $variation->id }}">
                                <input type="hidden" name="variations[{{ $i }}][id]" value="{{ $variation->id }}">
                                <div class="row g-2">
                                    <div class="col-md-3">
                                        <label>SKU</label>
                                        <input type="text" name="variations[{{ $i }}][sku]"
                                               class="form-control sku-field" value="{{ $variation->sku }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label>M.Cost</label>
                                        <input type="number" step="any"
                                               name="variations[{{ $i }}][manufacturing_cost]"
                                               class="form-control" value="{{ $variation->manufacturing_cost }}">
                                    </div>
                                    <div class="col-md-2">
                                        <label>Stock</label>
                                        <input type="number" step="any"
                                               name="variations[{{ $i }}][stock_quantity]"
                                               class="form-control" value="{{ $variation->stock_quantity }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label>Attributes</label>
                                        <select name="variations[{{ $i }}][attributes][]"
                                                multiple class="form-control select2-js variation-attributes">
                                            @foreach($attributes as $attribute)
                                                @foreach($attribute->values as $value)
                                                    <option value="{{ $value->id }}"
                                                        {{ $variation->attributeValues->pluck('id')->contains($value->id) ? 'selected' : '' }}>
                                                        {{ $attribute->name }} — {{ $value->value }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-1 d-flex align-items-end">
                                        <button type="button"
                                                class="btn btn-sm btn-danger remove-existing-variation"
                                                data-id="{{ $variation->id }}">✕</button>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ── Add New Variations ───────────────────────────────── --}}
                    <h5 class="mt-4">Add New Variations</h5>
                    <div id="new-variation-section"></div>
                    <button type="button" class="btn btn-sm btn-secondary mt-2" id="addNewVariationBtn">
                        <i class="fa fa-plus"></i> Add Variation
                    </button>

                </div>

                <footer class="card-footer text-end">
                    <a href="{{ route('products.index') }}" class="btn btn-danger">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </footer>
            </section>
        </form>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function () {
    $('.select2-js').select2();

    // Auto-update SKU when attributes change on existing variations
    $(document).on('change', '.variation-attributes', function () {
        const block    = $(this).closest('.variation-block');
        const mainSku  = $('#sku').val();
        const attrText = $(this).find('option:selected')
            .map((_, o) => $(o).text().split('—')[1]?.trim())
            .get().join('-');
        block.find('.sku-field').val(mainSku + (attrText ? '-' + attrText : ''));
    });

    // ── Add new variation row ─────────────────────────────────────────
    let newVarIdx = 0;
    $('#addNewVariationBtn').on('click', function () {
        newVarIdx++;
        const attrOptions = `
            @foreach($attributes as $attribute)
                @foreach($attribute->values as $value)
                    <option value="{{ $value->id }}">{{ $attribute->name }} — {{ $value->value }}</option>
                @endforeach
            @endforeach
        `;
        const html = `
            <div class="variation-block border rounded p-3 mb-3">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label>SKU</label>
                        <input type="text" name="new_variations[${newVarIdx}][sku]"
                               class="form-control sku-field">
                    </div>
                    <div class="col-md-2">
                        <label>M.Cost</label>
                        <input type="number" step="any" name="new_variations[${newVarIdx}][manufacturing_cost]"
                               value="0" class="form-control">
                    </div>
                    <div class="col-md-2">
                        <label>Stock</label>
                        <input type="number" step="any" name="new_variations[${newVarIdx}][stock_quantity]"
                               value="0" class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label>Attributes</label>
                        <select name="new_variations[${newVarIdx}][attributes][]"
                                multiple class="form-control select2-js variation-attributes">
                            ${attrOptions}
                        </select>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-sm btn-danger remove-new-variation">✕</button>
                    </div>
                </div>
            </div>
        `;
        $('#new-variation-section').append(html);
        $('.select2-js').select2();
    });

    $(document).on('click', '.remove-new-variation', function () {
        $(this).closest('.variation-block').remove();
    });

    // ── Remove existing variation (with undo) ─────────────────────────
    $(document).on('click', '.remove-existing-variation', function () {
        const variationId = $(this).data('id');
        // FIX: target block by data-variation-id attribute, not by hidden input name
        const block = $(`.variation-block[data-variation-id="${variationId}"]`);

        if (!confirm('Remove this variation?')) return;

        block.find('input, select, textarea').prop('disabled', true);
        block.hide();
        $('form#productForm').append(
            `<input type="hidden" name="removed_variations[]" value="${variationId}" class="removed-var-flag" data-var-id="${variationId}">`
        );

        const undo = $(`
            <div class="alert alert-warning d-flex justify-content-between align-items-center mb-2 undo-alert"
                 data-var-id="${variationId}">
                <span>Variation removed.</span>
                <button type="button" class="btn btn-sm btn-link p-0 undo-remove-variation">Undo</button>
            </div>
        `);
        block.after(undo);
    });

    $(document).on('click', '.undo-remove-variation', function () {
        const varId = $(this).closest('.undo-alert').data('var-id');
        // Re-enable the hidden block
        $(`.variation-block[data-variation-id="${varId}"]`)
            .find('input, select, textarea').prop('disabled', false)
            .end().show();
        // Remove the hidden input flag and the undo alert
        $(`.removed-var-flag[data-var-id="${varId}"]`).remove();
        $(this).closest('.undo-alert').remove();
    });

    // ── Image preview for new uploads ────────────────────────────────
    document.getElementById('imageUpload').addEventListener('change', function (e) {
        const container = document.getElementById('previewContainer');
        container.innerHTML = '';
        Array.from(e.target.files).forEach(file => {
            if (!file.type.startsWith('image/')) return;
            const reader = new FileReader();
            reader.onload = ev => {
                const wrap = document.createElement('div');
                wrap.classList.add('position-relative');

                const img = document.createElement('img');
                img.src = ev.target.result;
                img.classList.add('img-thumbnail');
                img.style.cssText = 'width:100px;height:100px;object-fit:cover;';

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.classList.add('btn','btn-sm','btn-danger','position-absolute','top-0','end-0');
                btn.innerHTML = '&times;';
                btn.onclick = () => wrap.remove();

                wrap.appendChild(img);
                wrap.appendChild(btn);
                container.appendChild(wrap);
            };
            reader.readAsDataURL(file);
        });
    });

    // ── Remove existing image ─────────────────────────────────────────
    document.getElementById('existingImages').addEventListener('click', function (e) {
        if (!e.target.classList.contains('remove-existing-image')) return;
        const id      = e.target.dataset.id;
        const wrapper = e.target.closest('.existing-image-wrapper');

        wrapper.style.display = 'none';
        wrapper.querySelector('input[name="keep_images[]"]')?.remove();

        const input   = document.createElement('input');
        input.type    = 'hidden';
        input.name    = 'removed_images[]';
        input.value   = id;
        document.getElementById('productForm').appendChild(input);
    });
});
</script>
@endpush

@endsection