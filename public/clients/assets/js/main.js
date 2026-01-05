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
                orderSummary.classList.add("shop_haiphonglife_order_summary_fixed");
            }

        } else {
            backToTopBtn.style.display = "none";

            const orderSummary = document.querySelector(".xanhworld_order_summary");
            if (orderSummary) {
                orderSummary.classList.remove("shop_haiphonglife_order_summary_fixed");
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

        const MAX_LENGTH = 200;
        const MIN_LENGTH = 5;

        const updateCharCount = () => {
            const charCountEl = document.getElementById("xanhworldChatCharCount");
            if (charCountEl && textarea) {
                const length = textarea.value.length;
                charCountEl.textContent = length;
                
                // Thay đổi màu khi gần đạt giới hạn
                if (length >= MAX_LENGTH * 0.9) {
                    charCountEl.style.color = "#ef4444";
                } else if (length >= MAX_LENGTH * 0.7) {
                    charCountEl.style.color = "#f59e0b";
                } else {
                    charCountEl.style.color = "";
                }
            }
        };

        const updateSendState = () => {
            const length = textarea.value.trim().length;
            sendButton.disabled = isProcessing || length < MIN_LENGTH || length > MAX_LENGTH;
            updateCharCount();
        };

        // INIT CHAT UI
        loadMessagesFromStorage();
        renderStoredMessages();
        syncHistoryFromMessages();
        
        // Khởi tạo counter sau khi DOM sẵn sàng
        setTimeout(() => {
            updateCharCount();
        }, 200);

        trigger.addEventListener("click", (e) => {
            e.preventDefault();
            togglePopup();
            // Cập nhật counter khi mở modal
            setTimeout(() => {
                updateCharCount();
            }, 150);
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

        textarea.addEventListener("input", () => {
            // Giới hạn số ký tự ở frontend
            if (textarea.value.length > MAX_LENGTH) {
                textarea.value = textarea.value.substring(0, MAX_LENGTH);
            }
            updateCharCount(); // Cập nhật counter ngay lập tức
            updateSendState();
        });
        
        // Cập nhật counter khi paste
        textarea.addEventListener("paste", () => {
            setTimeout(() => {
                if (textarea.value.length > MAX_LENGTH) {
                    textarea.value = textarea.value.substring(0, MAX_LENGTH);
                }
                updateCharCount();
                updateSendState();
            }, 10);
        });

        form.addEventListener("submit", async (e) => {
            e.preventDefault();
            if (isProcessing) return;

            const content = textarea.value.trim();
            if (content.length < MIN_LENGTH || content.length > MAX_LENGTH) {
                return;
            }

            addMessage("user", content);
            textarea.value = "";
            updateCharCount(); // Reset counter về 0
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
            if (e.key === "Escape" && popup.classList.contains("is-open")) {
                popup.classList.remove("is-open");
                // Khôi phục overflow khi đóng modal
                document.body.style.overflow = "";
                document.documentElement.style.overflow = "";
            }
        });

        // Đóng modal khi click ra ngoài
        popup.addEventListener("click", (e) => {
            if (e.target === popup) {
                popup.classList.remove("is-open");
                // Khôi phục overflow khi đóng modal
                document.body.style.overflow = "";
                document.documentElement.style.overflow = "";
            }
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

// Product Comparison Functionality
(function initProductComparison() {
    'use strict';

    // Đảm bảo window và document tồn tại
    if (typeof window === 'undefined' || typeof document === 'undefined') {
        console.error('window or document is not available');
        return;
    }

    // Đảm bảo document.addEventListener là function
    if (typeof document.addEventListener !== 'function') {
        console.error('document.addEventListener is not a function. Current type:', typeof document.addEventListener);
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initProductComparison);
        } else {
            // Try again after a short delay
            setTimeout(initProductComparison, 50);
        }
        return;
    }

    const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute("content") : '';

    // Update comparison count in header
    function updateComparisonCount() {
        fetch('/so-sanh/count', {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            const count = data.count || 0;
            const countEls = document.querySelectorAll('#comparisonCount, #comparisonCountMobile, .xanhworld_header_main_icon_compre__count');
            countEls.forEach(el => {
                if (el) {
                    el.textContent = count;
                    el.style.display = count > 0 ? '' : 'none';
                }
            });
        })
        .catch(err => {
            console.error('Failed to update comparison count:', err);
        });
    }

    // Add product to comparison
    function addToComparison(productId) {
        if (!productId) {
            console.error('Product ID is required');
            if (typeof showCustomToast === 'function') {
                showCustomToast('Không tìm thấy ID sản phẩm', 'error', 3000);
            }
            return;
        }

        console.log('Fetching to add product:', productId);
        
        fetch(`/so-sanh/${productId}/add`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })
        .then(res => {
            console.log('Response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                if (typeof showCustomToast === 'function') {
                    showCustomToast(data.message, 'success', 3000);
                }
                updateComparisonCount();
            } else {
                if (typeof showCustomToast === 'function') {
                    showCustomToast(data.message || 'Không thể thêm sản phẩm vào so sánh', 'error', 3000);
                }
            }
        })
        .catch(err => {
            console.error('Failed to add to comparison:', err);
            if (typeof showCustomToast === 'function') {
                showCustomToast('Có lỗi xảy ra. Vui lòng thử lại.', 'error', 3000);
            }
        });
    }

    // Remove product from comparison
    function removeFromComparison(productId) {
        if (!productId) {
            console.error('Product ID is required');
            return;
        }

        console.log('Removing product from comparison:', productId);

        fetch(`/so-sanh/${productId}/remove`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            console.log('Remove response:', data);
            if (data.success) {
                if (typeof showCustomToast === 'function') {
                    showCustomToast(data.message, 'success', 3000);
                }
                updateComparisonCount();
                // Reload page if on comparison page
                if (window.location.pathname.includes('/so-sanh')) {
                    setTimeout(() => window.location.reload(), 500);
                }
            } else {
                if (typeof showCustomToast === 'function') {
                    showCustomToast(data.message || 'Không thể xóa sản phẩm khỏi so sánh', 'error', 3000);
                }
            }
        })
        .catch(err => {
            console.error('Failed to remove from comparison:', err);
            if (typeof showCustomToast === 'function') {
                showCustomToast('Có lỗi xảy ra. Vui lòng thử lại.', 'error', 3000);
            }
        });
    }

    // Clear all comparison
    function clearAllComparison() {
        if (!confirm('Xóa tất cả sản phẩm khỏi danh sách so sánh?')) {
            return;
        }

        fetch('/so-sanh/clear', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            }
        })
        .then(res => res.json())
        .then(data => {
            console.log('Clear response:', data);
            if (data.success) {
                if (typeof showCustomToast === 'function') {
                    showCustomToast(data.message, 'success', 3000);
                }
                updateComparisonCount();
                setTimeout(() => window.location.reload(), 500);
            } else {
                if (typeof showCustomToast === 'function') {
                    showCustomToast(data.message || 'Không thể xóa tất cả sản phẩm', 'error', 3000);
                }
            }
        })
        .catch(err => {
            console.error('Failed to clear comparison:', err);
            if (typeof showCustomToast === 'function') {
                showCustomToast('Có lỗi xảy ra. Vui lòng thử lại.', 'error', 3000);
            }
        });
    }

    // Initialize event listeners when DOM is ready
    function initComparisonListeners() {
        // Handle click on "Add to Comparison" buttons
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.add-to-comparison-btn');
            if (btn) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const productId = btn.dataset.productId || btn.getAttribute('data-product-id');
                if (productId) {
                    console.log('Adding product to comparison:', productId);
                    addToComparison(productId);
                } else {
                    console.error('Product ID not found on button:', btn);
                }
                return;
            }

            // Handle remove from comparison
            const removeBtn = e.target.closest('.remove-comparison');
            if (removeBtn) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                const productId = removeBtn.dataset.productId || removeBtn.getAttribute('data-product-id');
                if (productId) {
                    console.log('Removing product from comparison:', productId);
                    removeFromComparison(productId);
                }
                return;
            }

            // Handle clear all comparison
            const clearBtn = e.target.closest('#clear-comparison');
            if (clearBtn) {
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                clearAllComparison();
                return;
            }
        });
    }

    // Initialize when DOM is ready
    function init() {
        try {
            initComparisonListeners();
            updateComparisonCount();
        } catch (error) {
            console.error('Error initializing product comparison:', error);
        }
    }

    // Load comparison count on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        // DOM already loaded, run immediately
        init();
    }
})();