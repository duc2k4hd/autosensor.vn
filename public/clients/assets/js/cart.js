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

document.addEventListener('DOMContentLoaded', () => {
    const formatCurrency = (value) =>
        new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND',
            maximumFractionDigits: 0,
        }).format(value);

    const rows = document.querySelectorAll('.xanhworld_cart_item');
    const subtotalEl = document.querySelector('.xanhworld_cart_summary_row_subtotal');
    const totalEl = document.querySelector('.xanhworld_cart_summary_amount');

    const recalcSummary = () => {
        let sum = 0;
        rows.forEach((row) => {
            const input = row.querySelector('.xanhworld_cart_item_quantity_input');
            if (! input) {
                return;
            }
            const qty = Math.max(parseInt(input.value, 10) || 0, 0);
            const price = parseFloat(row.dataset.unitPrice || '0');
            sum += price * qty;
        });

        if (subtotalEl) {
            subtotalEl.textContent = formatCurrency(sum);
    }
        if (totalEl) {
            totalEl.textContent = formatCurrency(sum);
            totalEl.setAttribute('data-amount', `${sum}`);
        }
    };

    const clampValue = (input) => {
        let value = parseInt(input.value, 10);
        if (isNaN(value) || value < 0) {
            value = 0;
        }
        const maxAttr = input.dataset.maxQuantity;
        const max = maxAttr === '' ? null : parseInt(maxAttr, 10);
        if (max !== null && ! Number.isNaN(max) && value > max) {
            value = max;
            }

        input.value = value;
        return value;
    };

    const updateRow = (row) => {
        const input = row.querySelector('.xanhworld_cart_item_quantity_input');
        const totalCell = row.querySelector('.xanhworld_cart_item_total');
        const stockBadge = row.querySelector('.xanhworld_cart_item_stock_notice');

        if (! input) {
            return;
        }

        const value = clampValue(input);
        const unitPrice = parseFloat(row.dataset.unitPrice || '0');

        if (totalCell) {
            totalCell.textContent = formatCurrency(unitPrice * value);
        }

        if (stockBadge) {
            const maxAttr = input.dataset.maxQuantity;
            const max = maxAttr === '' ? null : parseInt(maxAttr, 10);
            if (max !== null && ! Number.isNaN(max)) {
                const remaining = Math.max(max - value, 0);
                stockBadge.textContent = remaining;
            }
        }

        recalcSummary();
    };

    const updateForm = document.getElementById('cart-update-form');
    let updateTimeout = null;

    if (!updateForm) {
        console.warn('[Cart] Update form not found!');
    }

    const autoUpdateCart = () => {
        // Clear previous timeout
        if (updateTimeout) {
            clearTimeout(updateTimeout);
        }

        // Auto submit after 1.5 seconds of no changes
        updateTimeout = setTimeout(() => {
            if (updateForm) {
                // Collect all input values before submit
                const formData = new FormData(updateForm);
                const items = {};
                rows.forEach((row) => {
                    const input = row.querySelector('.xanhworld_cart_item_quantity_input');
                    if (input && input.name) {
                        const itemId = input.dataset.itemId || input.name.match(/\[(\d+)\]/)?.[1];
                        const value = parseInt(input.value, 10) || 0;
                        if (itemId) {
                            items[itemId] = value;
                        }
                    }
                });
                
                console.log('[Cart] Auto-updating cart...', {
                    items: items,
                    formData: Object.fromEntries(formData.entries())
                });
                
                // Ensure all inputs are in the form
                rows.forEach((row) => {
                    const input = row.querySelector('.xanhworld_cart_item_quantity_input');
                    if (input && !updateForm.contains(input)) {
                        // Input is outside form, need to add it
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = input.name;
                        hiddenInput.value = input.value;
                        updateForm.appendChild(hiddenInput);
                    }
                });
                
                // Show loading indicator
                const updateBtn = document.querySelector('.xanhworld_cart_update');
                if (updateBtn) {
                    const originalText = updateBtn.innerHTML;
                    updateBtn.disabled = true;
                    updateBtn.innerHTML = 'Đang cập nhật...';
                    
                    // Re-enable after 5 seconds if form doesn't submit
                    setTimeout(() => {
                        if (updateBtn.disabled) {
                            updateBtn.disabled = false;
                            updateBtn.innerHTML = originalText;
                        }
                    }, 5000);
                }
                updateForm.submit();
            } else {
                console.error('[Cart] Update form not found, cannot auto-update');
            }
        }, 1500);
    };

    rows.forEach((row) => {
        const input = row.querySelector('.xanhworld_cart_item_quantity_input');
        const increaseBtn = row.querySelector('.xanhworld_cart_item_quantity_increase');
        const decreaseBtn = row.querySelector('.xanhworld_cart_item_quantity_decrease');

        if (!input) {
            return;
        }

        increaseBtn?.addEventListener('click', () => {
            input.stepUp();
            updateRow(row);
            autoUpdateCart();
        });

        decreaseBtn?.addEventListener('click', () => {
            input.stepDown();
            if (parseInt(input.value, 10) < 0) {
                input.value = 0;
            }
            updateRow(row);
            autoUpdateCart();
        });

        input.addEventListener('change', () => {
            updateRow(row);
            autoUpdateCart();
        });

        input.addEventListener('input', () => {
            updateRow(row);
            autoUpdateCart();
        });

        updateRow(row);
    });

    // Manual update button (if user wants to update manually)
    const manualUpdateBtn = document.querySelector('.xanhworld_cart_update');
    if (manualUpdateBtn && updateForm) {
        manualUpdateBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (updateTimeout) {
                clearTimeout(updateTimeout);
            }
            
            // Ensure all inputs with form attribute are included
            const inputs = document.querySelectorAll('input[name^="items["]');
            inputs.forEach((input) => {
                if (input.getAttribute('form') === 'cart-update-form') {
                    // Create hidden input inside form if input is outside
                    if (!updateForm.contains(input)) {
                        const hiddenInput = document.createElement('input');
                        hiddenInput.type = 'hidden';
                        hiddenInput.name = input.name;
                        hiddenInput.value = input.value;
                        updateForm.appendChild(hiddenInput);
                    }
                }
            });
            
            console.log('[Cart] Manual update triggered', {
                formData: new FormData(updateForm)
            });
            updateForm.submit();
        });
    }
});
