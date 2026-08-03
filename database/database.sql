-- =========================================================
-- Life Decision Simulator Indonesia
-- Database Schema
-- Import via phpMyAdmin or: mysql -u root -p < database.sql
-- =========================================================

CREATE DATABASE IF NOT EXISTS life_decision_simulator
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE life_decision_simulator;

-- ---------------------------------------------------------
-- USERS
-- ---------------------------------------------------------
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  age INT UNSIGNED NULL,
  city VARCHAR(100) NULL,
  occupation VARCHAR(150) NULL,
  monthly_salary DECIMAL(15,2) NULL DEFAULT 0,
  onboarding_completed TINYINT(1) NOT NULL DEFAULT 0,
  risk_tolerance ENUM('konservatif','seimbang','agresif') DEFAULT 'seimbang',
  remember_token VARCHAR(255) NULL,
  status ENUM('active','suspended') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- USER PROFILES (onboarding detail)
-- ---------------------------------------------------------
CREATE TABLE user_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  employment_status VARCHAR(100) NULL,
  dependents INT UNSIGNED DEFAULT 0,
  primary_goal VARCHAR(100) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- FINANCIAL PROFILE (current snapshot, editable, drives dashboard)
-- ---------------------------------------------------------
CREATE TABLE financial_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  monthly_income DECIMAL(15,2) NOT NULL DEFAULT 0,
  monthly_expenses DECIMAL(15,2) NOT NULL DEFAULT 0,
  savings_balance DECIMAL(15,2) NOT NULL DEFAULT 0,
  emergency_fund DECIMAL(15,2) NOT NULL DEFAULT 0,
  total_debt DECIMAL(15,2) NOT NULL DEFAULT 0,
  monthly_debt_payment DECIMAL(15,2) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- DECISIONS (the simulator sessions)
-- ---------------------------------------------------------
CREATE TABLE decisions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  category ENUM('karier','pindah_kota','tempat_tinggal','pembelian','pendidikan','bisnis','masa_depan','custom') NOT NULL,
  title VARCHAR(255) NOT NULL,
  question TEXT NULL,
  weight_financial TINYINT UNSIGNED DEFAULT 30,
  weight_career TINYINT UNSIGNED DEFAULT 25,
  weight_lifestyle TINYINT UNSIGNED DEFAULT 20,
  weight_family TINYINT UNSIGNED DEFAULT 10,
  weight_freetime TINYINT UNSIGNED DEFAULT 10,
  weight_growth TINYINT UNSIGNED DEFAULT 5,
  status ENUM('draft','completed') DEFAULT 'draft',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- DECISION OPTIONS (A / B / C)
