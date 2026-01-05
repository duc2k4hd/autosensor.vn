document.addEventListener("click", async (e) => {
    // Bỏ qua nếu click vào menu mobile
    if (e.target.closest(".xanhworld_header_main_mobile_bars") || 
        e.target.closest(".xanhworld_header_mobile_main_nav")) {
        return;
    }
});

const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : '';

function sleep(ms) {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

function showCustomToast(
    message = "Thông báo!",
    type = "info",
    duration = 5000
) {
    const container = document.getElementById("custom-toast-container");
    if (!container) {
        console.warn('Toast container not found');
        return;
    }
    
    const toast = document.createElement("div");
    const icon = document.createElement("span");

    toast.className = `custom-toast ${type}`;
    icon.className = "custom-toast-icon";

    // Gán biểu tượng theo loại
    const icons = {
        success: "✅",
        error: "❌",
        warning: "⚠️",
        info: "💬",
    };
    icon.textContent = icons[type] || "🔔";

    toast.appendChild(icon);
    toast.appendChild(document.createTextNode(message));
    container.appendChild(toast);

    // Kích hoạt animation
    setTimeout(() => {
        if (toast && toast.classList) {
            toast.classList.add("show");
        }
    }, 100);

    toast.addEventListener("click", () => {
        if (toast && toast.classList) {
            toast.classList.remove("show");
        }
        setTimeout(() => {
            if (container && toast && container.contains(toast)) {
                container.removeChild(toast);
            }
        }, 300);
        return;
    });

    // Gỡ thông báo sau duration
    setTimeout(() => {
        if (toast && toast.classList) {
            toast.classList.remove("show");
        }
        setTimeout(() => {
            if (container && toast && container.contains(toast)) {
                container.removeChild(toast);
            }
        }, 300);
        return;
    }, duration);
}

async function showOverlayMain(ms) {
    const overlay = document.querySelector(".xanhworld_loading_overlay");
    if (!overlay) return;
    overlay.style.display = "flex";
    await sleep(ms);
    if (overlay) {
        overlay.style.display = "none";
    }
}

function parseVND(value) {
    if (typeof value !== "string") return 0;

    return parseInt(
        value.replace(/[^\d]/g, "") // Xoá mọi ký tự không phải số
    );
}

function formatCurrencyVND(amount) {
    if (isNaN(amount)) return 0;
    return Number(amount).toLocaleString("vi-VN");
}

function postAndRedirect(url, data = {}) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = url;

    // CSRF token nếu dùng Laravel web.php
    const csrf = document.querySelector('meta[name="csrf-token"]');
    if (csrf) {
        const token = document.createElement("input");
        token.type = "hidden";
        token.name = "_token";
        token.value = csrf.getAttribute("content") || csrfToken;
        form.appendChild(token);
    }

    // Đệ quy xử lý mảng/lồng object
    function appendFormData(key, value) {
        if (Array.isArray(value)) {
            value.forEach((v, i) => {
                for (const subKey in v) {
                    appendFormData(`${key}[${i}][${subKey}]`, v[subKey]);
                }
            });
        } else if (typeof value === "object") {
            for (const subKey in value) {
                appendFormData(`${key}[${subKey}]`, value[subKey]);
            }
        } else {
            const input = document.createElement("input");
            input.type = "hidden";
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
    }

    for (const key in data) {
        if (data.hasOwnProperty(key)) {
            appendFormData(key, data[key]);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

setTimeout(() => {
    document
        .querySelectorAll(".xanhworld_header_main_nav_links_item_title")
        .forEach((item, index) => {
            const list = document.querySelectorAll(
                ".xanhworld_header_main_nav_links_item_list"
            )[index];

            if (!item || !list) return;

            const left = item.getBoundingClientRect().left;

            list.style.transform = `translateX(-${left - 10}px)`;
        });
}, 10); // ⏱ chạy sau 200ms

const mainMenu = document.querySelector(".xanhworld_header_main_nav");

if (mainMenu) {
    window.addEventListener("scroll", () => {
        if (window.scrollY > 240) {
            mainMenu.classList.add("xanhworld_header_main_nav_fixed");
        } else {
            mainMenu.classList.remove("xanhworld_header_main_nav_fixed");
        }
    });
}


// Custom Xanhworld-select
function initCustomSelect(selector) {
    document.querySelectorAll(selector).forEach(select => {

        const isMultiple = select.dataset.multiple === "true";
        const wrapper = document.createElement("div");
        wrapper.className = "xanhworld-select-wrapper";

        const display = document.createElement("div");
        display.className = "xanhworld-select-display";
        display.textContent = "Chọn...";

        const dropdown = document.createElement("div");
        dropdown.className = "xanhworld-select-options";

        // Ẩn select gốc
        select.style.display = "none";
        select.parentNode.insertBefore(wrapper, select);
        wrapper.appendChild(select);
        wrapper.appendChild(display);
        wrapper.appendChild(dropdown);

        // Thêm option vào dropdown
        [...select.options].forEach(opt => {
            if (!opt.value) return;
            const item = document.createElement("div");
            item.className = "xanhworld-select-option";
            item.textContent = opt.textContent;
            item.dataset.value = opt.value;

            item.addEventListener("click", () => {
                if (isMultiple) {
                    opt.selected = !opt.selected;
                    item.classList.toggle("xanhworld-select-selected");
                } else {
                    [...dropdown.children].forEach(c => c.classList.remove("xanhworld-select-selected"));
                    item.classList.add("xanhworld-select-selected");

                    select.value = opt.value;
                    display.textContent = opt.textContent;
                    dropdown.style.display = "none";
                }

                if (isMultiple) {
                    const selected = [...select.selectedOptions].map(o => o.textContent);
                    display.textContent = selected.length ? selected.join(", ") : "Chọn...";
                }
            });

            dropdown.appendChild(item);
        });

        // Toggle dropdown
        display.addEventListener("click", () => {
            dropdown.style.display =
                dropdown.style.display === "block" ? "none" : "block";
        });

        // Click ngoài để tắt
        document.addEventListener("click", e => {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = "none";
            }
        });
    });
}

// Xử lý menu mobile - đảm bảo chạy sau khi DOM ready
function initMobileMenu() {
    const openMenuMobile = document.querySelector(
        ".xanhworld_header_main_mobile_bars"
    );
    const closeMenuMobile = document.querySelector(
        ".xanhworld_header_mobile_main_nav_close"
    );
    const menuMobile = document.querySelector(
        ".xanhworld_header_mobile_main_nav"
    );
    const overlay = document.querySelector(".xanhworld_header_mobile_overlay");

    // Kiểm tra phần tử tồn tại
    if (!openMenuMobile) {
        return;
    }
    if (!closeMenuMobile) {
        return;
    }
    if (!menuMobile) {
        return;
    }

    // open - sử dụng stopPropagation để tránh conflict
    openMenuMobile.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (menuMobile && menuMobile.classList) {
            menuMobile.classList.add("active");
        }
        if (overlay && overlay.classList) {
            overlay.classList.add("active");
        }
        // Ngăn scroll body khi menu mở
        if (document.body) {
            document.body.style.overflow = "hidden";
        }
    });

    // close
    closeMenuMobile.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (menuMobile && menuMobile.classList) {
            menuMobile.classList.remove("active");
        }
        if (overlay && overlay.classList) {
            overlay.classList.remove("active");
        }
        // Khôi phục scroll body
        if (document.body) {
            document.body.style.overflow = "";
        }
    });

    // Close khi click overlay
    if (overlay) {
        overlay.addEventListener("click", (e) => {
            e.preventDefault();
            e.stopPropagation();
            if (menuMobile && menuMobile.classList) {
                menuMobile.classList.remove("active");
            }
            if (overlay && overlay.classList) {
                overlay.classList.remove("active");
            }
            if (document.body) {
                document.body.style.overflow = "";
            }
        });
    }

    // Close khi click ra ngoài menu (nếu không có overlay)
    if (!overlay) {
        document.addEventListener("click", (e) => {
            if (menuMobile && menuMobile.classList && menuMobile.classList.contains("active")) {
                // Nếu click không phải vào menu hoặc button mở menu
                if (openMenuMobile && !menuMobile.contains(e.target) && !openMenuMobile.contains(e.target)) {
                    menuMobile.classList.remove("active");
                    if (document.body) {
                        document.body.style.overflow = "";
                    }
                }
            }
        });
    }
}

// Chạy khi DOM ready
if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initMobileMenu);
} else {
    // DOM đã sẵn sàng
    initMobileMenu();
}

// submenu toggle
document
    .querySelectorAll(".xanhworld_header_mobile_main_nav_links_item_title")
    .forEach((title) => {
        if (!title) {
            return;
        }
        title.addEventListener("click", () => {
            const subMenu = title.nextElementSibling;
            const svg = title.querySelector("svg");

            if (!subMenu || !subMenu.classList) {
                return;
            }

            const isOpen = subMenu.classList.contains("show");

            if (isOpen) {
                subMenu.classList.remove("show");
                if (svg && svg.style) {
                    svg.style.transform = "rotate(0deg)";
                }
            } else {
                subMenu.classList.add("show");
                if (svg && svg.style) {
                    svg.style.transform = "rotate(180deg)";
                }
            }
        });
    });

const backToTopBtn = document.querySelector(".xanhworld_back_to_top");

if (backToTopBtn) {
    window.addEventListener("scroll", () => {
        if (window.scrollY > 300) {
            backToTopBtn.style.display = "flex";

            const orderSummary = document.querySelector(".xanhworld_order_summary");
            if (orderSummary) {
                orderSummary.classList.add("xanhworld_order_summary_fixed");
            }

        } else {
            backToTopBtn.style.display = "none";

            const orderSummary = document.querySelector(".xanhworld_order_summary");
            if (orderSummary) {
                orderSummary.classList.remove("xanhworld_order_summary_fixed");
            }
        }
    });

    backToTopBtn.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
}

function toggleFormOverlay(show = true) {
    const overlay = document.querySelector(
        ".xanhworld_main_loading_form_overlay"
    );
    if (!overlay) return;
    if (show) overlay.removeAttribute("hidden");
    else overlay.setAttribute("hidden", "");
}

