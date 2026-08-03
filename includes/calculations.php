<?php
/**
 * CALCULATION LAYER
 * Life Decision Simulator Indonesia
 *
 * Every score in this file is derived from a deterministic formula based
 * on user-entered numbers. Nothing here is random. Each function is kept
 * pure (input -> output) so the scoring method can later be swapped for
 * SAW / TOPSIS / AHP without touching the rest of the app (see §61 of the
 * project brief — academic MCDM value).
 */

declare(strict_types=1);

/**
 * Clamp any raw sub-score into the 0-100 range.
 */
function clampScore(float $value): float
{
    return max(0.0, min(100.0, $value));
}

/**
 * Total monthly cost of living for a decision option.
 */
function calculateTotalExpenses(array $option): float
{
    return (float) $option['housing_cost']
        + (float) $option['food_cost']
        + (float) $option['transport_cost']
        + (float) $option['internet_cost']
        + (float) $option['entertainment_cost']
        + (float) $option['shopping_cost']
        + (float) $option['other_cost'];
}

/**
 * Sisa uang bulanan = Gaji - Total Pengeluaran
 */
function calculateMonthlySurplus(array $option): float
{
    return (float) $option['monthly_income'] - calculateTotalExpenses($option);
}

/**
 * Saving Rate = Tabungan Bulanan / Gaji x 100
 */
function calculateSavingsRate(float $monthlyIncome, float $monthlySurplus): float
{
    if ($monthlyIncome <= 0) {
        return 0.0;
    }
    return ($monthlySurplus / $monthlyIncome) * 100;
}

/**
 * Emergency Fund Coverage = Dana Darurat / Pengeluaran Wajib Bulanan
 */
function calculateEmergencyCoverage(float $emergencyFund, float $mandatoryMonthlyExpenses): float
{
    if ($mandatoryMonthlyExpenses <= 0) {
        return 0.0;
    }
    return $emergencyFund / $mandatoryMonthlyExpenses;
}

/**
 * Debt-to-income ratio (%) = Cicilan Bulanan / Pendapatan Bulanan x 100
 */
function calculateDebtRatio(float $monthlyDebtPayment, float $monthlyIncome): float
{
    if ($monthlyIncome <= 0) {
        return 100.0;
    }
    return ($monthlyDebtPayment / $monthlyIncome) * 100;
}

/**
 * FINANCIAL SCORE (0-100)
 * Combines saving rate, emergency fund coverage, and debt burden.
 *
 * Weighting inside the sub-score:
 *   - 50% saving rate    (target: healthy_saving_rate_min from settings, default 20%)
 *   - 30% emergency fund (target: emergency_fund_target_months, default 6 months)
 *   - 20% debt ratio      (target: below healthy_debt_ratio_max, default 30%)
 */
function calculateFinancialScore(array $option, float $emergencyFund, float $existingMonthlyDebtPayment): array
{
    $totalExpenses = calculateTotalExpenses($option);
    $surplus       = calculateMonthlySurplus($option);
    $savingRate    = calculateSavingsRate((float) $option['monthly_income'], $surplus);
    $coverage      = calculateEmergencyCoverage($emergencyFund, $totalExpenses);
    $debtRatio     = calculateDebtRatio($existingMonthlyDebtPayment, (float) $option['monthly_income']);

    $savingTarget    = (float) getSetting('healthy_saving_rate_min');       // e.g. 20
    $coverageTarget  = (float) getSetting('emergency_fund_target_months'); // e.g. 6
    $debtRatioMax    = (float) getSetting('healthy_debt_ratio_max');       // e.g. 30

    // Saving rate sub-score: 0% saving -> 0, target saving rate -> 100, negative surplus -> penalized below 0 then clamped
    $savingSub = $savingTarget > 0 ? ($savingRate / $savingTarget) * 100 : 0;
    $savingSub = clampScore($savingSub);

    // Emergency fund sub-score: 0 months -> 0, target months -> 100
    $coverageSub = $coverageTarget > 0 ? ($coverage / $coverageTarget) * 100 : 0;
    $coverageSub = clampScore($coverageSub);

    // Debt sub-score: 0% debt ratio -> 100, at/above max -> 0
    $debtSub = $debtRatioMax > 0 ? (1 - ($debtRatio / $debtRatioMax)) * 100 : 0;
    $debtSub = clampScore($debtSub);

    $financialScore = clampScore(($savingSub * 0.5) + ($coverageSub * 0.3) + ($debtSub * 0.2));

    return [
        'score'            => round($financialScore, 2),
        'total_expenses'   => $totalExpenses,
        'monthly_surplus'  => $surplus,
        'saving_rate'      => round($savingRate, 2),
        'emergency_coverage' => round($coverage, 2),
        'debt_ratio'       => round($debtRatio, 2),
    ];
}

