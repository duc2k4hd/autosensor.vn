@php
    $isEdit = $banner->exists;
    $positionDescriptions = [
        'homepage_banner_parent' => '🎠 Banner chính ở slider giữa trang chủ (có thể nhiều banner, tự động chuyển slide). Kích thước khuyến nghị: 1920x600px',
        'homepage_banner_children' => '📌 Banner phụ bên phải trang chủ (tối đa 2 banner, hiển thị cố định). Kích thước khuyến nghị: 400x300px',
        'homepage' => 'Hiển thị ở trang chủ (vị trí tổng quát)',
        'sidebar' => 'Hiển thị ở thanh bên (sidebar) của các trang',
        'footer' => 'Hiển thị ở phần chân trang (footer)',
        'header' => 'Hiển thị ở phần đầu trang (header)',
        'category' => 'Hiển thị trên các trang danh mục sản phẩm',
        'product' => 'Hiển thị trên các trang chi tiết sản phẩm',
        'post' => 'Hiển thị trên các trang bài viết/blog',
    ];
@endphp

@push('styles')
<style>
    .banner-form-section {
        background: #fff;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(15,23,42,0.08);
        margin-bottom: 20px;
    }
    .banner-form-section h3 {
        margin: 0 0 16px;
        font-size: 18px;
        font-weight: 600;
        color: #0f172a;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 8px;
    }
    .banner-image-upload {
        border: 2px dashed #cbd5e1;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8fafc;
        cursor: pointer;
        transition: all 0.3s;
    }
    .banner-image-upload:hover {
        border-color: #3b82f6;
        background: #eff6ff;
    }
    .banner-image-upload.has-image {
        border-style: solid;
        border-color: #10b981;
        background: #f0fdf4;
    }
    .banner-image-preview {
        margin-top: 12px;
        display: none;
    }
    .banner-image-preview.show {
        display: block;
    }
    .banner-image-preview img {
        max-width: 100%;
        max-height: 300px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .banner-image-info {
        margin-top: 8px;
        font-size: 12px;
        color: #64748b;
    }
    .banner-image-info strong {
        color: #1e40af;
    }
    .position-description {
        margin-top: 6px;
        font-size: 12px;
        color: #64748b;
        font-style: italic;
        padding: 8px;
        background: #f1f5f9;
        border-radius: 6px;
        border-left: 3px solid #3b82f6;
    }
    .image-picker-btn {
        margin-top: 8px;
        padding: 6px 12px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 13px;
    }
    .image-picker-btn:hover {
        background: #2563eb;
    }
</style>
@endpush

<form action="{{ $isEdit ? route('admin.banners.update', $banner) : route('admin.banners.store') }}" method="POST" enctype="multipart/form-data" id="banner-form">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h2 style="margin:0;">{{ $isEdit ? 'Chỉnh sửa banner' : 'Tạo banner mới' }}</h2>
            <p style="margin:4px 0 0;color:#64748b;">Quản lý banner hiển thị trên website</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">↩️ Quay lại</a>
            <button type="submit" class="btn btn-primary">💾 Lưu banner</button>
        </div>
    </div>

    <!-- Thông tin cơ bản -->
    <div class="banner-form-section">
        <h3>📋 Thông tin cơ bản</h3>
        <div class="grid-3">
            <div style="grid-column: span 2;">
                <label>Tiêu đề banner *</label>
                <input type="text" name="title" class="form-control" value="{{ old('title', $banner->title) }}" 
                       placeholder="Ví dụ: Khuyến mãi mùa hè 2025" required>
                <small style="color:#94a3b8;">Tiêu đề giúp bạn nhận biết banner này dễ dàng hơn</small>
                @error('title')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div>
                <label>Vị trí hiển thị *</label>
                <select name="position" class="form-control" id="banner-position" required>
                    <option value="">-- Chọn vị trí --</option>
                    @foreach($positions ?? config('banners.positions', []) as $key => $label)
                        <option value="{{ $key }}" {{ old('position', $banner->position) === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <div class="position-description" id="position-description" style="display:none;">
                    <span id="position-desc-text"></span>
                </div>
                @error('position')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
            <div style="grid-column: span 2;">
                <label>Liên kết khi click vào banner</label>
                <input type="url" name="link" class="form-control" value="{{ old('link', $banner->link) }}" 
                       placeholder="https://autosensor.vn/san-pham/... hoặc để trống nếu không cần link">
                <small style="color:#94a3b8;">Người dùng sẽ được chuyển đến link này khi click vào banner</small>
            </div>
            <div>
                <label>Cách mở link</label>
                <select name="target" class="form-control">
                    <option value="_blank" {{ old('target', $banner->target ?? '_blank') === '_blank' ? 'selected' : '' }}>🔗 Mở tab mới</option>
                    <option value="_self" {{ old('target', $banner->target ?? '_blank') === '_self' ? 'selected' : '' }}>📄 Cùng trang</option>
                </select>
            </div>
            <div>
                <label>Mô tả (tùy chọn)</label>
                <textarea name="description" rows="3" class="form-control" 
                          placeholder="Mô tả ngắn về banner này...">{{ old('description', $banner->description) }}</textarea>
            </div>
        </div>
    </div>

    <!-- Hình ảnh -->
    <div class="banner-form-section">
        <h3>🖼️ Hình ảnh banner</h3>
        <div class="grid-3">
            <!-- Desktop Image -->
            <div>
                <label>Ảnh cho máy tính (Desktop) {{ $isEdit ? '' : '*' }}</label>
                <div class="banner-image-upload {{ $isEdit && $banner->image_desktop ? 'has-image' : '' }}" 
                     onclick="document.getElementById('image_desktop_input').click()">
                    <input type="file" name="image_desktop" id="image_desktop_input" 
                           class="form-control" accept="image/*" {{ $isEdit ? '' : 'required' }} 
                           style="display:none;" onchange="previewImage(this, 'desktop')">
                    <div id="desktop_upload_area">
                        @if($isEdit && $banner->image_desktop)
                            <div class="banner-image-preview show" id="desktop_preview">
                                <img src="{{ $banner->image_desktop_url }}" alt="Desktop preview" id="desktop_preview_img">
                                <div class="banner-image-info">
                                    <strong>Ảnh hiện tại:</strong> {{ $banner->image_desktop }}<br>
                                    <span>Click để thay đổi</span>
                                </div>
                            </div>
                        @else
                            <div style="padding:20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#94a3b8" viewBox="0 0 640 640" style="margin:0 auto 12px;">
                                    <path d="M144 480C144 408.4 200.4 352 272 352C343.6 352 400 408.4 400 480C400 551.6 343.6 608 272 608C200.4 608 144 551.6 144 480zM272 320C174.8 320 96 398.8 96 496C96 593.2 174.8 672 272 672C369.2 672 448 593.2 448 496C448 398.8 369.2 320 272 320zM272 416C290.3 416 304 429.7 304 448C304 466.3 290.3 480 272 480C253.7 480 240 466.3 240 448C240 429.7 253.7 416 272 416zM512 0C547.3 0 576 28.65 576 64V192H672C707.3 192 736 220.7 736 256V448C736 483.3 707.3 512 672 512H512C476.7 512 448 483.3 448 448V256C448 220.7 476.7 192 512 192H576V64H448V192H512C547.3 192 576 220.7 576 256V448H672V256H512V0z"/>
                                </svg>
                                <p style="margin:0;color:#64748b;">Click để chọn ảnh hoặc kéo thả vào đây</p>
                            </div>
                        @endif
                    </div>
                    <div class="banner-image-preview" id="desktop_preview_new">
                        <img id="desktop_preview_img_new" src="" alt="Preview">
                    </div>
                </div>
                <div class="banner-image-info">
                    <strong>Kích thước khuyến nghị:</strong> {{ config('banners.image.desktop.width', 1920) }}x{{ config('banners.image.desktop.height', 600) }}px<br>
                    <strong>Định dạng:</strong> JPG, PNG, WEBP | <strong>Tối đa:</strong> 5MB
                </div>
                @error('image_desktop')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>

            <!-- Mobile Image -->
            <div>
                <label>Ảnh cho điện thoại (Mobile) <span style="color:#94a3b8;">(Tùy chọn)</span></label>
                <div class="banner-image-upload {{ $isEdit && $banner->image_mobile ? 'has-image' : '' }}" 
                     onclick="document.getElementById('image_mobile_input').click()">
                    <input type="file" name="image_mobile" id="image_mobile_input" 
                           class="form-control" accept="image/*" 
                           style="display:none;" onchange="previewImage(this, 'mobile')">
                    <div id="mobile_upload_area">
                        @if($isEdit && $banner->image_mobile)
                            <div class="banner-image-preview show" id="mobile_preview">
                                <img src="{{ $banner->image_mobile_url }}" alt="Mobile preview" id="mobile_preview_img">
                                <div class="banner-image-info">
                                    <strong>Ảnh hiện tại:</strong> {{ $banner->image_mobile }}<br>
                                    <span>Click để thay đổi</span>
                                </div>
                            </div>
                        @else
                            <div style="padding:20px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#94a3b8" viewBox="0 0 640 640" style="margin:0 auto 12px;">
                                    <path d="M144 480C144 408.4 200.4 352 272 352C343.6 352 400 408.4 400 480C400 551.6 343.6 608 272 608C200.4 608 144 551.6 144 480zM272 320C174.8 320 96 398.8 96 496C96 593.2 174.8 672 272 672C369.2 672 448 593.2 448 496C448 398.8 369.2 320 272 320zM272 416C290.3 416 304 429.7 304 448C304 466.3 290.3 480 272 480C253.7 480 240 466.3 240 448C240 429.7 253.7 416 272 416zM512 0C547.3 0 576 28.65 576 64V192H672C707.3 192 736 220.7 736 256V448C736 483.3 707.3 512 672 512H512C476.7 512 448 483.3 448 448V256C448 220.7 476.7 192 512 192H576V64H448V192H512C547.3 192 576 220.7 576 256V448H672V256H512V0z"/>
                                </svg>
                                <p style="margin:0;color:#64748b;">Click để chọn ảnh hoặc kéo thả vào đây</p>
                                <p style="margin:8px 0 0;font-size:12px;color:#94a3b8;">Nếu không chọn, sẽ dùng ảnh desktop</p>
                            </div>
                        @endif
                    </div>
                    <div class="banner-image-preview" id="mobile_preview_new">
                        <img id="mobile_preview_img_new" src="" alt="Preview">
                    </div>
                </div>
                <div class="banner-image-info">
                    <strong>Kích thước khuyến nghị:</strong> {{ config('banners.image.mobile.width', 768) }}x{{ config('banners.image.mobile.height', 400) }}px<br>
                    <strong>Định dạng:</strong> JPG, PNG, WEBP | <strong>Tối đa:</strong> 5MB
                </div>
                @error('image_mobile')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Cài đặt hiển thị -->
    <div class="banner-form-section">
        <h3>⚙️ Cài đặt hiển thị</h3>
        <div class="grid-3">
            <div>
                <label>Trạng thái</label>
                <select name="is_active" class="form-control">
                    <option value="1" {{ old('is_active', $banner->is_active ?? true) ? 'selected' : '' }}>✅ Đang bật (hiển thị)</option>
                    <option value="0" {{ old('is_active', $banner->is_active ?? true) ? '' : 'selected' }}>❌ Tắt (ẩn)</option>
                </select>
                <small style="color:#94a3b8;">Banner chỉ hiển thị khi trạng thái là "Đang bật"</small>
            </div>
            <div>
                <label>Thứ tự hiển thị</label>
                <input type="number" name="order" class="form-control" 
                       value="{{ old('order', $banner->order ?? ($isEdit ? $banner->order : '')) }}" 
                       min="0" 
                       placeholder="Tự động (để trống)">
                <small style="color:#94a3b8;">Số nhỏ hơn sẽ hiển thị trước. Để trống sẽ tự động đặt cuối cùng.</small>
            </div>
            <div>
                <label>Bắt đầu hiển thị</label>
                <input type="datetime-local" name="start_at" class="form-control"
                       value="{{ old('start_at', optional($banner->start_at)->format('Y-m-d\TH:i')) }}">
                <small style="color:#94a3b8;">Để trống = hiển thị ngay lập tức</small>
            </div>
            <div>
                <label>Kết thúc hiển thị</label>
                <input type="datetime-local" name="end_at" class="form-control"
                       value="{{ old('end_at', optional($banner->end_at)->format('Y-m-d\TH:i')) }}">
                <small style="color:#94a3b8;">Để trống = hiển thị mãi mãi</small>
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px;margin-bottom:20px;">
        <a href="{{ route('admin.banners.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
        <button type="submit" class="btn btn-primary">💾 Lưu banner</button>
    </div>
</form>

@push('scripts')
<script>
    // Position description
    const positionDescriptions = @json($positionDescriptions);
    const positionSelect = document.getElementById('banner-position');
    const positionDesc = document.getElementById('position-description');
    const positionDescText = document.getElementById('position-desc-text');

    function updatePositionDescription() {
        const selected = positionSelect.value;
        if (selected && positionDescriptions[selected]) {
            positionDescText.textContent = positionDescriptions[selected];
            positionDesc.style.display = 'block';
        } else {
            positionDesc.style.display = 'none';
        }
    }

    if (positionSelect) {
        positionSelect.addEventListener('change', updatePositionDescription);
        updatePositionDescription(); // Initial call
    }

    // Image preview
    function previewImage(input, type) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            const previewId = type + '_preview_new';
            const previewImgId = type + '_preview_img_new';
            const uploadAreaId = type + '_upload_area';

            reader.onload = function(e) {
                const preview = document.getElementById(previewId);
                const previewImg = document.getElementById(previewImgId);
                const uploadArea = document.getElementById(uploadAreaId);

                if (preview && previewImg) {
                    previewImg.src = e.target.result;
                    preview.classList.add('show');
                    if (uploadArea) {
                        uploadArea.style.display = 'none';
                    }
                }
            };

            reader.readAsDataURL(input.files[0]);

            // Update upload area style
            const uploadArea = document.querySelector(`#image_${type}_input`).closest('.banner-image-upload');
            if (uploadArea) {
                uploadArea.classList.add('has-image');
            }
        }
    }

    // Drag and drop
    ['desktop', 'mobile'].forEach(type => {
        const uploadArea = document.querySelector(`#image_${type}_input`).closest('.banner-image-upload');
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#3b82f6';
                uploadArea.style.background = '#eff6ff';
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.style.borderColor = '#cbd5e1';
                uploadArea.style.background = '#f8fafc';
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#cbd5e1';
                uploadArea.style.background = '#f8fafc';

                const files = e.dataTransfer.files;
                if (files.length > 0 && files[0].type.startsWith('image/')) {
                    const input = document.getElementById(`image_${type}_input`);
                    input.files = files;
                    previewImage(input, type);
                }
            });
        }
    });
</script>
@endpush

