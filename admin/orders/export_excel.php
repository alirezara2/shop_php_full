<?php
require_once dirname(__DIR__) . '/includes/admin_auth.php';

/**
 * خروجی اکسل سفارش‌ها
 * اگر PhpSpreadsheet نصب باشد از xlsx استفاده می‌کند، وگرنه CSV با BOM.
 */

// ----- همان فیلترهای list.php -----
$statusFilter = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($statusFilter !== '' && in_array($statusFilter, [ORDER_PENDING, ORDER_PAID, ORDER_PREPARING, ORDER_SHIPPING, ORDER_CANCELED], true)) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}
if ($search !== '') {
    $where[] = "(u.phone LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR o.id = ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = (int)$search;
}
if ($dateFrom !== '') {
    $where[] = "DATE(o.created_at) >= ?";
    $params[] = $dateFrom;
}
if ($dateTo !== '') {
    $where[] = "DATE(o.created_at) <= ?";
    $params[] = $dateTo;
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ----- گرفتن سفارش‌ها -----
$sql = "
    SELECT o.*, u.phone AS user_phone, u.first_name, u.last_name
    FROM orders o
    JOIN users u ON u.id = o.user_id
    $whereSql
    ORDER BY o.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// ----- هدرهای ستون‌ها -----
$headers = [
    'شماره سفارش', 'نام کاربر', 'شماره تماس', 'مبلغ (تومان)', 'وضعیت',
    'تاریخ', 'شهر', 'آدرس', 'کد پستی', 'تلفن ارسال', 'یادداشت ادمین',
];

$rows = [];
foreach ($orders as $o) {
    $rows[] = [
        generateOrderNumber((int)$o['id']),
        trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: '-',
        $o['user_phone'] ?? '-',
        (int)$o['total_amount'],
        statusLabel($o['status']),
        date('Y/m/d H:i', strtotime($o['created_at'])),
        $o['shipping_city'] ?? '-',
        $o['shipping_address'] ?? '-',
        $o['shipping_postal_code'] ?? '-',
        $o['shipping_phone'] ?? '-',
        $o['admin_note'] ?? '',
    ];
}

$filename = 'orders_' . date('Y-m-d_His');

// ----- اگر PhpSpreadsheet نصب است، xlsx -----
$usePhpSpreadsheet = file_exists(BASE_PATH . '/vendor/autoload.php');

if ($usePhpSpreadsheet) {
    require_once BASE_PATH . '/vendor/autoload.php';

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setRightToLeft(true);

    // عنوان فایل
    $sheet->setCellValue('A1', 'لیست سفارش‌ها — ' . getSetting('site_name', 'فروشگاه'));
    $sheet->mergeCells('A1:K1');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');

    // هدر
    $col = 'A';
    foreach ($headers as $h) {
        $sheet->setCellValue($col . '3', $h);
        $sheet->getStyle($col . '3')->getFont()->setBold(true);
        $sheet->getStyle($col . '3')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('2c5e3f');
        $sheet->getStyle($col . '3')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($col . '3')->getAlignment()->setHorizontal('center');
        $col++;
    }

    // داده‌ها
    $rowNum = 4;
    foreach ($rows as $row) {
        $col = 'A';
        foreach ($row as $value) {
            $sheet->setCellValue($col . $rowNum, $value);
            $col++;
        }
        $rowNum++;
    }

    // عرض خودکار ستون‌ها
    foreach (range('A', 'K') as $c) {
        $sheet->getColumnDimension($c)->setAutoSize(true);
    }

    // Border
    $lastRow = $rowNum - 1;
    $styleArray = [
        'borders' => [
            'allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN],
        ],
    ];
    $sheet->getStyle('A3:K' . $lastRow)->applyFromArray($styleArray);

    // خروجی
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

// ----- اگر PhpSpreadsheet نصب نیست، CSV با BOM -----
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment;filename="' . $filename . '.csv"');

$out = fopen('php://output', 'w');
// BOM برای نمایش صحیح فارسی در اکسل
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, $headers);
foreach ($rows as $row) {
    fputcsv($out, $row);
}
fclose($out);
exit;