// Show lọc danh mục sản phẩm
const autosensorFilterWrapper = document.querySelector('.autosensor_shop_products_filter');
const autosensorFilterHeading = document.querySelector('.autosensor_shop_products_filter_categories_title');

if (autosensorFilterWrapper && autosensorFilterHeading) {
    autosensorFilterHeading.addEventListener('click', function() {
        autosensorFilterWrapper.classList.toggle('autosensor_shop_products_filter_height_full');
    });
}

// Form lọc giá
async function setPrice(min, max) {
    showCustomToast(`Chọn giá từ ${min} đến ${max}`);
    await sleep(1000);
    const minInput = document.getElementById('minPriceRange');
    const maxInput = document.getElementById('maxPriceRange');
    const form = document.getElementById('autosensor_shop_products_filter_price_content_form');

    if (!minInput || !maxInput || !form) {
        return;
    }

    minInput.value = min;
    maxInput.value = max;
    form.submit();
}

// Function để update brand filter
function updateBrandFilter() {
    const checkboxes = document.querySelectorAll('.autosensor_shop_products_filter_brands_checkbox:checked');
    const selectedBrands = Array.from(checkboxes).map(cb => cb.value);
    
    // Lấy URL hiện tại
    const url = new URL(window.location.href);
    
    // Xóa brands cũ
    url.searchParams.delete('brands');
    url.searchParams.delete('brand'); // Xóa brand cũ nếu có (backward compatibility)
    
    // Thêm brands mới nếu có
    if (selectedBrands.length > 0) {
        url.searchParams.set('brands', selectedBrands.join(','));
    }
    
    // Reset về page 1 khi filter thay đổi
    url.searchParams.set('page', '1');
    
    // Giữ lại tất cả các filter khác (category, keyword, tags, price, rating, sort, perPage)
    // Các params này đã có trong URL nên sẽ tự động được giữ lại
    
    // Redirect
    window.location.href = url.toString();
}