/**
 * CAREER SCORE (0-100)
 * Combines salary growth vs current, qualitative career-growth rating,
 * job stability, and work-life balance (hours + commute).
 */
function calculateCareerScore(array $option, float $currentIncome): array
{
    $newIncome = (float) $option['monthly_income'];

    // Salary growth sub-score: 0% growth -> 50 (neutral), +50% growth -> 100, -50% -> 0
    $growthPercent = $currentIncome > 0 ? (($newIncome - $currentIncome) / $currentIncome) * 100 : 0;
    $salarySub = clampScore(50 + $growthPercent);

    $growthMap = ['rendah' => 30, 'sedang' => 65, 'tinggi' => 100];
    $careerGrowthSub = $growthMap[$option['career_growth']] ?? 50;

    $stabilityMap = ['rendah' => 30, 'sedang' => 65, 'tinggi' => 100];
    $stabilitySub = $stabilityMap[$option['job_stability']] ?? 50;

    // Work-life balance: ideal ~40h/week, penalize overtime; ideal commute <= 30 min
    $hours = (int) $option['work_hours_per_week'];
    $hoursSub = clampScore(100 - max(0, $hours - 40) * 2.5);

    $commute = (int) $option['commute_minutes'];
    $commuteSub = clampScore(100 - max(0, $commute - 30) * 1.2);
    $balanceSub = ($hoursSub + $commuteSub) / 2;

    $careerScore = clampScore(($salarySub * 0.3) + ($careerGrowthSub * 0.3) + ($stabilitySub * 0.2) + ($balanceSub * 0.2));

    return [
        'score'          => round($careerScore, 2),
        'salary_growth_percent' => round($growthPercent, 2),
        'balance_sub'    => round($balanceSub, 2),
    ];
}

/**
 * LIFESTYLE SCORE (0-100)
 * Combines commute burden, cost-of-living pressure relative to income,
 * and discretionary (entertainment) allowance as a proxy for quality of life.
 */
function calculateLifestyleScore(array $option): array
{
    $income = (float) $option['monthly_income'];
    $totalExpenses = calculateTotalExpenses($option);

    $commute = (int) $option['commute_minutes'];
    $commuteSub = clampScore(100 - $commute * 1.1);

    // Cost-of-living pressure: expenses as % of income; lower is better
    $expenseRatio = $income > 0 ? ($totalExpenses / $income) * 100 : 100;
    $pressureSub = clampScore(100 - max(0, $expenseRatio - 40) * 1.5);

    // Discretionary allowance relative to income
    $entertainment = (float) $option['entertainment_cost'];
    $discretionaryRatio = $income > 0 ? ($entertainment / $income) * 100 : 0;
    $discretionarySub = clampScore($discretionaryRatio * 10); // 10% of income on leisure -> 100

    $lifestyleScore = clampScore(($commuteSub * 0.35) + ($pressureSub * 0.45) + ($discretionarySub * 0.2));

    return [
        'score' => round($lifestyleScore, 2),
        'expense_to_income_ratio' => round($expenseRatio, 2),
    ];
}

/**
 * RISK SCORE (0-100, HIGHER = SAFER)
 * Penalizes negative/thin surplus, low emergency fund, high debt ratio,
 * and low job stability.
 */
