<?php
/**
 * Run this ONCE in your browser after importing database.sql + seed.sql:
 *   http://localhost/life-decision-simulator/database/seed_accounts.php
 *
 * Creates:
 *   Admin -> username: admin              password: admin123
 *   Demo  -> email: demo@lifedecision.id  password: demo1234
 *
 * DELETE THIS FILE after running it once.
 */

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

$pdo = getDbConnection();

// Admin account
$stmt = $pdo->prepare('SELECT id FROM admin_users WHERE username = :u');
$stmt->execute(['u' => 'admin']);
if (!$stmt->fetch()) {
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (:u, :p)');
    $stmt->execute(['u' => 'admin', 'p' => password_hash('admin123', PASSWORD_DEFAULT)]);
    echo "Admin account created (admin / admin123)<br>";
} else {
    echo "Admin account already exists<br>";
}

// Demo user account
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = :e');
$stmt->execute(['e' => 'demo@lifedecision.id']);
$existing = $stmt->fetch();

if (!$existing) {
    $stmt = $pdo->prepare('
        INSERT INTO users (full_name, email, password_hash, age, city, occupation, monthly_salary, onboarding_completed, risk_tolerance)
        VALUES (:name, :email, :hash, 24, "Bandung", "Software Developer", 5000000, 1, "seimbang")
    ');
    $stmt->execute([
        'name' => 'Vandi Pratama',
        'email' => 'demo@lifedecision.id',
        'hash' => password_hash('demo1234', PASSWORD_DEFAULT),
    ]);
    $demoUserId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare('
        INSERT INTO financial_profiles (user_id, monthly_income, monthly_expenses, savings_balance, emergency_fund, total_debt, monthly_debt_payment)
        VALUES (:uid, 5000000, 2300000, 10000000, 10000000, 0, 0)
    ');
    $stmt->execute(['uid' => $demoUserId]);

    echo "Demo user account created (demo@lifedecision.id / demo1234)<br>";
} else {
    echo "Demo user account already exists<br>";
}

echo "<br><strong>Done. Please delete this file (database/seed_accounts.php) now.</strong>";