document.addEventListener("DOMContentLoaded", function () {

    // Delay 200ms để tránh lỗi khi DOM chưa ổn định (giảm CLS)
    setTimeout(() => {

        const trigger = document.querySelector("[data-ai-chat-trigger]");
        const popup = document.getElementById("xanhworldChatPopup");
        if (!trigger || !popup) return;

        const form = popup.querySelector(".xanhworld_chat_form");
        const textarea = popup.querySelector("textarea");
        const sendButton = popup.querySelector(".xanhworld_chat_send");
        const messagesBox = popup.querySelector(".xanhworld_chat_messages");
        const closeButton = popup.querySelector(".xanhworld_chat_close");
        const endpoint = popup.dataset.endpoint;
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
        const history = [];
        const STORAGE_KEY = "xanhworld-chat-messages";
        const MAX_MESSAGES = 10;
        const defaultGreeting =
            "Xin chào! Bạn đang cần tư vấn cây cảnh, decor hay muốn tìm hiểu bài viết nào? Mình có thể dùng dữ liệu sản phẩm & bài viết mới nhất để trả lời ngay.";
        let isProcessing = false;
        let persistedMessages = [];

        const escapeHtml = (value) =>
            value.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");

        const formatMessageContent = (text = "") => {
            if (!text.trim()) return "<p></p>";

            let html = escapeHtml(text);
            html = html.replace(/\*\*(.+?)\*\*/g, "<strong>$1</strong>");
            html = html.replace(/\*(.+?)\*/g, "<em>$1</em>");
            html = html.replace(/`(.+?)`/g, "<code>$1</code>");

            const paragraphs = html
                .split(/\n{2,}/)
                .map(p => `<p>${p.trim().replace(/\n/g, "<br>")}</p>`);

            return paragraphs.join("");
        };

        const trimHistory = () => {
            while (history.length > 10) history.shift();
        };

        const normalizeReferences = (refs) => {
            if (!Array.isArray(refs)) return [];
            return refs
                .map((item) => {
                    if (!item) return null;
                    const url = item.url || item.link;
                    if (!url) return null;
                    const label = item.title || item.name || item.label || "Xem thêm";
                    return { label, url };
                })
                .filter(Boolean);
        };

        const loadMessagesFromStorage = () => {
            try {
                const raw = localStorage.getItem(STORAGE_KEY);
                if (!raw) return (persistedMessages = []);

                const parsed = JSON.parse(raw);
                if (!Array.isArray(parsed)) return (persistedMessages = []);

                persistedMessages = parsed.slice(-MAX_MESSAGES).map(entry => ({
                    role: entry.role || "assistant",
                    content: entry.content || "",
                    references: normalizeReferences(entry.references),
                }));
            } catch {
                persistedMessages = [];
            }
        };

        const saveMessagesToStorage = () => {
            try {
                localStorage.setItem(
                    STORAGE_KEY,
                    JSON.stringify(persistedMessages.slice(-MAX_MESSAGES))
                );
            } catch {}
        };

        const renderMessage = (entry) => {
            const message = document.createElement("div");
            message.className = `xanhworld_chat_message is-${entry.role}`;
            message.innerHTML = formatMessageContent(entry.content);

            if (entry.references?.length) {
                const refs = document.createElement("div");
                refs.className = "xanhworld_chat_sources";

                entry.references.forEach((r) => {
                    const a = document.createElement("a");
                    a.href = r.url;
                    a.target = "_blank";
                    a.textContent = r.label;
                    a.className = "xanhworld_chat_source_link";
                    refs.appendChild(a);
                });

                message.appendChild(refs);
            }

            messagesBox.appendChild(message);
            messagesBox.scrollTop = messagesBox.scrollHeight;
        };

        const renderStoredMessages = () => {
            messagesBox.innerHTML = "";
            if (!persistedMessages.length) {
                renderMessage({
                    role: "assistant",
                    content: defaultGreeting,
                    references: [],
                });
                return;
            }
            persistedMessages.forEach(renderMessage);
        };

        const syncHistoryFromMessages = () => {
            history.length = 0;
            persistedMessages.forEach(entry => {
                if (entry.content) history.push({ role: entry.role, content: entry.content });
            });
            trimHistory();
        };

        const addMessage = (role, text, references = null, opt = {}) => {
            const entry = {
                role,
                content: text,
                references: normalizeReferences(references),
            };

            renderMessage(entry);

            if (opt.persist === false) return entry;

            persistedMessages.push(entry);
            if (persistedMessages.length > MAX_MESSAGES) {
                persistedMessages = persistedMessages.slice(-MAX_MESSAGES);
            }
            saveMessagesToStorage();
            syncHistoryFromMessages();

            return entry;
        };

        const togglePopup = () => {
            const isOpening = !popup.classList.contains("is-open");
            popup.classList.toggle("is-open");
            
            // Toggle overflow hidden trên body/html để tránh cuộn không đúng
            if (isOpening) {
                document.body.style.overflow = "hidden";
                document.documentElement.style.overflow = "hidden";
                setTimeout(() => textarea.focus(), 150);
            } else {
                document.body.style.overflow = "";
                document.documentElement.style.overflow = "";
            }
        };

        const appendError = (msg) => {
            const div = document.createElement("div");
            div.className = "xanhworld_chat_message is-assistant is-error";
            div.innerHTML = formatMessageContent(msg);
            messagesBox.appendChild(div);
            messagesBox.scrollTop = messagesBox.scrollHeight;
        };

        const appendTyping = () => {
            const div = document.createElement("div");
            div.className = "xanhworld_chat_message is-assistant";
            div.innerHTML =
                '<div class="xanhworld_chat_typing"><span></span><span></span><span></span></div>';
            messagesBox.appendChild(div);
            messagesBox.scrollTop = messagesBox.scrollHeight;
            return div;
        };

        const setLoading = (b) => {
            isProcessing = b;
            updateSendState();
        };

        const updateSendState = () => {
            sendButton.disabled = isProcessing || textarea.value.trim().length < 5;
        };

        // INIT CHAT UI
        loadMessagesFromStorage();
        renderStoredMessages();
        syncHistoryFromMessages();

        trigger.addEventListener("click", (e) => {
            e.preventDefault();
            togglePopup();
        });

        closeButton.addEventListener("click", () => {
            popup.classList.remove("is-open");
            // Khôi phục overflow khi đóng modal
            document.body.style.overflow = "";
            document.documentElement.style.overflow = "";
        });

        // Xử lý tab switching
        const tabs = popup.querySelectorAll(".xanhworld_chat_tab");
        const tabContents = popup.querySelectorAll(".xanhworld_chat_tab_content");
        
        tabs.forEach(tab => {
            tab.addEventListener("click", () => {
                const targetTab = tab.dataset.tab;
                
                // Remove active class từ tất cả tabs và contents
                tabs.forEach(t => t.classList.remove("active"));
                tabContents.forEach(content => content.classList.remove("active"));
                
                // Add active class cho tab và content được chọn
                tab.classList.add("active");
                const targetContent = popup.querySelector(`[data-tab-content="${targetTab}"]`);
                if (targetContent) {
                    targetContent.classList.add("active");
                }
            });
        });

        textarea.addEventListener("input", updateSendState);

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (isProcessing) return;

            const content = textarea.value.trim();
            if (content.length < 5) return;

            addMessage("user", content);
            textarea.value = "";
            updateSendState();

            const typing = appendTyping();
            setLoading(true);

            try {
                const resp = await fetch(endpoint, {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrf,
                    },
                    body: JSON.stringify({
                        question: content,
                        history,
                    }),
                });

                const data = await resp.json();
                typing.remove();

                if (!resp.ok || !data.success) {
                    throw new Error(data.message || "Không gửi được câu hỏi. Hãy thử lại sau.");
                }

                const refs = [
                    ...(data.references?.products || []),
                    ...(data.references?.posts || []),
                ];

                addMessage("assistant", data.answer, refs);
            } catch (err) {
                typing.remove();
                appendError(err.message || "Trợ lý đang bận, thử lại giúp mình nhé.");
            } finally {
                setLoading(false);
            }
        });

        document.addEventListener("keyup", (e) => {
            if (e.key === "Escape") popup.classList.remove("is-open");
        });

    }, 200); // END DELAY 200ms
});

function openImageSearchModal() {
    document.getElementById('imageSearchModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeImageSearchModal() {
    document.getElementById('imageSearchModal').style.display = 'none';
    document.body.style.overflow = '';
    resetImageSearch();
}

function resetImageSearch() {
    document.getElementById('imageInput').value = '';
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('uploadArea').querySelector('.xanhworld_image_search_upload_content').style.display = 'block';
    document.getElementById('searchButton').disabled = true;
    document.getElementById('loadingState').style.display = 'none';
}

// Image search modal functionality
document.addEventListener('DOMContentLoaded', function() {
    const uploadArea = document.getElementById('uploadArea');
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const previewImage = document.getElementById('previewImage');
    const removeImage = document.getElementById('removeImage');
    const searchButton = document.getElementById('searchButton');
    const form = document.getElementById('imageSearchForm');
    const loadingState = document.getElementById('loadingState');

    if (!uploadArea || !imageInput || !form) return;

    // Click to select file
    uploadArea.addEventListener('click', function(e) {
        if (e.target !== removeImage && !e.target.closest('#removeImage')) {
            imageInput.click();
        }
    });

    // File selection
    imageInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('File quá lớn. Vui lòng chọn file nhỏ hơn 5MB.');
                return;
            }
            displayPreview(file);
        }
    });

    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('dragover');
    });

    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
    });

    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('dragover');
        
        const file = e.dataTransfer.files[0];
        if (file && file.type.startsWith('image/')) {
            if (file.size > 5 * 1024 * 1024) {
                alert('File quá lớn. Vui lòng chọn file nhỏ hơn 5MB.');
                return;
            }
            imageInput.files = e.dataTransfer.files;
            displayPreview(file);
        }
    });

    // Display preview
    function displayPreview(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            imagePreview.style.display = 'block';
            uploadArea.querySelector('.xanhworld_image_search_upload_content').style.display = 'none';
            searchButton.disabled = false;
            searchButton.style.opacity = '1';
        };
        reader.readAsDataURL(file);
    }

    // Remove image
    removeImage.addEventListener('click', function(e) {
        e.stopPropagation();
        resetImageSearch();
    });

    // Form submit
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        if (!imageInput.files[0]) {
            alert('Vui lòng chọn ảnh để tìm kiếm');
            return;
        }

        const formData = new FormData(form);
        
        // Show loading
        loadingState.style.display = 'block';
        searchButton.disabled = true;
        searchButton.style.opacity = '0.5';

        try {
            const response = await fetch('{{ route("client.image-search.search") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                // Redirect to shop page with image search results (dù products rỗng vẫn redirect)
                const keywords = data.keywords || [];
                const keywordParam = keywords.length > 0 ? keywords[0] : '';
                window.location.href = '{{ route("client.shop.index") }}?keyword=' + encodeURIComponent(keywordParam) + '&image_search=1';
            } else {
                alert(data.message || 'Không tìm thấy sản phẩm nào phù hợp với hình ảnh. Vui lòng thử với ảnh khác.');
                loadingState.style.display = 'none';
                searchButton.disabled = false;
                searchButton.style.opacity = '1';
            }
        } catch (error) {
            console.error('Search error:', error);
            alert('Có lỗi xảy ra. Vui lòng thử lại sau.');
            loadingState.style.display = 'none';
            searchButton.disabled = false;
            searchButton.style.opacity = '1';
        }
    });

    // Close on ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('imageSearchModal').style.display === 'flex') {
            closeImageSearchModal();
        }
    });
});

