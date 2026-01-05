<div class="item-row" data-index="{{ $index }}">
    <div class="item-row-header">
        <span class="item-row-title">Sản phẩm #{{ $index + 1 }}</span>
        <button type="button" class="btn-remove-item" onclick="removeItemRow(this)">🗑️ Xóa</button>
    </div>
    <div class="grid-3">
        <div>
            <label>Sản phẩm <span style="color:red;">*</span></label>
            <select name="items[{{ $index }}][product_id]" class="form-control product-select" required onchange="loadVariants(this, {{ $index }})" id="product-{{ $index }}">
                <option value="">-- Chọn sản phẩm --</option>
                @php
                    $products = \App\Models\Product::where('is_active', 1)->orderBy('name')->get();
                @endphp
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-price="{{ $product->price }}" data-category-id="{{ $product->primary_category_id }}" {{ (isset($item['product_id']) && $item['product_id'] == $product->id) ? 'selected' : '' }}>
                        {{ $product->name }} (SKU: {{ $product->sku }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label>Biến thể (tùy chọn)</label>
            <select name="items[{{ $index }}][product_variant_id]" class="form-control variant-select" id="variant-{{ $index }}">
                <option value="">-- Không có biến thể --</option>
                @if(isset($item['product_id']))
                    @php
                        $variants = \App\Models\ProductVariant::where('product_id', $item['product_id'])->where('is_active', 1)->get();
                    @endphp
                    @foreach($variants as $variant)
                        @php
                            if (is_array($variant->attributes)) {
                                $attrs = $variant->attributes;
                            } elseif (is_string($variant->attributes)) {
                                $attrs = json_decode($variant->attributes, true) ?: [];
                            } else {
                                $attrs = [];
                            }
                            $attrText = is_array($attrs) && !empty($attrs) ? implode(', ', array_map(fn($k, $v) => ucfirst($k) . ': ' . $v, array_keys($attrs), $attrs)) : '';
                        @endphp
                        <option value="{{ $variant->id }}" data-price="{{ $variant->price ?? $variant->sale_price ?? $variant->compare_at_price ?? '' }}" {{ (isset($item['product_variant_id']) && $item['product_variant_id'] == $variant->id) ? 'selected' : '' }}>
                            {{ $attrText ?: 'Biến thể #' . $variant->id }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>
        <div>
            <label>Số lượng <span style="color:red;">*</span></label>
            <input type="number" name="items[{{ $index }}][quantity]" class="form-control item-quantity" value="{{ $item['quantity'] ?? 1 }}" min="1" required oninput="updateItemTotal(this)">
        </div>
        <div>
            <label>Giá <span style="color:red;">*</span></label>
            <input type="number" name="items[{{ $index }}][price]" class="form-control item-price" value="{{ $item['price'] ?? 0 }}" min="0" step="1000" required oninput="updateItemTotal(this)">
        </div>
        <div>
            <label>Thành tiền</label>
            <input type="text" class="form-control item-total" value="{{ number_format(($item['quantity'] ?? 1) * ($item['price'] ?? 0)) }} đ" readonly style="background:#f8fafc;">
        </div>
    </div>
</div>