function calculateRiskScore(array $option, float $emergencyFund, float $existingMonthlyDebtPayment): array
{
    $surplus = calculateMonthlySurplus($option);
    $income = (float) $option['monthly_income'];
    $totalExpenses = calculateTotalExpenses($option);

    // Surplus safety: negative surplus is heavily penalized
    $surplusRatio = $income > 0 ? ($surplus / $income) * 100 : -100;
    $surplusSub = clampScore(50 + $surplusRatio * 2);

    $coverage = calculateEmergencyCoverage($emergencyFund, $totalExpenses);
    $coverageTarget = (float) getSetting('emergency_fund_target_months');
    $coverageSub = clampScore($coverageTarget > 0 ? ($coverage / $coverageTarget) * 100 : 0);

    $debtRatio = calculateDebtRatio($existingMonthlyDebtPayment, $income);
    $debtRatioMax = (float) getSetting('healthy_debt_ratio_max');
    $debtSub = clampScore($debtRatioMax > 0 ? (1 - ($debtRatio / $debtRatioMax)) * 100 : 0);

    $stabilityMap = ['rendah' => 20, 'sedang' => 60, 'tinggi' => 100];
    $stabilitySub = $stabilityMap[$option['job_stability']] ?? 50;

    $riskScore = clampScore(($surplusSub * 0.35) + ($coverageSub * 0.25) + ($debtSub * 0.2) + ($stabilitySub * 0.2));

    return ['score' => round($riskScore, 2)];
}

/**
 * OVERALL DECISION SCORE
 * Weighted sum of Financial, Career, Lifestyle, and Risk sub-scores using
 * the user's personal priority weights (financial/career/lifestyle grouped
 * as "Personal Priority" categories; family/freetime/growth fold into
 * lifestyle+career per §20 sliders). Risk uses a fixed 15% weight layered
 * on top so it can never be diluted away by priority sliders.
 *
 * weights: ['financial'=>.., 'career'=>.., 'lifestyle'=>.., 'family'=>.., 'freetime'=>.., 'growth'=>..] (percent, sum=100)
 */
function calculateOverallScore(float $financialScore, float $careerScore, float $lifestyleScore, float $riskScore, array $weights): array
{
    $wFinancial = (float) ($weights['financial'] ?? 30) / 100;
    $wCareer    = ((float) ($weights['career'] ?? 25) + (float) ($weights['growth'] ?? 5)) / 100;
    $wLifestyle = ((float) ($weights['lifestyle'] ?? 20) + (float) ($weights['family'] ?? 10) + (float) ($weights['freetime'] ?? 10)) / 100;

    // Normalize the three so they sum to 0.85, reserving a fixed 0.15 for risk
    $sum = $wFinancial + $wCareer + $wLifestyle;
    if ($sum <= 0) {
        $wFinancial = $wCareer = $wLifestyle = 1 / 3;
        $sum = 1;
    }
    $riskWeight = 0.15;
    $scaleFactor = (1 - $riskWeight) / $sum;

    $overall = ($financialScore * $wFinancial * $scaleFactor)
        + ($careerScore * $wCareer * $scaleFactor)
        + ($lifestyleScore * $wLifestyle * $scaleFactor)
        + ($riskScore * $riskWeight);

    $overall = clampScore($overall);
    $band = scoreBand($overall);

    return [
        'score' => round($overall, 2),
        'label' => $band['label'],
        'class' => $band['class'],
    ];
}

/**
 * Convenience wrapper: run the full scoring pipeline for one decision option.
 */