// ================== CONFIG ==================
const SELECTORS = {
    province: 'select[name="provinceId"]',
    district: 'select[name="districtId"]',
    ward: 'select[name="wardId"]',
    form_checkout: '#checkout-form',
};

// ================== PAGE LOADER ==================
let pageLoaderTimeout = null;

function showPageLoader() {
    const loader = document.getElementById('page_loader_overlay');
    if (!loader) return;

    loader.removeAttribute('hidden');

    // Safety timeout to prevent loader from getting stuck
    if (pageLoaderTimeout) clearTimeout(pageLoaderTimeout);
    pageLoaderTimeout = setTimeout(hidePageLoader, 15000); // 15 seconds
}

function hidePageLoader() {
    const loader = document.getElementById('page_loader_overlay');
    if (!loader) return;

    if (pageLoaderTimeout) {
        clearTimeout(pageLoaderTimeout);
        pageLoaderTimeout = null;
    }
    loader.setAttribute('hidden', '');
}

// ================== STATE ===================
const dataMain = {};
let isSubmitting = false;
let appliedVoucher = null;

// SlimSelect instances
const SS = { province: null, district: null, ward: null };

function updatePlaceOrderState() {
    const orderBtn = document.querySelector('.checkout-submit');
    if (!orderBtn) {
        return;
    }
    const hasShipping = Number(dataMain.shipping_fee ?? 0) > 0;
    const hasAddress = Boolean(dataMain.provinceId && dataMain.districtId && dataMain.wardId);
    const allow = hasShipping && hasAddress;
    orderBtn.disabled = !allow;
}

// ================== HELPERS =================
const domCache = {};
const getDOMElement = (selector) => {
    if (!domCache[selector])
        domCache[selector] = document.querySelector(selector);
    return domCache[selector];
};

const toArray = (x) => (Array.isArray(x) ? x : x ? [x] : []);
const hasSelection = (v) =>
    !(v === null || v === undefined || v === "" || v === "null");

function formatCurrencyVND(value) {
    const number = Number(value) || 0;
    return number.toLocaleString('vi-VN');
}

function updateHiddenLocationName(selectEl, hiddenId) {
    if (!selectEl || !hiddenId) {
        return;
    }

    const target = document.getElementById(hiddenId);
    if (!target) {
        return;
    }

    const selectedOption = selectEl.options?.[selectEl.selectedIndex];
    target.value = selectedOption?.text?.trim() ?? '';
}

async function apiRequest(
    url,
    method = "GET",
    body = null,
    headers = {},
    timeout = 8000
) {
    const defaultHeaders = {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        ...headers,
    };
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);

    try {
        const res = await fetch(url, {
            method,
            headers: defaultHeaders,
            body: body ? JSON.stringify(body) : null,
            signal: controller.signal,
        });
        clearTimeout(id);
        if (!res.ok)
            return {
                ok: false,
                status: res.status,
                message: `HTTP error ${res.status}`,
            };
        const data = await res.json().catch(() => ({}));
        return { ok: true, status: res.status, data };
    } catch (e) {
        clearTimeout(id);
        console.error('API request error:', e);
        return {
            ok: false,
            message: e.name === "AbortError" ? "Request timeout!" : e.message,
        };
    }
}

function collectCartItems() {
    const rows = document.querySelectorAll(".summary-item");

    const isSingleItem = document.querySelector('input[name="product_id"]') !== null;
    const productIdEl = document.querySelector('input[name="product_id"]');
    const productId = parseInt(productIdEl?.value.trim() || '0', 10);
    const cartIdEl = document.querySelector('input[name="cart_id"]');
    const cartId = parseInt(cartIdEl?.value.trim() || '0', 10);
    const uuid = document.querySelector('input[name="uuid"]');

    const items = [];

    rows.forEach((row) => {
        const productIdAttr = row.dataset.productId ? parseInt(row.dataset.productId, 10) : null;
        const categoryId = row.dataset.categoryId ? parseInt(row.dataset.categoryId, 10) : null;
        const name = row.dataset.itemName || "Sản phẩm";
        const price = parseFloat(row.dataset.itemPrice ?? '0') || 0;
        const quantity = parseInt(row.dataset.itemQty ?? '1', 10) || 1;
        const total_price = parseFloat(row.dataset.itemTotal ?? price * quantity) || (price * quantity);
        let attributes = [];

        if (row.dataset.itemOptions) {
            try {
                attributes = JSON.parse(row.dataset.itemOptions);
            } catch (_) {
                attributes = [];
            }
        }

        items.push({
            name,
            price,
            quantity,
            total_price,
            attributes,
            product_id: productIdAttr,
            category_id: categoryId,
        });
    });

    const subtotalInput = document.getElementById('checkout_subtotal_value');
    const shippingInput = document.getElementById('checkout_shipping_fee_value');
    const shippingOriginalInput = document.getElementById('checkout_shipping_fee_original');
    const totalInput = document.getElementById('checkout_total_value');

    const subtotal = parseFloat(subtotalInput?.value ?? '0') || 0;
    const shipping_fee = parseFloat(shippingInput?.value ?? '0') || 0;
    const shipping_fee_original = parseFloat(shippingOriginalInput?.value ?? shipping_fee) || 0;
    const total = parseFloat(totalInput?.value ?? subtotal + shipping_fee) || (subtotal + shipping_fee);

    if (isSingleItem) {
    dataMain.productId = productId;
        dataMain.uuid = uuid?.value.trim() || null;
    } else {
    dataMain.cartId = cartId;
    }

    dataMain.items = items;
    dataMain.subtotal = subtotal;
    dataMain.shipping_fee = shipping_fee;
    dataMain.shipping_fee_original = shipping_fee_original;
    dataMain.total = total;
    return dataMain;
}

// ================== SLIM SELECT ==================
function ensureSlimSelect(selector, placeholder, disabled = false) {
    const el = getDOMElement(selector);
    if (!el) return null;
    
    // Check if SlimSelect is available
    if (typeof SlimSelect === 'undefined') {
        console.error('SlimSelect is not loaded');
        return null;
    }
    
    if (!el._slim) {
        el._slim = new SlimSelect({
            select: el,
            placeholder,
            allowDeselect: true,
            hideSelectedOption: true,
        });
    }
    if (disabled) el._slim.disable();
    else el._slim.enable();
    return el._slim;
}

function resetSelect(ss, placeholderText) {
    if (!ss) return;
    ss.setData([{ text: placeholderText, value: "" }]);
    ss.setSelected([]);
}

// ================== API GHN ==================
async function getProvince() {
    try {
    const url = "/api/v1/ghn/province";
        
        // Check if SlimSelect is available
        const useFallback = typeof SlimSelect === 'undefined';
        
        if (useFallback) {
            // Fallback: populate native select
            const provinceSelect = document.querySelector(SELECTORS.province);
            const districtSelect = document.querySelector(SELECTORS.district);
            const wardSelect = document.querySelector(SELECTORS.ward);
            
            if (provinceSelect) {
                provinceSelect.innerHTML = '<option value="">Chọn Tỉnh/Thành Phố</option>';
                provinceSelect.disabled = false;
            }
            if (districtSelect) {
                districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
                districtSelect.disabled = true;
            }
            if (wardSelect) {
                wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
                wardSelect.disabled = true;
            }
            
            // Vẫn gọi API để lấy dữ liệu thật
            const res = await apiRequest(url, "GET");
            
            if (res.ok && res.data?.data && Array.isArray(res.data.data)) {
                res.data.data.forEach((p) => {
                    const option = document.createElement('option');
                    option.value = String(p.provinceId || p.ProvinceID || p.province_id);
                    option.textContent = p.provinceName || p.ProvinceName;
                    provinceSelect.appendChild(option);
                });
            } else {
                // Nếu API fail, dùng default options
                const defaultOptions = [
                    { text: "Hải Phòng", value: "225" },
                    { text: "Hà Nội", value: "201" },
                    { text: "TP. Hồ Chí Minh", value: "202" },
                ];
                defaultOptions.forEach(option => {
                    const optionEl = document.createElement('option');
                    optionEl.value = option.value;
                    optionEl.textContent = option.text;
                    provinceSelect.appendChild(optionEl);
                });
            }
            return;
        }
        
        // Initialize SlimSelect instances first
    SS.province = ensureSlimSelect(
        SELECTORS.province,
        "Chọn Tỉnh/Thành Phố",
        false
    );
    SS.district = ensureSlimSelect(SELECTORS.district, "Chọn Quận/Huyện", true);
    SS.ward = ensureSlimSelect(SELECTORS.ward, "Chọn Xã/Phường", true);

        if (!SS.province) {
            return;
        }

    resetSelect(SS.province, "Chọn Tỉnh/Thành Phố");
    resetSelect(SS.district, "Chọn Quận/Huyện");
    resetSelect(SS.ward, "Chọn Xã/Phường");

        const res = await apiRequest(url, "GET");

    if (!res.ok) {
            // Set default options even if API fails
            SS.province.setData([
                { text: "Chọn Tỉnh/Thành Phố", value: "" },
                { text: "Hải Phòng", value: "225" },
                { text: "Hà Nội", value: "201" },
                { text: "TP. Hồ Chí Minh", value: "202" },
            ]);
        return;
    }

    // Kiểm tra cấu trúc response
    // Server trả về: { code: 200, message: "...", data: [...] }
    // apiRequest trả về: { ok: true, data: { code: 200, message: "...", data: [...] } }
    const responseData = res.data;
    let list = [];
    
    if (responseData && responseData.data && Array.isArray(responseData.data)) {
        // Cấu trúc: { code: 200, data: [...] }
        list = responseData.data;
    } else if (Array.isArray(responseData)) {
        // Nếu responseData trực tiếp là array
        list = responseData;
    }
    
    if (!list || list.length === 0) {
        SS.province.setData([
            { text: "Chọn Tỉnh/Thành Phố", value: "" },
            { text: "Hải Phòng", value: "225" },
            { text: "Hà Nội", value: "201" },
            { text: "TP. Hồ Chí Minh", value: "202" },
        ]);
        return;
    }
    
    const options = list.map((p) => ({
        text: p.provinceName || p.ProvinceName || p.name || '',
        value: String(p.provinceId || p.ProvinceID || p.province_id || p.id || ''),
    })).filter(opt => opt.text && opt.value); // Lọc bỏ các option không hợp lệ
    
    if (options.length === 0) {
        SS.province.setData([
            { text: "Chọn Tỉnh/Thành Phố", value: "" },
            { text: "Hải Phòng", value: "225" },
            { text: "Hà Nội", value: "201" },
            { text: "TP. Hồ Chí Minh", value: "202" },
        ]);
        return;
    }
    
    SS.province.setData([
        { text: "Chọn Tỉnh/Thành Phố", value: "" },
        ...options,
    ]);
    SS.province.setSelected([]);
    } catch (error) {
        console.error('Error in getProvince:', error);
        // Set default options on error
        if (SS.province) {
            SS.province.setData([
                { text: "Chọn Tỉnh/Thành Phố", value: "" },
                { text: "Hải Phòng", value: "225" },
                { text: "Hà Nội", value: "201" },
                { text: "TP. Hồ Chí Minh", value: "202" },
            ]);
        }
    }
}

