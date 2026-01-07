@extends('admins.layouts.master')

@php
    $isEdit = $brand->exists;
    $pageTitle = $isEdit ? 'Chỉnh sửa hãng' : 'Tạo hãng mới';
    $metadata = $brand->metadata ?? [];
    if (is_string($metadata)) {
        $metadata = json_decode($metadata, true) ?? [];
    }
@endphp

@section('title', $pageTitle)
@section('page-title', '🏢 ' . $pageTitle)

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/brand-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card > h3 {
            margin: 0 0 16px;
            font-size: 18px;
            font-weight: 600;
            color: #1e293b;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        
        .grid-3 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 16px;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-control,
        textarea,
        select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-control:focus,
        textarea:focus,
        select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
            color: #1e293b;
        }
        
        .form-help {
            font-size: 12px;
            color: #64748b;
            margin-top: 4px;
        }
        
        .image-preview {
            margin-top: 12px;
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        
        .image-preview img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #e2e8f0;
        }
        
        .image-preview-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .brand-form-layout {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 20px;
            align-items: flex-start;
        }
        
        .brand-form-main {
            min-width: 0;
        }
        
        .brand-form-sidebar {
            position: sticky;
            top: 20px;
            max-height: calc(100vh - 40px);
            overflow-y: auto;
        }
        
        .sidebar-card {
            background: #fff;
            border-radius: 12px;
            padding: 16px 18px;
            margin-bottom: 14px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
        }
        
        .sidebar-card h4 {
            margin: 0 0 12px;
            font-size: 15px;
            font-weight: 600;
            color: #1f2937;
            padding-bottom: 8px;
            border-bottom: 2px solid #f3f4f6;
        }
        
        .sidebar-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .sidebar-actions .btn {
            width: 100%;
            justify-content: center;
        }
        
        .sidebar-info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 13px;
        }
        
        .sidebar-info-item:last-child {
            border-bottom: none;
        }
        
        .sidebar-info-label {
            color: #6b7280;
            font-weight: 500;
        }
        
        .sidebar-info-value {
            color: #111827;
            font-weight: 600;
            max-width: 60%;
            text-align: right;
            word-break: break-word;
        }
        
        .sidebar-status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .sidebar-status-badge.active {
            background: #dcfce7;
            color: #15803d;
        }
        
        .sidebar-status-badge.inactive {
            background: #fee2e2;
            color: #b91c1c;
        }
        
        @media (max-width: 1200px) {
            .brand-form-layout {
                grid-template-columns: 1fr;
            }
            
            .brand-form-sidebar {
                position: static;
                max-height: none;
            }
        }
    </style>
@endpush

