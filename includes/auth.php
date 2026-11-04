<?php
if (!defined('BASE_PATH')) { die('دسترسی مستقیم مجاز نیست.'); }

function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function currentUser(): ?array {
    global $pdo;
    if (!isLoggedIn()) return null;
    static $user = null;
    if ($user !== null) return $user;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch() ?: null;
    return $user;
}

function currentAdmin(): ?array {
    global $pdo;
    if (!isAdminLoggedIn()) return null;
    static $admin = null;
    if ($admin !== null) return $admin;

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch() ?: null;
    return $admin;
}

function requireLogin(string $redirectTo = null): void {
    if (!isLoggedIn()) {
        $redirectTo = $redirectTo ?? ($_SERVER['REQUEST_URI'] ?? '');
        $_SESSION['redirect_after_login'] = $redirectTo;
        setFlash('warning', 'برای دسترسی به این صفحه ابتدا وارد شوید.');
        redirect(BASE_URL . '/user/login.php');
    }
}

function requireAdmin(): void {
    if (!isAdminLoggedIn()) {
        redirect(BASE_URL . '/admin/login.php');
    }
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$user['id'];
    unset($_SESSION['admin_id']);
}

function loginAdmin(array $admin): void {
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int)$admin['id'];
}

function logoutUser(): void {
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function logoutAdmin(): void {
    unset($_SESSION['admin_id']);
    session_regenerate_id(true);
}

function registerUser(string $phone, string $password): array {
    global $pdo;

    $phone = normalizePhone($phone);
    if (!isValidPhone($phone)) {
        return ['ok' => false, 'msg' => 'شماره تماس معتبر نیست.'];
    }
    if (strlen($password) < 6) {
        return ['ok' => false, 'msg' => 'رمز عبور باید حداقل ۶ کاراکتر باشد.'];
    }

    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'msg' => 'این شماره قبلاً ثبت‌نام کرده است.'];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (phone, password) VALUES (?, ?)");
    $stmt->execute([$phone, $hash]);

    return ['ok' => true, 'user_id' => (int)$pdo->lastInsertId()];
}

function attemptUserLogin(string $phone, string $password): array {
    global $pdo;

    $phone = normalizePhone($phone);
    if (!isValidPhone($phone) || $password === '') {
        return ['ok' => false, 'msg' => 'شماره یا رمز عبور نامعتبر است.'];
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? LIMIT 1");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['ok' => false, 'msg' => 'شماره یا رمز عبور اشتباه است.'];
    }

    loginUser($user);
    return ['ok' => true, 'user' => $user];
}

function attemptAdminLogin(string $username, string $password): array {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password'])) {
        return ['ok' => false, 'msg' => 'نام کاربری یا رمز عبور اشتباه است.'];
    }

    loginAdmin($admin);
    return ['ok' => true, 'admin' => $admin];
}

function isProfileComplete(?array $user = null): bool {
    $user = $user ?? currentUser();
    if (!$user) return false;

    $required = ['first_name', 'last_name', 'address', 'postal_code', 'city', 'street', 'phone'];
    foreach ($required as $field) {
        if (empty(trim((string)($user[$field] ?? '')))) return false;
    }
    if (!isValidPostalCode($user['postal_code'])) return false;
    return true;
}