async function getDistrict(provinceId) {
    try {
    const url = `/api/v1/ghn/district/${provinceId}`;
    const res = await apiRequest(url, "POST", { province_id: provinceId });

    // Kiểm tra SlimSelect có sẵn sàng không
    if (typeof SlimSelect === 'undefined' || !SS.district) {
        const districtSelect = document.querySelector(SELECTORS.district);
        if (districtSelect) {
            districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
            districtSelect.disabled = false;
        }
        const wardSelect = document.querySelector(SELECTORS.ward);
        if (wardSelect) {
            wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
            wardSelect.disabled = true;
        }
        
        if (!res.ok || !res.data?.data) {
            return;
        }
        
        const list = toArray(res.data?.data);
        list.forEach((d) => {
            const option = document.createElement('option');
            option.value = String(d.districtID ?? d.DistrictID ?? d.districtId ?? d.district_id);
            option.textContent = d.districtName || d.DistrictName;
            districtSelect.appendChild(option);
        });
        return;
    }

    resetSelect(SS.district, "Chọn Quận/Huyện");
    resetSelect(SS.ward, "Chọn Xã/Phường");
    SS.district.enable();
    SS.ward.disable();

    if (!res.ok) {
        return;
    }

    const list = toArray(res.data?.data);
    const options = list.map((d) => ({
        text: d.districtName || d.DistrictName,
        value: String(d.districtID ?? d.DistrictID ?? d.districtId ?? d.district_id),
    }));

    SS.district.setData([{ text: "Chọn Quận/Huyện", value: "" }, ...options]);
    SS.district.setSelected([]);
    } catch (error) {
        console.error('Error in getDistrict:', error);
    }
}

async function getWard(districtId) {
    try {
    const url = `/api/v1/ghn/ward/${districtId}`;
    const res = await apiRequest(url, "POST", { district_id: districtId });

    // Kiểm tra SlimSelect có sẵn sàng không
    if (typeof SlimSelect === 'undefined' || !SS.ward) {
        const wardSelect = document.querySelector(SELECTORS.ward);
        if (wardSelect) {
            wardSelect.innerHTML = '<option value="">Chọn Xã/Phường</option>';
            wardSelect.disabled = false;
        }
        
        if (!res.ok || !res.data?.data) {
            console.error("Lỗi load xã/phường:", res.message);
            return;
        }
        
        const list = toArray(res.data?.data);
        list.forEach((w) => {
            const option = document.createElement('option');
            option.value = String(w.wardCode ?? w.WardCode ?? w.ward_code);
            option.textContent = w.wardName ?? w.WardName;
            wardSelect.appendChild(option);
        });
        return;
    }

    resetSelect(SS.ward, "Chọn Xã/Phường");
    SS.ward.enable();

    if (!res.ok) {
            console.error("Lỗi load xã/phường:", res.message);
        return;
    }

    const list = toArray(res.data?.data);
    const options = list.map((w) => ({
        text: w.wardName ?? w.WardName,
        value: String(w.wardCode ?? w.WardCode ?? w.ward_code),
    }));

    SS.ward.setData([{ text: "Chọn Xã/Phường", value: "" }, ...options]);
    SS.ward.setSelected([]);
    } catch (error) {
        console.error('Error in getWard:', error);
    }
}

// ================== HANDLERS ==================
async function onProvinceChange(el) {
    const value = el.value;
    if (hasSelection(value)) {
        // Lấy ID chính xác từ GHN (value đã là ID từ GHN)
        dataMain.provinceId = parseInt(value, 10);
        // Cập nhật hidden field trong form
        const provinceInput = document.getElementById('checkout_province_id') || document.querySelector('input[name="provinceId"]');
        if (provinceInput) provinceInput.value = dataMain.provinceId;
        
        if (typeof toggleFormOverlay === "function") showPageLoader();
        await getDistrict(value);
        if (typeof toggleFormOverlay === "function") hidePageLoader();
        updateHiddenLocationName(el, 'checkout_province_name');
    } else {
        resetSelect(SS.district, "Chọn Quận/Huyện");
        resetSelect(SS.ward, "Chọn Xã/Phường");
        SS.district.disable();
        SS.ward.disable();
        dataMain.provinceId = null;
        const provinceInput = document.getElementById('checkout_province_id') || document.querySelector('input[name="provinceId"]');
        if (provinceInput) provinceInput.value = '';
        updateHiddenLocationName(el, 'checkout_province_name');
    }

    // Reset shipping fee khi thay đổi tỉnh/thành
    dataMain.shipping_fee = 0;
    dataMain.shipping_fee_original = 0;
    const shippingHidden = document.getElementById('checkout_shipping_fee_value');
    const shippingOriginalHidden = document.getElementById('checkout_shipping_fee_original');
    if (shippingHidden) {
        shippingHidden.value = 0;
    }
    if (shippingOriginalHidden) {
        shippingOriginalHidden.value = 0;
    }
    totalAmount();
    updatePlaceOrderState();
    updatePlaceOrderState();
    
    // Cập nhật trạng thái voucher input (disable vì chưa có shipping fee)
    if (typeof updateVoucherInputState === 'function') {
        updateVoucherInputState();
    }
    
    // Revalidate voucher nếu đang có (có thể sẽ fail vì shipping = 0)
    if (typeof revalidateVoucher === 'function') {
        await revalidateVoucher();
    }
}

async function onDistrictChange(el) {
    const value = el.value;
    if (hasSelection(value)) {
        // Lấy ID chính xác từ GHN (value đã là ID từ GHN)
        dataMain.districtId = parseInt(value, 10);
        // Cập nhật hidden field trong form
        const districtInput = document.getElementById('checkout_district_id') || document.querySelector('input[name="districtId"]');
        if (districtInput) districtInput.value = dataMain.districtId;
        
        if (typeof toggleFormOverlay === "function") showPageLoader();
        await getWard(value);
        if (typeof toggleFormOverlay === "function") hidePageLoader();
        updateHiddenLocationName(el, 'checkout_district_name');
    } else {
        resetSelect(SS.ward, "Chọn Xã/Phường");
        SS.ward.disable();
        dataMain.districtId = null;
        const districtInput = document.getElementById('checkout_district_id') || document.querySelector('input[name="districtId"]');
        if (districtInput) districtInput.value = '';
        updateHiddenLocationName(el, 'checkout_district_name');
    }

    // Reset shipping fee khi thay đổi quận/huyện
    dataMain.shipping_fee = 0;
    const shippingHidden = document.getElementById('checkout_shipping_fee_value');
    const shippingOriginalHidden = document.getElementById('checkout_shipping_fee_original');
    if (shippingHidden) {
        shippingHidden.value = 0;
    }
    if (shippingOriginalHidden) {
        shippingOriginalHidden.value = 0;
    }
    totalAmount();
    
    // Cập nhật trạng thái voucher input
    if (typeof updateVoucherInputState === 'function') {
        updateVoucherInputState();
    }
    
    // Revalidate voucher nếu đang có
    if (typeof revalidateVoucher === 'function') {
        await revalidateVoucher();
    }
}

function totalAmount(overrideShipping = null) {
    const subtotalInput = document.getElementById('checkout_subtotal_value');
    const totalInput = document.getElementById('checkout_total_value');
    const summarySubtotal = document.getElementById('summary-subtotal');
    const summaryShipping = document.getElementById('summary-shipping');
    const summaryTotal = document.getElementById('summary-total');
    const voucherDiscountRow = document.getElementById('voucher_discount_row');
    const voucherDiscountEl = document.getElementById('checkout_voucher_discount');
    const shippingHidden = document.getElementById('checkout_shipping_fee_value');

    const subtotal = parseFloat(subtotalInput?.value ?? dataMain.subtotal ?? '0') || 0;
    const shipping = overrideShipping !== null
        ? Number(overrideShipping)
        : Number(dataMain.shipping_fee ?? 0);
    const voucherDiscount = appliedVoucher ? Number(appliedVoucher.discount_amount) : 0;
    const grandTotal = Math.max(subtotal + shipping - voucherDiscount, 0);

    if (summarySubtotal) {
        summarySubtotal.textContent = `${formatCurrencyVND(subtotal)}₫`;
    }
    if (summaryShipping) {
        summaryShipping.textContent = `${formatCurrencyVND(shipping)}₫`;
    }
    if (summaryTotal) {
        summaryTotal.textContent = `${formatCurrencyVND(grandTotal)}₫`;
    }

    if (voucherDiscountRow) {
        voucherDiscountRow.style.display = voucherDiscount ? 'flex' : 'none';
    }
    if (voucherDiscountEl) {
        voucherDiscountEl.textContent = voucherDiscount ? `-${formatCurrencyVND(voucherDiscount)}₫` : '0₫';
    }

    if (shippingHidden) {
        shippingHidden.value = shipping;
    }
    if (totalInput) {
        totalInput.value = grandTotal;
    }

    dataMain.subtotal = subtotal;
  dataMain.shipping_fee = shipping;
  dataMain.voucher_discount = voucherDiscount;
  dataMain.total = grandTotal;
}

