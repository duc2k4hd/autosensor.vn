// public/clients/assets/js/blog-detail.js
// Deferred blog detail page initialization
// Load after page interactive (defer non-critical)

document.addEventListener('DOMContentLoaded', initBlogDetail);

function initBlogDetail() {
    // Use requestIdleCallback để defer non-critical init
    if ('requestIdleCallback' in window) {
        requestIdleCallback(() => {
            initCartoon();
            initTOCHighlight();
            initImageAnimations();
        }, { timeout: 2000 });
    } else {
        // Fallback cho old browsers
        setTimeout(() => {
            initCartoon();
            initTOCHighlight();
            initImageAnimations();
        }, 100);
    }
    
    // Critical init (chạy ngay)
    initSmoothScroll();
    initCarousel();
}

// 1. CRITICAL: Smooth scroll links (thường cần dùng)
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        }, { passive: true });
    });
}

// 2. CRITICAL: Carousel (dùng thường nếu có images)
function initCarousel() {
    const carousel = document.getElementById('postImageCarousel');
    if (!carousel) return;

    const items = carousel.querySelectorAll('.autosensor-article-carousel-item');
    if (items.length <= 1) return;

    const prevBtn = carousel.querySelector('.autosensor-article-carousel-prev');
    const nextBtn = carousel.querySelector('.autosensor-article-carousel-next');
    let currentIndex = 0;

    function showSlide(index) {
        items.forEach((item, i) => {
            item.classList.toggle('active', i === index);
        });
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % items.length;
        showSlide(currentIndex);
    }

    function prevSlide() {
        currentIndex = (currentIndex - 1 + items.length) % items.length;
        showSlide(currentIndex);
    }

    // Event delegation for passive scrolling
    if (prevBtn) prevBtn.addEventListener('click', prevSlide, { passive: true });
    if (nextBtn) nextBtn.addEventListener('click', nextSlide, { passive: true });
}

// 3. NON-CRITICAL: Defer TOC highlight (complex logic)
function initTOCHighlight() {
    const tocDesktop = document.getElementById('toc-desktop');
    const tocMobile = document.getElementById('toc-mobile');
    const tocContainer = tocDesktop || tocMobile;
    
    if (!tocContainer) return;

    const contentSections = document.querySelectorAll(
        '.autosensor_blog_article-content h2[id], ' +
        '.autosensor_blog_article-content h3[id]'
    );
    if (contentSections.length === 0) return;

    // Cache TOC links in Map (instead of query each time)
    const tocLinks = new Map();
    tocContainer.querySelectorAll('a[href]').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.startsWith('#')) {
            const id = href.substring(1);
            tocLinks.set(id, link);
        }
    });

    let activeId = null;

    // Single IntersectionObserver (instead of multiple)
    const observer = new IntersectionObserver((entries) => {
        // Find most visible entry
        let mostVisible = null;
        let maxRatio = 0;

        for (const entry of entries) {
            if (entry.intersectionRatio > maxRatio) {
                maxRatio = entry.intersectionRatio;
                mostVisible = entry;
            }
        }

        if (!mostVisible) {
            // If none visible, pick closest to viewport top
            const viewportTop = window.scrollY + 100;
            for (const entry of entries) {
                const elementTop = entry.target.getBoundingClientRect().top + window.scrollY;
                if (elementTop <= viewportTop) {
                    mostVisible = entry;
                    break;
                }
            }
        }

        if (mostVisible) {
            const id = mostVisible.target.getAttribute('id');
            if (id && id !== activeId) {
                activeId = id;

                // Update active link
                tocContainer.querySelectorAll('a').forEach(link => {
                    link.classList.remove('active');
                });

                const activeLink = tocLinks.get(id);
                if (activeLink) {
                    activeLink.classList.add('active');
                    
                    // Auto-scroll TOC if needed
                    autoScrollTOC(tocContainer, activeLink);
                }
            }
        }
    }, {
        rootMargin: '-100px 0px -60% 0px',
        threshold: [0, 0.1, 0.5, 1]
    });

    // Observe all headings
    contentSections.forEach(section => observer.observe(section));

    // TOC click handler
    tocContainer.addEventListener('click', (e) => {
        const link = e.target.closest('a[href^="#"]');
        if (!link) return;

        const targetId = link.getAttribute('href').substring(1);
        const target = document.getElementById(targetId);
        if (target) {
            const offset = 100;
            const targetPosition = target.getBoundingClientRect().top + window.pageYOffset - offset;
            
            window.scrollTo({
                top: targetPosition,
                behavior: 'smooth'
            });

            // Update active after scroll
            setTimeout(() => {
                activeId = null; // Reset to force update
            }, 100);
        }
    }, { passive: true });

    // Lazy load images in content
    const images = document.querySelectorAll(
        '.autosensor_blog_article-content img:not([loading])'
    );
    images.forEach(img => {
        img.setAttribute('loading', 'lazy');
    });
}

// Helper: Auto-scroll TOC if link is out of view
function autoScrollTOC(container, link) {
    if (container.scrollHeight <= container.clientHeight) return;

    const linkTop = link.offsetTop;
    const linkHeight = link.offsetHeight;
    const containerHeight = container.clientHeight;
    const scrollTop = container.scrollTop;

    if (linkTop < scrollTop) {
        container.scrollTop = linkTop - 20;
    } else if (linkTop + linkHeight > scrollTop + containerHeight) {
        container.scrollTop = linkTop - containerHeight + linkHeight + 20;
    }
}

// 4. NON-CRITICAL: Defer image animations
function initImageAnimations() {
    const images = document.querySelectorAll('.autosensor_blog_article-content img');
    if (images.length === 0) return;

    // Setup initial styles (use CSS class instead of inline)
    const style = document.createElement('style');
    style.textContent = `
        .blog-image-fade-in {
            animation: blog-fade-in 0.6s ease-in forwards;
        }
        @keyframes blog-fade-in {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);

    // IntersectionObserver for lazy animation
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('blog-image-fade-in');
                imageObserver.unobserve(entry.target);
            }
        });
    }, { rootMargin: '50px' });

    images.forEach(img => imageObserver.observe(img));
}

// 5. NON-CRITICAL: Chat bubble animation
function initChatBubble() {
    const chatBubble = document.querySelector('.autosensor_blog_chat-bubble');
    if (!chatBubble) return;

    const pulseStyle = document.createElement('style');
    pulseStyle.id = 'blog-pulse-animation';
    pulseStyle.textContent = `
        @keyframes blog-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .chat-pulse {
            animation: blog-pulse 1s ease-in-out;
        }
    `;
    document.head.appendChild(pulseStyle);

    // Pulse animation every 5 seconds
    setInterval(() => {
        chatBubble.classList.add('chat-pulse');
        setTimeout(() => {
            chatBubble.classList.remove('chat-pulse');
        }, 1000);
    }, 5000);
}

// Defer chat bubble init (nice-to-have)
if ('requestIdleCallback' in window) {
    requestIdleCallback(initChatBubble, { timeout: 5000 });
} else {
    setTimeout(initChatBubble, 2000);
}

// Export for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { initBlogDetail, initSmoothScroll, initCarousel, initTOCHighlight };
}