function scoreDecisionOption(array $option, array $context): array
{
    $currentIncome = (float) ($context['current_income'] ?? $option['monthly_income']);
    $emergencyFund = (float) ($context['emergency_fund'] ?? 0);
    $existingDebtPayment = (float) ($context['monthly_debt_payment'] ?? 0);
    $weights = $context['weights'] ?? DEFAULT_PRIORITY_WEIGHTS;

    $financial = calculateFinancialScore($option, $emergencyFund, $existingDebtPayment);
    $career    = calculateCareerScore($option, $currentIncome);
    $lifestyle = calculateLifestyleScore($option);
    $risk      = calculateRiskScore($option, $emergencyFund, $existingDebtPayment);

    $overall = calculateOverallScore(
        $financial['score'],
        $career['score'],
        $lifestyle['score'],
        $risk['score'],
        $weights
    );

    return [
        'financial' => $financial,
        'career'    => $career,
        'lifestyle' => $lifestyle,
        'risk'      => $risk,
        'overall'   => $overall,
    ];
}

/**
 * FIVE-YEAR SIMULATION
 * Projects monthly surplus compounding into savings over N years, applying
 * salary growth and cost-of-living growth assumptions.
 */
function simulateFutureSavings(float $startingSavings, float $monthlyIncome, float $monthlyExpenses, int $years): array
{
    $salaryGrowth = (float) getSetting('salary_growth_yearly') / 100;
    $expenseGrowth = (float) getSetting('cost_of_living_growth_yearly') / 100;
    $investReturn = (float) getSetting('investment_return_yearly') / 100;

    $projection = [];
    $savings = $startingSavings;
    $income = $monthlyIncome;
    $expenses = $monthlyExpenses;

    for ($year = 1; $year <= $years; $year++) {
        $income *= (1 + $salaryGrowth);
        $expenses *= (1 + $expenseGrowth);
        $monthlySurplus = max(0, $income - $expenses);
        $yearlyContribution = $monthlySurplus * 12;
        $savings = ($savings * (1 + $investReturn)) + $yearlyContribution;

        $projection[] = [
            'year'    => $year,
            'income'  => round($income),
            'expenses' => round($expenses),
            'savings' => round($savings),
        ];
    }

    return $projection;
}

/**
 * AFFORDABILITY CHECKER
 * Score 0-100 (higher = safer purchase) based on: portion of savings used,
 * post-purchase emergency fund impact, and whether monthly surplus can
 * absorb it without new debt.
 */
function calculateAffordabilityScore(float $price, float $savingsBalance, float $emergencyFund, float $monthlySurplus, float $monthlyMandatoryExpenses): array
{
    $savingsAfter = $savingsBalance - $price;
    $portionOfSavings = $savingsBalance > 0 ? ($price / $savingsBalance) * 100 : 100;

    // How many months of surplus it would take to save for this without touching savings
    $monthsToSaveInstead = $monthlySurplus > 0 ? $price / $monthlySurplus : 999;

    $emergencyIntact = $savingsAfter >= $emergencyFund;
    $coverageAfter = calculateEmergencyCoverage(max(0, $savingsAfter), $monthlyMandatoryExpenses > 0 ? $monthlyMandatoryExpenses : 1);
    $coverageTarget = (float) getSetting('emergency_fund_target_months');

    $portionSub = clampScore(100 - $portionOfSavings);
    $emergencySub = $emergencyIntact ? 100 : clampScore(($coverageAfter / max($coverageTarget, 1)) * 100);
    $paceSub = clampScore(100 - min($monthsToSaveInstead, 24) * 4);

    $affordability = clampScore(($portionSub * 0.45) + ($emergencySub * 0.35) + ($paceSub * 0.2));

    if ($affordability >= 75) {
        $label = 'AMAN';
    } elseif ($affordability >= 60) {
        $label = 'MASIH MASUK AKAL';
    } elseif ($affordability >= 45) {
        $label = 'PERLU DIPERTIMBANGKAN';
    } elseif ($affordability >= 25) {
        $label = 'BERISIKO';
    } else {
        $label = 'TIDAK DISARANKAN';
    }

    return [
        'score' => round($affordability, 2),
        'label' => $label,
        'portion_of_savings' => round($portionOfSavings, 1),
        'emergency_fund_intact' => $emergencyIntact,
        'months_to_save_instead' => round($monthsToSaveInstead, 1),
    ];
}
