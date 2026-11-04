        </main>
        <!-- پایان محتوای صفحه -->

    </div>
    <!-- پایان admin-main -->

</div>
<!-- پایان admin-layout -->

<script>
(function () {
    'use strict';

    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('adminOverlay');
    const toggle = document.getElementById('sidebarToggle');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            if (overlay) overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // تأیید حذف
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const msg = this.getAttribute('data-confirm') || 'آیا مطمئن هستید؟';
            if (!confirm(msg)) {
                e.preventDefault();
                return false;
            }
        });
    });

    // پیش‌نمایش عکس
    document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
        input.addEventListener('change', function () {
            const targetId = this.getAttribute('data-preview');
            const target = document.getElementById(targetId);
            if (!target) return;
            const file = this.files && this.files[0];
            if (!file) {
                target.style.display = 'none';
                return;
            }
            const reader = new FileReader();
            reader.onload = function (e) {
                target.src = e.target.result;
                target.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });

    // به‌روزرسانی خودکار فایل‌های انتخاب‌شده (نمایش نام)
    document.querySelectorAll('input[type="file"]').forEach(function (input) {
        input.addEventListener('change', function () {
            const fileNameEl = document.getElementById(this.id + '_name');
            if (!fileNameEl) return;
            if (this.files && this.files.length > 0) {
                fileNameEl.textContent = this.files[0].name;
            } else {
                fileNameEl.textContent = '';
            }
        });
    });

})();
</script>

</body>
</html>