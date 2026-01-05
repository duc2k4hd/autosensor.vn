@extends('admins.layouts.master')

@section('title', 'Địa chỉ giao hàng')
@section('page-title', '📍 Danh sách địa chỉ giao hàng')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.css">
@endpush

@section('content')
    <div class="card mb-3">
        <div class="card-header">
            <h5 class="mb-0">Bộ lọc</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.addresses.index') }}" class="row g-3" id="filterForm">
                <div class="col-md-3">
                    <label class="form-label">Tài khoản</label>
                    <select name="account_id" id="filter_account_id" class="form-select">
                        <option value="">Tất cả</option>
                        @foreach($accounts->take(20) as $account)
                            <option value="{{ $account->id }}" @selected(($filters['account_id'] ?? null) == $account->id)>
                                {{ $account->name }} ({{ $account->email }})
                            </option>
                        @endforeach
                        @if(isset($filters['account_id']) && $filters['account_id'])
                            @php
                                $selectedAccount = $accounts->firstWhere('id', $filters['account_id']);
                            @endphp
                            @if($selectedAccount && !$accounts->take(20)->contains('id', $selectedAccount->id))
                                <option value="{{ $selectedAccount->id }}" selected>
                                    {{ $selectedAccount->name }} ({{ $selectedAccount->email }})
                                </option>
                            @endif
                        @endif
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Họ tên nhận hàng</label>
                    <input type="text" name="full_name" value="{{ $filters['full_name'] ?? '' }}" class="form-control"
                           placeholder="Nhập họ tên">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Số điện thoại</label>
                    <input type="text" name="phone_number" value="{{ $filters['phone_number'] ?? '' }}" class="form-control"
                           placeholder="SĐT">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tỉnh / Thành</label>
                    <select name="province" id="filter_province" class="form-select">
                        <option value="">Tất cả</option>
                        @if(isset($filters['province']) && $filters['province'])
                            <option value="{{ $filters['province'] }}" selected>{{ $filters['province'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Quận / Huyện</label>
                    <select name="district" id="filter_district" class="form-select">
                        <option value="">Tất cả</option>
                        @if(isset($filters['district']) && $filters['district'])
                            <option value="{{ $filters['district'] }}" selected>{{ $filters['district'] }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Loại địa chỉ</label>
                    <select name="address_type" id="filter_address_type" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="home" @selected(($filters['address_type'] ?? null) === 'home')>Nhà riêng</option>
                        <option value="work" @selected(($filters['address_type'] ?? null) === 'work')>Cơ quan</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Địa chỉ mặc định</label>
                    <select name="is_default" id="filter_is_default" class="form-select">
                        <option value="">Tất cả</option>
                        <option value="1" @selected(($filters['is_default'] ?? null) === '1')>Chỉ địa chỉ mặc định</option>
                        <option value="0" @selected(($filters['is_default'] ?? null) === '0')>Không phải mặc định</option>
                    </select>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2 mt-2">
                    <button type="submit" class="btn btn-primary">Lọc</button>
                    <a href="{{ route('admin.addresses.index') }}" class="btn btn-outline-secondary">Đặt lại</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Danh sách địa chỉ ({{ $addresses->total() }})</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tài khoản</th>
                        <th>Người nhận</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th>Loại</th>
                        <th>Mặc định</th>
                        <th>Cập nhật</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($addresses as $address)
                        <tr>
                            <td>#{{ $address->id }}</td>
                            <td>
                                @if($address->account)
                                    <div>{{ $address->account->name }}</div>
                                    <div class="text-muted" style="font-size: 12px;">{{ $address->account->email }}</div>
                                @else
                                    <span class="text-muted">Không rõ</span>
                                @endif
                            </td>
                            <td>{{ $address->full_name }}</td>
                            <td>{{ $address->phone_number }}</td>
                            <td style="max-width: 260px;">
                                <div>{{ $address->detail_address }}</div>
                                <div class="text-muted" style="font-size: 12px;">
                                    {{ $address->ward ? $address->ward . ', ' : '' }}
                                    {{ $address->district }}, {{ $address->province }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ $address->address_type === 'work' ? 'Cơ quan' : 'Nhà riêng' }}
                                </span>
                            </td>
                            <td>
                                @if($address->is_default)
                                    <span class="badge bg-success">Mặc định</span>
                                @else
                                    <span class="badge bg-light text-muted">-</span>
                                @endif
                            </td>
                            <td>{{ $address->updated_at?->format('d/m/Y H:i') }}</td>
                            <td class="text-end">
                                <a href="{{ route('admin.addresses.show', $address) }}" class="btn btn-sm btn-outline-primary">
                                    Xem
                                </a>
                                <a href="{{ route('admin.addresses.edit', $address) }}" class="btn btn-sm btn-outline-secondary">
                                    Sửa
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Chưa có địa chỉ nào.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($addresses->hasPages())
            <div class="card-footer">
                {{ $addresses->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/slim-select@2.8.2/dist/slimselect.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let accountSlimSelect = null;
            let provinceSlimSelect = null;
            let districtSlimSelect = null;
            let addressTypeSlimSelect = null;
            let isDefaultSlimSelect = null;

            // Account select với remote search
            let accountSearchTimeout = null;
            let accountInitialOptions = [];
            let accountRemoteSearchActive = false;
            
            // Load initial options từ select element
            const accountSelect = document.getElementById('filter_account_id');
            if (accountSelect) {
                Array.from(accountSelect.options).forEach(option => {
                    if (option.value) {
                        accountInitialOptions.push({
                            value: option.value,
                            text: option.text,
                        });
                    }
                });
            }

            // Khởi tạo SlimSelect với initial data
            accountSlimSelect = new SlimSelect({
                select: '#filter_account_id',
                placeholder: 'Tìm kiếm tài khoản...',
                searchText: 'Không tìm thấy',
                searchPlaceholder: 'Nhập tên hoặc email để tìm kiếm...',
                data: accountInitialOptions,
                searchFilter: function(option, search) {
                    // Nếu đang remote search, không filter local
                    if (accountRemoteSearchActive) {
                        return true;
                    }
                    // Filter local trong initial options
                    if (!search) return true;
                    const text = option.text.toLowerCase();
                    return text.includes(search.toLowerCase());
                },
                ajax: function(search, callback) {
                    if (accountSearchTimeout) {
                        clearTimeout(accountSearchTimeout);
                    }

                    // Nếu không có search hoặc search rỗng, không gọi API
                    if (!search || search.length < 1) {
                        accountRemoteSearchActive = false;
                        // Không gọi callback, giữ nguyên data hiện tại
                        return;
                    }

                    // Có search, chuyển sang chế độ remote search
                    accountRemoteSearchActive = true;
                    accountSearchTimeout = setTimeout(function() {
                        fetch(`{{ route('admin.addresses.search.accounts') }}?keyword=${encodeURIComponent(search)}&limit=100`)
                            .then(res => res.json())
                            .then(data => {
                                const options = data.map(account => ({
                                    value: account.value.toString(),
                                    text: account.text,
                                }));
                                callback(options);
                            })
                            .catch(() => {
                                accountRemoteSearchActive = false;
                                callback([]);
                            });
                    }, 400);
                },
            });

            // Province select với remote search
            let provinceSearchTimeout = null;
            provinceSlimSelect = new SlimSelect({
                select: '#filter_province',
                placeholder: 'Tìm kiếm tỉnh/thành...',
                searchText: 'Không tìm thấy',
                searchPlaceholder: 'Nhập tên tỉnh/thành...',
                searchFilter: function(option, search) {
                    if (!search) return true;
                    const text = option.text.toLowerCase();
                    return text.includes(search.toLowerCase());
                },
                ajax: function(search, callback) {
                    if (provinceSearchTimeout) {
                        clearTimeout(provinceSearchTimeout);
                    }

                    if (!search || search.length < 1) {
                        callback([]);
                        return;
                    }

                    provinceSearchTimeout = setTimeout(function() {
                        fetch(`{{ route('admin.addresses.search.provinces') }}?keyword=${encodeURIComponent(search)}&limit=100`)
                            .then(res => res.json())
                            .then(data => {
                                callback(data);
                            })
                            .catch(() => callback([]));
                    }, 400);
                },
            });

            // District select với remote search (có thể filter theo province)
            let districtSearchTimeout = null;
            districtSlimSelect = new SlimSelect({
                select: '#filter_district',
                placeholder: 'Tìm kiếm quận/huyện...',
                searchText: 'Không tìm thấy',
                searchPlaceholder: 'Nhập tên quận/huyện...',
                searchFilter: function(option, search) {
                    if (!search) return true;
                    const text = option.text.toLowerCase();
                    return text.includes(search.toLowerCase());
                },
                ajax: function(search, callback) {
                    if (districtSearchTimeout) {
                        clearTimeout(districtSearchTimeout);
                    }

                    if (!search || search.length < 1) {
                        callback([]);
                        return;
                    }

                    districtSearchTimeout = setTimeout(function() {
                        const province = document.getElementById('filter_province').value;
                        let url = `{{ route('admin.addresses.search.districts') }}?keyword=${encodeURIComponent(search)}&limit=100`;
                        if (province) {
                            url += `&province=${encodeURIComponent(province)}`;
                        }
                        fetch(url)
                            .then(res => res.json())
                            .then(data => {
                                callback(data);
                            })
                            .catch(() => callback([]));
                    }, 400);
                },
            });

            // Address type select (đơn giản, không remote)
            addressTypeSlimSelect = new SlimSelect({
                select: '#filter_address_type',
                placeholder: 'Chọn loại địa chỉ...',
            });

            // Is default select (đơn giản, không remote)
            isDefaultSlimSelect = new SlimSelect({
                select: '#filter_is_default',
                placeholder: 'Chọn trạng thái...',
            });

            // Khi province thay đổi, reset district
            document.getElementById('filter_province').addEventListener('change', function() {
                document.getElementById('filter_district').value = '';
                if (districtSlimSelect) {
                    districtSlimSelect.set([]);
                }
            });
        });
    </script>
@endpush

