/* ============================================
   اسکریپت عمومی سایت
   ============================================ */

(function () {
    'use strict';

    /* ---------- منوی موبایل ---------- */
    const menuToggle = document.getElementById('menuToggle');
    const mainNav = document.getElementById('mainNav');
    const overlay = document.getElementById('overlay');

    function openMenu() {
        if (!mainNav || !menuToggle) return;
        mainNav.classList.add('open');
        menuToggle.classList.add('active');
        menuToggle.setAttribute('aria-expanded', 'true');
        if (overlay) overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeMenu() {
        if (!mainNav || !menuToggle) return;
        mainNav.classList.remove('open');
        menuToggle.classList.remove('active');
        menuToggle.setAttribute('aria-expanded', 'false');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            if (mainNav.classList.contains('open')) {
                closeMenu();
            } else {
                openMenu();
            }
        });
    }

    if (overlay) {
        overlay.addEventListener('click', closeMenu);
    }

    // بستن با Esc
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeMenu();
    });

    // بستن وقتی روی لینک‌های منو کلیک می‌شود (در موبایل)
    if (mainNav) {
        mainNav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    closeMenu();
                }
            });
        });
    }

    // اگر صفحه از موبایل به دسکتاپ تغییر کرد، منو بسته شود
    let resizeTimer;
    window.addEventListener('resize', function () {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function () {
            if (window.innerWidth > 768) {
                closeMenu();
            }
        }, 200);
    });

    /* ---------- اسلایدر ---------- */
    const slider = document.querySelector('.hero-slider');
    if (slider) {
        const track = slider.querySelector('.slider-track');
        const slides = slider.querySelectorAll('.slide');
        const prevBtn = slider.querySelector('.slider-prev');
        const nextBtn = slider.querySelector('.slider-next');
        const dotsContainer = slider.querySelector('.slider-dots');
        let currentIndex = 0;
        let autoTimer = null;
        const total = slides.length;

        if (total > 0) {
            if (dotsContainer) {
                for (let i = 0; i < total; i++) {
                    const dot = document.createElement('button');
                    dot.className = 'slider-dot' + (i === 0 ? ' active' : '');
                    dot.setAttribute('aria-label', 'اسلاید ' + (i + 1));
                    dot.addEventListener('click', function () { goTo(i); });
                    dotsContainer.appendChild(dot);
                }
            }

            function goTo(index) {
                if (index < 0) index = total - 1;
                if (index >= total) index = 0;
                currentIndex = index;
                track.style.transform = 'translateX(' + (index * 100) + '%)';
                updateDots();
            }

            function updateDots() {
                if (!dotsContainer) return;
                const dots = dotsContainer.querySelectorAll('.slider-dot');
                dots.forEach(function (d, i) {
                    d.classList.toggle('active', i === currentIndex);
                });
            }

            function next() { goTo(currentIndex + 1); }
            function prev() { goTo(currentIndex - 1); }

            if (nextBtn) nextBtn.addEventListener('click', next);
            if (prevBtn) prevBtn.addEventListener('click', prev);

            function startAuto() {
                stopAuto();
                if (total > 1) autoTimer = setInterval(next, 5000);
            }

            function stopAuto() {
                if (autoTimer) { clearInterval(autoTimer); autoTimer = null; }
            }

            slider.addEventListener('mouseenter', stopAuto);
            slider.addEventListener('mouseleave', startAuto);
            startAuto();
        }
    }

    /* ---------- تب‌ها ---------- */
    document.querySelectorAll('.tabs-nav').forEach(function (nav) {
        const buttons = nav.querySelectorAll('button');
        const tabsWrapper = nav.closest('.tabs');
        if (!tabsWrapper) return;
        const contents = tabsWrapper.querySelectorAll('.tab-content');

        buttons.forEach(function (btn, index) {
            btn.addEventListener('click', function () {
                buttons.forEach(function (b) { b.classList.remove('active'); });
                contents.forEach(function (c) { c.classList.remove('active'); });
                btn.classList.add('active');
                if (contents[index]) contents[index].classList.add('active');
            });
        });
    });

    /* ---------- انتخاب تعداد ---------- */
    document.querySelectorAll('.quantity-selector').forEach(function (wrap) {
        const input = wrap.querySelector('input');
        const minus = wrap.querySelector('.qty-minus');
        const plus = wrap.querySelector('.qty-plus');
        if (!input) return;

        if (minus) {
            minus.addEventListener('click', function () {
                let v = parseInt(input.value, 10) || 1;
                if (v > 1) input.value = v - 1;
            });
        }
        if (plus) {
            plus.addEventListener('click', function () {
                let v = parseInt(input.value, 10) || 1;
                const max = parseInt(input.getAttribute('max'), 10) || 999;
                if (v < max) input.value = v + 1;
            });
        }
        input.addEventListener('change', function () {
            let v = parseInt(this.value, 10);
            if (isNaN(v) || v < 1) v = 1;
            const max = parseInt(this.getAttribute('max'), 10) || 999;
            if (v > max) v = max;
            this.value = v;
        });
    });

    /* ---------- پیش‌نمایش عکس ---------- */
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const targetId = this.getAttribute('data-preview');
            const target = document.getElementById(targetId);
            if (!target) return;
            const file = this.files && this.files[0];
            if (!file) { target.style.display = 'none'; return; }
            const reader = new FileReader();
            reader.onload = function (e) {
                target.src = e.target.result;
                target.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });

    /* ---------- تأیید حذف ---------- */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'آیا مطمئن هستید؟';
            if (!confirm(msg)) {
                e.preventDefault();
                return false;
            }
        });
    });

})();