@section('content')
    <form action="{{ $isEdit ? route('admin.brands.update', $brand) : route('admin.brands.store') }}"
          method="POST" enctype="multipart/form-data" id="brandForm">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 style="margin:0;">{{ $pageTitle }}</h2>
        </div>

        <div class="brand-form-layout">
            <div class="brand-form-main">
        <div class="card">
            <h3>Thông tin cơ bản</h3>
            
            <div class="grid-3">
                <div class="form-group">
                    <label for="name">Tên hãng <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="name" id="name" class="form-control"
                           value="{{ old('name', $brand->name) }}" required minlength="2" maxlength="150">
                    <div class="form-help">Tên hãng (2-150 ký tự)</div>
                    @error('name')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="slug">Slug</label>
                    <input type="text" name="slug" id="slug" class="form-control"
                           value="{{ old('slug', $brand->slug) }}"
                           pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                           placeholder="Tự động tạo từ tên">
                    <div class="form-help">Slug sẽ tự động tạo nếu để trống</div>
                    @error('slug')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="order">Thứ tự</label>
                    <input type="number" name="order" id="order" class="form-control"
                           value="{{ old('order', $brand->order ?? 0) }}" min="0">
                    <div class="form-help">Số càng nhỏ, hiển thị càng trước</div>
                    @error('order')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="is_active">Trạng thái</label>
                    <select name="is_active" id="is_active" class="form-control">
                        <option value="1" {{ old('is_active', $brand->is_active ?? true) ? 'selected' : '' }}>Hiển thị</option>
                        <option value="0" {{ old('is_active', $brand->is_active ?? true) ? '' : 'selected' }}>Tạm ẩn</option>
                    </select>
                    <div class="form-help">Chọn trạng thái hiển thị của hãng</div>
                    @error('is_active')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Thông tin bổ sung</h3>
            <div class="grid-2">
                <div class="form-group">
                    <label for="website">Website</label>
                    <input type="url" name="website" id="website" class="form-control"
                           value="{{ old('website', $brand->website) }}"
                           placeholder="https://example.com" maxlength="255">
                    <div class="form-help">URL website của hãng</div>
                    @error('website')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="form-group">
                    <label for="country">Quốc gia</label>
                    <input type="text" name="country" id="country" class="form-control"
                           value="{{ old('country', $brand->country) }}"
                           placeholder="Việt Nam" maxlength="100">
                    <div class="form-help">Quốc gia xuất xứ</div>
                    @error('country')
                        <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>

        <div class="card">
            <h3>Mô tả</h3>
            <div class="form-group">
                <label for="description">Mô tả hãng</label>
                <textarea name="description" id="description" rows="5" class="form-control" maxlength="5000">{{ old('description', $brand->description) }}</textarea>
                <div class="form-help">Mô tả chi tiết về hãng (tối đa 5000 ký tự)</div>
                @error('description')
                    <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card">
            <h3>Ảnh đại diện</h3>
            <div class="form-group">
                <label for="image">Ảnh hãng</label>
                <input type="file" name="image" id="image" class="form-control" accept="image/jpeg,image/png,image/webp">
                <div class="form-help">Định dạng: JPG, PNG, WebP. Kích thước tối đa: 1MB</div>
                @error('image')
                    <div style="color:#ef4444;font-size:12px;margin-top:4px;">{{ $message }}</div>
                @enderror
                
                @if($isEdit && $brand->image)
                    <div class="image-preview">
                        @php
                            $imageUrl = null;
                            if ($brand->image && file_exists(public_path($brand->image))) {
                                $imageUrl = asset($brand->image);
                            } else {
                                $imageUrl = asset('clients/assets/img/business/no-image.webp');
                            }
                        @endphp
                        <img src="{{ $imageUrl }}" 
                             alt="{{ $brand->name }}" 
                             id="imagePreview"
                             onerror="this.onerror=null; this.src='{{ asset('clients/assets/img/business/no-image.webp') }}';">
                        <div class="image-preview-actions">
                            <label style="margin:0;">
                                <input type="checkbox" name="delete_image" value="1">
                                Xóa ảnh hiện tại
                            </label>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="card">
            <h3>SEO Meta (Metadata)</h3>
            <div class="grid-3">
                <div class="form-group">
                    <label for="meta_title">Meta Title</label>
                    <input type="text" name="metadata[meta_title]" id="meta_title" class="form-control"
                           value="{{ old('metadata.meta_title', $metadata['meta_title'] ?? '') }}" maxlength="255">
                    <div class="form-help">Tiêu đề SEO (tối đa 255 ký tự)</div>
                </div>
                
                <div class="form-group">
                    <label for="meta_canonical">Meta Canonical URL</label>
                    <input type="url" name="metadata[meta_canonical]" id="meta_canonical" class="form-control"
                           value="{{ old('metadata.meta_canonical', $metadata['meta_canonical'] ?? '') }}"
                           placeholder="https://example.com/..." maxlength="500">
                    <div class="form-help">URL canonical cho SEO (tự động tạo từ slug)</div>
                </div>
                
                <div class="form-group">
                    <label for="meta_keywords">Meta Keywords</label>
                    <input type="text" name="metadata[meta_keywords]" id="meta_keywords" class="form-control"
                           value="{{ old('metadata.meta_keywords', $metadata['meta_keywords'] ?? '') }}"
                           placeholder="từ khóa 1, từ khóa 2" maxlength="255">
                    <div class="form-help">Từ khóa SEO (phân cách bằng dấu phẩy)</div>
                </div>
            </div>
            <div class="form-group">
                <label for="meta_description">Meta Description</label>
                <textarea name="metadata[meta_description]" id="meta_description" rows="3" class="form-control" maxlength="500">{{ old('metadata.meta_description', $metadata['meta_description'] ?? '') }}</textarea>
                <div class="form-help">Mô tả SEO (tối đa 500 ký tự)</div>
            </div>
        </div>
            </div> {{-- /.brand-form-main --}}

            <div class="brand-form-sidebar">
                <div class="sidebar-card">
                    <h4>Thao tác</h4>
                    <div class="sidebar-actions">
                        <button type="submit" form="brandForm" class="btn btn-primary">💾 Lưu hãng</button>
                        <a href="{{ route('admin.brands.index') }}" class="btn btn-secondary">↩️ Quay lại danh sách</a>
                        @if($isEdit)
                            <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-outline-secondary">✏️ Mở lại form</a>
                        @endif
            </div>
        </div>

                @if($isEdit)
                    <div class="sidebar-card">
                        <h4>Thông tin nhanh</h4>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">ID:</span>
                            <span class="sidebar-info-value">{{ $brand->id }}</span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Slug:</span>
                            <span class="sidebar-info-value">{{ $brand->slug }}</span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Trạng thái:</span>
                            <span class="sidebar-info-value">
                                <span class="sidebar-status-badge {{ $brand->is_active ? 'active' : 'inactive' }}">
                                    {{ $brand->is_active ? 'Hiển thị' : 'Tạm ẩn' }}
                                </span>
                            </span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Sản phẩm:</span>
                            <span class="sidebar-info-value">
                                <span class="badge badge-info">{{ $brand->products_count ?? 0 }}</span>
                            </span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Ngày tạo:</span>
                            <span class="sidebar-info-value">
                                {{ $brand->created_at?->format('d/m/Y') ?? '-' }}
                            </span>
                        </div>
                        <div class="sidebar-info-item">
                            <span class="sidebar-info-label">Cập nhật:</span>
                            <span class="sidebar-info-value">
                                {{ $brand->updated_at?->format('d/m/Y') ?? '-' }}
                            </span>
                        </div>
        </div>
                @endif
            </div> {{-- /.brand-form-sidebar --}}
        </div> {{-- /.brand-form-layout --}}
    </form>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Auto-generate slug from name
            const nameInput = document.getElementById('name');
            const slugInput = document.getElementById('slug');
            
            if (nameInput && slugInput) {
                let slugManuallyEdited = false;
                
                nameInput.addEventListener('input', () => {
                    if (!slugManuallyEdited && !slugInput.value) {
                        slugInput.value = nameInput.value
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .replace(/[^a-z0-9]+/g, '-')
                            .replace(/^-+|-+$/g, '');
                    }
                });
                
                slugInput.addEventListener('input', () => {
                    slugManuallyEdited = slugInput.value.length > 0;
                });
            }
            
            // Image preview
            const imageInput = document.getElementById('image');
            const imagePreview = document.getElementById('imagePreview');
            
            if (imageInput) {
                imageInput.addEventListener('change', (e) => {
                    const file = e.target.files[0];
                    if (file) {
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            if (imagePreview) {
                                imagePreview.src = e.target.result;
                            } else {
                                // Create preview if doesn't exist
                                const previewDiv = imageInput.closest('.form-group');
                                const previewHtml = `
                                    <div class="image-preview">
                                        <img src="${e.target.result}" id="imagePreview" style="width:150px;height:150px;object-fit:cover;border-radius:8px;border:2px solid #e2e8f0;">
                                    </div>
                                `;
                                previewDiv.insertAdjacentHTML('beforeend', previewHtml);
                            }
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        });
    </script>
@endpush

