@extends('admins.layouts.master')

@section('title', 'Import Sản Phẩm từ Excel')
@section('page-title', '📥 Import Excel')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/imports-excel.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #ddd;
            border-radius: 6px;
            background: #fafafa;
            cursor: pointer;
        }
        input[type="file"]:hover {
            border-color: #007bff;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            color: #fff;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .info-box h3 {
            margin-bottom: 10px;
            color: #007bff;
        }
        .info-box ul {
            margin-left: 20px;
            color: #555;
        }
        .info-box li {
            margin-bottom: 5px;
        }
        .sheet-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .sheet-info h4 {
            color: #333;
            margin-bottom: 8px;
        }
        .sheet-info code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 13px;
        }
    </style>
@endpush

@section('content')
    <div>
        <div style="display:flex; justify-content: space-between; align-items:center; gap:15px;">
            <div>
                <h1>📥 Import Sản Phẩm từ Excel</h1>
                <p class="subtitle">Upload file Excel để import sản phẩm, hình ảnh, biến thể, FAQs và hướng dẫn</p>
            </div>
            <div style="display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end;">
                <a href="{{ route('admin.products.index') }}"
                   style="
                       display:inline-block;
                       padding:10px 18px;
                       background:#475569;
                       color:#fff;
                       border-radius:8px;
                       text-decoration:none;
                       font-weight:600;
                   ">
                    ↩️ Quản lý sản phẩm
                </a>
                <a href="{{ route('admin.products.export-excel') }}"
                   style="
                       display: inline-block;
                       padding: 10px 18px;
                       background: linear-gradient(135deg, #0f766e, #0ea5e9);
                       color: #fff;
                       border-radius: 8px;
                       text-decoration: none;
                       font-size: 14px;
                       font-weight: 600;
                       box-shadow: 0 3px 8px rgba(0,0,0,0.15);
                       transition: 0.25s ease;
                   "
                   onmouseover="this.style.background='linear-gradient(135deg,#2a5298,#1e3c72)'"
                   onmouseout="this.style.background='linear-gradient(135deg,#1e3c72,#2a5298)'"
                >
                    ⬇️ Export toàn bộ sản phẩm
                </a>
            </div>
        </div>
        

        @if(session('success'))
            <div class="alert alert-success">
                ✅ {{ session('success') }}
                @if(session('log_file'))
                    <br><br>
                    <strong>📄 File log lỗi:</strong> 
                    <code>{{ session('log_file') }}</code><br>
                    <small>Đường dẫn: <code>storage/logs/imports/{{ session('log_file') }}</code></small>
                @endif
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-error">
                ❌ {{ session('error') }}
                @if(session('log_file'))
                    <br><br>
                    <strong>📄 File log lỗi:</strong> 
                    <code>{{ session('log_file') }}</code><br>
                    <small>Đường dẫn: <code>storage/logs/imports/{{ session('log_file') }}</code></small>
                @endif
            </div>
        @endif

        <div class="info-box">
            <h3>📋 Cấu trúc file Excel yêu cầu:</h3>
            <p style="margin-bottom: 15px; color: #666;">File Excel phải có <strong>4 sheets</strong> với tên chính xác như sau:</p>
            
            <div class="sheet-info">
                <h4>📦 Sheet 1: <code>products</code> (Bắt buộc)</h4>
                <p style="font-family: monospace; background: #f1f3f5; padding: 10px; border-radius: 4px; margin: 8px 0; font-size: 12px; word-break: break-all;">
                    sku | name | slug | description | short_description | price | sale_price | cost_price | stock_quantity | meta_title | meta_description | meta_keywords | meta_canonical | primary_category_slug | category_slugs | tag_slugs | image_ids | is_featured | is_active | created_by
                </p>
                <div style="margin-top: 10px; font-size: 13px; color: #555;">
                    <p><strong>📝 Giải thích các cột quan trọng:</strong></p>
                    <ul style="margin-left: 20px; line-height: 1.8;">
                        <li><strong>sku</strong>: Mã SKU duy nhất của sản phẩm (bắt buộc). Nếu SKU đã tồn tại → cập nhật, chưa có → tạo mới.</li>
                        <li><strong>primary_category_slug</strong>: Slug của danh mục chính (phải tồn tại trong hệ thống).</li>
                        <li><strong>category_slugs</strong>: Danh sách slug danh mục phụ, cách nhau bởi dấu phẩy (ví dụ: <code>ao-nam,ao-thun</code>).</li>
                        <li><strong>tag_slugs</strong>: Danh sách <strong>tên tag</strong> (không phải slug), cách nhau bởi dấu phẩy. Hệ thống tự tạo tag mới nếu chưa có.</li>
                        <li><strong>image_ids</strong>: Danh sách image_key (ví dụ: <code>IMG1,IMG2,IMG3</code>) tương ứng với Sheet 2.</li>
                        <li><strong>is_featured</strong>: 1 = nổi bật, 0 = không nổi bật.</li>
                        <li><strong>is_active</strong>: 1 = hiển thị, 0 = ẩn.</li>
                        <li><strong>created_by</strong>: ID người tạo (thường là ID admin).</li>
                    </ul>
                </div>
            </div>
            
            <div class="sheet-info">
                <h4>🖼️ Sheet 2: <code>images</code> (Tùy chọn - Khuyến nghị)</h4>
                <p style="font-family: monospace; background: #f1f3f5; padding: 10px; border-radius: 4px; margin: 8px 0; font-size: 12px;">
                    sku | image_key | url | title | notes | alt | is_primary | order
                </p>
                <div style="margin-top: 10px; font-size: 13px; color: #555;">
                    <p><strong>📝 Giải thích:</strong></p>
                    <ul style="margin-left: 20px; line-height: 1.8;">
                        <li><strong>sku</strong>: <span style="color: #d63384; font-weight: 600;">Mã SKU của sản phẩm</span> (bắt buộc) - dùng để liên kết ảnh với sản phẩm.</li>
                        <li><strong>image_key</strong>: Mã định danh ảnh (ví dụ: <code>IMG1</code>, <code>IMG2</code>). Nếu có ID cũ → cập nhật, chưa có → tạo mới.</li>
                        <li><strong>url</strong>: Tên file ảnh (ví dụ: <code>ao-so-mi-nam-123.webp</code>). Ảnh phải có sẵn trong <code>public/clients/assets/img/clothes/</code>.</li>
                        <li><strong>title</strong>: Tiêu đề ảnh (tùy chọn).</li>
                        <li><strong>notes</strong>: Ghi chú về ảnh (tùy chọn).</li>
                        <li><strong>alt</strong>: Alt text cho SEO (tùy chọn).</li>
                        <li><strong>is_primary</strong>: 1 = ảnh chính, 0 = ảnh phụ.</li>
                        <li><strong>order</strong>: Thứ tự hiển thị (số nguyên, 0 = đầu tiên).</li>
                    </ul>
                    <p style="margin-top: 10px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; border-radius: 4px;">
                        <strong>💡 Lưu ý:</strong> Mỗi dòng trong sheet này đại diện cho <strong>một ảnh thuộc một sản phẩm</strong>. 
                        Nếu một sản phẩm có nhiều ảnh, hãy tạo nhiều dòng với cùng SKU.
                    </p>
                </div>
            </div>
            
            <div class="sheet-info">
                <h4>❓ Sheet 3: <code>faqs</code> (Tùy chọn)</h4>
                <p style="font-family: monospace; background: #f1f3f5; padding: 10px; border-radius: 4px; margin: 8px 0; font-size: 12px;">
                    sku | question | answer | order
                </p>
                <div style="margin-top: 10px; font-size: 13px; color: #555;">
                    <ul style="margin-left: 20px; line-height: 1.8;">
                        <li><strong>sku</strong>: Mã SKU của sản phẩm.</li>
                        <li><strong>question</strong>: Câu hỏi (bắt buộc).</li>
                        <li><strong>answer</strong>: Câu trả lời (tùy chọn).</li>
                        <li><strong>order</strong>: Thứ tự hiển thị.</li>
                    </ul>
                </div>
            </div>
            
            <div class="sheet-info">
                <h4>📖 Sheet 4: <code>how_tos</code> (Tùy chọn)</h4>
                <p style="font-family: monospace; background: #f1f3f5; padding: 10px; border-radius: 4px; margin: 8px 0; font-size: 12px;">
                    sku | title | description | steps | supplies | is_active
                </p>
                <div style="margin-top: 10px; font-size: 13px; color: #555;">
                    <ul style="margin-left: 20px; line-height: 1.8;">
                        <li><strong>sku</strong>: Mã SKU của sản phẩm.</li>
                        <li><strong>title</strong>: Tiêu đề hướng dẫn (bắt buộc).</li>
                        <li><strong>description</strong>: Mô tả tổng quan (tùy chọn).</li>
                        <li><strong>steps</strong>: Danh sách bước (JSON array), ví dụ: <code>["Bước 1", "Bước 2"]</code> hoặc để trống.</li>
                        <li><strong>supplies</strong>: Dụng cụ cần thiết (JSON array), ví dụ: <code>["Kéo", "Kim"]</code> hoặc để trống.</li>
                        <li><strong>is_active</strong>: 1 = hiển thị, 0 = ẩn.</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="info-box" style="background: #fff3cd; border-left-color: #ffc107; margin-top: 20px;">
            <h3>⚠️ Lưu ý quan trọng:</h3>
            <ul style="margin: 0; color: #856404; line-height: 1.8;">
                <li><strong>Danh mục (Categories):</strong> Phải được tạo trước trong hệ thống. Sau đó lấy <code>slug</code> của danh mục để điền vào cột <code>primary_category_slug</code> và <code>category_slugs</code> trong Sheet 1 (products). Nếu slug không tồn tại, hệ thống sẽ bỏ qua và ghi vào log lỗi.</li>
                <li><strong>Ảnh (Images):</strong> File ảnh phải có sẵn trong thư mục <code>public/clients/assets/img/clothes/</code> trước khi import. Trong cột <code>url</code> của Sheet 2, chỉ cần điền <strong>tên file</strong> (ví dụ: <code>ao-so-mi-nam-123.webp</code>), không cần đường dẫn đầy đủ.</li>
                <li><strong>SKU trong Sheet images:</strong> Cột <code>sku</code> ở Sheet 2 là <strong>bắt buộc</strong> để hệ thống biết ảnh thuộc sản phẩm nào. Nếu SKU không tồn tại, ảnh sẽ bị bỏ qua.</li>
                <li><strong>Import/Export:</strong> File Excel export và import phải có <strong>cấu trúc giống nhau 100%</strong>. Khuyến nghị export file mẫu trước, sau đó chỉnh sửa và import lại.</li>
            </ul>
        </div>

        <form action="{{ route('admin.products.import-excel.process') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="excel_file">Chọn file Excel (.xlsx, .xls)</label>
                <input type="file" name="excel_file" id="excel_file" accept=".xlsx,.xls" required>
                @error('excel_file')
                    <div style="color: #dc3545; margin-top: 5px;">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="btn btn-primary">🚀 Bắt đầu Import</button>
        </form>
    </div>
@endsection