async function onWardChange(el) {
    const value = el.value;
    if (hasSelection(value)) {
        // Lấy WardCode chính xác từ GHN (value đã là WardCode từ GHN)
        dataMain.wardId = String(value); // WardCode có thể là string
        // Cập nhật hidden field trong form
        const wardInput = document.getElementById('checkout_ward_id') || document.querySelector('input[name="wardId"]');
        if (wardInput) wardInput.value = dataMain.wardId;
        updateHiddenLocationName(el, 'checkout_ward_name');
        
        showPageLoader();
        try {
            const districtId = dataMain.districtId
                || parseInt(document.getElementById('checkout_district_id')?.value ?? '', 10)
                || null;

            if (!districtId || Number.isNaN(districtId)) {
                throw new Error('district_id_missing');
            }

            const url = `/api/v1/ghn/services/${encodeURIComponent(districtId)}`;
            const res = await fetch(url, { 
                method: "GET",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                }
            });

            if (!res.ok) {
                const errorText = await res.text();
                console.error('Services error response:', errorText);
                throw new Error(`HTTP ${res.status}: ${errorText}`);
            }

            const json = await res.json();

            if (json && json.data && Array.isArray(json.data)) {
                // Tìm dịch vụ "Hàng nhẹ"
                const lightService = json.data.find(
                    (s) => s.shortName === "Hàng nhẹ"
                );
                const heavyService = json.data.find(
                    (s) => s.shortName === "Hàng nặng"
                );

                if (lightService) {
                    // ✅ Có dịch vụ hàng nhẹ → dùng nó
                    dataMain.serviceId = lightService.serviceId || lightService.service_id;
                    dataMain.serviceTypeId = lightService.serviceTypeId || lightService.service_type_id;
                    
                    // Cập nhật hidden fields
                    const serviceIdInput = document.getElementById('checkout_service_id');
                    const serviceTypeIdInput = document.getElementById('checkout_service_type_id');
                    if (serviceIdInput) serviceIdInput.value = dataMain.serviceId;
                    if (serviceTypeIdInput) serviceTypeIdInput.value = dataMain.serviceTypeId;

                } else if (heavyService) {
                    // ⚙️ Không có hàng nhẹ → fallback sang hàng nặng
                    dataMain.serviceId = heavyService.serviceId || heavyService.service_id;
                    dataMain.serviceTypeId = heavyService.serviceTypeId || heavyService.service_type_id;
                    
                    // Cập nhật hidden fields
                    const serviceIdInput = document.getElementById('checkout_service_id');
                    const serviceTypeIdInput = document.getElementById('checkout_service_type_id');
                    if (serviceIdInput) serviceIdInput.value = dataMain.serviceId;
                    if (serviceTypeIdInput) serviceTypeIdInput.value = dataMain.serviceTypeId;

                    // Hiển thị cảnh báo thân thiện cho người dùng
                    showCustomToast(
                        "Không có phương thức 'Hàng nhẹ', hệ thống tự động chuyển sang 'Hàng nặng'.",
                        "warning"
                    );
                } else {
                    // ❌ Không có bất kỳ dịch vụ nào
                    showCustomToast(
                        "Hiện tại GHN chưa hỗ trợ giao hàng đến khu vực này.",
                        "error"
                    );
                }

                if (
                    dataMain &&
                    typeof dataMain.serviceId === "number" &&
                    dataMain.serviceId > 0 &&
                    typeof dataMain.serviceTypeId === "number" &&
                    dataMain.serviceTypeId > 0
                ) {
                    try {
                        const url = `/api/v1/ghn/calculate-fee`;
                        const requestData = {
                                items: dataMain.items || [],              // danh sách sản phẩm
                                provinceId: dataMain.provinceId || null,  // nếu có
                                districtId: dataMain.districtId || null,  // ID quận/huyện
                                wardId: dataMain.wardId || null,          // ID phường/xã
                                serviceId: dataMain.serviceId || null,    // ID dịch vụ GHN (vd: 53322)
                                serviceTypeId: dataMain.serviceTypeId || 2, // mặc định hàng nhẹ
                                subtotal: dataMain.subtotal || 0,         // tạm tính
                                total: dataMain.total || 0,               // tổng thanh toán (để làm insurance_value)
                                payment: dataMain.payment || "cod",       // phương thức thanh toán
                        };
                        
                        const res = await fetch(url, {
                            method: "POST",
                            headers: { 
                                "Content-Type": "application/json",
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify(requestData),
                        });

                        if (!res.ok) {
                            const errorText = await res.text();
                            console.error('Calculate fee error response:', errorText);
                            throw new Error(`HTTP ${res.status}: ${errorText}`);
                        }

                        const json = await res.json();
                        

                        // Xử lý nếu thành công
                        if (json?.code === 200 && json?.data && typeof json.data === "object") {
                            const shippingFee = Math.round(Number(json.data?.total ?? 0) / 1000) * 1000;
                            const shippingLabel = json?.data?.serviceName
                                || json?.data?.service_fee?.service_name
                                || 'GHN - Giao hàng tiêu chuẩn';
                            
                            const shippingInput = document.getElementById('checkout_shipping_value');
                            const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
                            const shippingOriginalInput = document.getElementById('checkout_shipping_fee_original');
                            const shippingLabelInput = document.getElementById('checkout_shipping_label');
                            if (shippingInput) shippingInput.value = shippingFee;
                            if (shippingFeeInput) shippingFeeInput.value = shippingFee;
                            if (shippingOriginalInput) shippingOriginalInput.value = shippingFee;
                            if (shippingLabelInput) shippingLabelInput.value = shippingLabel;
                            
                            dataMain.shipping_fee_original = shippingFee;
                            dataMain.shipping_fee = shippingFee;
                            
                            const shippingContainer = document.querySelector('.xanhworld_main_checkout_options');
                            if (shippingContainer) {
                                shippingContainer.innerHTML = `
                                    <label class="radio-card active">
                                        <input value="${shippingFee}" type="radio" name="shipping" data-label="${shippingLabel}" checked />
                                        <div>
                                            <strong>${shippingLabel}</strong>
                                            <p style="margin:4px 0 0;color:#6b7280;font-size:14px;">Phí dự kiến: ${formatCurrencyVND(shippingFee)}đ</p>
                                        </div>
                                    </label>
                                `;
                            }

                            if (typeof updateVoucherInputState === 'function') {
                                updateVoucherInputState();
                            }
                            
                            totalAmount();
                            updatePlaceOrderState();
                            
                            // Revalidate voucher nếu đang có (để tính lại discount với shipping fee mới)
                            if (typeof revalidateVoucher === 'function') {
                                await revalidateVoucher();
                            } else {
                                // Nếu chưa có voucher, chỉ cập nhật total
                                totalAmount();
                            }
                            
                            showCustomToast(
                              "Đã lấy được phương thức giao hàng 🚚. Vui lòng chọn cách giao phù hợp nhé!",
                              "success"
                            );
                        }
                    } catch (err) {
                        console.error("❌ Lỗi khi gọi GHN:", err);
                        showCustomToast(
                            "Có lỗi xảy ra trong quá trình xử lý dữ liệu vận chuyển 🚚. Hệ thống đang khắc phục!",
                            "error"
                        );
                    }
                } else {
                    // ❌ Thiếu hoặc sai định dạng
                }
            } else {
                showCustomToast(
                    "Không lấy được danh sách dịch vụ từ GHN.",
                    "error"
                );
            }
        } catch (err) {
            console.error("Geocode error:", err);
            const box = ensureDropdown();
            box.innerHTML = `<p style="padding:8px;color:red;">Lỗi tải địa chỉ</p>`;
            box.style.display = "block";
        } finally {
            if (typeof toggleFormOverlay === "function")
                hidePageLoader();
        }
    } else {
        document.querySelector(
            ".xanhworld_main_checkout_options"
        ).innerHTML = `
              <div style="
                  padding: 16px;
                  border: 1px dashed #d0d0d0;
                  border-radius: 10px;
                  background: #fafafa;
                  text-align: center;
                  color: #ff0000ff;
                  font-size: 15px;
                  line-height: 1.6;
                  font-family: 'Segoe UI', Roboto, sans-serif;
                  margin-top: 10px;
              ">
                  🚚 <strong>Chưa có phương thức giao hàng</strong><br>
                  <span style="color:#666;">
                    Vui lòng chọn <b>Tỉnh/Thành</b>, <b>Quận/Huyện</b> và <b>Xã/Phường</b> 
                    để hiển thị các lựa chọn giao hàng phù hợp nhé 💌
                  </span>
              </div>
            `;
    }
}

