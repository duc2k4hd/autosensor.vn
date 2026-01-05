@php
    $aiChatEndpoint = route('client.ai.chat');
@endphp
<!-- Chat -->
<section>
    <div class="autosensor_chat">
        <div class="autosensor_back_to_top">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path
                    d="M270.7 9.7C268.2 3.8 262.4 0 256 0s-12.2 3.8-14.7 9.7L197.2 112.6c-3.4 8-5.2 16.5-5.2 25.2l0 77-144 84L48 280c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 56 0 32 0 24c0 13.3 10.7 24 24 24s24-10.7 24-24l0-8 144 0 0 32.7L133.5 468c-3.5 3-5.5 7.4-5.5 12l0 16c0 8.8 7.2 16 16 16l96 0 0-64c0-8.8 7.2-16 16-16s16 7.2 16 16l0 64 96 0c8.8 0 16-7.2 16-16l0-16c0-4.6-2-9-5.5-12L320 416.7l0-32.7 144 0 0 8c0 13.3 10.7 24 24 24s24-10.7 24-24l0-24 0-32 0-56c0-13.3-10.7-24-24-24s-24 10.7-24 24l0 18.8-144-84 0-77c0-8.7-1.8-17.2-5.2-25.2L270.7 9.7z" />
            </svg>
        </div>
        <!-- Zalo -->
        <a href="https://zalo.me/{{ $settings->contact_zalo ?? '' }}" aria-label="Zalo" alt="Zalo" title="Zalo" target="_blank" class="autosensor_chat_zalo" rel="noreferrer">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path
                    d="M164.9 24.6c-7.7-18.6-28-28.5-47.4-23.2l-88 24C12.1 30.2 0 46 0 64C0 311.4 200.6 512 448 512c18 0 33.8-12.1 38.6-29.5l24-88c5.3-19.4-4.6-39.7-23.2-47.4l-96-40c-16.3-6.8-35.2-2.1-46.3 11.6L304.7 368C234.3 334.7 177.3 277.7 144 207.3L193.3 167c13.7-11.2 18.4-30 11.6-46.3l-40-96z" />
            </svg>
            <span class="sr-only">Call Zalo</span>
        </a>
        <!-- Gọi điện -->
        <a href="tel:{{ $settings->contact_phone ?? '' }}" class="autosensor_chat_phone" aria-label="Phone" alt="Phone" title="Phone">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
                <path
                    d="M256.6 8C116.5 8 8 110.3 8 248.6c0 72.3 29.7 134.8 78.1 177.9 8.4 7.5 6.6 11.9 8.1 58.2A19.9 19.9 0 0 0 122 502.3c52.9-23.3 53.6-25.1 62.6-22.7C337.9 521.8 504 423.7 504 248.6 504 110.3 396.6 8 256.6 8zm149.2 185.1l-73 115.6a37.4 37.4 0 0 1 -53.9 9.9l-58.1-43.5a15 15 0 0 0 -18 0l-78.4 59.4c-10.5 7.9-24.2-4.6-17.1-15.7l73-115.6a37.4 37.4 0 0 1 53.9-9.9l58.1 43.5a15 15 0 0 0 18 0l78.4-59.4c10.4-8 24.1 4.5 17.1 15.6z" />
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