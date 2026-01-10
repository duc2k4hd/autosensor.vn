<div class="autosensor_sidebar_support_info" style="margin-top: 30px;">
    <h3 class="autosensor_single_desc_tabs_describe_product_new_title">📞 Thông tin hỗ trợ</h3>
    <div style="display: flex; align-items: center; justify-content: center; margin: 1rem 0;">
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
        <span style="padding: 0 12px; color: #f74a4a; font-weight: bold;">Thông tin hỗ trợ</span>
        <hr style="flex: 1; height: 2px; background-color: #e6525e; border: none; margin: 0;">
    </div>
    
    <div class="autosensor_sidebar_support_content" style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <!-- Hotline -->
        @if($settings->contact_phone ?? null)
            <div style="margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span style="font-size: 20px;">📞</span>
                    <strong style="font-size: 14px;">Hotline</strong>
                </div>
                <a href="tel:{{ $settings->contact_phone }}" style="display: block; padding: 10px; background: white; border-radius: 6px; text-decoration: none; color: #2563EB; font-weight: 600; font-size: 16px; text-align: center;">
                    {{ preg_replace('/(\d{4})(\d{3})(\d{3})/', '$1.$2.$3', $settings->contact_phone) }}
                </a>
            </div>
        @endif
        
        <!-- Zalo -->
        @if($settings->contact_zalo ?? null)
            <div style="margin-bottom: 15px;">
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                    <span style="font-size: 20px;">💬</span>
                    <strong style="font-size: 14px;">Zalo</strong>
                </div>
                <a href="https://zalo.me/{{ $settings->contact_zalo }}" target="_blank" rel="nofollow" style="display: block; padding: 10px; background: #0068FF; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 16px; text-align: center;">
                    Chat Zalo ngay
                </a>
            </div>
        @endif
        
        <!-- CSKH Team -->
        @if(($supportStaff ?? collect())->isNotEmpty())
            <div style="margin-top: 20px;">
                <strong style="font-size: 14px; display: block; margin-bottom: 10px;">👥 Đội ngũ CSKH</strong>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    @foreach(($supportStaff ?? collect())->take(3) as $support)
                        <div style="display: flex; align-items: center; gap: 10px; padding: 10px; background: white; border-radius: 6px;">
                            @if($support->avatar)
                                <img src="{{ asset('clients/assets/img/avatars/' . $support->avatar) }}" 
                                     alt="{{ $support->name }}"
                                     style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;"
                                     onerror="this.onerror=null;this.src='{{ asset('clients/assets/img/clothes/no-image.webp') }}'">
                            @else
                                <div style="width: 40px; height: 40px; border-radius: 50%; background: #2563EB; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                    {{ strtoupper(substr($support->name, 0, 1)) }}
                                </div>
                            @endif
                            <div style="flex: 1;">
                                <div style="font-weight: 600; font-size: 13px;">{{ $support->name }}</div>
                                <div style="font-size: 11px; color: #666;">{{ $support->role ?? 'CSKH' }}</div>
                            </div>
                            @if($support->phone)
                                <a href="tel:{{ $support->phone }}" style="padding: 6px 10px; background: #2563EB; color: white; border-radius: 4px; text-decoration: none; font-size: 11px;">📞</a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
        
        <!-- Thời gian làm việc -->
        <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #dee2e6;">
            <div style="font-size: 12px; color: #666; text-align: center;">
                <strong>🕒 Thời gian hỗ trợ:</strong><br>
                8:30 - 22:30 mỗi ngày
            </div>
        </div>
    </div>
</div>