// ================== VALIDATION ==================
function validateFormCheckout() {
    const form = getDOMElement(SELECTORS.form_checkout);
    if (!form) return false;

    let firstNoticeShown = false;
    let isValid = true;

    const showOnce = (msg, el, focusable = true) => {
        if (!firstNoticeShown) {
            showCustomToast(msg, "error");
            if (focusable && el?.focus) el.focus();
            else el?.scrollIntoView({ behavior: "smooth", block: "center" });
            firstNoticeShown = true;
        }
    };

    const markError = (el, hasError) => {
        if (!el) return;
        el.classList.toggle("error", !!hasError);
    };

    // === 1. INPUT / TEXTAREA ===
    const fullname = form.querySelector('input[name="fullname"]');
    const email = form.querySelector('input[name="email"]');
    const phone = form.querySelector('input[name="phone"]');
    const address = form.querySelector('input[name="address"]');

    const nameVal = fullname?.value.trim() || "";
    const emailVal = email?.value.trim() || "";
    const phoneVal = phone?.value.trim() || "";
    const addrVal = address?.value.trim() || "";

    // --- Họ tên ---
    const nameRegex = /^[A-Za-zÀ-ỹ\s'.-]+$/;
    if (!nameVal) {
        markError(fullname, true);
        showOnce("Vui lòng nhập Họ và tên", fullname);
        isValid = false;
    } else if (nameVal.length < 4 || !nameRegex.test(nameVal)) {
        markError(fullname, true);
        showOnce(
            "Họ và tên không hợp lệ (ít nhất 4 ký tự, không chứa số)",
            fullname
        );
        isValid = false;
    } else {
        dataMain.fullname = nameVal;
        markError(fullname, false);
    }

    // --- Email ---
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailVal) {
        markError(email, true);
        showOnce("Vui lòng nhập Email", email);
        isValid = false;
    } else if (!emailRegex.test(emailVal)) {
        markError(email, true);
        showOnce("Email không hợp lệ", email);
        isValid = false;
    } else {
        dataMain.email = emailVal;
        markError(email, false);
    }

    // --- SĐT ---
    const phoneRegex = /^(0|\+84)\d{9}$/;
    if (!phoneVal) {
        markError(phone, true);
        showOnce("Vui lòng nhập Số điện thoại", phone);
        isValid = false;
    } else if (!phoneRegex.test(phoneVal)) {
        markError(phone, true);
        showOnce("Số điện thoại không hợp lệ", phone);
        isValid = false;
    } else {
        dataMain.phone = phoneVal;
        markError(phone, false);
    }

    // --- Địa chỉ ---
    if (!addrVal) {
        markError(address, true);
        showOnce("Vui lòng nhập Địa chỉ", address);
        isValid = false;
    } else {
        dataMain.address = addrVal;
        markError(address, false);
    }

    // --- Ghi chú đơn hàng ---
    const customerNote = form.querySelector('textarea[name="customer_note"]');
    const noteVal = customerNote?.value.trim() || "";
    dataMain.customer_note = sanitizeInput(noteVal);

    // === 2. GHN Selects ===
    const province = getDOMElement(SELECTORS.province);
    const district = getDOMElement(SELECTORS.district);
    const ward = getDOMElement(SELECTORS.ward);
    

    const slimError = (sel, msg) => {
        const slimContainer = sel?.closest(".ss-main");
        if (slimContainer) slimContainer.classList.add("error");
        if (!firstNoticeShown) {
            showCustomToast(msg, "error");
            slimContainer?.scrollIntoView({
                behavior: "smooth",
                block: "center",
            });
            firstNoticeShown = true;
        }
        isValid = false;
    };

    const slimClear = (sel) =>
        sel?.closest(".ss-main")?.classList.remove("error");

    // Lấy value từ SlimSelect hoặc native select
    const getProvinceValue = () => {
        if (SS.province && typeof SS.province.selected === "function") {
            const selected = SS.province.selected();
            if (selected && selected.length > 0) {
                return selected[0];
            }
        }
        if (province && typeof province.value !== "undefined") {
            return province.value;
        }
        return document.getElementById("checkout_province_id")?.value || null;
    };
    
    const getDistrictValue = () => {
        if (SS.district && typeof SS.district.selected === "function") {
            const selected = SS.district.selected();
            if (selected && selected.length > 0) {
                return selected[0];
            }
        }
        if (district && typeof district.value !== "undefined") {
            return district.value;
        }
        return document.getElementById("checkout_district_id")?.value || null;
    };
    
    const getWardValue = () => {
        if (SS.ward && typeof SS.ward.selected === "function") {
            const selected = SS.ward.selected();
            if (selected && selected.length > 0) {
                return selected[0];
            }
        }
        if (ward && typeof ward.value !== "undefined") {
            return ward.value;
        }
        return document.getElementById("checkout_ward_id")?.value || null;
    };

    const provinceValue = getProvinceValue();
    const districtValue = getDistrictValue();
    const wardValue = getWardValue();

    if (!provinceValue || provinceValue === "null" || provinceValue === "") {
      slimError(province, "Vui lòng chọn Tỉnh/Thành phố");
      totalAmount();
    } else {
        slimClear(province);
        dataMain.provinceId = parseInt(provinceValue, 10);
    }

    if (!districtValue || districtValue === "null" || districtValue === "") {
      slimError(district, "Vui lòng chọn Quận/Huyện");
      totalAmount();
    } else {
        slimClear(district);
        dataMain.districtId = parseInt(districtValue, 10);
    }

    if (!wardValue || wardValue === "null" || wardValue === "") {
        slimError(ward, "Vui lòng chọn Xã/Phường");
        totalAmount();
    } else {
        slimClear(ward);
        dataMain.wardId = String(wardValue); // WardCode có thể là string
    }

    // === 3. RADIO ===
    const shipping = form.querySelectorAll('input[name="shipping"]');
    const payment = form.querySelectorAll('input[name="payment"]');

    const radioGroups = [
        { key: "shipping", name: "Phương thức giao hàng", radios: shipping },
        { key: "payment", name: "Phương thức thanh toán", radios: payment },
    ];

    radioGroups.forEach(({ key, name, radios }) => {
        const checkedRadio = Array.from(radios).find((r) => r.checked);

        if (!checkedRadio) {
            // ❌ Chưa chọn radio nào
            radios.forEach((r) => r.closest("label")?.classList.add("error"));
            showOnce(`Vui lòng chọn ${name.toLowerCase()}`, radios[0], false);
            isValid = false;
        } else {
            // ✅ Đã chọn radio → bỏ class lỗi và gán value
            radios.forEach((r) =>
                r.closest("label")?.classList.remove("error")
            );
            const value = checkedRadio.value;
            dataMain[key] = value; // 🟢 Lưu giá trị vào object
            
            // Cập nhật hidden field cho shipping
            if (key === 'shipping') {
                const shippingInput = document.getElementById('checkout_shipping_value');
                if (shippingInput) shippingInput.value = value;
            }
        }
    });
    collectCartItems();
    
    // Đảm bảo các ID từ GHN được gửi đúng
    const provinceInput = document.getElementById('checkout_province_id') || form.querySelector('input[name="provinceId"]');
    const districtInput = document.getElementById('checkout_district_id') || form.querySelector('input[name="districtId"]');
    const wardInput = document.getElementById('checkout_ward_id') || form.querySelector('input[name="wardId"]');
    
    if (provinceInput && dataMain.provinceId) {
        provinceInput.value = dataMain.provinceId;
    }
    if (districtInput && dataMain.districtId) {
        districtInput.value = dataMain.districtId;
    }
    if (wardInput && dataMain.wardId) {
        wardInput.value = dataMain.wardId;
    }
    
    // Cập nhật các hidden fields khác
    const serviceIdInput = document.getElementById('checkout_service_id');
    const serviceTypeIdInput = document.getElementById('checkout_service_type_id');
    const shippingInput = document.getElementById('checkout_shipping_value');
    const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
    const subtotalInput = document.getElementById('checkout_subtotal_value');
    const totalInput = document.getElementById('checkout_total_value');
    
    if (serviceIdInput && dataMain.serviceId) serviceIdInput.value = dataMain.serviceId;
    if (serviceTypeIdInput && dataMain.serviceTypeId) serviceTypeIdInput.value = dataMain.serviceTypeId;
    if (shippingInput && dataMain.shipping) shippingInput.value = dataMain.shipping;
    if (shippingFeeInput && dataMain.shipping_fee) shippingFeeInput.value = dataMain.shipping_fee;
    if (subtotalInput && dataMain.subtotal) subtotalInput.value = dataMain.subtotal;
    if (totalInput && dataMain.total) totalInput.value = dataMain.total;

    const shippingOriginalVal = parseFloat(document.getElementById('checkout_shipping_fee_original')?.value ?? '0');
    if (!shippingOriginalVal || shippingOriginalVal <= 0) {
        showOnce("Vui lòng chọn địa chỉ để hệ thống lấy phí vận chuyển trước khi đặt hàng.", document.querySelector('.xanhworld_main_checkout_options'), false);
        isValid = false;
    }

    return isValid;
}

// ================== GỢI Ý ĐỊA CHỈ (GEOCODE API - dùng GET ?q=) ==================
function setupAddressAutocomplete() {
    const input = document.getElementById(
        "xanhworld_main_checkout_form_address"
    );
    const addressWrapper = document.querySelector(
        ".xanhworld_main_checkout_form_address"
    );
    if (!input || !addressWrapper) return;

    let dropdown = null;

    // Hàm tạo box dropdown khi cần
    const ensureDropdown = () => {
        if (!dropdown) {
            dropdown = document.createElement("div");
            dropdown.className = "xanhworld_main_checkout_form_address_all";
            addressWrapper.appendChild(dropdown);
        }
        return dropdown;
    };

    let debounceTimer;

    input.addEventListener("input", () => {
        clearTimeout(debounceTimer);
        const query = input.value.trim();

        // Nếu trống => ẩn gợi ý
        if (!query) {
            if (dropdown) dropdown.style.display = "none";
            return;
        }

        debounceTimer = setTimeout(async () => {
            const box = ensureDropdown();
            box.style.display = "block";
            box.innerHTML = `<p style="padding:8px;color:#999;">Đang tìm kiếm...</p>`;

            if (typeof toggleFormOverlay === "function")
                showPageLoader();

            try {
                const url = `/api/v1/general/geocode?q=${encodeURIComponent(
                    query
                )}`;
                const res = await fetch(url, { method: "GET" });
                if (!res.ok) throw new Error(`HTTP ${res.status}`);

                const data = await res.json();

                // HERE API trả về { items: [ { address: { label: "..." } } ] }
                const items = Array.isArray(data.items) ? data.items : [];

                if (!items.length) {
                    box.innerHTML = `<p style="padding:8px;color:#999;">Không tìm thấy địa chỉ phù hợp</p>`;
                    box.style.display = "block";
                    return;
                }

                // Hiển thị danh sách gợi ý — mỗi item 1 dòng
                box.innerHTML = items
                    .map(
                        (item) =>
                            `<p class="xanhworld_main_checkout_form_address_item">${item.address.label}</p>`
                    )
                    .join("");

                box.style.display = "block";

                // Sự kiện click chọn địa chỉ
                box.querySelectorAll(
                    ".xanhworld_main_checkout_form_address_item"
                ).forEach((el) => {
                    el.addEventListener("click", () => {
                        input.value = el.textContent.trim();
                        box.style.display = "none";
                    });
                });
            } catch (err) {
                if (err.message === 'district_id_missing') {
                    showCustomToast('Vui lòng chọn Quận/Huyện hợp lệ trước khi chọn phường.', 'error');
                    return;
                }

                let displayMessage = 'Không thể lấy dịch vụ GHN cho khu vực này.';
                if (err.message?.startsWith('HTTP')) {
                    try {
                        const [, payload] = err.message.split(':', 2);
                        const parsed = JSON.parse(payload.trim());
                        displayMessage = parsed.message || displayMessage;
                    } catch (_) {
                        // ignore parse error
                    }
                }
                console.error("GHN services error:", err);
                showCustomToast(displayMessage, 'error');
            } finally {
                if (typeof toggleFormOverlay === "function")
                    hidePageLoader();
            }
        }, 500);
    });

    // Click ra ngoài => ẩn dropdown
    // document.addEventListener('click', (e) => {
    //   if (!addressWrapper.contains(e.target)) {
    //     if (dropdown) dropdown.style.display = 'none';
    //   }
    // });
}

function setFieldValue(element, value = "") {
    if (!element) {
        return;
    }
    element.value = value ?? "";
    element.dispatchEvent(new Event("input", { bubbles: true }));
    element.classList.remove("error");
}

