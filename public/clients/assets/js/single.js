document.addEventListener("click", async (e) => {
    // Bỏ qua nếu click vào menu mobile
    if (e.target.closest(".autosensor_header_main_mobile_bars") || 
        e.target.closest(".autosensor_header_mobile_main_nav")) {
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
    const overlay = document.querySelector(".autosensor_loading_overlay");
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
        .querySelectorAll(".autosensor_header_main_nav_links_item_title")
        .forEach((item, index) => {
            const list = document.querySelectorAll(
                ".autosensor_header_main_nav_links_item_list"
            )[index];

            if (!item || !list) return;

            const left = item.getBoundingClientRect().left;

            list.style.transform = `translateX(-${left - 10}px)`;
        });
}, 10); // ⏱ chạy sau 200ms

const mainMenu = document.querySelector(".autosensor_header_main_nav");

if (mainMenu) {
    window.addEventListener("scroll", () => {
        if (window.scrollY > 240) {
            mainMenu.classList.add("autosensor_header_main_nav_fixed");
        } else {
            mainMenu.classList.remove("autosensor_header_main_nav_fixed");
        }
    });
}


// Custom autosensor-select
function initCustomSelect(selector) {
    document.querySelectorAll(selector).forEach(select => {

        const isMultiple = select.dataset.multiple === "true";
        const wrapper = document.createElement("div");
        wrapper.className = "autosensor-select-wrapper";

        const display = document.createElement("div");
        display.className = "autosensor-select-display";
        display.textContent = "Chọn...";

        const dropdown = document.createElement("div");
        dropdown.className = "autosensor-select-options";

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
            item.className = "autosensor-select-option";
            item.textContent = opt.textContent;
            item.dataset.value = opt.value;

            item.addEventListener("click", () => {
                if (isMultiple) {
                    opt.selected = !opt.selected;
                    item.classList.toggle("autosensor-select-selected");
                } else {
                    [...dropdown.children].forEach(c => c.classList.remove("autosensor-select-selected"));
                    item.classList.add("autosensor-select-selected");

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
        ".autosensor_header_main_mobile_bars"
    );
    const closeMenuMobile = document.querySelector(
        ".autosensor_header_mobile_main_nav_close"
    );
    const menuMobile = document.querySelector(
        ".autosensor_header_mobile_main_nav"
    );
    const overlay = document.querySelector(".autosensor_header_mobile_overlay");

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
    .querySelectorAll(".autosensor_header_mobile_main_nav_links_item_title")
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

const backToTopBtn = document.querySelector(".autosensor_back_to_top");

if (backToTopBtn) {
    window.addEventListener("scroll", () => {
        if (window.scrollY > 300) {
            backToTopBtn.style.display = "flex";

            const orderSummary = document.querySelector(".autosensor_order_summary");
            if (orderSummary) {
                orderSummary.classList.add("autosensor_order_summary_fixed");
            }

        } else {
            backToTopBtn.style.display = "none";

            const orderSummary = document.querySelector(".autosensor_order_summary");
            if (orderSummary) {
                orderSummary.classList.remove("autosensor_order_summary_fixed");
            }
        }
    });

    backToTopBtn.addEventListener("click", () => {
        window.scrollTo({ top: 0, behavior: "smooth" });
    });
}

function toggleFormOverlay(show = true) {
    const overlay = document.querySelector(
        ".autosensor_main_loading_form_overlay"
    );
    if (!overlay) return;
    if (show) overlay.removeAttribute("hidden");
    else overlay.setAttribute("hidden", "");
}

document.addEventListener("DOMContentLoaded", function () {

    // Delay 200ms để tránh lỗi khi DOM chưa ổn định (giảm CLS)
    setTimeout(() => {

        const trigger = document.querySelector("[data-ai-chat-trigger]");
        const popup = document.getElementById("autosensorChatPopup");
        if (!trigger || !popup) return;

        const form = popup.querySelector(".autosensor_chat_form");
        const textarea = popup.querySelector("textarea");
        const sendButton = popup.querySelector(".autosensor_chat_send");
        const messagesBox = popup.querySelector(".autosensor_chat_messages");
        const closeButton = popup.querySelector(".autosensor_chat_close");
        const endpoint = popup.dataset.endpoint;
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
        const history = [];
        const pageContext = (() => {
            // Nếu page đã set context chuẩn thì ưu tiên dùng
            if (window.aiPageContext && typeof window.aiPageContext === "object") {
                return window.aiPageContext;
            }
            const base = {
                url: window.location.href,
                title: document.title || "",
            };

            if (window.productData && window.productData.id) {
                return {
                    ...base,
                    page: "product_detail",
                    product_id: window.productData.id,
                    product_name: window.productData.name || "",
                    category_ids: window.productData.categoryIds || [],
                };
            }

            return {
                ...base,
                page: "generic",
            };
        })();
        const STORAGE_KEY = "autosensor-chat-messages";
        const MAX_MESSAGES = 10;
        const defaultGreeting =
            "Xin chào! Bạn đang cần tư vấn thiết bị tự động hóa, giải pháp kỹ thuật hay muốn tìm hiểu bài viết nào? Mình có thể dùng dữ liệu sản phẩm & bài viết mới nhất để trả lời ngay.";
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
            message.className = `autosensor_chat_message is-${entry.role}`;
            message.innerHTML = formatMessageContent(entry.content);

            if (entry.references?.length) {
                const refs = document.createElement("div");
                refs.className = "autosensor_chat_sources";

                entry.references.forEach((r) => {
                    const a = document.createElement("a");
                    a.href = r.url;
                    a.target = "_blank";
                    a.textContent = r.label;
                    a.className = "autosensor_chat_source_link";
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
            div.className = "autosensor_chat_message is-assistant is-error";
            div.innerHTML = formatMessageContent(msg);
            messagesBox.appendChild(div);
            messagesBox.scrollTop = messagesBox.scrollHeight;
        };

        const appendTyping = () => {
            const div = document.createElement("div");
            div.className = "autosensor_chat_message is-assistant";
            div.innerHTML =
                '<div class="autosensor_chat_typing"><span></span><span></span><span></span></div>';
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
        const tabs = popup.querySelectorAll(".autosensor_chat_tab");
        const tabContents = popup.querySelectorAll(".autosensor_chat_tab_content");
        
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
                        context: pageContext,
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
    document.getElementById('uploadArea').querySelector('.autosensor_image_search_upload_content').style.display = 'block';
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
            uploadArea.querySelector('.autosensor_image_search_upload_content').style.display = 'none';
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
            const response = await fetch(window.imageSearchRoute, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: formData
            });

            // Kiểm tra nếu response không phải JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                throw new Error('Server trả về dữ liệu không hợp lệ. Status: ' + response.status);
            }

            const data = await response.json();

            if (data.success) {
                // Redirect to shop page with image search results (dù products rỗng vẫn redirect)
                const keywords = data.keywords || [];
                const keywordParam = keywords.length > 0 ? keywords[0] : '';
                window.location.href = window.shopIndexRoute + '?keyword=' + encodeURIComponent(keywordParam) + '&image_search=1';
            } else {
                // Hiển thị message từ server
                const errorMessage = data.message || 'Không tìm thấy sản phẩm nào phù hợp với hình ảnh. Vui lòng thử với ảnh khác.';
                alert(errorMessage);
                loadingState.style.display = 'none';
                searchButton.disabled = false;
                searchButton.style.opacity = '1';
            }
        } catch (error) {
            console.error('Search error:', error);
            
            // Xử lý các loại lỗi khác nhau
            let errorMessage = 'Có lỗi xảy ra. Vui lòng thử lại sau.';
            
            if (error.message) {
                if (error.message.includes('429') || error.message.includes('quá nhiều')) {
                    errorMessage = 'Bạn đã tìm kiếm quá nhiều lần. Vui lòng đợi 1 phút rồi thử lại.';
                } else if (error.message.includes('422') || error.message.includes('validation')) {
                    errorMessage = 'Ảnh không hợp lệ. Vui lòng chọn ảnh định dạng JPG, PNG hoặc WEBP, kích thước tối đa 5MB.';
                } else if (error.message.includes('Network') || error.message.includes('fetch')) {
                    errorMessage = 'Lỗi kết nối. Vui lòng kiểm tra kết nối internet và thử lại.';
                } else {
                    errorMessage = error.message;
                }
            }
            
            alert(errorMessage);
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


// Tabs mô tả
const tabButtons = document.querySelectorAll(
    ".autosensor_single_desc_button button"
);
const tabContents = document.querySelectorAll(
    ".autosensor_single_desc_tabs > div"
);

tabButtons.forEach((btn, i) => {
    btn.addEventListener("click", () => {
        tabButtons.forEach((b) =>
            b.classList.remove("autosensor_single_desc_button_active")
        );
        tabContents.forEach((tab) =>
            tab.classList.remove("autosensor_single_desc_tabs_active")
        );
        btn.classList.add("autosensor_single_desc_button_active");
        tabContents[i].classList.add("autosensor_single_desc_tabs_active");
    });
});
if (tabButtons[0]) {
    tabButtons[0]?.click();
}

function tabReview() {
    if (tabButtons[2]) {
        tabButtons[2]?.click();
    }
}

function tabSizeGuide() {
    if (tabButtons[0]) {
        tabButtons[0]?.click();
    }
}

// Click ảnh con => ảnh chính
const mainIMG = document.querySelector(
    ".autosensor_single_info_images_main_image"
);
const galleryImages = document.querySelectorAll(
    ".autosensor_single_info_images_gallery_image"
);

// Đảm bảo luôn có 1 ảnh đầu tiên active khi load trang
if (galleryImages.length > 0) {
    const currentActive = document.querySelector(
        ".autosensor_single_info_images_gallery_image_active"
    );
    if (!currentActive) {
        const first = galleryImages[0];
        if (first) {
            first.classList.add(
                "autosensor_single_info_images_gallery_image_active"
            );
            const firstSrc = first.dataset.src || first.src;
            if (mainIMG && firstSrc) {
                mainIMG.removeAttribute("srcset");
                mainIMG.removeAttribute("sizes");
                mainIMG.setAttribute("src", firstSrc);
            }
        }
    }
}

galleryImages.forEach((img) => {
    img.addEventListener("click", () => {
        const newSrc = img.dataset.src || img.src;
        if (newSrc && mainIMG) {
            mainIMG.removeAttribute("srcset");
            mainIMG.removeAttribute("sizes");
            mainIMG.setAttribute("src", newSrc);
            galleryImages.forEach((i) =>
                i.classList.remove(
                    "autosensor_single_info_images_gallery_image_active"
                )
            );
            img.classList.add(
                "autosensor_single_info_images_gallery_image_active"
            );
        }
    });
});

document
    .querySelectorAll(".autosensor_single_info_voucher_code_item")
    ?.forEach((item) => {
        item.addEventListener("click", () => {
            navigator.clipboard
                .writeText(item.textContent.trim())
                .then(() =>
                    showCustomToast(
                        "Mã voucher đã được sao chép vào clipboard!",
                        "info"
                    )
                )
                .catch((error) => {
                    console.error("Error:", error);
                    showCustomToast(
                        "Có lỗi xảy ra khi sao chép mã voucher.",
                        "error"
                    );
                });
        });
    });

const qtyDisplay = document.querySelector(
    ".autosensor_single_info_specifications_actions_value"
);
const qtyWrapper = document.querySelector(
    ".autosensor_single_info_specifications_actions_qty"
);
const qtyInputField = document.querySelector("input[name='quantity']");
function getQtyMax() {
    const wrapper = document.querySelector('.autosensor_single_info_specifications_actions_qty');
    const ds = wrapper?.dataset?.maxStock;
    const parsed = parseInt(ds || '9999', 10);
    return Number.isFinite(parsed) && parsed > 0 ? parsed : 9999;
}

function safeToast(message, type = "info") {
    if (typeof showCustomToast === "function") {
        showCustomToast(message, type);
    }
}

function syncQuantity(val) {
    if (qtyDisplay) {
        qtyDisplay.textContent = val;
    }
    if (qtyInputField) {
        qtyInputField.value = val;
    }
}

function currentQty() {
    return (
        parseInt(qtyDisplay?.textContent || qtyInputField?.value || "1", 10) ||
        1
    );
}

// Variant selection handler
function selectVariant(variantId, price, salePrice, stock) {
    // Sync bottom variant label + hidden inputs always

    // Update active state
    const clickedButton = document.querySelector(`.autosensor_single_info_specifications_variant_item[data-variant-id="${variantId}"]`);
    
    document.querySelectorAll('.autosensor_single_info_specifications_variant_item').forEach(btn => {
        btn.classList.remove('active');
    });
    
    if (clickedButton) {
        clickedButton.classList.add('active');
    }
    
    // Update hidden input (QUAN TRỌNG để submit đúng biến thể)
    const variantInput = document.getElementById('selected_variant_id');
    const formVariantInput = document.getElementById('form_variant_id');
    if (variantInput) variantInput.value = variantId;
    if (formVariantInput) formVariantInput.value = variantId;

    // Nếu cần hiển thị tên biến thể ở thanh đáy (optional)
    const activeBtn = document.querySelector(`.autosensor_single_info_specifications_variant_item[data-variant-id="${variantId}"]`);
    const bottomVariantName = document.getElementById('autosensor_single_add_to_cart_bottom_variant');
    if (bottomVariantName && activeBtn) {
        const nameEl = activeBtn.querySelector('.variant-name');
        bottomVariantName.textContent = nameEl ? nameEl.textContent.trim() : '';
    }
    
    // Update price display
    const priceElement = document.querySelector('.autosensor_single_info_specifications_new_price');
    const oldPriceElement = document.querySelector('.autosensor_single_info_specifications_old_price');
    
    // Update price display
    if (priceElement) {
        const displayPrice = salePrice && salePrice > 0 && salePrice < price ? salePrice : price;
        priceElement.textContent = formatCurrencyVND(displayPrice) + '₫';
    }
    
    // Update old price (strikethrough)
    if (oldPriceElement) {
        if (salePrice && salePrice > 0 && salePrice < price) {
            oldPriceElement.textContent = formatCurrencyVND(price) + '₫';
            oldPriceElement.style.display = 'inline';
        } else {
            oldPriceElement.style.display = 'none';
        }
    }
    
    // Update max stock for quantity
    const quantityBox = document.getElementById('quantity_box');
    if (quantityBox) {
        if (stock !== null && stock !== undefined) {
            const maxStock = Math.max(1, stock);
            quantityBox.setAttribute('data-max-stock', maxStock);
            const currentQty = parseInt(document.querySelector('.autosensor_single_info_specifications_actions_value')?.textContent || '1');
            if (currentQty > maxStock) {
                const qtyValueEl = document.querySelector('.autosensor_single_info_specifications_actions_value');
                const qtyInputEl = document.getElementById('quantity_input');
                if (qtyValueEl) qtyValueEl.textContent = maxStock;
                if (qtyInputEl) qtyInputEl.value = maxStock;
            }
        } else {
            quantityBox.setAttribute('data-max-stock', '9999');
        }
    }
    
    // Check if out of stock
    const addToCartBtn = document.querySelector('.autosensor_single_info_specifications_actions_cart');
    const buyNowBtn = document.querySelector('.autosensor_single_info_specifications_actions_buy');
    
    if (stock !== null && stock <= 0) {
        if (addToCartBtn) {
            addToCartBtn.classList.add('disabled');
            addToCartBtn.disabled = true;
        }
        if (buyNowBtn) {
            buyNowBtn.classList.add('disabled');
            buyNowBtn.style.pointerEvents = 'none';
        }
    } else {
        if (addToCartBtn) {
            addToCartBtn.classList.remove('disabled');
            addToCartBtn.disabled = false;
        }
        if (buyNowBtn) {
            buyNowBtn.classList.remove('disabled');
            buyNowBtn.style.pointerEvents = 'auto';
        }
    }

    // Cập nhật hiển thị tồn kho (text) theo biến thể
    const stockInfoEl = document.querySelector('.autosensor_single_info_specifications_stock');
    if (stockInfoEl) {
        if (stock !== null && stock !== undefined) {
            if (stock <= 0) {
                stockInfoEl.innerHTML = '<span style="color: #d33;">Hết hàng</span>';
            } else {
                stockInfoEl.innerHTML = 'Còn lại <strong class="autosensor_single_info_specifications_stock_value">' + stock + '</strong> sản phẩm';
            }
        } else {
            stockInfoEl.innerHTML = '<span class="autosensor_single_info_specifications_stock_value">Còn hàng</span>';
        }
    }

    // === CẬP NHẬT THANH BOTTOM: giá + tồn kho + max qty ===
    const bottomStockEl = document.getElementById('autosensor_single_add_to_cart_bottom_stock');
    const bottomQtyBox = document.getElementById('autosensor_single_add_to_cart_bottom_qty');
    const bottomPriceNew = document.getElementById('autosensor_single_add_to_cart_bottom_price_new');
    const bottomPriceOld = document.getElementById('autosensor_single_add_to_cart_bottom_price_old');

    // Giá ở thanh đáy
    if (bottomPriceNew) {
        const displayPrice = (salePrice && salePrice > 0 && salePrice < price) ? salePrice : price;
        bottomPriceNew.textContent = formatCurrencyVND(displayPrice) + '₫';
    }

    // Giá gốc ở thanh đáy (ẩn/hiện)
    if (bottomPriceOld) {
        if (salePrice && salePrice > 0 && salePrice < price) {
            bottomPriceOld.textContent = formatCurrencyVND(price) + '₫';
            bottomPriceOld.style.display = 'inline';
        } else {
            bottomPriceOld.style.display = 'none';
        }
    }

    // Tồn kho ở thanh đáy
    if (bottomStockEl) {
        if (stock !== null && stock !== undefined) {
            bottomStockEl.textContent = (stock <= 0) ? 'Hết hàng' : ('Còn ' + stock + ' sản phẩm');
        } else {
            bottomStockEl.textContent = 'Còn hàng';
        }
    }

    // max qty ở thanh đáy
    if (bottomQtyBox) {
        if (stock !== null && stock !== undefined) {
            bottomQtyBox.setAttribute('data-max-stock', Math.max(1, stock));
        } else {
            bottomQtyBox.setAttribute('data-max-stock', '9999');
        }
    }
}

function increaseQty() {
    const qty = currentQty();
    const qtyMax = getQtyMax();
    if (qty >= qtyMax) {
        safeToast(`Số lượng tối đa trong kho là ${qtyMax}`, "warning");
        return;
    }
    syncQuantity(qty + 1);
}

function decreaseQty() {
    const qty = currentQty();
    if (qty <= 1) {
        safeToast("Số lượng tối thiểu là 1", "warning");
        return;
    }
    syncQuantity(qty - 1);
}

function countDownFlashSale(endTimestamp) {
    const daysEl = document.querySelector(
        ".autosensor_single_info_specifications_box_days"
    );
    const hoursEl = document.querySelector(
        ".autosensor_single_info_specifications_box_house"
    );
    const minutesEl = document.querySelector(
        ".autosensor_single_info_specifications_box_minute"
    );
    const secondsEl = document.querySelector(
        ".autosensor_single_info_specifications_box_second"
    );
    if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;

    const endTime = new Date(endTimestamp); // timestamp ms

    function updateCountdown() {
        const now = new Date();
        const distance = endTime.getTime() - now.getTime();

        if (distance <= 0) {
            // Hết hạn → reload 1 lần
            location.reload();
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor(
            (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        updateBox(daysEl, days);
        updateBox(hoursEl, hours);
        updateBox(minutesEl, minutes);
        updateBox(secondsEl, seconds);
    }

    function updateBox(el, newValue) {
        const oldValue = el.textContent;
        const formatted = newValue.toString().padStart(2, "0");

        if (oldValue !== formatted) {
            el.textContent = formatted;
            el.classList.remove("animate");
            void el.offsetWidth; // trigger reflow
            el.classList.add("animate");
        }
    }

    // ✅ chạy ngay khi load
    updateCountdown();

    // Sau đó lặp lại mỗi giây
    setInterval(updateCountdown, 1000);
}

if (typeof endTime !== "undefined") {
    // Truyền timestamp ms
    countDownFlashSale(endTime);
}

function showPopupVoucher() {
    const popup = document.querySelector(
        ".autosensor_main_show_popup_overlay"
    );
    const closeBtn = document.querySelector(
        ".autosensor_main_show_popup_close"
    );
    const codeEl = document.querySelectorAll(
        ".autosensor_main_show_popup_code"
    );

    // Nếu không có popup trên trang hiện tại thì thoát sớm, tránh lỗi null.style
    if (!popup) {
        return;
    }

    popup.style.display = "flex";

    // Đóng popup
    if (closeBtn) {
        closeBtn.addEventListener("click", () => {
            popup.style.display = "none";
        });
    }

    // Click ra ngoài để đóng
    popup.addEventListener("click", (e) => {
        if (e.target === popup) {
            popup.style.display = "none";
        }
    });

    // Copy voucher code khi click
    codeEl.forEach((el) => {
        el.addEventListener("click", () => {
            if (el.dataset.copied === "true") return; // nếu voucher này đã copy rồi thì bỏ qua

            const originalText = el.textContent.trim();

            navigator.clipboard
                .writeText(originalText)
                .then(() => {
                    showCustomToast("Mã voucher đã được sao chép!", "info");
                    el.textContent = "Đã sao chép!";
                    el.dataset.copied = "true"; // đánh dấu riêng cho voucher này

                    // Reset lại sau 2 giây
                    setTimeout(() => {
                        el.textContent = originalText;
                        el.dataset.copied = "false";
                    }, 5000);
                })
                .catch((err) => {
                    console.error("Copy thất bại: ", err);
                });
        });
    });
}

setTimeout(() => {
    showPopupVoucher();
}, 20000);

document.addEventListener("DOMContentLoaded", () => {
    // === BASE ELEMENTS ===
    const autosensorOverlay = document.querySelector(
        ".autosensor_single_info_images_main_overlay"
    );
    const autosensorOverlayImagesWrapper = document.querySelector(
        ".autosensor_single_info_images_main_overlay_images"
    );
    const autosensorOverlayImageItems = document.querySelectorAll(
        ".autosensor_single_info_images_main_overlay_image"
    );

    // === STATE ===
    let autosensorCurrentIndex = 0;
    let autosensorTouchStartX = 0;

    // === BUTTONS ===
    const autosensorBtnPrev = document.createElement("div");
    autosensorBtnPrev.className = "autosensor_nav_btn autosensor_prev";
    autosensorBtnPrev.textContent = "‹";

    const autosensorBtnNext = document.createElement("div");
    autosensorBtnNext.className = "autosensor_nav_btn autosensor_next";
    autosensorBtnNext.textContent = "›";

    const autosensorBtnClose = document.createElement("div");
    autosensorBtnClose.className = "autosensor_close_btn";
    autosensorBtnClose.textContent = "✕";

    autosensorOverlay.appendChild(autosensorBtnPrev);
    autosensorOverlay.appendChild(autosensorBtnNext);
    autosensorOverlay.appendChild(autosensorBtnClose);

    // === LOCK/UNLOCK BODY SCROLL ===
    let scrollPosition = 0;
    
    function lockBodyScroll() {
        // Lưu vị trí scroll hiện tại
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        // Lock scroll
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollPosition}px`;
        document.body.style.width = '100%';
    }

    function unlockBodyScroll() {
        // Unlock scroll
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        
        // Restore scroll position
        window.scrollTo(0, scrollPosition);
    }

    // === CLICK THUMBNAIL TO OPEN OVERLAY (TÍCH HỢP VÀO CODE CŨ) ===
    document
        .querySelectorAll(".autosensor_single_info_images_main img")
        .forEach((thumb, index) => {
            thumb.addEventListener("click", () => {
                autosensorCurrentIndex = index;
                autosensorOverlay.style.display = "flex";
                lockBodyScroll(); // Lock scroll khi mở overlay

                setTimeout(() => {
                    autosensorOverlay.classList.add("autosensor_show");
                }, 10);

                autosensorUpdatePosition();
            });
        });
    // === OPEN OVERLAY ===
    document
        .querySelectorAll(
            ".autosensor_single_info_images_main_overlay_image"
        )
        .forEach((autosensorImg, i) => {
            autosensorImg.addEventListener("click", () => {
                autosensorCurrentIndex = i;
                autosensorOverlay.style.display = "flex";
                lockBodyScroll(); // Lock scroll khi mở overlay
                setTimeout(
                    () => autosensorOverlay.classList.add("autosensor_show"),
                    10
                );
                autosensorUpdatePosition();
            });
        });

    // === UPDATE POSITION ===
    function autosensorUpdatePosition() {
        autosensorOverlayImagesWrapper.style.transform = `translateX(-${
            autosensorCurrentIndex * 100
        }vw)`;
    }

    // === NEXT / PREV ===
    autosensorBtnNext.addEventListener("click", () => {
        autosensorCurrentIndex =
            (autosensorCurrentIndex + 1) % autosensorOverlayImageItems.length;
        autosensorUpdatePosition();
    });

    autosensorBtnPrev.addEventListener("click", () => {
        autosensorCurrentIndex =
            (autosensorCurrentIndex - 1 + autosensorOverlayImageItems.length) %
            autosensorOverlayImageItems.length;
        autosensorUpdatePosition();
    });

    // === CLOSE ===
    autosensorBtnClose.addEventListener("click", autosensorCloseOverlay);

    autosensorOverlay.addEventListener("click", (e) => {
        if (e.target === autosensorOverlay) autosensorCloseOverlay();
    });

    function autosensorCloseOverlay() {
        autosensorOverlay.classList.remove("autosensor_show");
        unlockBodyScroll(); // Unlock scroll khi đóng overlay
        setTimeout(() => (autosensorOverlay.style.display = "none"), 200);
    }

    // === ESC TO CLOSE ===
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") autosensorCloseOverlay();
    });

    // === MOBILE SWIPE ===
    autosensorOverlayImagesWrapper.addEventListener("touchstart", (e) => {
        autosensorTouchStartX = e.touches[0].clientX;
    });

    autosensorOverlayImagesWrapper.addEventListener("touchend", (e) => {
        let touchEndX = e.changedTouches[0].clientX;
        let touchDiff = autosensorTouchStartX - touchEndX;

        if (touchDiff > 50) {
            autosensorCurrentIndex =
                (autosensorCurrentIndex + 1) % autosensorOverlayImageItems.length;
            autosensorUpdatePosition();
        }
        if (touchDiff < -50) {
            autosensorCurrentIndex =
                (autosensorCurrentIndex -
                    1 +
                    autosensorOverlayImageItems.length) %
                autosensorOverlayImageItems.length;
            autosensorUpdatePosition();
        }
    });

    // === DOUBLE TAP TO ZOOM ===
    let autosensorLastTap = 0;

    autosensorOverlay.addEventListener("touchend", () => {
        const now = Date.now();
        if (now - autosensorLastTap < 250) {
            autosensorOverlay.classList.toggle("autosensor_zoom_active");
        }
        autosensorLastTap = now;
    });

    initAccessoryDragScroll();
    initAccessoryQuickAdd();
});

function addWishlist(productId) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/san-pham/yeu-thich";

    const inputProduct = document.createElement("input");
    inputProduct.type = "hidden";
    inputProduct.name = "product_id";
    inputProduct.value = productId;

    const inputToken = document.createElement("input");
    inputToken.type = "hidden";
    inputToken.name = "_token";
    inputToken.value = csrfToken;

    form.appendChild(inputProduct);
    form.appendChild(inputToken);

    document.body.appendChild(form);
    form.submit();
}

function removeWishlist(productId) {
    const form = document.createElement("form");
    form.method = "POST";
    form.action = "/san-pham/yeu-thich";

    const token = document.createElement("input");
    token.type = "hidden";
    token.name = "_token";
    token.value = csrfToken;

    const method = document.createElement("input");
    method.type = "hidden";
    method.name = "_method";
    method.value = "DELETE";

    const id = document.createElement("input");
    id.type = "hidden";
    id.name = "product_id";
    id.value = productId;

    form.appendChild(token);
    form.appendChild(method);
    form.appendChild(id);

    document.body.appendChild(form);
    form.submit();
}

function initAccessoryDragScroll() {
    const scrollers = document.querySelectorAll("[data-accessory-scroll]");
    if (!scrollers.length) {
        return;
    }

    scrollers.forEach((scroller) => {
        let isDown = false;
        let startX = 0;
        let scrollLeft = 0;

        scroller.addEventListener("mousedown", (e) => {
            isDown = true;
            scroller.classList.add("is-dragging");
            startX = e.pageX - scroller.offsetLeft;
            scrollLeft = scroller.scrollLeft;
        });

        ["mouseleave", "mouseup"].forEach((evt) => {
            scroller.addEventListener(evt, () => {
                isDown = false;
                scroller.classList.remove("is-dragging");
            });
        });

        scroller.addEventListener("mousemove", (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - scroller.offsetLeft;
            const walk = (x - startX) * 1.2;
            scroller.scrollLeft = scrollLeft - walk;
        });
    });
}

function initAccessoryQuickAdd() {
    const buttons = document.querySelectorAll("[data-accessory-add]");
    if (!buttons.length) {
        return;
    }

    const csrf = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    if (!csrf) {
        console.warn("CSRF token not found for accessory quick add.");
        return;
    }

    buttons.forEach((btn) => {
        btn.addEventListener("click", () => handleAccessoryAdd(btn, csrf));
    });

    // Xử lý modal variant cho accessories
    const modal = document.getElementById('accessory-variant-modal');
    if (modal) {
        // Đảm bảo modal ẩn mặc định khi khởi tạo
        if (modal.classList.contains('active')) {
            modal.classList.remove('active');
        }
        // Đảm bảo body không bị lock scroll
        if (document.body.style.overflow === 'hidden') {
            document.body.style.overflow = '';
        }

        const modalOverlay = modal.querySelector('.autosensor_variant_modal_overlay');
        const modalClose = modal.querySelector('.autosensor_variant_modal_close');
        const modalCancel = document.getElementById('accessory-modal-cancel-btn');
        const quantityInput = document.getElementById('accessory-modal-quantity');
        const quantityDecrease = modal.querySelector('[data-action="decrease"]');
        const quantityIncrease = modal.querySelector('[data-action="increase"]');
        const addToCartBtn = document.getElementById('accessory-modal-add-to-cart-btn');

        // Đóng modal - function global để có thể gọi từ nơi khác
        window.closeAccessoryModal = function() {
            if (modal) {
                modal.classList.remove('active');
                document.body.style.overflow = '';
                // Reset form
                const qtyInput = document.getElementById('accessory-modal-quantity');
                if (qtyInput) {
                    qtyInput.value = 1;
                }
                // Reset variant selection
                const variantItems = modal.querySelectorAll('#accessory-modal-variants-list .autosensor_variant_modal_variant_item');
                variantItems.forEach(item => item.classList.remove('active'));
                if (variantItems.length > 0 && !variantItems[0].disabled) {
                    variantItems[0].classList.add('active');
                }
            }
        };

        const closeModal = window.closeAccessoryModal;

        if (modalOverlay) {
            modalOverlay.addEventListener('click', closeModal);
        }
        if (modalClose) {
            modalClose.addEventListener('click', closeModal);
        }
        if (modalCancel) {
            modalCancel.addEventListener('click', closeModal);
        }

        // Đóng modal khi bấm ESC (chỉ khi modal đang mở)
        function handleEscapeKey(e) {
            if (e.key === 'Escape' && modal && modal.classList.contains('active')) {
                e.preventDefault();
                e.stopPropagation();
                closeModal();
            }
        }
        document.addEventListener('keydown', handleEscapeKey);

        // Tăng/giảm số lượng
        if (quantityDecrease) {
            quantityDecrease.addEventListener('click', function() {
                const input = quantityInput;
                const currentValue = parseInt(input.value) || 1;
                if (currentValue > 1) {
                    input.value = currentValue - 1;
                }
            });
        }

        if (quantityIncrease) {
            quantityIncrease.addEventListener('click', function() {
                const input = quantityInput;
                const currentValue = parseInt(input.value) || 1;
                const maxStock = parseInt(input.max) || 999;
                if (currentValue < maxStock) {
                    input.value = currentValue + 1;
                }
            });
        }

        // Validate số lượng khi nhập
        if (quantityInput) {
            quantityInput.addEventListener('change', function() {
                const value = parseInt(this.value) || 1;
                const maxStock = parseInt(this.max) || 999;
                const minValue = parseInt(this.min) || 1;
                
                if (value < minValue) {
                    this.value = minValue;
                } else if (value > maxStock) {
                    this.value = maxStock;
                }
            });
        }

        // Submit form thêm vào giỏ
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function() {
                const productId = modal.dataset.currentProductId;
                const csrfToken = modal.dataset.currentCsrf;
                const quantity = parseInt(quantityInput.value) || 1;
                
                if (!productId || !csrfToken) {
                    showCustomToast('Lỗi: Không tìm thấy thông tin sản phẩm', 'error');
                    return;
                }

                // Validate số lượng
                if (quantity < 1) {
                    showCustomToast('Số lượng phải lớn hơn 0', 'error');
                    quantityInput.focus();
                    return;
                }

                // Lấy variant ID nếu có
                const selectedVariant = modal.querySelector('#accessory-modal-variants-list .autosensor_variant_modal_variant_item.active');
                const variantId = selectedVariant ? selectedVariant.dataset.variantId : null;

                // Validate variant nếu có variants
                const hasVariants = document.getElementById('accessory-modal-variants-section').style.display !== 'none';
                if (hasVariants && !variantId) {
                    showCustomToast('Vui lòng chọn biến thể sản phẩm', 'error');
                    return;
                }

                // Validate stock
                if (selectedVariant) {
                    const stock = selectedVariant.dataset.variantStock;
                    if (stock !== 'null' && stock !== null && parseInt(stock) <= 0) {
                        showCustomToast('Sản phẩm này đã hết hàng', 'error');
                        return;
                    }
                    if (stock !== 'null' && stock !== null && quantity > parseInt(stock)) {
                        showCustomToast(`Số lượng vượt quá tồn kho (còn ${stock} sản phẩm)`, 'error');
                        quantityInput.value = stock;
                        quantityInput.focus();
                        return;
                    }
                }

                // Disable button
                this.disabled = true;
                const originalText = this.innerHTML;
                this.innerHTML = '<span>Đang thêm...</span>';

                // Gửi request
                const requestBody = {
                    product_id: productId,
                    quantity: quantity,
                };

                if (variantId) {
                    requestBody.product_variant_id = variantId;
                }

                fetch("/api/v1/cart/accessories", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Accept: "application/json",
                        "X-CSRF-TOKEN": csrfToken,
                    },
                    body: JSON.stringify(requestBody),
                })
                    .then(async (response) => {
                        const data = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            throw new Error(data.message || 'Có lỗi xảy ra khi thêm vào giỏ hàng');
                        }
                        return data;
                    })
                    .then((data) => {
                        // Đóng modal trước
                        if (typeof window.closeAccessoryModal === 'function') {
                            window.closeAccessoryModal();
                        }
                        // Hiển thị thông báo thành công
                        if (data.message) {
                            showCustomToast(data.message, 'success');
                        } else {
                            showCustomToast('Đã thêm vào giỏ hàng thành công!', 'success');
                        }
                        // Reload trang để cập nhật giỏ hàng
                        window.location.reload();
                    })
                    .catch((error) => {
                        console.error('Error adding to cart:', error);
                        showCustomToast(error.message || 'Có lỗi xảy ra khi thêm vào giỏ hàng', 'error');
                        this.disabled = false;
                        this.innerHTML = originalText;
                    });
            });
        }
    }
}

function handleAccessoryAdd(button, csrf) {
    const productId = button.dataset.accessoryAdd;
    if (!productId) {
        return;
    }

    const hasVariants = button.dataset.accessoryHasVariants === '1';
    const variantsData = button.dataset.accessoryVariants ? JSON.parse(button.dataset.accessoryVariants) : [];

    // Nếu có variants, hiển thị modal
    if (hasVariants && variantsData.length > 0) {
        openAccessoryVariantModal(button, csrf, productId, variantsData);
        return;
    }

    // Nếu không có variants, thêm trực tiếp
    addAccessoryToCartDirect(productId, 1, button, csrf);
}

function openAccessoryVariantModal(button, csrf, productId, variantsData) {
    const modal = document.getElementById('accessory-variant-modal');
    if (!modal) {
        console.error('[Accessory Modal] Modal not found');
        // Nếu không có modal, thêm trực tiếp với variant đầu tiên
        if (variantsData.length > 0) {
            const firstVariant = variantsData[0];
            addAccessoryToCartDirect(productId, 1, button, csrf, firstVariant.id);
        } else {
            addAccessoryToCartDirect(productId, 1, button, csrf);
        }
        return;
    }

    const productName = button.dataset.accessoryName || '';
    const productImage = button.dataset.accessoryImage || '';
    const productPrice = parseFloat(button.dataset.accessoryPrice) || 0;
    const productSalePrice = button.dataset.accessorySalePrice ? parseFloat(button.dataset.accessorySalePrice) : null;

    // Hiển thị thông tin sản phẩm
    document.getElementById('accessory-modal-product-image').src = productImage;
    document.getElementById('accessory-modal-product-image').alt = productName;
    document.getElementById('accessory-modal-product-name').textContent = productName;
    
    // Hiển thị giá
    let priceHtml = '';
    if (productSalePrice && productSalePrice > 0 && productSalePrice < productPrice) {
        priceHtml = `<span style="color: #e6525e; font-weight: bold; font-size: 18px;">${formatCurrencyVND(productSalePrice)}₫</span> <span style="text-decoration: line-through; color: #999; font-size: 14px;">${formatCurrencyVND(productPrice)}₫</span>`;
    } else {
        priceHtml = `<span style="color: #e6525e; font-weight: bold; font-size: 18px;">${formatCurrencyVND(productPrice)}₫</span>`;
    }
    document.getElementById('accessory-modal-product-price').innerHTML = priceHtml;

    // Hiển thị variants
    const variantsSection = document.getElementById('accessory-modal-variants-section');
    const variantsList = document.getElementById('accessory-modal-variants-list');
    variantsList.innerHTML = '';

    if (variantsData.length > 0) {
        variantsSection.style.display = 'block';
        variantsData.forEach((variant, index) => {
            const variantBtn = document.createElement('button');
            variantBtn.type = 'button';
            variantBtn.className = 'autosensor_variant_modal_variant_item' + (index === 0 ? ' active' : '');
            variantBtn.dataset.variantId = variant.id;
            variantBtn.dataset.variantPrice = variant.display_price || variant.price;
            variantBtn.dataset.variantStock = variant.stock_quantity ?? 'null';
            
            const attrs = variant.attributes || {};
            const details = [];
            if (attrs.size) details.push(attrs.size);
            if (attrs.has_pot === true || attrs.has_pot === '1' || attrs.has_pot === 1) details.push('Có chậu');
            if (attrs.combo_type) details.push(attrs.combo_type);
            if (attrs.notes) details.push(attrs.notes);
            const detailsText = details.length > 0 ? ' (' + details.join(', ') + ')' : '';

            let variantHtml = `<span class="variant-name">${variant.name}${detailsText}</span>`;
            variantHtml += `<span class="variant-price">${formatCurrencyVND(variant.display_price || variant.price)}₫</span>`;
            
            if (variant.sale_price && variant.sale_price > 0 && variant.sale_price < variant.price) {
                const discount = Math.round(((variant.price - variant.sale_price) / variant.price) * 100);
                variantHtml += `<span class="variant-discount">-${discount}%</span>`;
            }
            
            if (variant.stock_quantity !== null && variant.stock_quantity <= 0) {
                variantHtml += `<span class="variant-out-of-stock">Hết hàng</span>`;
                variantBtn.disabled = true;
                variantBtn.classList.add('disabled');
            }

            variantBtn.innerHTML = variantHtml;
            variantBtn.addEventListener('click', function() {
                if (this.disabled) return;
                document.querySelectorAll('#accessory-modal-variants-list .autosensor_variant_modal_variant_item').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                updateAccessoryModalPrice(this.dataset.variantPrice);
                updateAccessoryModalStock(this.dataset.variantStock);
            });
            variantsList.appendChild(variantBtn);
        });

        // Chọn variant đầu tiên mặc định
        const firstVariant = variantsList.querySelector('.autosensor_variant_modal_variant_item');
        if (firstVariant && !firstVariant.disabled) {
            updateAccessoryModalPrice(firstVariant.dataset.variantPrice);
            updateAccessoryModalStock(firstVariant.dataset.variantStock);
        }
    } else {
        variantsSection.style.display = 'none';
    }

    // Reset quantity
    document.getElementById('accessory-modal-quantity').value = 1;

    // Lưu thông tin để submit
    modal.dataset.currentProductId = productId;
    modal.dataset.currentCsrf = csrf;

    // Hiển thị modal
    // Đảm bảo body không bị lock từ trước
    document.body.style.overflow = '';
    // Thêm class active để hiển thị modal
    modal.classList.add('active');
    // Lock scroll sau khi modal hiển thị
    setTimeout(() => {
        document.body.style.overflow = 'hidden';
    }, 10);
}

function updateAccessoryModalPrice(price) {
    const priceEl = document.getElementById('accessory-modal-product-price');
    if (priceEl) {
        priceEl.innerHTML = `<span style="color: #e6525e; font-weight: bold; font-size: 18px;">${formatCurrencyVND(price)}₫</span>`;
    }
}

function updateAccessoryModalStock(stock) {
    const quantityInput = document.getElementById('accessory-modal-quantity');
    if (quantityInput && stock !== 'null' && stock !== null) {
        const maxStock = parseInt(stock) || 999;
        quantityInput.max = maxStock;
        if (parseInt(quantityInput.value) > maxStock) {
            quantityInput.value = maxStock;
        }
    }
}

// formatCurrencyVND đã được định nghĩa ở trên, không cần định nghĩa lại

function addAccessoryToCartDirect(productId, quantity, button, csrf, variantId = null) {
    const originalText = button.textContent;
    button.disabled = true;
    button.dataset.loadingText = originalText;
    button.textContent = "Đang thêm...";

    const requestBody = {
        product_id: productId,
        quantity: quantity,
    };
    
    if (variantId) {
        requestBody.product_variant_id = variantId;
    }

    fetch("/api/v1/cart/accessories", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": csrf,
        },
        body: JSON.stringify(requestBody),
    })
        .then(async (response) => {
            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                const message =
                    data?.message ||
                    Object.values(data?.errors ?? {})?.[0]?.[0] ||
                    "Không thể thêm sản phẩm vào giỏ.";
                throw new Error(message);
            }

            updateCartCountBadge(data.cart_total_items ?? null);

            safeToast(
                data.message || "Đã thêm sản phẩm đi kèm vào giỏ hàng.",
                "success"
            );
        })
        .catch((error) => {
            safeToast(error.message || "Không thể thêm sản phẩm.", "error");
        })
        .finally(() => {
            button.disabled = false;
            button.textContent = button.dataset.loadingText || originalText;
            delete button.dataset.loadingText;
        });
}

function updateCartCountBadge(count) {
    if (typeof count !== "number") {
        return;
    }

    document
        .querySelectorAll(".autosensor_header_main_icon_cart_count")
        .forEach((badge) => {
            badge.textContent = count;
            if (count <= 0) {
                badge.classList.add("d-none");
            } else {
                badge.classList.remove("d-none");
            }
        });
}

// --- Thanh thêm nhanh dưới đáy ---
const bottomBar = document.getElementById('autosensor_single_add_to_cart_bottom');
const bottomQtyValue = document.getElementById('autosensor_single_add_to_cart_bottom_qty_value');
const quantityInput = document.getElementById('quantity_input');
const quantityDisplay = document.querySelector('.autosensor_single_info_specifications_actions_value');

function syncBottomQtyToMain(val) {
    if (!quantityInput || !quantityDisplay) return;
    quantityInput.value = val;
    quantityDisplay.textContent = val;
}

window.autosensorBottomIncreaseQty = function () {
    const maxStock = parseInt(document.getElementById('autosensor_single_add_to_cart_bottom_qty')?.dataset.maxStock || '9999', 10);
    let current = parseInt(bottomQtyValue?.textContent || '1', 10) || 1;
    current = Math.min(current + 1, maxStock);
    if (bottomQtyValue) bottomQtyValue.textContent = current;
    syncBottomQtyToMain(current);
}

window.autosensorBottomDecreaseQty = function () {
    let current = parseInt(bottomQtyValue?.textContent || '1', 10) || 1;
    current = Math.max(current - 1, 1);
    if (bottomQtyValue) bottomQtyValue.textContent = current;
    syncBottomQtyToMain(current);
}

window.autosensorBottomAddToCart = function () {
    // đảm bảo hidden input variant đúng với nút biến thể đang active
    const activeVariantBtn = document.querySelector('.autosensor_single_info_specifications_variant_item.active');
    const variantId = activeVariantBtn?.dataset?.variantId;
    if (variantId) {
        const formVariantInput = document.getElementById('form_variant_id');
        const selectedVariantInput = document.getElementById('selected_variant_id');
        if (formVariantInput) formVariantInput.value = variantId;
        if (selectedVariantInput) selectedVariantInput.value = variantId;
    }

    // đồng bộ quantity từ thanh đáy -> input hidden
    const bottomQty = parseInt(document.getElementById('autosensor_single_add_to_cart_bottom_qty_value')?.textContent || '1', 10) || 1;
    const qtyInput = document.getElementById('quantity_input');
    const qtyValueEl = document.querySelector('.autosensor_single_info_specifications_actions_value');
    if (qtyInput) qtyInput.value = bottomQty;
    if (qtyValueEl) qtyValueEl.textContent = bottomQty;

    const form = document.querySelector('.autosensor_single_info_specifications_actions');
    if (form) form.submit();
}

function toggleBottomBar() {
    if (!bottomBar) return;
    // Nếu đã đóng thì không hiện lại
    if (sessionStorage.getItem('bottomCartBarClosed') === 'true') {
        bottomBar.classList.remove('show');
        return;
    }
    const triggerY = 420; // Ẩn khi ở gần đầu trang, hiện khi cuộn xuống
    if (window.scrollY > triggerY) {
        bottomBar.classList.add('show');
    } else {
        bottomBar.classList.remove('show');
    }
}

window.closeBottomCartBar = function() {
    if (bottomBar) {
        bottomBar.classList.remove('show');
        // Lưu vào localStorage để không hiện lại trong session này
        sessionStorage.setItem('bottomCartBarClosed', 'true');
    }
}

// Kiểm tra nếu đã đóng thì không hiện lại
if (sessionStorage.getItem('bottomCartBarClosed') === 'true') {
    if (bottomBar) {
        bottomBar.classList.remove('show');
    }
}

document.addEventListener('scroll', toggleBottomBar, { passive: true });
window.addEventListener('load', toggleBottomBar);

// ============================================================
// Accessory Carousel — moved from inline script in single.blade.php
// ============================================================
(function () {
    function initAccessoryCarousels() {
        document.querySelectorAll('[data-accessory-scroll]').forEach(function (carousel) {
            var groupIndex = carousel.getAttribute('data-group-index');
            var prevBtn = document.querySelector('.autosensor_single_accessories_nav_prev[data-group-index="' + groupIndex + '"]');
            var nextBtn = document.querySelector('.autosensor_single_accessories_nav_next[data-group-index="' + groupIndex + '"]');
            var items = carousel.querySelectorAll('.autosensor_single_accessories_item');

            if (items.length === 0) return;

            var isDragging = false;
            var startX = 0;
            var scrollLeft = 0;

            function getVisibleItems() {
                var width = carousel.offsetWidth;
                if (width >= 1200) return 6;
                if (width >= 992) return 5;
                if (width >= 768) return 4;
                if (width >= 576) return 3;
                return 2;
            }

            function scrollTo(direction) {
                var visibleItems = getVisibleItems();
                var itemWidth = items[0].offsetWidth + 12;
                var scrollAmount = itemWidth * visibleItems;
                carousel.scrollBy({ left: direction === 'next' ? scrollAmount : -scrollAmount, behavior: 'smooth' });
            }

            function updateButtons() {
                var isAtStart = carousel.scrollLeft <= 0;
                var isAtEnd = carousel.scrollLeft >= carousel.scrollWidth - carousel.offsetWidth - 10;
                if (prevBtn) { prevBtn.disabled = isAtStart; prevBtn.classList.toggle('disabled', isAtStart); }
                if (nextBtn) { nextBtn.disabled = isAtEnd; nextBtn.classList.toggle('disabled', isAtEnd); }
            }

            if (prevBtn) prevBtn.addEventListener('click', function () { scrollTo('prev'); });
            if (nextBtn) nextBtn.addEventListener('click', function () { scrollTo('next'); });

            carousel.addEventListener('mousedown', function (e) {
                isDragging = true; startX = e.pageX - carousel.offsetLeft;
                scrollLeft = carousel.scrollLeft; carousel.style.cursor = 'grabbing'; carousel.style.userSelect = 'none';
            });
            carousel.addEventListener('touchstart', function (e) {
                isDragging = true; startX = e.touches[0].pageX - carousel.offsetLeft; scrollLeft = carousel.scrollLeft;
            });
            carousel.addEventListener('mouseleave', function () { isDragging = false; carousel.style.cursor = 'grab'; });
            carousel.addEventListener('mouseup', function () { isDragging = false; carousel.style.cursor = 'grab'; carousel.style.userSelect = ''; });
            carousel.addEventListener('touchend', function () { isDragging = false; });
            carousel.addEventListener('mousemove', function (e) {
                if (!isDragging) return; e.preventDefault();
                var x = e.pageX - carousel.offsetLeft;
                carousel.scrollLeft = scrollLeft - (x - startX) * 2;
            });
            carousel.addEventListener('touchmove', function (e) {
                if (!isDragging) return; e.preventDefault();
                var x = e.touches[0].pageX - carousel.offsetLeft;
                carousel.scrollLeft = scrollLeft - (x - startX) * 2;
            });
            carousel.addEventListener('scroll', updateButtons);

            var resizeTimer;
            window.addEventListener('resize', function () { clearTimeout(resizeTimer); resizeTimer = setTimeout(updateButtons, 250); });

            updateButtons();
            carousel.style.cursor = 'grab';
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccessoryCarousels);
    } else {
        initAccessoryCarousels();
    }
})();