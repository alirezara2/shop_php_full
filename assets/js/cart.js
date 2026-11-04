/* ============================================
   اسکریپت سبد خرید (آجاکس اختیاری)
   ============================================ */

(function () {
    'use strict';

    const BASE_URL = window.BASE_URL || '';

    /**
     * به‌روزرسانی تعداد در سبد خرید
     */
    document.querySelectorAll('.cart-qty-input').forEach(function (input) {
        let timer = null;
        input.addEventListener('change', function () {
            const productId = this.getAttribute('data-product-id');
            const qty = parseInt(this.value, 10) || 1;

            clearTimeout(timer);
            timer = setTimeout(function () {
                updateCartItem(productId, qty);
            }, 300);
        });
    });

    function updateCartItem(productId, qty) {
        fetch(BASE_URL + '/user/cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'action=update&product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(qty)
        })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.ok) {
                    window.location.reload();
                } else {
                    alert(data.msg || 'خطا در به‌روزرسانی');
                }
            })
            .catch(function () {
                alert('خطا در ارتباط با سرور');
            });
    }

    /**
     * دکمه‌های افزودن به سبد در صفحه محصول (صفحه محصولات دیگر)
     */
    document.querySelectorAll('.add-to-cart-ajax').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const productId = this.getAttribute('data-product-id');
            const qtyInput = document.querySelector('.quantity-input-' + productId);
            const qty = qtyInput ? (parseInt(qtyInput.value, 10) || 1) : 1;

            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = 'در حال افزودن...';

            fetch(BASE_URL + '/user/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=add&product_id=' + encodeURIComponent(productId) + '&quantity=' + encodeURIComponent(qty)
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok) {
                        updateCartBadge(data.count);
                        showToast('محصول به سبد خرید اضافه شد', 'success');
                    } else {
                        showToast(data.msg || 'خطا در افزودن به سبد', 'error');
                    }
                })
                .catch(function () {
                    showToast('خطا در ارتباط با سرور', 'error');
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = originalText;
                });
        });
    });

    /**
     * افزودن چندتایی به سبد (صفحه محصولات دیگر)
     */
    const multiAddBtn = document.querySelector('#multi-add-btn');
    if (multiAddBtn) {
        multiAddBtn.addEventListener('click', function () {
            const checked = document.querySelectorAll('input[name="related_products[]"]:checked');
            if (checked.length === 0) {
                showToast('حداقل یک محصول انتخاب کنید', 'warning');
                return;
            }

            const ids = Array.from(checked).map(function (el) { return el.value; });
            this.disabled = true;
            const originalText = this.textContent;
            this.textContent = 'در حال افزودن...';

            fetch(BASE_URL + '/user/cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'action=add_multi&product_ids=' + encodeURIComponent(JSON.stringify(ids))
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.ok) {
                        updateCartBadge(data.count);
                        showToast(data.added + ' محصول به سبد اضافه شد', 'success');
                        checked.forEach(function (c) { c.checked = false; });
                    } else {
                        showToast(data.msg || 'خطا در افزودن', 'error');
                    }
                })
                .catch(function () {
                    showToast('خطا در ارتباط با سرور', 'error');
                })
                .finally(function () {
                    multiAddBtn.disabled = false;
                    multiAddBtn.textContent = originalText;
                });
        });
    }

    /**
     * به‌روزرسانی بج تعداد سبد در هدر
     */
    function updateCartBadge(count) {
        const badge = document.querySelector('.cart-badge');
        if (!badge) return;
        badge.textContent = count;
        if (count > 0) {
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }

    /**
     * نمایش توست ساده
     */
    function showToast(message, type) {
        const colors = {
            success: '#27ae60',
            error: '#c0392b',
            warning: '#f39c12',
            info: '#2980b9'
        };
        const toast = document.createElement('div');
        toast.style.cssText =
            'position:fixed;bottom:24px;left:50%;transform:translateX(-50%);' +
            'background:' + (colors[type] || colors.info) + ';color:#fff;' +
            'padding:12px 24px;border-radius:8px;z-index:9999;' +
            'box-shadow:0 4px 20px rgba(0,0,0,0.2);font-size:0.95rem;' +
            'font-family:inherit;direction:rtl;';
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(function () {
            toast.style.transition = 'opacity 0.4s';
            toast.style.opacity = '0';
            setTimeout(function () { toast.remove(); }, 400);
        }, 2800);
    }

})();