async function applySavedAddressPayload(payload = {}) {
    const requiredCodes = ["province_code", "district_code", "ward_code"];
    const missing = requiredCodes.filter((key) => !payload[key]);

    if (missing.length) {
        throw new Error("missing_codes");
    }

    setFieldValue(document.getElementById("fullname"), payload.full_name ?? "");
    setFieldValue(document.getElementById("phone"), payload.phone_number ?? "");
    setFieldValue(document.getElementById("address"), payload.detail_address ?? "");

    const provinceHidden = document.getElementById("checkout_province_name");
    const districtHidden = document.getElementById("checkout_district_name");
    const wardHidden = document.getElementById("checkout_ward_name");

    if (provinceHidden) provinceHidden.value = payload.province ?? "";
    if (districtHidden) districtHidden.value = payload.district ?? "";
    if (wardHidden) wardHidden.value = payload.ward ?? "";

    const postalInput = document.querySelector('input[name="postal_code"]');
    const countryInput = document.querySelector('input[name="country"]');
    if (postalInput) postalInput.value = payload.postal_code ?? "00000";
    if (countryInput) countryInput.value = payload.country ?? "Việt Nam";

    const provinceInput = document.getElementById("checkout_province_id");
    const districtInput = document.getElementById("checkout_district_id");
    const wardInput = document.getElementById("checkout_ward_id");

    if (provinceInput) provinceInput.value = payload.province_code ?? "";
    if (districtInput) districtInput.value = payload.district_code ?? "";
    if (wardInput) wardInput.value = payload.ward_code ?? "";

    const provinceSelect = document.querySelector(SELECTORS.province);
    const districtSelect = document.querySelector(SELECTORS.district);
    const wardSelect = document.querySelector(SELECTORS.ward);

    if (!provinceSelect || !districtSelect || !wardSelect) {
        throw new Error("select_missing");
    }

    const assignSelectValue = (selectEl, value) => {
        if (!selectEl) {
            return;
        }
        const normalized = value !== undefined && value !== null ? String(value) : "";
        selectEl.value = normalized;
        if (selectEl._slim && typeof selectEl._slim.setSelected === "function") {
            selectEl._slim.setSelected(normalized);
        }
    };

    assignSelectValue(provinceSelect, payload.province_code);
    await onProvinceChange(provinceSelect);

    assignSelectValue(districtSelect, payload.district_code);
    await onDistrictChange(districtSelect);

    assignSelectValue(wardSelect, payload.ward_code);
    await onWardChange(wardSelect);
}

function setupSavedAddressPicker() {
    const buttons = Array.from(document.querySelectorAll(".saved-address"));
    if (!buttons.length) {
        return;
    }

    const toast = (message, type = "info") => {
        if (typeof showCustomToast === "function") {
            showCustomToast(message, type);
        } else {
            console.log(`[${type}] ${message}`);
        }
    };

    const clearActiveState = () => {
        buttons.forEach((btn) => btn.classList.remove("active"));
    };

    buttons.forEach((button) => {
        button.addEventListener("click", async () => {
            const payloadRaw = button.getAttribute("data-address");
            if (!payloadRaw) {
                toast("Không thể đọc dữ liệu địa chỉ đã lưu. Vui lòng nhập tay.", "error");
                return;
            }

            let payload;
            try {
                payload = JSON.parse(payloadRaw);
            } catch (error) {
                console.error("Saved address parse error:", error);
                toast("Dữ liệu địa chỉ không hợp lệ. Vui lòng nhập tay.", "error");
                return;
            }

            if (typeof toggleFormOverlay === "function") {
                showPageLoader();
            }

            try {
                await applySavedAddressPayload(payload);
                clearActiveState();
                button.classList.add("active");
                toast("Đã áp dụng địa chỉ giao nhận.", "success");
            } catch (error) {
                console.error("Apply saved address error:", error);
                const message =
                    error?.message === "missing_codes"
                        ? "Địa chỉ này chưa lưu đủ mã GHN. Vui lòng nhập tay để tiếp tục."
                        : "Không thể áp dụng địa chỉ này. Vui lòng nhập tay.";
                toast(message, "error");
            } finally {
                if (typeof toggleFormOverlay === "function") {
                    hidePageLoader();
                }
            }
        });
    });
}

async function processCheckout() {
    const form = getDOMElement(SELECTORS.form_checkout);
    if (!form) {
        console.error('Checkout form not found');
        return;
    }

    showPageLoader();
    isSubmitting = true;

    try {
        const formData = new FormData(form);
        const response = await fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Có lỗi xảy ra, vui lòng thử lại.');
        }

        if (result.success) {
            if (result.payment_method === 'bank_transfer' && result.checkout_url) {
                window.location.href = result.checkout_url;
            } else if (result.payment_method === 'cod' && result.redirect_url) {
                window.location.href = result.redirect_url;
            } else {
                throw new Error('Phản hồi từ máy chủ không hợp lệ.');
            }
        } else {
            throw new Error(result.message || 'Không thể xử lý đơn hàng.');
        }
    } catch (error) {
        console.error('Checkout error:', error);
        showCustomToast(error.message, 'error');
        hidePageLoader();
        isSubmitting = false;
    }
}

// ================== INIT ==================
document.addEventListener("DOMContentLoaded", async () => {
    // Force hide any existing loading overlay first
    hidePageLoader();

    const initialProvinceId = parseInt(document.getElementById('checkout_province_id')?.value ?? '', 10);
    const initialDistrictId = parseInt(document.getElementById('checkout_district_id')?.value ?? '', 10);
    const initialWardId = document.getElementById('checkout_ward_id')?.value ?? '';
    const initialShippingFee = parseFloat(document.getElementById('checkout_shipping_fee_value')?.value ?? '0');
    const initialShippingFeeOriginal = parseFloat(document.getElementById('checkout_shipping_fee_original')?.value ?? '0');

    if (!Number.isNaN(initialProvinceId)) {
        dataMain.provinceId = initialProvinceId;
    }
    if (!Number.isNaN(initialDistrictId)) {
        dataMain.districtId = initialDistrictId;
    }
    if (initialWardId) {
        dataMain.wardId = initialWardId;
    }
    if (!Number.isNaN(initialShippingFeeOriginal)) {
        dataMain.shipping_fee_original = initialShippingFeeOriginal;
    }
    if (!Number.isNaN(initialShippingFee)) {
        dataMain.shipping_fee = initialShippingFee;
    }

    const initialVoucherCode = document.getElementById('voucher_code_input')?.value || '';
    const initialVoucherDiscount = parseFloat(document.getElementById('voucher_discount_input')?.value ?? '0');
    if (initialVoucherCode) {
        appliedVoucher = {
            code: initialVoucherCode,
            discount_amount: initialVoucherDiscount,
        };
    }
    
    // ===== LOAD TỈNH =====
    if (typeof toggleFormOverlay === "function") showPageLoader();
    await getProvince();
    if (typeof toggleFormOverlay === "function") hidePageLoader();

    const form = getDOMElement(SELECTORS.form_checkout);
    const orderBtn = document.querySelector(".checkout-submit");

    if (!form || !orderBtn) {
        console.error('Checkout form (#checkout-form) hoặc nút ".checkout-submit" không tồn tại.');
        return;
    }

    collectCartItems();
    totalAmount();
    updatePlaceOrderState();

    // ===== Khi bấm nút "Đặt hàng" =====
    orderBtn.addEventListener("click", (e) => {
        e.preventDefault();
        if (isSubmitting) {
            return;
        }

        const confirmed = window.confirm('Xác nhận đặt đơn hàng này?');
        if (!confirmed) {
            return;
        }

        const isValid = validateFormCheckout();
        if (!isValid) {
            return;
        }

        processCheckout();
    });

    // ===== Khi người dùng nhập lại thì bỏ viền đỏ =====
    form.addEventListener("input", (e) => {
        if (
            e.target.classList.contains("error") &&
            e.target.value.trim() !== ""
        ) {
            e.target.classList.remove("error");
        }
    });

    setupAddressAutocomplete();
    setupSavedAddressPicker();
    
    // ===== VOUCHER FUNCTIONALITY =====
    setupVoucherHandlers();
    
    // ===== SHIPPING METHOD CHANGE LISTENER =====
    // Listen cho sự kiện thay đổi shipping method (radio button)
    // Sử dụng event delegation để bắt sự kiện từ các radio button được tạo động
    document.addEventListener('change', async function(e) {
        if (e.target.name === 'shipping' && e.target.checked) {
            const shippingFee = parseFloat(e.target.value) || 0;
            dataMain.shipping_fee = shippingFee;
            dataMain.shipping_fee_original = shippingFee;
            
            // Cập nhật hidden field
            const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
            if (shippingFeeInput) shippingFeeInput.value = shippingFee;
            const shippingOriginalInput = document.getElementById('checkout_shipping_fee_original');
            if (shippingOriginalInput) shippingOriginalInput.value = shippingFee;
            const shippingLabelInput = document.getElementById('checkout_shipping_label');
            if (shippingLabelInput) {
                shippingLabelInput.value = e.target.dataset.label || shippingLabelInput.value || 'GHN';
            }
            
            // Cập nhật trạng thái voucher input
            if (typeof updateVoucherInputState === 'function') {
                updateVoucherInputState();
            }
            
            // Revalidate voucher nếu đang có (để tính lại discount với shipping fee mới)
            if (typeof revalidateVoucher === 'function') {
                await revalidateVoucher();
            } else {
                // Nếu chưa có voucher, chỉ cập nhật total
                totalAmount();
            }
        }
    });
    
    // ===== CUSTOMER NOTE COUNTER =====
    setupCustomerNoteCounter();
    
    // ===== ERROR HANDLING =====
    // Hide loading overlay on any error
    window.addEventListener('error', () => {
        hidePageLoader();
    });
    
    // Hide loading overlay on unhandled promise rejection
    window.addEventListener('unhandledrejection', () => {
        hidePageLoader();
    });
});

