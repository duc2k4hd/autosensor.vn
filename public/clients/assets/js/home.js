(async () => {
    // === SLIDER CHÍNH ===
const sliderTrack =
    document.querySelector(".autosensor_main_slider_main_slider_track") ||
    document.querySelector(".autosensor_main_slider_track");

    const slides = document.querySelectorAll(
        ".autosensor_main_slider_main_slide, .autosensor_main_slider_item"
    );

    // === Tạo dots tự động ===
    const dotsContainer = document.querySelector(".autosensor_main_slider_main_dots");

    if (dotsContainer) {
        dotsContainer.innerHTML = "";
        slides.forEach((_, idx) => {
            const dot = document.createElement("button");
            dot.className = "autosensor_main_slider_dot";
            if (idx === 0) dot.classList.add("autosensor_main_slider_dot_active");
            dotsContainer.appendChild(dot);
        });
    }

    const dots = document.querySelectorAll(
        ".autosensor_main_slider_main_dots .autosensor_main_slider_dot"
    );

    let currentSlide = 0;
    let autoSlide;
    let isDragging = false;
    let startPos = 0;
    let currentTranslate = 0;
    let prevTranslate = 0;
    let animationID;

    // ---- Update Slider ----
    const updateSlider = () => {
        if (!sliderTrack || slides.length === 0) return;

        dots.forEach(dot => dot.classList.remove("autosensor_main_slider_dot_active"));
        if (dots[currentSlide]) {
            dots[currentSlide].classList.add("autosensor_main_slider_dot_active");
        }

        sliderTrack.style.transition = "transform .35s ease";
        sliderTrack.style.transform = `translateX(-${currentSlide * 100}%)`;

        prevTranslate = -currentSlide * sliderTrack.offsetWidth;
    };

    // ---- Auto Slide ----
    const startAuto = () => {
        stopAuto();
        autoSlide = setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            updateSlider();
        }, 5000);
    };

    const stopAuto = () => clearInterval(autoSlide);

    // ---- BUTTONS ----
    document.querySelector(".autosensor_main_slider_prev")?.addEventListener("click", () => {
        currentSlide = (currentSlide - 1 + slides.length) % slides.length;
        updateSlider();
        startAuto();
    });

    document.querySelector(".autosensor_main_slider_next")?.addEventListener("click", () => {
        currentSlide = (currentSlide + 1) % slides.length;
        updateSlider();
        startAuto();
    });

    // ---- DOTS ----
    dots.forEach((dot, idx) => {
        dot.addEventListener("click", () => {
            currentSlide = idx;
            updateSlider();
            startAuto();
        });
    });

    // ---- DRAG & TOUCH ----
    const touchStart = (x) => {
        isDragging = true;
        startPos = x - prevTranslate;
        sliderTrack.style.transition = "none";
        stopAuto();
    };

    const touchMove = (x) => {
        if (!isDragging) return;
        currentTranslate = x - startPos;
        sliderTrack.style.transform = `translateX(${currentTranslate}px)`;
    };

    const touchEnd = () => {
        if (!isDragging) return;
        isDragging = false;

        const moved = currentTranslate - prevTranslate;
        const threshold = sliderTrack.offsetWidth * 0.2; // 20% width

        if (moved < -threshold) currentSlide = Math.min(currentSlide + 1, slides.length - 1);
        if (moved > threshold) currentSlide = Math.max(currentSlide - 1, 0);

        updateSlider();
        startAuto();
    };

    // ---- MOUSE EVENTS ----
    sliderTrack.addEventListener("mousedown", e => touchStart(e.clientX));
    sliderTrack.addEventListener("mousemove", e => touchMove(e.clientX));
    sliderTrack.addEventListener("mouseup", touchEnd);
    sliderTrack.addEventListener("mouseleave", touchEnd);

    // ---- TOUCH EVENTS ----
    sliderTrack.addEventListener("touchstart", e => touchStart(e.touches[0].clientX));
    sliderTrack.addEventListener("touchmove", e => touchMove(e.touches[0].clientX));
    sliderTrack.addEventListener("touchend", touchEnd);

    // ---- START ----
    if (sliderTrack && slides.length > 1) startAuto();


    // Set thời gian kết thúc Flash Sale (ví dụ lấy từ DB)
    const endTime = typeof timeFlashSale !== "undefined"
        ? Number(timeFlashSale)
        : null;

    // Lấy các phần tử hiển thị
    const daysEl = document.querySelector(".autosensor_flash_sale_timer_days");
    const hoursEl = document.querySelector(
        ".autosensor_flash_sale_timer_hours"
    );
    const minutesEl = document.querySelector(
        ".autosensor_flash_sale_timer_minutes"
    );
    const secondsEl = document.querySelector(
        ".autosensor_flash_sale_timer_seconds"
    );

    // Lưu giá trị trước đó để so sánh
    let prevDays, prevHours, prevMinutes, prevSeconds;

    function updateTimer() {
        const now = new Date().getTime();
        let distance = endTime - now;

        if (distance <= 0) {
            daysEl.textContent = "00";
            hoursEl.textContent = "00";
            minutesEl.textContent = "00";
            secondsEl.textContent = "00";
            clearInterval(interval);
            return;
        }

        let days = Math.floor(distance / (1000 * 60 * 60 * 24));
        let hours = Math.floor(
            (distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60)
        );
        let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Format 2 chữ số
        days = String(days).padStart(2, "0");
        hours = String(hours).padStart(2, "0");
        minutes = String(minutes).padStart(2, "0");
        seconds = String(seconds).padStart(2, "0");

        // Chỉ animate khi giá trị thay đổi
        if (seconds !== prevSeconds) animateFlip(secondsEl, seconds);
        if (minutes !== prevMinutes) animateFlip(minutesEl, minutes);
        if (hours !== prevHours) animateFlip(hoursEl, hours);
        if (days !== prevDays) animateFlip(daysEl, days);

        // Cập nhật giá trị trước đó
        prevDays = days;
        prevHours = hours;
        prevMinutes = minutes;
        prevSeconds = seconds;
    }

    // Hàm gán text + trigger animation
    function animateFlip(el, newValue) {
        el.textContent = newValue;
        el.classList.remove("flip-animate");
        void el.offsetWidth; // reset
        el.classList.add("flip-animate");
    }

    if (endTime && !Number.isNaN(endTime)) {
        const interval = setInterval(updateTimer, 1000);
        updateTimer();
    }
})();


