@extends('admins.layouts.master')

@section('title', 'Chỉnh sửa tài khoản')

@section('page-title', '👤 Chỉnh sửa tài khoản')

@push('head')
    <link rel="shortcut icon" href="{{ asset('admins/img/icons/account-icon.png') }}" type="image/x-icon">
@endpush

@push('styles')
    <style>
        .account-edit-page {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        /* Header Card */
        .account-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 24px;
            color: white;
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
        }

        .account-header-content {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .account-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background: white;
        }

        .account-info h2 {
            margin: 0 0 8px;
            font-size: 24px;
            font-weight: 600;
        }

        .account-info p {
            margin: 0;
            opacity: 0.9;
            font-size: 14px;
        }

        .account-badges {
            display: flex;
            gap: 8px;
            margin-top: 12px;
            flex-wrap: wrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 500;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
        }

        .badge.success {
            background: rgba(34, 197, 94, 0.3);
        }

        .badge.warning {
            background: rgba(251, 191, 36, 0.3);
        }

        .badge.danger {
            background: rgba(239, 68, 68, 0.3);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .stat-card h6 {
            margin: 0 0 8px;
            font-size: 12px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-card strong {
            display: block;
            font-size: 24px;
            color: #0f172a;
            font-weight: 700;
        }

        .stat-card small {
            display: block;
            margin-top: 4px;
            font-size: 12px;
            color: #94a3b8;
        }

        /* Tabs */
        .tabs-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .tabs {
            display: flex;
            gap: 0;
            border-bottom: 2px solid #e2e8f0;
            background: #f8fafc;
        }

        .tabs button {
            border: none;
            background: transparent;
            padding: 16px 24px;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 3px solid transparent;
            position: relative;
        }

        .tabs button:hover {
            background: #f1f5f9;
            color: #475569;
        }

        .tabs button.active {
            color: #667eea;
            background: white;
            border-bottom-color: #667eea;
        }

        /* Tab Content */
        .tab-content {
            padding: 24px;
        }

        .tab-panel {
            display: none;
        }

        .tab-panel.active {
            display: block;
        }

        /* Forms */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover {
            background: #475569;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #cbd5e1;
            color: #475569;
        }

        .btn-outline:hover {
            background: #f8fafc;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        /* Logs */
        .logs-container {
            max-height: 600px;
            overflow-y: auto;
        }

        .log-item {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            transition: background 0.2s;
        }

        .log-item:hover {
            background: #f8fafc;
        }

        .log-item:last-child {
            border-bottom: none;
        }

        .log-item h4 {
            margin: 0 0 8px;
            font-size: 14px;
            font-weight: 600;
            color: #0f172a;
        }

        .log-item small {
            display: block;
            color: #64748b;
            font-size: 12px;
        }

        .log-payload {
            margin-top: 8px;
            padding: 12px;
            background: #f8fafc;
            border-radius: 6px;
            font-size: 12px;
            overflow-x: auto;
        }

        /* Loading */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            color: #64748b;
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #e2e8f0;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Notifications */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 16px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 10000;
            animation: slideIn 0.3s ease;
        }

        .notification.success {
            background: #10b981;
            color: white;
        }

        .notification.error {
            background: #ef4444;
            color: white;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .account-header-content {
                flex-direction: column;
                text-align: center;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .tabs {
                overflow-x: auto;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $apiRoutes = [];
        
        // API Routes
        if (Route::has('admin.api.accounts.show')) {
            $apiRoutes['account'] = route('admin.api.accounts.show', $account);
        }
        if (Route::has('admin.api.accounts.update')) {
            $apiRoutes['updateAccount'] = route('admin.api.accounts.update', $account);
        }
        if (Route::has('admin.api.accounts.toggle')) {
            $apiRoutes['toggle'] = route('admin.api.accounts.toggle', $account);
        }
        if (Route::has('admin.api.accounts.change-role')) {
            $apiRoutes['changeRole'] = route('admin.api.accounts.change-role', $account);
        }
        if (Route::has('admin.api.accounts.reset-password')) {
            $apiRoutes['resetPassword'] = route('admin.api.accounts.reset-password', $account);
        }
        if (Route::has('admin.api.accounts.force-logout')) {
            $apiRoutes['forceLogout'] = route('admin.api.accounts.force-logout', $account);
        }
        if (Route::has('admin.api.accounts.verify-email')) {
            $apiRoutes['verifyEmail'] = route('admin.api.accounts.verify-email', $account);
        }
        if (Route::has('admin.api.accounts.profile.show')) {
            $apiRoutes['profile'] = route('admin.api.accounts.profile.show', $account);
        }
        if (Route::has('admin.api.accounts.profile.update')) {
            $apiRoutes['profileUpdate'] = route('admin.api.accounts.profile.update', $account);
        }
        if (Route::has('admin.api.accounts.profile.visibility')) {
            $apiRoutes['profileVisibility'] = route('admin.api.accounts.profile.visibility', $account);
        }
        if (Route::has('admin.api.accounts.profile.avatar')) {
            $apiRoutes['avatarUpload'] = route('admin.api.accounts.profile.avatar', $account);
        }
        if (Route::has('admin.api.accounts.logs.index')) {
            $apiRoutes['logs'] = route('admin.api.accounts.logs.index', $account);
        }
        if (Route::has('admin.api.accounts.logs.export')) {
            $apiRoutes['logsExport'] = route('admin.api.accounts.logs.export', $account);
        }

        // Fallback to web routes
        if (!isset($apiRoutes['account']) && Route::has('admin.accounts.show')) {
            $apiRoutes['account'] = route('admin.accounts.show', $account);
        }
        if (!isset($apiRoutes['updateAccount']) && Route::has('admin.accounts.update')) {
            $apiRoutes['updateAccount'] = route('admin.accounts.update', $account);
        }
        if (!isset($apiRoutes['toggle']) && Route::has('admin.accounts.toggle')) {
            $apiRoutes['toggle'] = route('admin.accounts.toggle', $account);
        }
        if (!isset($apiRoutes['resetPassword']) && Route::has('admin.accounts.reset-password')) {
            $apiRoutes['resetPassword'] = route('admin.accounts.reset-password', $account);
        }
        if (!isset($apiRoutes['verifyEmail']) && Route::has('admin.accounts.verify-email')) {
            $apiRoutes['verifyEmail'] = route('admin.accounts.verify-email', $account);
        }
    @endphp

    <div class="account-edit-page" id="accountEditRoot" 
         data-account-id="{{ $account->id }}"
         data-endpoints='@json($apiRoutes)'
         data-roles='@json($roles)'
         data-statuses='@json($accountStatuses)'>
        
        <!-- Header -->
        <div class="account-header">
            <div class="account-header-content">
                <img id="accountAvatar" class="account-avatar" src="https://ui-avatars.com/api/?name={{ urlencode($account->name) }}&background=667eea&color=fff" alt="Avatar">
                <div class="account-info" style="flex: 1;">
                    <h2 id="accountName">{{ $account->name }}</h2>
                    <p id="accountEmail">{{ $account->email }}</p>
                    <div class="account-badges" id="accountBadges">
                        <span class="badge" id="roleBadge">{{ ucfirst($account->role) }}</span>
                        <span class="badge" id="statusBadge">{{ $accountStatusLabels[$account->status] ?? $account->status }}</span>
                    </div>
                </div>
                <div>
                    <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline" style="background: rgba(255,255,255,0.2); color: white; border-color: rgba(255,255,255,0.3);">
                        ← Quay lại
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <h6>Lần đăng nhập cuối</h6>
                <strong id="lastLogin">—</strong>
                <small id="lastLoginTime"></small>
            </div>
            <div class="stat-card">
                <h6>Đổi mật khẩu cuối</h6>
                <strong id="lastPasswordChange">—</strong>
                <small id="lastPasswordChangeTime"></small>
            </div>
            <div class="stat-card">
                <h6>Số lần đăng nhập sai</h6>
                <strong id="loginAttempts">{{ $account->login_attempts ?? 0 }}</strong>
            </div>
            <div class="stat-card">
                <h6>Email đã xác minh</h6>
                <strong id="emailVerified">{{ $account->email_verified_at ? 'Có' : 'Chưa' }}</strong>
                <small id="emailVerifiedAt">{{ $account->email_verified_at ? $account->email_verified_at->format('d/m/Y H:i') : '' }}</small>
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-container">
            <div class="tabs" id="accountTabs">
                <button type="button" class="active" data-tab="info">📝 Thông tin cơ bản</button>
                <button type="button" data-tab="profile">👤 Hồ sơ</button>
                <button type="button" data-tab="security">🔒 Bảo mật</button>
                <button type="button" data-tab="logs">📋 Nhật ký hoạt động</button>
            </div>

            <div class="tab-content">
                <!-- Tab: Thông tin cơ bản -->
                <div class="tab-panel active" data-panel="info">
                    <form id="accountInfoForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Họ và tên *</label>
                                <input type="text" name="name" value="{{ $account->name }}" required>
                            </div>
                            <div class="form-group">
                                <label>Email *</label>
                                <input type="email" name="email" value="{{ $account->email }}" required>
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" name="phone" value="{{ $account->phone }}">
                            </div>
                            <div class="form-group">
                                <label>Vai trò *</label>
                                <select name="role" required>
                                    @foreach($roles as $role)
                                        <option value="{{ $role }}" {{ $account->role === $role ? 'selected' : '' }}>
                                            {{ ucfirst($role) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Trạng thái *</label>
                                <select name="status" required>
                                    @foreach($accountStatuses as $status)
                                        <option value="{{ $status }}" {{ $account->status === $status ? 'selected' : '' }}>
                                            {{ $accountStatusLabels[$status] ?? $status }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Lưu thay đổi</button>
                            <button type="button" class="btn btn-outline" onclick="location.reload()">🔄 Làm mới</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Hồ sơ -->
                <div class="tab-panel" data-panel="profile">
                    <form id="profileForm">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Họ tên đầy đủ</label>
                                <input type="text" name="full_name" placeholder="Nhập họ tên đầy đủ">
                            </div>
                            <div class="form-group">
                                <label>Biệt danh</label>
                                <input type="text" name="nickname" placeholder="Nhập biệt danh">
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại</label>
                                <input type="text" name="phone" placeholder="Nhập số điện thoại">
                            </div>
                            <div class="form-group">
                                <label>Giới tính</label>
                                <select name="gender">
                                    <option value="">Chọn giới tính</option>
                                    <option value="male">Nam</option>
                                    <option value="female">Nữ</option>
                                    <option value="other">Khác</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Ngày sinh</label>
                                <input type="date" name="birthday">
                            </div>
                            <div class="form-group">
                                <label>Địa chỉ</label>
                                <input type="text" name="location" placeholder="Nhập địa chỉ">
                            </div>
                            <div class="form-group" style="grid-column: 1 / -1;">
                                <label>Giới thiệu</label>
                                <textarea name="bio" placeholder="Nhập giới thiệu về bản thân"></textarea>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="is_public" value="1"> Hiển thị công khai
                                </label>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">💾 Lưu hồ sơ</button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Bảo mật -->
                <div class="tab-panel" data-panel="security">
                    <div class="form-grid">
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <h3 style="margin: 0 0 16px;">Đặt lại mật khẩu</h3>
                            <form id="passwordForm">
                                <div class="form-group">
                                    <label>Mật khẩu mới *</label>
                                    <input type="password" name="password" required minlength="8" placeholder="Tối thiểu 8 ký tự">
                                </div>
                                <div class="form-group">
                                    <label>Xác nhận mật khẩu *</label>
                                    <input type="password" name="password_confirmation" required minlength="8" placeholder="Nhập lại mật khẩu">
                                </div>
                                <div class="form-actions">
                                    <button type="submit" class="btn btn-primary">🔑 Đặt lại mật khẩu</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn btn-secondary" id="verifyEmailBtn">📧 Xác minh email</button>
                        <button type="button" class="btn btn-secondary" id="toggleStatusBtn">🔄 Đổi trạng thái</button>
                        <button type="button" class="btn btn-danger" id="forceLogoutBtn">🚪 Force logout</button>
                    </div>
                </div>

                <!-- Tab: Nhật ký hoạt động -->
                <div class="tab-panel" data-panel="logs">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                        <h3 style="margin: 0;">Nhật ký hoạt động</h3>
                        @if(isset($apiRoutes['logsExport']))
                            <a href="{{ $apiRoutes['logsExport'] }}" class="btn btn-outline" target="_blank">📥 Xuất CSV</a>
                        @endif
                    </div>
                    <div class="logs-container" id="logsContainer">
                        <div class="loading">
                            <div class="spinner"></div>
                            Đang tải...
                        </div>
                    </div>
                    <div id="logsPagination" style="margin-top: 16px; display: flex; gap: 8px; justify-content: center;"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const root = document.getElementById('accountEditRoot');
            if (!root) return;

            const endpoints = JSON.parse(root.dataset.endpoints || '{}');
            const roles = JSON.parse(root.dataset.roles || '[]');
            const statuses = JSON.parse(root.dataset.statuses || '[]');
            const accountId = root.dataset.accountId;

            let state = {
                account: null,
                profile: null,
                logs: [],
                logsMeta: null
            };

            // Utility functions
            function notify(message, type = 'success') {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.textContent = message;
                document.body.appendChild(notification);
                setTimeout(() => {
                    notification.style.animation = 'slideIn 0.3s ease reverse';
                    setTimeout(() => notification.remove(), 300);
                }, 3000);
            }

            async function fetchJson(url, options = {}) {
                const token = document.querySelector('meta[name="csrf-token"]')?.content;
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json',
                        ...options.headers
                    },
                    body: options.body ? (options.body instanceof FormData ? options.body : JSON.stringify(options.body)) : undefined
                });

                if (!response.ok) {
                    const error = await response.json().catch(() => ({ message: 'Có lỗi xảy ra' }));
                    throw error.message || 'Có lỗi xảy ra';
                }

                return response.json();
            }

            // Tab switching
            document.querySelectorAll('#accountTabs button').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tab = btn.dataset.tab;
                    document.querySelectorAll('#accountTabs button').forEach(b => b.classList.remove('active'));
                    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
                    btn.classList.add('active');
                    document.querySelector(`[data-panel="${tab}"]`)?.classList.add('active');
                });
            });

            // Load account data
            async function loadAccount() {
                if (!endpoints.account) {
                    notify('Route API không khả dụng', 'error');
                    return;
                }

                try {
                    const response = await fetchJson(endpoints.account);
                    state.account = response.data || response;
                    renderAccount();
                } catch (error) {
                    notify(error, 'error');
                }
            }

            function renderAccount() {
                const account = state.account;
                if (!account) return;

                // Update header
                if (account.name) document.getElementById('accountName').textContent = account.name;
                if (account.email) document.getElementById('accountEmail').textContent = account.email;
                
                // Update badges
                const roleBadge = document.getElementById('roleBadge');
                const statusBadge = document.getElementById('statusBadge');
                if (roleBadge) roleBadge.textContent = account.role ? account.role.charAt(0).toUpperCase() + account.role.slice(1) : '';
                if (statusBadge) {
                    statusBadge.textContent = account.status || account.account_status || '';
                    statusBadge.className = 'badge ' + (account.status === 'active' ? 'success' : account.status === 'locked' ? 'danger' : 'warning');
                }

                // Update stats
                if (account.login_history) {
                    const date = new Date(account.login_history);
                    document.getElementById('lastLogin').textContent = date.toLocaleDateString('vi-VN');
                    document.getElementById('lastLoginTime').textContent = date.toLocaleTimeString('vi-VN');
                }
                if (account.last_password_changed_at) {
                    const date = new Date(account.last_password_changed_at);
                    document.getElementById('lastPasswordChange').textContent = date.toLocaleDateString('vi-VN');
                    document.getElementById('lastPasswordChangeTime').textContent = date.toLocaleTimeString('vi-VN');
                }
                if (account.login_attempts !== undefined) {
                    document.getElementById('loginAttempts').textContent = account.login_attempts;
                }

                // Update form
                const form = document.getElementById('accountInfoForm');
                if (form) {
                    if (account.name) form.name.value = account.name;
                    if (account.email) form.email.value = account.email;
                    if (account.phone) form.phone.value = account.phone;
                    if (account.role) form.role.value = account.role;
                    if (account.status || account.account_status) {
                        form.status.value = account.status || account.account_status;
                    }
                }
            }

            // Load profile
            async function loadProfile() {
                if (!endpoints.profile) return;

                try {
                    const response = await fetchJson(endpoints.profile);
                    state.profile = response.data || response;
                    renderProfile();
                } catch (error) {
                    // Silent fail for profile
                }
            }

            function renderProfile() {
                const profile = state.profile;
                if (!profile) return;

                const form = document.getElementById('profileForm');
                if (!form) return;

                if (profile.full_name) form.full_name.value = profile.full_name;
                if (profile.nickname) form.nickname.value = profile.nickname;
                if (profile.phone) form.phone.value = profile.phone;
                if (profile.gender) form.gender.value = profile.gender;
                if (profile.birthday) form.birthday.value = profile.birthday;
                if (profile.location) form.location.value = profile.location;
                if (profile.bio) form.bio.value = profile.bio;
                if (profile.is_public !== undefined) form.is_public.checked = profile.is_public;
            }

            // Load logs
            async function loadLogs(url = null) {
                const endpoint = url || endpoints.logs;
                if (!endpoint) {
                    document.getElementById('logsContainer').innerHTML = '<div class="empty-state">Route API logs không khả dụng</div>';
                    return;
                }

                try {
                    const type = document.getElementById('logTypeFilter')?.value;
                    const query = type ? `${endpoint}?type=${encodeURIComponent(type)}` : endpoint;
                    const response = await fetchJson(query);
                    state.logs = response.data || [];
                    state.logsMeta = response.meta || null;
                    renderLogs();
                } catch (error) {
                    document.getElementById('logsContainer').innerHTML = `<div class="empty-state">Lỗi: ${error}</div>`;
                }
            }

            function renderLogs() {
                const container = document.getElementById('logsContainer');
                if (!container) return;

                if (!state.logs || state.logs.length === 0) {
                    container.innerHTML = '<div class="empty-state">Chưa có nhật ký nào</div>';
                    return;
                }

                container.innerHTML = state.logs.map(log => `
                    <div class="log-item">
                        <h4>${log.type || 'N/A'}</h4>
                        <small>${new Date(log.created_at).toLocaleString('vi-VN')} • ${log.admin_name || 'System'}</small>
                        ${log.payload ? `<pre class="log-payload">${JSON.stringify(log.payload, null, 2)}</pre>` : ''}
                    </div>
                `).join('');

                // Pagination
                const pagination = document.getElementById('logsPagination');
                if (pagination && state.logsMeta) {
                    pagination.innerHTML = '';
                    if (state.logsMeta.prev_page_url) {
                        const btn = document.createElement('button');
                        btn.className = 'btn btn-outline';
                        btn.textContent = '← Trước';
                        btn.onclick = () => loadLogs(state.logsMeta.prev_page_url);
                        pagination.appendChild(btn);
                    }
                    if (state.logsMeta.next_page_url) {
                        const btn = document.createElement('button');
                        btn.className = 'btn btn-outline';
                        btn.textContent = 'Sau →';
                        btn.onclick = () => loadLogs(state.logsMeta.next_page_url);
                        pagination.appendChild(btn);
                    }
                }
            }

            // Form handlers
            function handleForm(formId, endpoint, method = 'POST', onSuccess = null) {
                const form = document.getElementById(formId);
                if (!form || !endpoint) return;

                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const submitBtn = form.querySelector('button[type="submit"]');
                    const originalText = submitBtn?.textContent;
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Đang xử lý...';
                    }

                    try {
                        const formData = new FormData(form);
                        const data = {};
                        formData.forEach((value, key) => {
                            // Skip empty values
                            if (value === '' || value === null) {
                                return;
                            }
                            
                            if (key === 'is_public') {
                                data[key] = formData.has(key) && formData.get(key) !== '0';
                            } else if (key === 'status' || key === 'role') {
                                // Ensure status and role are valid strings
                                data[key] = String(value).trim();
                            } else {
                                data[key] = value;
                            }
                        });

                        // Remove empty values
                        Object.keys(data).forEach(key => {
                            if (data[key] === '' || data[key] === null || data[key] === undefined) {
                                delete data[key];
                            }
                        });

                        await fetchJson(endpoint, {
                            method,
                            body: data
                        });

                        notify('Cập nhật thành công!');
                        if (onSuccess) onSuccess();
                    } catch (error) {
                        notify(error, 'error');
                    } finally {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        }
                    }
                });
            }

            // Initialize forms
            if (endpoints.updateAccount) {
                handleForm('accountInfoForm', endpoints.updateAccount, 'PUT', loadAccount);
            }
            if (endpoints.profileUpdate) {
                handleForm('profileForm', endpoints.profileUpdate, 'PUT', loadProfile);
            }
            if (endpoints.resetPassword) {
                handleForm('passwordForm', endpoints.resetPassword, 'PATCH', () => {
                    document.getElementById('passwordForm').reset();
                    notify('Đã đặt lại mật khẩu');
                });
            }

            // Action buttons
            document.getElementById('verifyEmailBtn')?.addEventListener('click', async () => {
                if (!endpoints.verifyEmail) {
                    notify('Route API không khả dụng', 'error');
                    return;
                }
                try {
                    await fetchJson(endpoints.verifyEmail, { method: 'POST' });
                    notify('Đã gửi email xác minh');
                    loadAccount();
                } catch (error) {
                    notify(error, 'error');
                }
            });

            document.getElementById('toggleStatusBtn')?.addEventListener('click', async () => {
                if (!endpoints.toggle) {
                    notify('Route API không khả dụng', 'error');
                    return;
                }
                if (!confirm('Bạn chắc chắn muốn đổi trạng thái tài khoản này?')) return;
                try {
                    await fetchJson(endpoints.toggle, { method: 'PATCH' });
                    notify('Đã cập nhật trạng thái');
                    loadAccount();
                } catch (error) {
                    notify(error, 'error');
                }
            });

            document.getElementById('forceLogoutBtn')?.addEventListener('click', async () => {
                if (!endpoints.forceLogout) {
                    notify('Route API không khả dụng', 'error');
                    return;
                }
                if (!confirm('Xác nhận force logout tất cả phiên đăng nhập?')) return;
                try {
                    await fetchJson(endpoints.forceLogout, { method: 'POST' });
                    notify('Đã force logout');
                } catch (error) {
                    notify(error, 'error');
                }
            });

            // Initial load
            loadAccount();
            loadProfile();
            loadLogs();
        })();
    </script>
@endpush
