<?php
require_once dirname(__DIR__) . '/config/config.php';

logoutAdmin();
setFlash('success', 'با موفقیت از حساب خود خارج شدید.');
redirect(BASE_URL . '/admin/login.php');