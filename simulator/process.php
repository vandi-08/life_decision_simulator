<?php
require_once __DIR__ . '/../includes/bootstrap.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/simulator/new.php');
}
requireValidCsrf();

$pdo = getDbConnection();
$userId = currentUserId();

$category = $_POST['category'] ?? 'custom';
if (!array_key_exists($category, DECISION_CATEGORIES)) {
    $category = 'custom';
}
$title = cleanString($_POST['title'] ?? '');
$question = cleanString($_POST['question'] ?? '');

if ($title === '') {
    setFlash('error', 'Judul keputusan wajib diisi.');
    redirect('/simulator/new.php');
}

$currentIncome = parseRupiah($_POST['current_income'] ?? '0');
$emergencyFund = parseRupiah($_POST['emergency_fund'] ?? '0');
$monthlyDebtPayment = parseRupiah($_POST['monthly_debt_payment'] ?? '0');

$weights = [];
$weightTotal = 0;
foreach (DEFAULT_PRIORITY_WEIGHTS as $key => $default) {
    $val = (int) ($_POST['weight_' . $key] ?? $default);
    $val = max(0, min(100, $val));
    $weights[$key] = $val;
    $weightTotal += $val;
}
// Guard rail: if sliders don't sum to 100 (JS disabled, tampering, etc.), normalize server-side.
if ($weightTotal > 0 && $weightTotal !== 100) {
    foreach ($weights as $key => $val) {
        $weights[$key] = ($val / $weightTotal) * 100;
    }
}

function buildOptionFromPost(string $letter): array
{
    return [
        'label'               => cleanString($_POST['option_' . $letter . '_label'] ?? ('Pilihan ' . $letter)),
        'monthly_income'      => parseRupiah($_POST['option_' . $letter . '_income'] ?? '0'),
        'housing_cost'        => parseRupiah($_POST['option_' . $letter . '_housing'] ?? '0'),
        'food_cost'           => parseRupiah($_POST['option_' . $letter . '_food'] ?? '0'),
        'transport_cost'      => parseRupiah($_POST['option_' . $letter . '_transport'] ?? '0'),
        'internet_cost'       => parseRupiah($_POST['option_' . $letter . '_internet'] ?? '0'),
        'entertainment_cost'  => parseRupiah($_POST['option_' . $letter . '_entertainment'] ?? '0'),
        'shopping_cost'       => parseRupiah($_POST['option_' . $letter . '_shopping'] ?? '0'),
        'other_cost'          => parseRupiah($_POST['option_' . $letter . '_other'] ?? '0'),
        'career_growth'       => in_array($_POST['option_' . $letter . '_career_growth'] ?? '', ['rendah','sedang','tinggi'], true) ? $_POST['option_' . $letter . '_career_growth'] : 'sedang',
        'job_stability'       => in_array($_POST['option_' . $letter . '_job_stability'] ?? '', ['rendah','sedang','tinggi'], true) ? $_POST['option_' . $letter . '_job_stability'] : 'sedang',
        'work_hours_per_week' => max(1, min(120, (int) ($_POST['option_' . $letter . '_work_hours'] ?? 40))),
        'commute_minutes'     => max(0, min(300, (int) ($_POST['option_' . $letter . '_commute'] ?? 30))),
    ];
}

$optionA = buildOptionFromPost('A');
$optionB = buildOptionFromPost('B');

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('
        INSERT INTO decisions (user_id, category, title, question, weight_financial, weight_career, weight_lifestyle, weight_family, weight_freetime, weight_growth, status)
        VALUES (:uid, :cat, :title, :question, :wf, :wc, :wl, :wfam, :wft, :wg, "completed")
    ');
    $stmt->execute([
        'uid' => $userId, 'cat' => $category, 'title' => $title, 'question' => $question ?: null,
        'wf' => round($weights['financial']), 'wc' => round($weights['career']), 'wl' => round($weights['lifestyle']),
        'wfam' => round($weights['family']), 'wft' => round($weights['freetime']), 'wg' => round($weights['growth']),
    ]);
    $decisionId = (int) $pdo->lastInsertId();

    $context = [
        'current_income' => $currentIncome,
        'emergency_fund' => $emergencyFund,
        'monthly_debt_payment' => $monthlyDebtPayment,
        'weights' => $weights,
    ];

    $results = [];
    foreach (['A' => $optionA, 'B' => $optionB] as $letter => $option) {
        $stmt = $pdo->prepare('
            INSERT INTO decision_options
              (decision_id, label, monthly_income, housing_cost, food_cost, transport_cost, internet_cost, entertainment_cost, shopping_cost, other_cost, career_growth, work_hours_per_week, commute_minutes, job_stability, sort_order)
            VALUES
              (:did, :label, :income, :housing, :food, :transport, :internet, :entertainment, :shopping, :other, :career_growth, :work_hours, :commute, :stability, :sort)
        ');
        $stmt->execute([
            'did' => $decisionId, 'label' => $option['label'], 'income' => $option['monthly_income'],
            'housing' => $option['housing_cost'], 'food' => $option['food_cost'], 'transport' => $option['transport_cost'],
            'internet' => $option['internet_cost'], 'entertainment' => $option['entertainment_cost'],
            'shopping' => $option['shopping_cost'], 'other' => $option['other_cost'],
            'career_growth' => $option['career_growth'], 'work_hours' => $option['work_hours_per_week'],
            'commute' => $option['commute_minutes'], 'stability' => $option['job_stability'],
            'sort' => $letter === 'A' ? 0 : 1,
        ]);
        $optionId = (int) $pdo->lastInsertId();

        $score = scoreDecisionOption($option, $context);

        $stmt = $pdo->prepare('
            INSERT INTO decision_results
              (decision_id, option_id, financial_score, career_score, lifestyle_score, risk_score, overall_score, monthly_surplus, saving_rate, status_label, is_recommended)
            VALUES
              (:did, :oid, :fs, :cs, :ls, :rs, :os, :surplus, :sr, :label, 0)
        ');
        $stmt->execute([
            'did' => $decisionId, 'oid' => $optionId,
            'fs' => $score['financial']['score'], 'cs' => $score['career']['score'], 'ls' => $score['lifestyle']['score'],
            'rs' => $score['risk']['score'], 'os' => $score['overall']['score'],
            'surplus' => $score['financial']['monthly_surplus'], 'sr' => $score['financial']['saving_rate'],
            'label' => $score['overall']['label'],
        ]);

        $results[$letter] = ['option_id' => $optionId, 'score' => $score];
    }

    // Mark whichever option scored higher as recommended
    $winner = $results['A']['score']['overall']['score'] >= $results['B']['score']['overall']['score'] ? 'A' : 'B';
    $stmt = $pdo->prepare('UPDATE decision_results SET is_recommended = 1 WHERE option_id = :oid');
    $stmt->execute(['oid' => $results[$winner]['option_id']]);

    $pdo->commit();
    redirect('/simulator/result.php?id=' . $decisionId);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    logError('simulator_process', $e);
    setFlash('error', 'Terjadi masalah saat memproses simulasi. Periksa kembali data yang kamu masukkan.');
    redirect('/simulator/new.php');
}
