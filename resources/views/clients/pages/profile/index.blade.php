@extends('clients.layouts.master')

@section('title', 'Thông tin tài khoản - '.($settings->site_name ?? $settings->subname ?? 'AutoSensor Việt Nam'))

@section('head')
    <meta name="description" content="Quản lý thông tin tài khoản của bạn tại {{ $settings->site_name ?? 'AutoSensor Việt Nam' }}">
    <meta name="robots" content="noindex, nofollow">
@endsection

@push('js_page')
    <script src="{{ asset('clients/assets/js/main.js') }}"></script>
@endpush

@section('content')
    <!-- Breadcrumb -->
    <section>
        <div class="xanhworld_profile_breadcrumb" style="background: #f8f9fa; padding: 15px 0; border-bottom: 1px solid #e0e0e0;">
            <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
                <div style="display: flex; align-items: center; gap: 10px; font-size: 14px; color: #666;">
                    <a href="{{ route('client.home.index') }}" style="color: #198754; text-decoration: none; transition: color 0.3s;" onmouseover="this.style.color='#0f5132';" onmouseout="this.style.color='#198754';">
                        Trang chủ
                    </a>
                    <span style="color: #999;">>></span>
                    <span style="color: #333; font-weight: 500;">Thông tin tài khoản</span>
                </div>
            </div>
        </div>
    </section>

    <div class="xanhworld_profile_page" style="min-height: 60vh; padding: 20px 20px; background: #f5f5f5;">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            {{-- <div class="xanhworld_profile_header" style="margin-bottom: 30px;">
                <h1 style="font-size: 32px; font-weight: 700; color: #0f5132; margin-bottom: 10px;">
                    Thông tin tài khoản
                </h1>
                <p style="color: #666; font-size: 16px;">Quản lý thông tin cá nhân và bảo mật tài khoản của bạn</p>
            </div> --}}

            @if(session('success'))
                <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="xanhworld_profile_content" style="display: grid; grid-template-columns: 280px 1fr; gap: 30px;">
                <!-- Sidebar -->
                <div class="xanhworld_profile_sidebar">
                    <div class="profile-menu" style="background: #fff; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <div class="profile-avatar" style="text-align: center; margin-bottom: 20px;">
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #0f5132 0%, #198754 100%); display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 40px; color: #fff; font-weight: 700;">
                                {{ strtoupper(mb_substr($account->name ?? 'U', 0, 1)) }}
                            </div>
                            <h3 style="margin-top: 15px; font-size: 18px; font-weight: 600; color: #333;">{{ $account->name }}</h3>
                            <p style="color: #666; font-size: 14px; margin-top: 5px;">{{ $account->email }}</p>
                        </div>
                        <nav class="profile-nav">
                            <a href="#profile-info" class="profile-nav-item active" data-tab="profile-info" style="display: block; padding: 12px 15px; color: #333; text-decoration: none; border-radius: 8px; margin-bottom: 8px; transition: all 0.3s; background: #e8f5e9;">
                                <i class="fas fa-user" style="margin-right: 10px;"></i> Thông tin cá nhân
                            </a>
                            <a href="#order-history" class="profile-nav-item" data-tab="order-history" style="display: block; padding: 12px 15px; color: #333; text-decoration: none; border-radius: 8px; margin-bottom: 8px; transition: all 0.3s;">
                                <i class="fas fa-receipt" style="margin-right: 10px;"></i> Đơn hàng của tôi
                            </a>
                            <a href="#change-password" class="profile-nav-item" data-tab="change-password" style="display: block; padding: 12px 15px; color: #333; text-decoration: none; border-radius: 8px; margin-bottom: 8px; transition: all 0.3s;">
                                <i class="fas fa-lock" style="margin-right: 10px;"></i> Đổi mật khẩu
                            </a>
                            <a href="{{ route('client.affiliate.index') }}" class="profile-nav-item" style="display: block; padding: 12px 15px; color: #333; text-decoration: none; border-radius: 8px; margin-bottom: 8px; transition: all 0.3s;">
                                <i class="fas fa-hand-holding-usd" style="margin-right: 10px;"></i> Chương trình Affiliate
                            </a>
                        </nav>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="xanhworld_profile_main">
                    <!-- Profile Info Tab -->
                    <div id="profile-info" class="profile-tab active" style="background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h2 style="font-size: 24px; font-weight: 600; color: #0f5132; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e8f5e9;">
                            Thông tin cá nhân
                        </h2>
                        <form action="{{ route('client.profile.update') }}" method="POST">
                            @csrf
                            @method('PUT')
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="name" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    Họ và tên <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $account->name) }}" 
                                       required
                                       style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: all 0.3s;"
                                       onfocus="this.style.borderColor='#198754'; this.style.boxShadow='0 0 0 3px rgba(25, 135, 84, 0.1)';"
                                       onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                                @error('name')
                                    <span style="color: #dc3545; font-size: 14px; margin-top: 5px; display: block;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="email" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    Email <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="{{ old('email', $account->email) }}" 
                                       required
                                       style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: all 0.3s;"
                                       onfocus="this.style.borderColor='#198754'; this.style.boxShadow='0 0 0 3px rgba(25, 135, 84, 0.1)';"
                                       onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                                @error('email')
                                    <span style="color: #dc3545; font-size: 14px; margin-top: 5px; display: block;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    Trạng thái tài khoản
                                </label>
                                <div style="padding: 12px 15px; background: #f8f9fa; border-radius: 8px; border: 2px solid #e0e0e0;">
                                    <span style="color: {{ $account->status === 'active' ? '#198754' : '#dc3545' }}; font-weight: 600;">
                                        {{ $account->status === 'active' ? '✓ Đã kích hoạt' : '✗ Chưa kích hoạt' }}
                                    </span>
                                    @if($account->email_verified_at)
                                        <span style="color: #198754; margin-left: 15px;">
                                            <i class="fas fa-check-circle"></i> Email đã xác thực
                                        </span>
                                    @else
                                        <span style="color: #ffc107; margin-left: 15px;">
                                            <i class="fas fa-exclamation-circle"></i> Email chưa xác thực
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    Ngày tham gia
                                </label>
                                <div style="padding: 12px 15px; background: #f8f9fa; border-radius: 8px; border: 2px solid #e0e0e0; color: #666;">
                                    {{ $account->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>

                            <button type="submit" 
                                    style="background: linear-gradient(135deg, #0f5132 0%, #198754 100%); color: #fff; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s;"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(15, 81, 50, 0.3)';"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                <i class="fas fa-save" style="margin-right: 8px;"></i> Lưu thay đổi
                            </button>
                        </form>
                    </div>

                    <!-- Change Password Tab -->
                    <div id="change-password" class="profile-tab" style="display: none; background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h2 style="font-size: 24px; font-weight: 600; color: #0f5132; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e8f5e9;">
                            Đổi mật khẩu
                        </h2>
                        <form action="{{ route('client.profile.change-password') }}" method="POST">
                            @csrf
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="current_password" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    Mật khẩu hiện tại <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="password" 
                                       id="current_password" 
                                       name="current_password" 
                                       required
                                       style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: all 0.3s;"
                                       onfocus="this.style.borderColor='#198754'; this.style.boxShadow='0 0 0 3px rgba(25, 135, 84, 0.1)';"
                                       onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                                @error('current_password')
                                    <span style="color: #dc3545; font-size: 14px; margin-top: 5px; display: block;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="password" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    Mật khẩu mới <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="password" 
                                       id="password" 
                                       name="password" 
                                       required
                                       minlength="8"
                                       style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: all 0.3s;"
                                       onfocus="this.style.borderColor='#198754'; this.style.boxShadow='0 0 0 3px rgba(25, 135, 84, 0.1)';"
                                       onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                                @error('password')
                                    <span style="color: #dc3545; font-size: 14px; margin-top: 5px; display: block;">{{ $message }}</span>
                                @enderror
                                <small style="color: #666; font-size: 14px; margin-top: 5px; display: block;">Mật khẩu phải có ít nhất 8 ký tự</small>
                            </div>

                            <div class="form-group" style="margin-bottom: 20px;">
                                <label for="password_confirmation" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333;">
                                    Xác nhận mật khẩu mới <span style="color: #dc3545;">*</span>
                                </label>
                                <input type="password" 
                                       id="password_confirmation" 
                                       name="password_confirmation" 
                                       required
                                       minlength="8"
                                       style="width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 16px; transition: all 0.3s;"
                                       onfocus="this.style.borderColor='#198754'; this.style.boxShadow='0 0 0 3px rgba(25, 135, 84, 0.1)';"
                                       onblur="this.style.borderColor='#e0e0e0'; this.style.boxShadow='none';">
                            </div>

                            <button type="submit" 
                                    style="background: linear-gradient(135deg, #0f5132 0%, #198754 100%); color: #fff; padding: 12px 30px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; transition: all 0.3s;"
                                    onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 4px 12px rgba(15, 81, 50, 0.3)';"
                                    onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='none';">
                                <i class="fas fa-key" style="margin-right: 8px;"></i> Đổi mật khẩu
                            </button>
                        </form>
                    </div>

                    <!-- Order History Tab -->
                    <div id="order-history" class="profile-tab" style="display: none; background: #fff; border-radius: 12px; padding: 30px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        <h2 style="font-size: 24px; font-weight: 600; color: #0f5132; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 2px solid #e8f5e9;">
                            Đơn hàng của tôi
                        </h2>

                        @php
                            $statusBadges = [
                                'pending' => ['label' => 'Chờ xác nhận', 'color' => '#b45309', 'bg' => 'rgba(249,115,22,0.15)'],
                                'confirmed' => ['label' => 'Đang xử lý', 'color' => '#1d4ed8', 'bg' => 'rgba(59,130,246,0.15)'],
                                'shipping' => ['label' => 'Đang vận chuyển', 'color' => '#0369a1', 'bg' => 'rgba(14,165,233,0.15)'],
                                'completed' => ['label' => 'Hoàn tất', 'color' => '#15803d', 'bg' => 'rgba(34,197,94,0.15)'],
                                'cancelled' => ['label' => 'Đã hủy', 'color' => '#b91c1c', 'bg' => 'rgba(248,113,113,0.15)'],
                            ];
                        @endphp

                        @if ($orders->isEmpty())
                            <p style="color: #6b7280;">Bạn chưa có đơn hàng nào.</p>
                        @else
                            <div class="order-table-wrapper" style="overflow-x: auto;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="text-transform: uppercase; letter-spacing: 0.05em; font-size: 13px; color: #9ca3af;">
                                            <th style="text-align: left; padding-bottom: 12px;">Mã đơn</th>
                                            <th style="text-align: left; padding-bottom: 12px;">Ngày tạo</th>
                                            <th style="text-align: left; padding-bottom: 12px;">Sản phẩm</th>
                                            <th style="text-align: left; padding-bottom: 12px;">Tổng tiền</th>
                                            <th style="text-align: left; padding-bottom: 12px;">Trạng thái</th>
                                            <th style="text-align: left; padding-bottom: 12px;">Hành động</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($orders as $order)
                                            @php
                                                $badge = $statusBadges[$order->status] ?? ['label' => ucfirst($order->status), 'color' => '#374151', 'bg' => '#e5e7eb'];
                                            @endphp
                                            <tr style="border-top: 1px solid #f3f4f6;">
                                                <td style="padding: 16px 0; font-weight: 600;">#{{ $order->code }}</td>
                                                <td>{{ optional($order->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                                                <td>{{ $order->items_count }} sản phẩm</td>
                                                <td style="font-weight: 600; color: #0f5132;">{{ number_format((float) $order->final_price, 0, ',', '.') }}₫</td>
                                                <td>
                                                    <span style="display: inline-block; padding: 6px 14px; border-radius: 999px; font-size: 13px; font-weight: 600; color: {{ $badge['color'] }}; background: {{ $badge['bg'] }};">
                                                        {{ $badge['label'] }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="{{ route('client.orders.show', $order->code) }}" style="color: #198754; font-weight: 600; text-decoration: none;">
                                                        Xem chi tiết →
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.profile-nav-item').forEach(item => {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                const tabId = this.getAttribute('data-tab');
                
                // Remove active class from all nav items and tabs
                document.querySelectorAll('.profile-nav-item').forEach(nav => {
                    nav.classList.remove('active');
                    nav.style.background = '';
                });
                document.querySelectorAll('.profile-tab').forEach(tab => {
                    tab.classList.remove('active');
                    tab.style.display = 'none';
                });
                
                // Add active class to clicked nav item and corresponding tab
                this.classList.add('active');
                this.style.background = '#e8f5e9';
                document.getElementById(tabId).classList.add('active');
                document.getElementById(tabId).style.display = 'block';
            });
        });
    </script>

    <style>
        @media (max-width: 768px) {
            .xanhworld_profile_content {
                grid-template-columns: 1fr !important;
            }
            .xanhworld_profile_sidebar {
                order: 2;
            }
            .xanhworld_profile_main {
                order: 1;
            }
        }
    </style>
@endsection
