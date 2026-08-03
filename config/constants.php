<?php
/**
 * Application constants
 * Life Decision Simulator Indonesia
 */

declare(strict_types=1);

define('APP_NAME', 'Life Decision Simulator Indonesia');
define('BASE_URL', '/life-decision-simulator');

// Decision categories shown throughout the app
const DECISION_CATEGORIES = [
    'karier'          => 'Karier',
    'pindah_kota'     => 'Pindah Kota',
    'tempat_tinggal'  => 'Tempat Tinggal',
    'pembelian'       => 'Pembelian',
    'pendidikan'      => 'Pendidikan',
    'bisnis'          => 'Bisnis',
    'masa_depan'      => 'Hubungan / Masa Depan',
    'custom'          => 'Lainnya',
];

// Overall decision score bands (see includes/calculations.php)
const SCORE_BANDS = [
    ['min' => 85, 'label' => 'SANGAT KUAT',            'class' => 'score-excellent'],
    ['min' => 70, 'label' => 'BAIK',                   'class' => 'score-good'],
    ['min' => 55, 'label' => 'PERLU DIPERTIMBANGKAN',  'class' => 'score-consider'],
    ['min' => 40, 'label' => 'BERISIKO',               'class' => 'score-risky'],
    ['min' => 0,  'label' => 'TIDAK DISARANKAN',       'class' => 'score-bad'],
];

// Default personal-priority weights (percent, must total 100)
const DEFAULT_PRIORITY_WEIGHTS = [
    'financial' => 30,
    'career'    => 25,
    'lifestyle' => 20,
    'family'    => 10,
    'freetime'  => 10,
    'growth'    => 5,
];

// Default financial assumptions (overridden by system_settings table when available)
const DEFAULT_ASSUMPTIONS = [
    'inflation_rate_yearly'         => 4.5,
    'salary_growth_yearly'          => 8.0,
    'cost_of_living_growth_yearly'  => 5.0,
    'investment_return_yearly'      => 6.0,
    'emergency_fund_target_months'  => 6,
    'healthy_saving_rate_min'       => 20,
    'healthy_debt_ratio_max'        => 30,
];