document.addEventListener('DOMContentLoaded', function() {
    const partnersSlider = document.querySelector('.autosensor_partners_slider');
    const partnersTrack = document.querySelector('.autosensor_partners_slider_track');
    
    if (partnersSlider && partnersTrack && window.innerWidth >= 769) {
        let currentScroll = 0;
        let animationFrame = null;
        
        // Lưu vị trí scroll hiện tại khi hover
        partnersSlider.addEventListener('mouseenter', function() {
            // Pause animation và chuyển sang scroll mode
            partnersTrack.style.animationPlayState = 'paused';
            // Lưu vị trí transform hiện tại
            const transform = window.getComputedStyle(partnersTrack).transform;
            if (transform && transform !== 'none') {
                const matrix = transform.match(/matrix\(([^)]+)\)/);
                if (matrix) {
                    const values = matrix[1].split(',');
                    currentScroll = parseFloat(values[4]) || 0;
                    partnersSlider.scrollLeft = Math.abs(currentScroll);
                }
            }
        });
        
        // Resume animation khi rời chuột
        partnersSlider.addEventListener('mouseleave', function() {
            // Reset scroll và resume animation
            partnersSlider.scrollLeft = 0;
            partnersTrack.style.animationPlayState = 'running';
        });
        
        // Enable scroll on mouse wheel
        partnersSlider.addEventListener('wheel', function(e) {
            e.preventDefault();
            partnersSlider.scrollBy({
                left: e.deltaY > 0 ? 100 : -100,
                behavior: 'smooth'
            });
        }, { passive: false });
        
        // Enable drag to scroll
        let isDragging = false;
        let startX = 0;
        let scrollLeft = 0;
        
        partnersSlider.addEventListener('mousedown', function(e) {
            isDragging = true;
            startX = e.pageX - partnersSlider.offsetLeft;
            scrollLeft = partnersSlider.scrollLeft;
            partnersSlider.style.cursor = 'grabbing';
        });
        
        partnersSlider.addEventListener('mouseup', function() {
            isDragging = false;
            partnersSlider.style.cursor = 'grab';
        });
        
        partnersSlider.addEventListener('mousemove', function(e) {
            if (!isDragging) return;
            e.preventDefault();
            const x = e.pageX - partnersSlider.offsetLeft;
            const walk = (x - startX) * 2;
            partnersSlider.scrollLeft = scrollLeft - walk;
        });
    } else if (partnersSlider && partnersTrack && window.innerWidth < 769) {
        // Mobile: always use native scroll
        partnersSlider.style.overflowX = 'auto';
        partnersTrack.style.animation = 'none';
    }
});

(function() {
    const productsSection = document.querySelector('.autosensor_home_products_section');
    if (!productsSection) return;

    const viewport = productsSection.querySelector('.autosensor_home_products_viewport');
    const list = productsSection.querySelector('.autosensor_home_products_list');
    const prevBtn = productsSection.querySelector('.autosensor_home_products_nav_prev');
    const nextBtn = productsSection.querySelector('.autosensor_home_products_nav_next');

    if (!viewport || !list || !prevBtn || !nextBtn) return;

    const cardWidth = 200;
    const gap = 16;
    const scrollAmount = cardWidth + gap;

    function updateNavButtons() {
        const scrollLeft = viewport.scrollLeft;
        const maxScroll = list.scrollWidth - viewport.clientWidth;
        
        if (scrollLeft <= 1) {
            prevBtn.disabled = true;
            prevBtn.setAttribute('aria-disabled', 'true');
        } else {
            prevBtn.disabled = false;
            prevBtn.setAttribute('aria-disabled', 'false');
        }
        
        if (scrollLeft >= maxScroll - 1) {
            nextBtn.disabled = true;
            nextBtn.setAttribute('aria-disabled', 'true');
        } else {
            nextBtn.disabled = false;
            nextBtn.setAttribute('aria-disabled', 'false');
        }
    }

    prevBtn.addEventListener('click', function() {
        if (this.disabled) return;
        viewport.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
    });

    nextBtn.addEventListener('click', function() {
        if (this.disabled) return;
        viewport.scrollBy({ left: scrollAmount, behavior: 'smooth' });
    });

    viewport.addEventListener('scroll', updateNavButtons);
    
    // Initial check
    setTimeout(updateNavButtons, 100);
    
    // Check on resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(updateNavButtons, 150);
    });
})();

const emblaHomeSlider = document.querySelector(".autosensor_main_slider_main_slider_track");
EmblaCarousel(emblaHomeSlider, { loop: false, dragFree: false })

const emblaCategoriesList = document.querySelector(".autosensor_main_categories_viewport");
EmblaCarousel(emblaCategoriesList, { 
    dragFree: true,
    align: 'start',
    containScroll: 'trimSnaps',
    loop: false
})
