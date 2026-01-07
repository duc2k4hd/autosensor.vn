@php
    $aiChatEndpoint = route('client.ai.chat');
@endphp
<!-- Chat -->
<section>
    <div class="autosensor_chat">
        <div class="autosensor_back_to_top">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><path d="M4 8l5 -5l5 5l-1 1l-4 -4l-4 4ZM4 12l5 -5l5 5l-1 1l-4 -4l-4 4Z" /></svg>
        </div>
        <!-- Zalo -->
        <a href="https://zalo.me/{{ $settings->contact_zalo ?? '' }}" aria-label="Zalo" alt="Zalo" title="Zalo" target="_blank" class="autosensor_chat_zalo" rel="noreferrer">
            <svg xmlns="http://www.w3.org/2000/svg" width="50" height="50" viewBox="0 0 50 50" fill="none"><script xmlns="" id="eppiocemhmnlbhjplcgkofciiegomcon"/><script xmlns=""/><script xmlns=""/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M22.782 0.166016H27.199C33.2653 0.166016 36.8103 1.05701 39.9572 2.74421C43.1041 4.4314 45.5875 6.89585 47.2557 10.0428C48.9429 13.1897 49.8339 16.7347 49.8339 22.801V27.1991C49.8339 33.2654 48.9429 36.8104 47.2557 39.9573C45.5685 43.1042 43.1041 45.5877 39.9572 47.2559C36.8103 48.9431 33.2653 49.8341 27.199 49.8341H22.8009C16.7346 49.8341 13.1896 48.9431 10.0427 47.2559C6.89583 45.5687 4.41243 43.1042 2.7442 39.9573C1.057 36.8104 0.166016 33.2654 0.166016 27.1991V22.801C0.166016 16.7347 1.057 13.1897 2.7442 10.0428C4.43139 6.89585 6.89583 4.41245 10.0427 2.74421C13.1707 1.05701 16.7346 0.166016 22.782 0.166016Z" fill="#0068FF"/>
                <path opacity="0.12" fill-rule="evenodd" clip-rule="evenodd" d="M49.8336 26.4736V27.1994C49.8336 33.2657 48.9427 36.8107 47.2555 39.9576C45.5683 43.1045 43.1038 45.5879 39.9569 47.2562C36.81 48.9434 33.265 49.8344 27.1987 49.8344H22.8007C17.8369 49.8344 14.5612 49.2378 11.8104 48.0966L7.27539 43.4267L49.8336 26.4736Z" fill="#001A33"/>
                <path fill-rule="evenodd" clip-rule="evenodd" d="M7.779 43.5892C10.1019 43.846 13.0061 43.1836 15.0682 42.1825C24.0225 47.1318 38.0197 46.8954 46.4923 41.4732C46.8209 40.9803 47.1279 40.4677 47.4128 39.9363C49.1062 36.7779 50.0004 33.22 50.0004 27.1316V22.7175C50.0004 16.629 49.1062 13.0711 47.4128 9.91273C45.7385 6.75436 43.2461 4.28093 40.0877 2.58758C36.9293 0.894239 33.3714 0 27.283 0H22.8499C17.6644 0 14.2982 0.652754 11.4699 1.89893C11.3153 2.03737 11.1636 2.17818 11.0151 2.32135C2.71734 10.3203 2.08658 27.6593 9.12279 37.0782C9.13064 37.0921 9.13933 37.1061 9.14889 37.1203C10.2334 38.7185 9.18694 41.5154 7.55068 43.1516C7.28431 43.399 7.37944 43.5512 7.779 43.5892Z" fill="white"/>
                <path d="M20.5632 17H10.8382V19.0853H17.5869L10.9329 27.3317C10.7244 27.635 10.5728 27.9194 10.5728 28.5639V29.0947H19.748C20.203 29.0947 20.5822 28.7156 20.5822 28.2606V27.1421H13.4922L19.748 19.2938C19.8428 19.1801 20.0134 18.9716 20.0893 18.8768L20.1272 18.8199C20.4874 18.2891 20.5632 17.8341 20.5632 17.2844V17Z" fill="#0068FF"/>
                <path d="M32.9416 29.0947H34.3255V17H32.2402V28.3933C32.2402 28.7725 32.5435 29.0947 32.9416 29.0947Z" fill="#0068FF"/>
                <path d="M25.814 19.6924C23.1979 19.6924 21.0747 21.8156 21.0747 24.4317C21.0747 27.0478 23.1979 29.171 25.814 29.171C28.4301 29.171 30.5533 27.0478 30.5533 24.4317C30.5723 21.8156 28.4491 19.6924 25.814 19.6924ZM25.814 27.2184C24.2785 27.2184 23.0273 25.9672 23.0273 24.4317C23.0273 22.8962 24.2785 21.645 25.814 21.645C27.3495 21.645 28.6007 22.8962 28.6007 24.4317C28.6007 25.9672 27.3685 27.2184 25.814 27.2184Z" fill="#0068FF"/>
                <path d="M40.4867 19.6162C37.8516 19.6162 35.7095 21.7584 35.7095 24.3934C35.7095 27.0285 37.8516 29.1707 40.4867 29.1707C43.1217 29.1707 45.2639 27.0285 45.2639 24.3934C45.2639 21.7584 43.1217 19.6162 40.4867 19.6162ZM40.4867 27.2181C38.9322 27.2181 37.681 25.9669 37.681 24.4124C37.681 22.8579 38.9322 21.6067 40.4867 21.6067C42.0412 21.6067 43.2924 22.8579 43.2924 24.4124C43.2924 25.9669 42.0412 27.2181 40.4867 27.2181Z" fill="#0068FF"/>
                <path d="M29.4562 29.0944H30.5747V19.957H28.6221V28.2793C28.6221 28.7153 29.0012 29.0944 29.4562 29.0944Z" fill="#0068FF"/>
                </svg>
            <span class="sr-only">Call Zalo</span>
        </a>
        <!-- Gọi điện -->
        <a href="tel:{{ $settings->contact_phone ?? '' }}" class="autosensor_chat_phone" aria-label="Phone" alt="Phone" title="Phone">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path
                    d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
            </svg>
            <span class="sr-only">Call Phone</span>
        </a>
        <!-- Trợ lý AI -->
        <a href="#" title="Trợ lý AI dành riêng cho {{ $settings->site_name }}" class="autosensor_chat_facebook" data-ai-chat-trigger aria-label="Trợ lý AI dành riêng cho {{ $settings->site_name }}" alt="Trợ lý AI dành riêng cho {{ $settings->site_name }}">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512" aria-hidden="true">
                <path
                    d="M320 0c17.7 0 32 14.3 32 32l0 64 120 0c39.8 0 72 32.2 72 72l0 272c0 39.8-32.2 72-72 72l-304 0c-39.8 0-72-32.2-72-72l0-272c0-39.8 32.2-72 72-72l120 0 0-64c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224l16 0 0 192-16 0c-26.5 0-48-21.5-48-48l0-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48l0 96c0 26.5-21.5 48-48 48l-16 0 0-192 16 0z" />
            </svg>
            <span class="sr-only">Trợ lý AI</span>
        </a>
        
        <script>
            document.querySelector('.autosensor_chat_facebook').addEventListener('click', (e) => e.preventDefault());
        </script>
    </div>

    <div class="autosensor_chat_popup" id="autosensorChatPopup" data-endpoint="{{ $aiChatEndpoint }}">
        <div class="autosensor_chat_panel" role="dialog" aria-modal="true" aria-label="Trợ lý AutoSensor Việt Nam">
            <div class="autosensor_chat_header">
                <div>
                    <p class="autosensor_chat_title">Trợ lý AutoSensor Việt Nam</p>
                    <p class="autosensor_chat_desc">Hỏi mọi thứ về sản phẩm, bài viết, giải pháp kỹ thuật</p>
                </div>
                <button type="button" class="autosensor_chat_close" aria-label="Đóng trợ lý">&times;</button>
            </div>
            
            <!-- Tabs -->
            <div class="autosensor_chat_tabs">
                <button type="button" class="autosensor_chat_tab active" data-tab="chat">💬 Chat AI</button>
                <button type="button" class="autosensor_chat_tab" data-tab="contact">📞 Liên hệ</button>
            </div>

            <!-- Tab Content: Chat -->
            <div class="autosensor_chat_tab_content active" data-tab-content="chat">
                <div class="autosensor_chat_messages" aria-live="polite">
                    <div class="autosensor_chat_message is-assistant">
                        Xin chào! Bạn đang cần tư vấn thiết bị tự động hóa, giải pháp kỹ thuật hay muốn tìm hiểu bài viết nào? Mình có thể dùng dữ liệu sản phẩm & bài viết mới nhất để trả lời ngay.
                    </div>
                </div>
                <form class="autosensor_chat_form" autocomplete="off">
                    <label class="sr-only" for="autosensorChatInput">Nhập câu hỏi</label>
                    <textarea id="autosensorChatInput" name="question" rows="2" placeholder="Nhập câu hỏi (ví dụ: Cảm biến nào phù hợp cho ứng dụng công nghiệp?)" minlength="5" maxlength="200"></textarea>
                    <div class="autosensor_chat_form_actions">
                        <span class="autosensor_chat_hint">
                            <span class="autosensor_chat_char_count"><span id="autosensorChatCharCount">0</span>/200</span>
                            <span class="autosensor_chat_hint_text">Nhập tối thiểu 5 ký tự. Bạn có thể hỏi bằng tiếng Việt.</span>
                        </span>
                        <button type="submit" class="autosensor_chat_send" disabled>Gửi</button>
                    </div>
                </form>
            </div>

            <!-- Tab Content: Contact -->
            <div class="autosensor_chat_tab_content" data-tab-content="contact">
                <div class="autosensor_chat_contact">
                    <div class="autosensor_chat_contact_section">
                        <!-- CSKH Team -->
                        <h3 class="autosensor_chat_contact_title">ĐỘI NGŨ CSKH</h3>
                        <p class="autosensor_single_info_policy_subtitle">Liên hệ đội ngũ CSKH để được hỗ trợ tốt nhất</p>
                        <div class="autosensor_single_info_policy_cskh">
                            @foreach(($supportStaff ?? collect()) as $support)
                                <div class="autosensor_single_info_policy_cskh_item" style="background: {{ $support->color ?? '#f9f9f9' }};">
                                    <div class="cskh-info">
                                        @if($support->avatar)
                                            <div class="cskh-avatar">
                                                <img src="{{ asset('clients/assets/img/avatars/' . $support->avatar) }}" alt="{{ $support->name }}">
                                            </div>
                                        @endif
                                        <div class="cskh-info-content">
                                            <div class="cskh-name">{{ $support->name }}</div>
                                            <div class="cskh-role">{{ $support->role }}</div>
                                        </div>
                                    </div>
                                    <div class="cskh-contact">
                                        @if($support->phone)
                                            <a class="cskh-phone" href="tel:{{ $support->phone }}">📞 {{ $support->phone }}</a>
                                        @endif
                                        @if($support->zalo)
                                            <a class="cskh-zalo" href="https://zalo.me/{{ $support->zalo }}" target="_blank">Zalo</a>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                            @if(($supportStaff ?? collect())->isEmpty())
                                <p>Đang cập nhật đội ngũ CSKH.</p>
                            @endif
                        </div>
                    </div>

                    <div class="autosensor_chat_contact_section">
                        <h3 class="autosensor_chat_contact_title">📞 Thông tin liên hệ</h3>
                        <div class="autosensor_chat_contact_item">
                            <strong>Điện thoại:</strong>
                            <a href="tel:{{ $settings->contact_phone ?? '' }}" class="autosensor_chat_contact_link">
                                {{ preg_replace('/^(\d{4})(\d{3})(\d{3})$/', '$1.$2.$3', preg_replace('/\D/', '', $settings->contact_phone ?? '')) }}
                            </a>
                        </div>
                        <div class="autosensor_chat_contact_item">
                            <strong>Email:</strong>
                            <a href="mailto:{{ $settings->contact_email ?? '' }}" class="autosensor_chat_contact_link">
                                {{ $settings->contact_email ?? '' }}
                            </a>
                        </div>
                        <div class="autosensor_chat_contact_item">
                            <strong>Địa chỉ:</strong>
                            <span>{{ $settings->contact_address ?? '' }}</span>
                        </div>
                        <div class="autosensor_chat_contact_item">
                            <strong>Giờ làm việc:</strong>
                            <span>8:00 - 17:00 từ thứ 2 đến thứ 7</span>
                        </div>
                    </div>

                    <div class="autosensor_chat_contact_section">
                        <h3 class="autosensor_chat_contact_title">💬 Liên hệ nhanh</h3>
                        <div class="autosensor_chat_contact_quick">
                            <a href="tel:{{ $settings->contact_phone ?? '' }}" class="autosensor_chat_contact_quick_btn" aria-label="Gọi điện">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20">
                                    <path d="M256.6 8C116.5 8 8 110.3 8 248.6c0 72.3 29.7 134.8 78.1 177.9 8.4 7.5 6.6 11.9 8.1 58.2A19.9 19.9 0 0 0 122 502.3c52.9-23.3 53.6-25.1 62.6-22.7C337.9 521.8 504 423.7 504 248.6 504 110.3 396.6 8 256.6 8zm149.2 185.1l-73 115.6a37.4 37.4 0 0 1 -53.9 9.9l-58.1-43.5a15 15 0 0 0 -18 0l-78.4 59.4c-10.5 7.9-24.2-4.6-17.1-15.7l73-115.6a37.4 37.4 0 0 1 53.9-9.9l58.1 43.5a15 15 0 0 0 18 0l78.4-59.4c10.4-8 24.1 4.5 17.1 15.6z" />
                                </svg>
                                Gọi điện
                            </a>
                            <a href="https://zalo.me/{{ $settings->contact_zalo ?? '' }}" target="_blank" class="autosensor_chat_contact_quick_btn" aria-label="Zalo" rel="noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20">
                                    <path d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
                                </svg>
                                Zalo
                            </a>
                        </div>
                    </div>

                    <div class="autosensor_chat_contact_section">
                        <h3 class="autosensor_chat_contact_title">🌐 Mạng xã hội</h3>
                        <div class="autosensor_chat_contact_socials">
                            @if ($settings->facebook_link ?? null)
                            <a href="{{ $settings->facebook_link }}" target="_blank" class="autosensor_chat_contact_social" aria-label="Facebook" rel="noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24" fill="currentColor">
                                    <path d="M504 256C504 119 393 8 256 8S8 119 8 256c0 123.78 90.69 226.38 209 244.97V334.5h-63V256h63v-54.64c0-62.15 37-96.48 93.67-96.48 27.14 0 55.52 4.84 55.52 4.84v61h-31.28c-30.8 0-40.41 19.12-40.41 38.73V256h68.78l-11 78.5h-57.78V500.97C413.31 482.38 504 379.78 504 256z"/>
                                </svg>
                                <span>Facebook</span>
                            </a>
                            @endif
                            @if ($settings->instagram_link ?? null)
                            <a href="{{ $settings->instagram_link }}" target="_blank" class="autosensor_chat_contact_social" aria-label="Instagram" rel="noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" width="24" height="24" fill="currentColor">
                                    <path d="M224.1 141c-63.6 0-114.9 51.3-114.9 114.9s51.3 114.9 114.9 114.9S339 319.5 339 255.9 287.7 141 224.1 141zm0 189.6c-41.1 0-74.7-33.5-74.7-74.7s33.5-74.7 74.7-74.7 74.7 33.5 74.7 74.7-33.6 74.7-74.7 74.7zm146.4-194.3c0 14.9-12 26.8-26.8 26.8-14.9 0-26.8-12-26.8-26.8s12-26.8 26.8-26.8 26.8 12 26.8 26.8zm76.1 27.2c-1.7-35.9-9.9-67.7-36.2-93.9-26.2-26.2-58-34.4-93.9-36.2-37-2.1-147.9-2.1-184.9 0-35.8 1.7-67.6 9.9-93.9 36.1s-34.4 58-36.2 93.9c-2.1 37-2.1 147.9 0 184.9 1.7 35.9 9.9 67.7 36.2 93.9s58 34.4 93.9 36.2c37 2.1 147.9 2.1 184.9 0 35.9-1.7 67.7-9.9 93.9-36.2 26.2-26.2 34.4-58 36.2-93.9 2.1-37 2.1-147.8 0-184.8zM398.8 388c-7.8 19.6-22.9 34.7-42.6 42.6-29.5 11.7-99.5 9-132.1 9s-102.7 2.6-132.1-9c-19.6-7.8-34.7-22.9-42.6-42.6-11.7-29.5-9-99.5-9-132.1s-2.6-102.7 9-132.1c7.8-19.6 22.9-34.7 42.6-42.6 29.5-11.7 99.5-9 132.1-9s102.7-2.6 132.1 9c19.6 7.8 34.7 22.9 42.6 42.6 11.7 29.5 9 99.5 9 132.1s2.7 102.7-9 132.1z"/>
                                </svg>
                                <span>Instagram</span>
                            </a>
                            @endif
                            @if ($settings->twitter_link ?? null)
                            <a href="{{ $settings->twitter_link }}" target="_blank" class="autosensor_chat_contact_social" aria-label="Twitter" rel="noreferrer">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="24" height="24" fill="currentColor">
                                    <path d="M459.37 151.716c.325 4.548.325 9.097.325 13.645 0 138.72-105.583 298.558-298.558 298.558-59.452 0-114.68-17.219-161.137-47.106 8.447.974 16.568 1.299 25.34 1.299 49.055 0 94.213-16.568 130.274-44.832-46.132-.975-84.792-31.188-98.112-72.772 6.498.974 12.995 1.624 19.818 1.624 9.421 0 18.843-1.3 27.614-3.573-48.081-9.747-84.143-51.98-84.143-102.985v-1.299c13.969 7.797 30.214 12.67 47.431 13.319-28.264-18.843-46.781-51.005-46.781-87.391 0-19.492 5.197-37.36 14.294-52.954 51.655 63.675 129.3 105.258 216.365 109.807-1.624-7.797-2.599-15.918-2.599-24.04 0-57.828 46.782-104.934 104.934-104.934 30.213 0 57.502 12.67 76.67 33.137 23.715-4.548 46.456-13.32 66.599-25.34-7.798 24.366-24.366 44.833-46.132 57.827 21.117-2.273 41.584-8.122 60.426-16.243-14.292 21.843-32.161 41.281-52.829 56.961z"/>
                                </svg>
                                <span>Twitter</span>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>