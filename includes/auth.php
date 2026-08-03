<?php
/**
 * Authentication helpers
 * Life Decision Simulator Indonesia
 */

declare(strict_types=1);

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function currentUserId(): ?int
{
    return $_SESSION['user_id'] ?? null;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

function isAdminLoggedIn(): bool
{
    return !empty($_SESSION['admin_id']);
}

function requireAdmin(): void
{
    if (!isAdminLoggedIn()) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function getCurrentUser(): ?array
{
    static $user = null;
    if ($user !== null) {
        return $user;
    }
    $userId = currentUserId();
    if (!$userId) {
        return null;
    }
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $userId]);
    $row = $stmt->fetch();
    $user = $row ?: null;
    return $user;
}

/**
 * Very small fixed-window login rate limiter, keyed by email + IP.
 * Not a replacement for a proper rate-limiting layer in production,
 * but stops naive brute-force attempts.
 */
function tooManyLoginAttempts(string $email): bool
{
    $key = 'login_attempts_' . md5(strtolower($email) . '_' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $window = 300; // 5 minutes
    $maxAttempts = 5;

    $data = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];

    if (time() - $data['first'] > $window) {
        $data = ['count' => 0, 'first' => time()];
    }

    $_SESSION[$key] = $data;
    return $data['count'] >= $maxAttempts;
}

function registerLoginAttempt(string $email): void
{
    $key = 'login_attempts_' . md5(strtolower($email) . '_' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    $data = $_SESSION[$key] ?? ['count' => 0, 'first' => time()];
    $data['count']++;
    $_SESSION[$key] = $data;
}

function clearLoginAttempts(string $email): void
{
    $key = 'login_attempts_' . md5(strtolower($email) . '_' . ($_SERVER['REMOTE_ADDR'] ?? ''));
    unset($_SESSION[$key]);
}

function attemptLogin(string $email, string $password): array
{
    $email = trim($email);

    if (tooManyLoginAttempts($email)) {
        return ['success' => false, 'message' => 'Terlalu banyak percobaan login. Coba lagi dalam beberapa menit.'];
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        registerLoginAttempt($email);
        return ['success' => false, 'message' => 'Email atau password salah.'];
    }

    if ($user['status'] !== 'active') {
        return ['success' => false, 'message' => 'Akun ini sedang tidak aktif. Hubungi admin.'];
    }

    clearLoginAttempts($email);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = $user['full_name'];

    return ['success' => true, 'user' => $user];
}

function registerUser(array $data): array
{
    $pdo = getDbConnection();

    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'message' => 'Email ini sudah terdaftar. Silakan login.'];
    }

    $hash = password_hash($data['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('
        INSERT INTO users (full_name, email, password_hash, age, city, occupation, monthly_salary)
        VALUES (:full_name, :email, :password_hash, :age, :city, :occupation, :monthly_salary)
    ');
    $stmt->execute([
        'full_name'      => $data['full_name'],
        'email'          => $data['email'],
        'password_hash'  => $hash,
        'age'            => $data['age'] ?: null,
        'city'           => $data['city'] ?: null,
        'occupation'     => $data['occupation'] ?: null,
        'monthly_salary' => $data['monthly_salary'] ?: 0,
    ]);

    $userId = (int) $pdo->lastInsertId();

    // Seed an empty financial profile so dashboard queries always find a row
    $stmt = $pdo->prepare('
        INSERT INTO financial_profiles (user_id, monthly_income, monthly_expenses, savings_balance, emergency_fund, total_debt, monthly_debt_payment)
        VALUES (:user_id, :income, 0, 0, 0, 0, 0)
    ');
    $stmt->execute([
        'user_id' => $userId,
        'income'  => $data['monthly_salary'] ?: 0,
    ]);

    session_regenerate_id(true);
    $_SESSION['user_id'] = $userId;
    $_SESSION['user_name'] = $data['full_name'];

    return ['success' => true, 'user_id' => $userId];
}

function logoutUser(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
