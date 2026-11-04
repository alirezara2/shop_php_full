<?php
require_once dirname(__DIR__) . '/config/config.php';

logoutUser();
setFlash('success', 'با موفقیت از حساب خود خارج شدید.');
redirect(BASE_URL . '/user/login.php');