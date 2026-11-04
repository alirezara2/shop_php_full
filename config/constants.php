<?php
/**
 * ثابت‌های سراسری
 */

// وضعیت سفارش
define('ORDER_PENDING',    'pending');
define('ORDER_PAID',       'paid');
define('ORDER_PREPARING',  'preparing');
define('ORDER_SHIPPING',   'shipping');
define('ORDER_CANCELED',   'canceled');

// برچسب فارسی وضعیت سفارش
$GLOBALS['ORDER_STATUS_LABELS'] = [
    ORDER_PENDING   => 'در انتظار تایید فیش',
    ORDER_PAID      => 'پرداخت شده',
    ORDER_PREPARING => 'در حال آماده‌سازی',
    ORDER_SHIPPING  => 'در حال تحویل بسته پستی',
    ORDER_CANCELED  => 'لغو شده',
];

// رنگ Badge برای وضعیت
$GLOBALS['ORDER_STATUS_COLORS'] = [
    ORDER_PENDING   => '#f39c12',
    ORDER_PAID      => '#27ae60',
    ORDER_PREPARING => '#2980b9',
    ORDER_SHIPPING  => '#8e44ad',
    ORDER_CANCELED  => '#c0392b',
];

// وضعیت نظر
define('COMMENT_PENDING', 0);
define('COMMENT_APPROVED', 1);

// نقش‌ها (برای توسعه احتمالی)
define('ROLE_USER',  'user');
define('ROLE_ADMIN', 'admin');