// ================== VOUCHER FUNCTIONALITY ==================
function setupVoucherHandlers() {
    const voucherCodeInput = document.getElementById('voucher_code');
    const applyVoucherBtn = document.getElementById('apply_voucher_btn');
    const removeVoucherBtn = document.getElementById('remove_voucher_btn');
    const voucherInfoRemoveBtn = document.getElementById('voucher_info_remove');
    const voucherResult = document.getElementById('voucher_result');
    const voucherInfo = document.getElementById('voucher_info');
    const voucherSuggestions = document.getElementById('voucher_suggestions');
    const voucherHint = document.getElementById('voucher_hint');

    if (!voucherCodeInput || !applyVoucherBtn) {
        return;
    }

    const canUseVoucher = window.checkoutConfig?.canUseVoucher ?? true;
    if (!canUseVoucher) {
        return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    const showMessage = (message, type = 'error') => {
        if (!voucherResult) {
            return;
        }
        const successEl = voucherResult.querySelector('.voucher_success');
        const errorEl = voucherResult.querySelector('.voucher_error');

        voucherResult.style.display = 'block';
        if (type === 'success') {
            successEl.style.display = 'block';
            errorEl.style.display = 'none';
            successEl.textContent = message;
        } else {
            successEl.style.display = 'none';
            errorEl.style.display = 'block';
            errorEl.textContent = message;
        }
    };

    const showInfo = (voucherName, discountAmount) => {
        if (!voucherInfo) {
            return;
        }
        voucherInfo.querySelector('.voucher_name').textContent = voucherName;
        const discountEl = voucherInfo.querySelector('.voucher_discount');
        discountEl.textContent = discountAmount > 0 ? `-${formatCurrencyVND(discountAmount)}₫` : '';
        voucherInfo.style.display = 'flex';
        if (removeVoucherBtn) {
            removeVoucherBtn.style.display = 'inline-flex';
        }
        if (voucherSuggestions) {
            voucherSuggestions.style.display = 'none';
        }
    };

    const resetVoucherState = () => {
        appliedVoucher = null;
        document.getElementById('voucher_code_input').value = '';
        document.getElementById('voucher_discount_input').value = 0;
        const info = document.getElementById('voucher_info');
        if (info) {
            info.style.display = 'none';
        }
        if (removeVoucherBtn) {
            removeVoucherBtn.style.display = 'none';
        }
        const result = document.getElementById('voucher_result');
        if (result) {
            result.style.display = 'none';
        }
    };

    function updateVoucherInputState() {
        const shippingFee = dataMain.shipping_fee_original ?? dataMain.shipping_fee ?? 0;
        const hasShipping = shippingFee > 0;
        
        voucherCodeInput.disabled = !hasShipping;
        applyVoucherBtn.disabled = !hasShipping;

        if (hasShipping) {
            voucherCodeInput.removeAttribute('disabled');
            applyVoucherBtn.removeAttribute('disabled');
        } else {
            voucherCodeInput.setAttribute('disabled', 'disabled');
            applyVoucherBtn.setAttribute('disabled', 'disabled');
        }
        
        if (!hasShipping) {
            voucherCodeInput.placeholder = 'Vui lòng chọn địa chỉ giao hàng trước';
            resetVoucherState();
            totalAmount();
        } else {
            voucherCodeInput.placeholder = 'Nhập mã giảm giá (VD: SALE10, WELCOME20)';
        }

        if (voucherHint) {
            voucherHint.textContent = hasShipping
                ? 'Nhập mã giảm giá để được ưu đãi cho đơn hàng này.'
                : 'Chọn tỉnh/thành, quận/huyện, phường/xã để hệ thống lấy phí ship trước khi nhập mã.';
        }
    }

    window.updateVoucherInputState = updateVoucherInputState;
    updateVoucherInputState();

    const hasAddressData = () => {
        const provinceHidden = document.getElementById('checkout_province_id');
        const districtHidden = document.getElementById('checkout_district_id');
        const wardHidden = document.getElementById('checkout_ward_id');
        return Boolean(
            (provinceHidden?.value ?? '').trim() &&
            (districtHidden?.value ?? '').trim() &&
            (wardHidden?.value ?? '').trim()
        );
    };

    const debugVoucherState = () => {
        console.group('Voucher Debug');
        console.log('shipping_fee_original', dataMain.shipping_fee_original);
        console.log('shipping_fee', dataMain.shipping_fee);
        console.log('province_id', document.getElementById('checkout_province_id')?.value);
        console.log('district_id', document.getElementById('checkout_district_id')?.value);
        console.log('ward_id', document.getElementById('checkout_ward_id')?.value);
        console.log('voucher_input', voucherCodeInput?.value);
        console.groupEnd();
    };

    const applyVoucherRequest = async (code, { silent = false } = {}) => {
        debugVoucherState();
        const trimmed = (code || '').trim();
        if (!trimmed) {
            if (!silent) {
                showMessage('Vui lòng nhập mã voucher.', 'error');
            }
            return false;
        }

        if (!hasAddressData()) {
            if (!silent) {
                showMessage('Vui lòng chọn đầy đủ Tỉnh/Quận/Phường trước khi áp dụng voucher.', 'error');
            }
            return false;
        }

        if (!dataMain.shipping_fee_original || dataMain.shipping_fee_original <= 0) {
            if (!silent) {
                showMessage('Vui lòng chọn địa chỉ giao hàng trước khi áp dụng voucher.', 'error');
            }
            return false;
        }

        if (!silent) {
        applyVoucherBtn.classList.add('loading');
        applyVoucherBtn.disabled = true;
            if (typeof toggleFormOverlay === "function") {
                showPageLoader();
            }
        }

        try {
            const orderData = collectCartItems();
            const response = await fetch('/api/v1/vouchers/apply', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({
                    voucher_code: trimmed,
                    order_data: {
                        subtotal: orderData.subtotal,
                        shipping_fee: orderData.shipping_fee_original ?? orderData.shipping_fee ?? 0,
                        shipping_fee_after_discount: orderData.shipping_fee ?? 0,
                        items: orderData.items ?? [],
                    },
                }),
            });

            const result = await response.json();

            if (!response.ok || !result.success) {
                if (!silent) {
                    showMessage(result.message || 'Voucher không hợp lệ.', 'error');
                }
                return false;
            }

            const summary = result.data.summary || {};
            const voucher = result.data.voucher || {};

                appliedVoucher = {
                code: voucher.code,
                discount_amount: Number(result.data.discount || 0),
            };

            dataMain.shipping_fee_original = Number(summary.original_shipping_fee ?? dataMain.shipping_fee_original ?? 0);
            dataMain.shipping_fee = Number(summary.shipping_fee ?? dataMain.shipping_fee ?? 0);

            document.getElementById('checkout_shipping_fee_original').value = dataMain.shipping_fee_original;
            document.getElementById('checkout_shipping_fee_value').value = dataMain.shipping_fee;
            document.getElementById('checkout_shipping_value').value = dataMain.shipping_fee;
            document.getElementById('voucher_code_input').value = voucher.code;
            document.getElementById('voucher_discount_input').value = appliedVoucher.discount_amount;

            showInfo(voucher.name || voucher.code, appliedVoucher.discount_amount);
            if (!silent) {
                showMessage(result.message || 'Áp dụng mã thành công.', 'success');
            }

            totalAmount();
            updatePlaceOrderState();
                voucherCodeInput.value = '';
                return true;
        } catch (error) {
            console.error('Voucher validation error:', error);
            if (!silent) {
                showMessage('Có lỗi xảy ra khi xử lý voucher.', 'error');
            }
            return false;
        } finally {
            if (!silent) {
            applyVoucherBtn.classList.remove('loading');
            applyVoucherBtn.disabled = false;
                if (typeof toggleFormOverlay === "function") {
                    hidePageLoader();
        }
    }
        }
    };

    applyVoucherBtn.addEventListener('click', async () => {
        await applyVoucherRequest(voucherCodeInput.value);
    });

    voucherCodeInput.addEventListener('keypress', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            if (!voucherCodeInput.disabled) {
                applyVoucherBtn.click();
            }
        }
    });

    const removeVoucher = async (silent = false) => {
        try {
            await fetch('/api/v1/vouchers/apply', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                },
            });
        } catch (error) {
            if (!silent) {
                console.error('Remove voucher error:', error);
            }
        } finally {
            if (typeof dataMain.shipping_fee_original !== 'undefined') {
                dataMain.shipping_fee = dataMain.shipping_fee_original;
                const shippingFeeInput = document.getElementById('checkout_shipping_fee_value');
                if (shippingFeeInput) {
                    shippingFeeInput.value = dataMain.shipping_fee;
                }
            }
            resetVoucherState();
            totalAmount();
            updatePlaceOrderState();
            if (!silent) {
                showMessage('Đã hủy mã voucher.', 'success');
            }
        }
    };

    if (removeVoucherBtn) {
        removeVoucherBtn.addEventListener('click', () => removeVoucher());
    }
    if (voucherInfoRemoveBtn) {
        voucherInfoRemoveBtn.addEventListener('click', () => removeVoucher());
    }

    window.revalidateVoucher = async () => {
        if (appliedVoucher?.code) {
            const ok = await applyVoucherRequest(appliedVoucher.code, { silent: true });
            if (!ok) {
                await removeVoucher(true);
            }
        }
    };

    const loadVoucherSuggestions = () => {
        if (!voucherSuggestions || !dataMain.shipping_fee || dataMain.shipping_fee <= 0) {
            return;
        }
        fetch('/voucher/available', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (!data.success || !data.vouchers || !data.vouchers.length) {
                    voucherSuggestions.style.display = 'none';
                    return;
                }
                const listEl = document.getElementById('voucher_suggestions_list');
                if (!listEl) {
                    return;
                }
                listEl.innerHTML = '';
                data.vouchers.slice(0, 3).forEach((voucher) => {
                    const wrapper = document.createElement('div');
                    wrapper.className = 'voucher-suggestion-item';
                    wrapper.style.cssText = 'padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 6px; background: #f9f9f9; cursor: pointer; transition: all 0.2s;';
                    wrapper.innerHTML = `
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <div>
                                <strong style="color:#FF3366;">${voucher.code}</strong>
                                ${voucher.name ? `<div style="font-size:12px;color:#666;">${voucher.name}</div>` : ''}
                            </div>
                            <button type="button" class="btn-apply-suggestion" data-code="${voucher.code}" 
                                style="padding:4px 12px;background:#FF3366;color:#fff;border:none;border-radius:4px;font-size:12px;cursor:pointer;">
                                Áp dụng
                            </button>
                        </div>
                    `;
                    wrapper.querySelector('.btn-apply-suggestion').addEventListener('click', async (e) => {
                        e.stopPropagation();
                        const code = e.target.getAttribute('data-code');
                        voucherCodeInput.value = code;
                        await applyVoucherRequest(code);
                    });
                    listEl.appendChild(wrapper);
                });
                voucherSuggestions.style.display = 'block';
        })
            .catch((error) => console.error('Error loading voucher suggestions:', error));
    };

            if (dataMain.shipping_fee > 0) {
                loadVoucherSuggestions();
            }
}

// ================== CUSTOMER NOTE COUNTER ==================
function setupCustomerNoteCounter() {
    const noteTextarea = document.querySelector('textarea[name="customer_note"]');
    const counter = document.getElementById('note-counter');
    
    if (!noteTextarea || !counter) return;
    
    function updateCounter() {
        const length = noteTextarea.value.length;
        counter.textContent = length;
        
        // Change color based on length
        if (length > 450) {
            counter.style.color = '#dc2626';
        } else if (length > 400) {
            counter.style.color = '#f59e0b';
        } else {
            counter.style.color = '#666';
        }
    }
    
    // Update on input
    noteTextarea.addEventListener('input', updateCounter);
    
    // Initial update
    updateCounter();
}

// ================== XSS PROTECTION ==================
function sanitizeInput(input) {
    if (typeof input !== 'string') return input;
    
    // Remove HTML tags
    let sanitized = input.replace(/<[^>]*>/g, '');
    
    // Remove script tags and javascript: protocols
    sanitized = sanitized.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
    sanitized = sanitized.replace(/javascript:/gi, '');
    
    // Remove event handlers
    sanitized = sanitized.replace(/on\w+\s*=/gi, '');
    
    // Remove potential SQL injection patterns
    sanitized = sanitized.replace(/['";]/g, '');
    
    // Remove potential XSS patterns
    sanitized = sanitized.replace(/[<>]/g, '');
    
    return sanitized.trim();
}

