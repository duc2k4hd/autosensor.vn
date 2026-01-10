(function() {
    'use strict';

    // Cấu hình
    const CONFIG = {
        VIEW_TIME_THRESHOLD: 30000, // 30 giây (để test, có thể đổi lại 120000 = 2 phút)
        MULTIPLE_PRODUCTS_THRESHOLD: 3, // Xem 2 sản phẩm cùng nhóm
        POPUP_DELAY: 1000, // Delay 1 giây sau khi đạt điều kiện
        SESSION_STORAGE_KEY: 'autosensor_quick_consultation',
        VIEWED_PRODUCTS_KEY: 'autosensor_viewed_products',
    };

    // Kiểm tra xem đã submit form chưa (tránh hiển thị lại)
    function hasSubmittedLead() {
        return sessionStorage.getItem('autosensor_consultation_submitted') === 'true';
    }

    function markAsSubmitted() {
        sessionStorage.setItem('autosensor_consultation_submitted', 'true');
    }

    // Lấy session ID
    function getSessionId() {
        let sessionId = sessionStorage.getItem('autosensor_session_id');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('autosensor_session_id', sessionId);
        }
        return sessionId;
    }

    // Theo dõi thời gian xem sản phẩm
    function trackViewTime() {
        if (!window.productData || hasSubmittedLead()) {
            return;
        }

        const startTime = Date.now();
        const productId = window.productData.id;

        // Lưu thời gian bắt đầu xem
        const viewData = {
            productId: productId,
            startTime: startTime,
        };
        sessionStorage.setItem('autosensor_product_view', JSON.stringify(viewData));

        // Kiểm tra sau mỗi 10 giây
        const checkInterval = setInterval(() => {
            if (hasSubmittedLead()) {
                clearInterval(checkInterval);
                return;
            }

            const currentTime = Date.now();
            const elapsed = currentTime - startTime;

            if (elapsed >= CONFIG.VIEW_TIME_THRESHOLD) {
                clearInterval(checkInterval);
                console.log('Quick Consultation: View time threshold reached', elapsed);
                console.log('Quick Consultation: About to call showQuickConsultationPopup');
                try {
                    showQuickConsultationPopup('view_time', {
                        viewTime: Math.floor(elapsed / 1000), // giây
                    });
                    console.log('Quick Consultation: showQuickConsultationPopup called successfully');
                } catch (error) {
                    console.error('Quick Consultation: Error calling showQuickConsultationPopup', error);
                }
            }
        }, 10000); // Check mỗi 10 giây

        // Cleanup khi rời trang
        window.addEventListener('beforeunload', () => {
            clearInterval(checkInterval);
        });
    }

    // Theo dõi số lượng sản phẩm cùng nhóm đã xem
    function trackMultipleProducts() {
        if (!window.productData || hasSubmittedLead()) {
            return;
        }

        const productId = window.productData.id;
        const categoryIds = window.productData.categoryIds || [];

        // Lấy danh sách sản phẩm đã xem
        let viewedProducts = JSON.parse(sessionStorage.getItem(CONFIG.VIEWED_PRODUCTS_KEY) || '[]');

        // Kiểm tra xem sản phẩm hiện tại đã được đếm chưa
        const alreadyCounted = viewedProducts.some(p => p.productId === productId);
        
        if (!alreadyCounted) {
            // Thêm sản phẩm hiện tại vào danh sách
            viewedProducts.push({
                productId: productId,
                categoryIds: categoryIds,
                viewedAt: Date.now(),
            });

            // Lọc các sản phẩm cùng nhóm (có ít nhất 1 category chung)
            const sameGroupProducts = viewedProducts.filter(p => {
                if (p.productId === productId) return true;
                return p.categoryIds.some(catId => categoryIds.includes(catId));
            });

            // Nếu đạt ngưỡng, hiển thị popup
            if (sameGroupProducts.length >= CONFIG.MULTIPLE_PRODUCTS_THRESHOLD) {
                console.log('Quick Consultation: Multiple products threshold reached', sameGroupProducts.length);
                console.log('Quick Consultation: About to call showQuickConsultationPopup');
                try {
                    showQuickConsultationPopup('multiple_products', {
                        viewedCount: sameGroupProducts.length,
                        categoryIds: categoryIds,
                    });
                    console.log('Quick Consultation: showQuickConsultationPopup called successfully');
                } catch (error) {
                    console.error('Quick Consultation: Error calling showQuickConsultationPopup', error);
                }
            } else {
                // Lưu lại danh sách
                sessionStorage.setItem(CONFIG.VIEWED_PRODUCTS_KEY, JSON.stringify(viewedProducts));
            }
        }
    }

    // Hiển thị popup tư vấn nhanh
    function showQuickConsultationPopup(triggerType, behaviorData) {
        console.log('Quick Consultation: ========== showQuickConsultationPopup CALLED ==========');
        console.log('Quick Consultation: triggerType:', triggerType);
        console.log('Quick Consultation: behaviorData:', behaviorData);
        
        // Kiểm tra lại xem đã submit chưa
        const submitted = hasSubmittedLead();
        console.log('Quick Consultation: hasSubmittedLead?', submitted);
        if (submitted) {
            console.log('Quick Consultation: Already submitted, skipping');
            return;
        }

        // Kiểm tra xem popup đã được hiển thị chưa - kiểm tra cả sessionStorage và DOM
        const popupShown = sessionStorage.getItem('autosensor_popup_shown');
        const existingPopup = document.querySelector('.autosensor_quick_consultation_popup');
        
        console.log('Quick Consultation: popupShown check:', popupShown);
        console.log('Quick Consultation: existingPopup in DOM?', existingPopup !== null);
        
        // Chỉ skip nếu cả sessionStorage VÀ popup thực sự tồn tại trong DOM
        if (popupShown === 'true' && existingPopup) {
            console.log('Quick Consultation: Popup already shown and exists in DOM, skipping');
            return;
        }
        
        // Nếu sessionStorage là 'true' nhưng popup không tồn tại trong DOM, có thể đã bị xóa
        // Cho phép hiển thị lại
        if (popupShown === 'true' && !existingPopup) {
            console.log('Quick Consultation: popupShown is true but popup not in DOM, clearing and retrying');
            sessionStorage.removeItem('autosensor_popup_shown');
        }
        
        console.log('Quick Consultation: All checks passed, proceeding to show popup');

        // Delay một chút trước khi hiển thị
        setTimeout(() => {
            // Kiểm tra lại xem đã submit chưa (có thể đã submit trong lúc delay)
            if (hasSubmittedLead()) {
                console.log('Quick Consultation: Already submitted, skipping popup');
                return;
            }

            console.log('Quick Consultation: Creating popup', triggerType, behaviorData);

            // Tạo popup
            const popup = createPopup(triggerType, behaviorData);
            if (!popup) {
                console.error('Quick Consultation: Failed to create popup element!');
                return;
            }
            
            document.body.appendChild(popup);
            console.log('Quick Consultation: Popup element created and appended', popup);
            console.log('Quick Consultation: Popup in DOM?', document.body.contains(popup));
            console.log('Quick Consultation: Popup classes:', popup.className);
            
            // Kiểm tra CSS có được load không
            const computedStyle = window.getComputedStyle(popup);
            console.log('Quick Consultation: Popup computed style:', {
                display: computedStyle.display,
                position: computedStyle.position,
                opacity: computedStyle.opacity,
                visibility: computedStyle.visibility,
                zIndex: computedStyle.zIndex,
                width: computedStyle.width,
                height: computedStyle.height,
            });

            // Hiển thị với animation - force reflow để đảm bảo CSS được apply
            popup.offsetHeight; // Force reflow
            
            setTimeout(() => {
                console.log('Quick Consultation: Adding show class now');
                popup.classList.add('show');
                
                // Force update inline style với !important để override CSS
                popup.style.setProperty('opacity', '1', 'important');
                popup.style.setProperty('visibility', 'visible', 'important');
                popup.style.setProperty('pointer-events', 'auto', 'important');
                popup.style.setProperty('display', 'flex', 'important');
                
                console.log('Quick Consultation: Added show class and inline styles with !important');
                console.log('Quick Consultation: Popup classes after show:', popup.className);
                console.log('Quick Consultation: Popup inline styles:', {
                    opacity: popup.style.opacity,
                    visibility: popup.style.visibility,
                    display: popup.style.display,
                });
                
                // Force reflow
                popup.offsetHeight;
                
                const finalStyle = window.getComputedStyle(popup);
                console.log('Quick Consultation: Final computed style:', {
                    display: finalStyle.display,
                    opacity: finalStyle.opacity,
                    visibility: finalStyle.visibility,
                    zIndex: finalStyle.zIndex,
                });
                
                // Kiểm tra xem popup có thực sự visible không
                const rect = popup.getBoundingClientRect();
                console.log('Quick Consultation: Popup bounding rect:', rect);
                const isVisible = rect.width > 0 && rect.height > 0 && parseFloat(finalStyle.opacity) > 0;
                console.log('Quick Consultation: Popup is visible?', isVisible);
                
                // CHỈ ĐÁNH DẤU popupShown SAU KHI POPUP THỰC SỰ HIỂN THỊ
                if (isVisible) {
                    sessionStorage.setItem('autosensor_popup_shown', 'true');
                    console.log('Quick Consultation: Popup successfully shown, marked in sessionStorage');
                } else {
                    console.error('Quick Consultation: Popup created but not visible! Check CSS.');
                    console.error('Quick Consultation: Computed opacity:', finalStyle.opacity);
                    console.error('Quick Consultation: Computed visibility:', finalStyle.visibility);
                    console.error('Quick Consultation: Computed display:', finalStyle.display);
                }
            }, 100);
        }, CONFIG.POPUP_DELAY);
    }

    // Tạo popup HTML
    function createPopup(triggerType, behaviorData) {
        const popup = document.createElement('div');
        popup.className = 'autosensor_quick_consultation_popup';
        popup.innerHTML = `
            <div class="autosensor_quick_consultation_overlay"></div>
            <div class="autosensor_quick_consultation_content">
                <button class="autosensor_quick_consultation_close" aria-label="Đóng">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512" width="20" height="20">
                        <path fill="currentColor" d="M324.5 411.1c6.2 6.2 16.4 6.2 22.6 0s6.2-16.4 0-22.6L214.6 256 347.1 123.5c6.2-6.2 6.2-16.4 0-22.6s-16.4-6.2-22.6 0L192 233.4 59.5 100.9c-6.2-6.2-16.4-6.2-22.6 0s-6.2 16.4 0 22.6L169.4 256 36.9 388.5c-6.2 6.2-6.2 16.4 0 22.6s16.4 6.2 22.6 0L192 278.6 324.5 411.1z"/>
                    </svg>
                </button>
                <div class="autosensor_quick_consultation_header">
                    <div class="autosensor_quick_consultation_icon">💬</div>
                    <h3 class="autosensor_quick_consultation_title">Bạn cần kỹ sư tư vấn nhanh dòng này không?</h3>
                    <p class="autosensor_quick_consultation_subtitle">Để lại thông tin, chúng tôi sẽ gọi lại ngay!</p>
                </div>
                <form class="autosensor_quick_consultation_form" id="quick-consultation-form" data-route-url="/san-pham/quick-consultation">
                    <div class="autosensor_quick_consultation_form_group">
                        <label for="qc-name">Họ tên (tùy chọn)</label>
                        <input type="text" id="qc-name" name="name" placeholder="Nhập họ tên">
                    </div>
                    <div class="autosensor_quick_consultation_form_group">
                        <label for="qc-phone">Số điện thoại <span class="required">*</span></label>
                        <input type="tel" id="qc-phone" name="phone" required pattern="[0-9]{10,11}" placeholder="Nhập số điện thoại" maxlength="11">
                    </div>
                    <div class="autosensor_quick_consultation_form_group">
                        <label for="qc-email">Email (tùy chọn)</label>
                        <input type="email" id="qc-email" name="email" placeholder="Nhập email">
                    </div>
                    <div class="autosensor_quick_consultation_form_group">
                        <label for="qc-message">Tin nhắn (tùy chọn)</label>
                        <textarea id="qc-message" name="message" rows="3" placeholder="Nhập câu hỏi hoặc yêu cầu của bạn" maxlength="500"></textarea>
                    </div>
                    <input type="hidden" name="product_id" value="${window.productData.id}">
                    <input type="hidden" name="trigger_type" value="${triggerType}">
                    <input type="hidden" name="session_id" value="${getSessionId()}">
                    <input type="hidden" name="behavior_data" value='${JSON.stringify(behaviorData)}'>
                    <button type="submit" class="autosensor_quick_consultation_submit">
                        <span class="btn-text">Gửi yêu cầu tư vấn</span>
                        <span class="btn-loading" style="display: none;">Đang gửi...</span>
                    </button>
                </form>
            </div>
        `;

        // Xử lý đóng popup
        const closeBtn = popup.querySelector('.autosensor_quick_consultation_close');
        const overlay = popup.querySelector('.autosensor_quick_consultation_overlay');
        
        const closePopup = () => {
            popup.classList.remove('show');
            setTimeout(() => {
                popup.remove();
            }, 300);
        };

        closeBtn.addEventListener('click', closePopup);
        overlay.addEventListener('click', closePopup);

        // Xử lý submit form
        const form = popup.querySelector('#quick-consultation-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('.autosensor_quick_consultation_submit');
            const btnText = submitBtn.querySelector('.btn-text');
            const btnLoading = submitBtn.querySelector('.btn-loading');
            
            // Disable button
            submitBtn.disabled = true;
            btnText.style.display = 'none';
            btnLoading.style.display = 'inline-block';

            // Lấy dữ liệu form
            const formData = new FormData(form);
            const data = Object.fromEntries(formData.entries());
            data.behavior_data = JSON.parse(data.behavior_data);

            // Gửi request
            try {
                const routeUrl = form.dataset.routeUrl || '/san-pham/quick-consultation';
                const response = await fetch(routeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify(data),
                });

                const result = await response.json();

                if (result.success) {
                    // Đánh dấu đã submit
                    markAsSubmitted();
                    
                    // Hiển thị thông báo thành công
                    if (typeof showCustomToast === 'function') {
                        showCustomToast(result.message || 'Cảm ơn bạn! Chúng tôi sẽ liên hệ sớm nhất.', 'success', 5000);
                    } else {
                        alert(result.message || 'Cảm ơn bạn! Chúng tôi sẽ liên hệ sớm nhất.');
                    }
                    
                    // Đóng popup
                    closePopup();
                } else {
                    throw new Error(result.message || 'Có lỗi xảy ra');
                }
            } catch (error) {
                if (typeof showCustomToast === 'function') {
                    showCustomToast(error.message || 'Có lỗi xảy ra. Vui lòng thử lại.', 'error', 4000);
                } else {
                    alert(error.message || 'Có lỗi xảy ra. Vui lòng thử lại.');
                }
                
                // Re-enable button
                submitBtn.disabled = false;
                btnText.style.display = 'inline-block';
                btnLoading.style.display = 'none';
            }
        });

        // Đóng bằng phím Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && popup.classList.contains('show')) {
                closePopup();
            }
        });

        return popup;
    }

    // Khởi tạo khi DOM ready
    function init() {
        if (!window.productData) {
            console.warn('Quick Consultation: productData not found');
            return;
        }

        console.log('Quick Consultation: Initializing with productData', window.productData);

        // Bắt đầu theo dõi thời gian xem
        trackViewTime();

        // Kiểm tra số lượng sản phẩm đã xem
        trackMultipleProducts();
    }

    // Chờ DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        setTimeout(init, 500); // Delay một chút để đảm bảo productData đã được set
    }
})();