-- ---------------------------------------------------------
CREATE TABLE decision_options (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  decision_id INT UNSIGNED NOT NULL,
  label VARCHAR(150) NOT NULL,
  monthly_income DECIMAL(15,2) NOT NULL DEFAULT 0,
  housing_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
  food_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
  transport_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
  internet_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
  entertainment_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
  shopping_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
  other_cost DECIMAL(15,2) NOT NULL DEFAULT 0,
  career_growth ENUM('rendah','sedang','tinggi') DEFAULT 'sedang',
  work_hours_per_week INT UNSIGNED DEFAULT 40,
  commute_minutes INT UNSIGNED DEFAULT 30,
  job_stability ENUM('rendah','sedang','tinggi') DEFAULT 'sedang',
  sort_order TINYINT UNSIGNED DEFAULT 0,
  FOREIGN KEY (decision_id) REFERENCES decisions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- DECISION FACTORS (personal priority sliders, snapshot per decision)
-- ---------------------------------------------------------
CREATE TABLE decision_factors (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  decision_id INT UNSIGNED NOT NULL,
  factor_name VARCHAR(100) NOT NULL,
  weight_percent TINYINT UNSIGNED NOT NULL,
  FOREIGN KEY (decision_id) REFERENCES decisions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- DECISION RESULTS (computed scores per option)
-- ---------------------------------------------------------
CREATE TABLE decision_results (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  decision_id INT UNSIGNED NOT NULL,
  option_id INT UNSIGNED NOT NULL,
  financial_score DECIMAL(5,2) NOT NULL,
  career_score DECIMAL(5,2) NOT NULL,
  lifestyle_score DECIMAL(5,2) NOT NULL,
  risk_score DECIMAL(5,2) NOT NULL,
  overall_score DECIMAL(5,2) NOT NULL,
  monthly_surplus DECIMAL(15,2) NOT NULL,
  saving_rate DECIMAL(5,2) NOT NULL,
  status_label VARCHAR(50) NOT NULL,
  is_recommended TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (decision_id) REFERENCES decisions(id) ON DELETE CASCADE,
  FOREIGN KEY (option_id) REFERENCES decision_options(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- FINANCIAL TRANSACTIONS
-- ---------------------------------------------------------
CREATE TABLE financial_transactions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('income','expense') NOT NULL,
  category VARCHAR(100) NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  note VARCHAR(255) NULL,
  transaction_date DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- GOALS
-- ---------------------------------------------------------
CREATE TABLE goals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  target_amount DECIMAL(15,2) NOT NULL,
  current_amount DECIMAL(15,2) NOT NULL DEFAULT 0,
  monthly_contribution DECIMAL(15,2) NOT NULL DEFAULT 0,
  target_date DATE NULL,
  status ENUM('active','achieved','cancelled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE goal_contributions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  goal_id INT UNSIGNED NOT NULL,
  amount DECIMAL(15,2) NOT NULL,
  contributed_at DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (goal_id) REFERENCES goals(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SUBSCRIPTIONS
-- ---------------------------------------------------------
CREATE TABLE subscriptions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  name VARCHAR(150) NOT NULL,
  monthly_cost DECIMAL(15,2) NOT NULL,
  category ENUM('penting','berguna','jarang_digunakan','tidak_perlu') DEFAULT 'berguna',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- JOB OFFERS (comparator)
-- ---------------------------------------------------------
CREATE TABLE job_offers (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  company_label VARCHAR(150) NOT NULL,
  salary DECIMAL(15,2) NOT NULL,
  work_mode ENUM('wfh','wfo','hybrid') DEFAULT 'wfo',
  career_growth ENUM('rendah','sedang','tinggi') DEFAULT 'sedang',
  commute_cost DECIMAL(15,2) DEFAULT 0,
  food_cost DECIMAL(15,2) DEFAULT 0,
  other_cost DECIMAL(15,2) DEFAULT 0,
  effective_income DECIMAL(15,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CITIES + COST OF LIVING
-- ---------------------------------------------------------
CREATE TABLE cities (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  province VARCHAR(100) NULL,
  is_demo_data TINYINT(1) DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE city_costs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  city_id INT UNSIGNED NOT NULL,
  avg_kos DECIMAL(15,2) DEFAULT 0,
  avg_food DECIMAL(15,2) DEFAULT 0,
  avg_transport DECIMAL(15,2) DEFAULT 0,
  avg_internet DECIMAL(15,2) DEFAULT 0,
  avg_lifestyle DECIMAL(15,2) DEFAULT 0,
  avg_salary_entry DECIMAL(15,2) DEFAULT 0,
  career_opportunity ENUM('rendah','sedang','tinggi') DEFAULT 'sedang',
  is_demo_data TINYINT(1) DEFAULT 1,
  FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- CAREER PROFILES (career switch simulator inputs)
-- ---------------------------------------------------------
CREATE TABLE career_profiles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  current_salary DECIMAL(15,2) DEFAULT 0,
  target_field VARCHAR(150) NULL,
  course_cost DECIMAL(15,2) DEFAULT 0,
  learning_months INT UNSIGNED DEFAULT 0,
  expected_salary DECIMAL(15,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- BUSINESS SIMULATIONS
-- ---------------------------------------------------------
CREATE TABLE business_simulations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  business_name VARCHAR(150) NOT NULL,
  capital DECIMAL(15,2) DEFAULT 0,
  operational_cost DECIMAL(15,2) DEFAULT 0,
  price_per_unit DECIMAL(15,2) DEFAULT 0,
  cost_per_unit DECIMAL(15,2) DEFAULT 0,
  target_sales_per_month INT UNSIGNED DEFAULT 0,
  marketing_cost DECIMAL(15,2) DEFAULT 0,
  platform_fee_percent DECIMAL(5,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- DEBT SIMULATIONS
-- ---------------------------------------------------------
CREATE TABLE debt_simulations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  debt_type VARCHAR(100) NOT NULL,
  principal DECIMAL(15,2) NOT NULL,
  interest_rate_yearly DECIMAL(5,2) DEFAULT 0,
  duration_months INT UNSIGNED NOT NULL,
  monthly_installment DECIMAL(15,2) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- NOTIFICATIONS / INSIGHTS
-- ---------------------------------------------------------
CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  type ENUM('warning','info','success') DEFAULT 'info',
  message VARCHAR(255) NOT NULL,
  is_read TINYINT(1) DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SAVED SCENARIOS
-- ---------------------------------------------------------
CREATE TABLE saved_scenarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  decision_id INT UNSIGNED NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (decision_id) REFERENCES decisions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ARTICLES (education content, admin managed)
-- ---------------------------------------------------------
CREATE TABLE articles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  content TEXT NOT NULL,
  category VARCHAR(100) NULL,
  published TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- ADMIN USERS
-- ---------------------------------------------------------
CREATE TABLE admin_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- SYSTEM SETTINGS (financial assumptions used by calculation layer)
-- ---------------------------------------------------------
CREATE TABLE system_settings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) NOT NULL UNIQUE,
  setting_value VARCHAR(255) NOT NULL,
  description VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Indexes for common lookups
CREATE INDEX idx_decisions_user ON decisions(user_id);
CREATE INDEX idx_options_decision ON decision_options(decision_id);
CREATE INDEX idx_results_decision ON decision_results(decision_id);
CREATE INDEX idx_transactions_user_date ON financial_transactions(user_id, transaction_date);
CREATE INDEX idx_goals_user ON goals(user_id);
CREATE INDEX idx_city_costs_city ON city_costs(city_id);
