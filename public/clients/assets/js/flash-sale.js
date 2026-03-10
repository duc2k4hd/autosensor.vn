document.addEventListener('DOMContentLoaded', () => {
    const meta = window.flashSaleMeta || {};
    initCountdown(meta);
    initFilters();
    initRemindButtons();
    initScrollTriggers();
});

function initCountdown(meta) {
    if (!meta?.hasFlashSale) {
        const sticky = document.querySelector('[data-countdown-part="sticky"]');
        if (sticky) {
            sticky.textContent = 'Đang cập nhật';
        }
        return;
    }

    const end = meta.endTime ? new Date(meta.endTime) : null;
    const start = meta.startTime ? new Date(meta.startTime) : null;

    if (!end || Number.isNaN(end.getTime())) {
        return;
    }

    const update = () => {
        const now = new Date();
        let diff = end - now;

        if (start && now < start) {
            diff = start - now;
        }

        if (diff <= 0) {
            renderCountdown(0);
            return;
        }

        renderCountdown(diff);
    };

    update();
    setInterval(update, 1000);
}

function renderCountdown(ms) {
    const seconds = Math.floor(ms / 1000);
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    const mapping = {
        days,
        hours,
        minutes,
        seconds: secs,
        sticky: `${pad(hours + days * 24)}:${pad(minutes)}:${pad(secs)}`,
    };

    Object.entries(mapping).forEach(([key, value]) => {
        document.querySelectorAll(`[data-countdown-part="${key}"]`).forEach((el) => {
            el.textContent = typeof value === 'number' ? pad(value) : value;
        });
    });
}

function pad(val) {
    return String(val).padStart(2, '0');
}

function initFilters() {
    const cardsContainer = document.querySelector('.flash-sale-grid');
    if (!cardsContainer) {
        return;
    }

    const cards = Array.from(cardsContainer.querySelectorAll('.flash-card'));
    const searchInput = document.getElementById('flash-search');
    const filterButtons = document.querySelectorAll('.filter-pill');
    const sortSelect = document.getElementById('flash-sort');
    const refreshBtn = document.getElementById('flash-refresh');

    let currentFilter = 'all';
    let currentKeyword = '';

    const applyFilters = () => {
        const normalizedKeyword = currentKeyword.trim().toLowerCase();
        cards.forEach((card) => {
            const name = card.dataset.name ?? '';
            const discount = Number(card.dataset.discount ?? 0);
            const remaining = Number(card.dataset.remaining ?? 0);
            const price = Number(card.dataset.price ?? 0);
            const lowStockFlag = card.dataset.lowStock === '1';
            const budgetFlag = card.dataset.budget === '1';

            const matchesKeyword = !normalizedKeyword || name.includes(normalizedKeyword);

            let matchesFilter = true;
            switch (currentFilter) {
                case 'hot':
                    matchesFilter = discount >= 40;
                    break;
                case 'low-stock':
                    matchesFilter = lowStockFlag || remaining <= 5;
                    break;
                case 'budget':
                    matchesFilter = budgetFlag || price < 500000;
                    break;
                default:
                    matchesFilter = true;
            }

            const shouldShow = matchesKeyword && matchesFilter;
            card.style.display = shouldShow ? '' : 'none';
        });
    };

    const applySort = (mode) => {
        const sorted = [...cards].sort((a, b) => {
            const priceA = Number(a.dataset.price ?? 0);
            const priceB = Number(b.dataset.price ?? 0);
            const discountA = Number(a.dataset.discount ?? 0);
            const discountB = Number(b.dataset.discount ?? 0);
            const stockA = Number(a.dataset.stock ?? 0);
            const stockB = Number(b.dataset.stock ?? 0);

            switch (mode) {
                case 'discount_desc':
                    return discountB - discountA;
                case 'price_asc':
                    return priceA - priceB;
                case 'price_desc':
                    return priceB - priceA;
                case 'stock_desc':
                    return stockB - stockA;
                default:
                    return discountB - discountA;
            }
        });

        sorted.forEach((card) => cardsContainer.appendChild(card));
    };

    searchInput?.addEventListener('input', (event) => {
        currentKeyword = event.target.value;
        applyFilters();
    });

    filterButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            filterButtons.forEach((button) => button.classList.remove('is-active'));
            btn.classList.add('is-active');
            currentFilter = btn.dataset.filter ?? 'all';
            applyFilters();
        });
    });

    sortSelect?.addEventListener('change', (event) => {
        applySort(event.target.value);
    });

    refreshBtn?.addEventListener('click', () => {
        currentFilter = 'all';
        currentKeyword = '';
        if (searchInput) {
            searchInput.value = '';
        }
        filterButtons.forEach((button) => button.classList.remove('is-active'));
        filterButtons[0]?.classList.add('is-active');
        cards.forEach((card) => {
            card.style.display = '';
        });
        applySort('featured');
    });

    applyFilters();
    applySort('featured');
}

function initRemindButtons() {
    document.querySelectorAll('.upcoming-remind').forEach((button) => {
        button.addEventListener('click', () => {
            const title = button.dataset.title ?? 'Flash Sale';
            const time = button.dataset.time ? new Date(button.dataset.time) : null;
            const timeLabel = time ? time.toLocaleString('vi-VN') : 'sắp diễn ra';
            alert(`Đã ghi nhớ: ${title} (${timeLabel}). Chúng tôi sẽ nhắc bạn ngay khi mở bán!`);
        });
    });
}

function initScrollTriggers() {
    document.querySelectorAll('[data-scroll]').forEach((trigger) => {
        trigger.addEventListener('click', () => {
            const targetSelector = trigger.getAttribute('data-scroll');
            const target = targetSelector ? document.querySelector(targetSelector) : null;
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

