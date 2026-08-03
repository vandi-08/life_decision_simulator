USE life_decision_simulator;

-- ---------------------------------------------------------
-- SYSTEM SETTINGS (Calculation Layer assumptions — editable by admin)
-- ---------------------------------------------------------
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('inflation_rate_yearly', '4.5', 'Asumsi inflasi tahunan (%) untuk proyeksi 5 tahun'),
('salary_growth_yearly', '8', 'Asumsi kenaikan gaji tahunan (%)'),
('cost_of_living_growth_yearly', '5', 'Asumsi kenaikan biaya hidup tahunan (%)'),
('investment_return_yearly', '6', 'Asumsi return investasi konservatif tahunan (%)'),
('emergency_fund_target_months', '6', 'Target coverage dana darurat (bulan)'),
('healthy_saving_rate_min', '20', 'Saving rate minimum yang dianggap sehat (%)'),
('healthy_debt_ratio_max', '30', 'Debt-to-income ratio maksimum yang dianggap aman (%)');

-- ---------------------------------------------------------
-- CITIES + COST OF LIVING (Data Contoh / Demo — bukan data resmi)
-- ---------------------------------------------------------
INSERT INTO cities (name, province, is_demo_data) VALUES
('Jakarta', 'DKI Jakarta', 1),
('Bandung', 'Jawa Barat', 1),
('Surabaya', 'Jawa Timur', 1),
('Yogyakarta', 'DI Yogyakarta', 1),
('Medan', 'Sumatera Utara', 1),
('Makassar', 'Sulawesi Selatan', 1),
('Denpasar', 'Bali', 1),
('Semarang', 'Jawa Tengah', 1),
('Malang', 'Jawa Timur', 1),
('Bekasi', 'Jawa Barat', 1),
('Depok', 'Jawa Barat', 1),
('Tangerang', 'Banten', 1),
('Bogor', 'Jawa Barat', 1);

INSERT INTO city_costs (city_id, avg_kos, avg_food, avg_transport, avg_internet, avg_lifestyle, avg_salary_entry, career_opportunity, is_demo_data) VALUES
((SELECT id FROM cities WHERE name='Jakarta'),   2200000, 1800000, 800000, 300000, 700000, 6500000, 'tinggi', 1),
((SELECT id FROM cities WHERE name='Bandung'),   1300000, 1200000, 500000, 250000, 500000, 5000000, 'sedang', 1),
((SELECT id FROM cities WHERE name='Surabaya'),  1500000, 1300000, 550000, 250000, 500000, 5500000, 'tinggi', 1),
((SELECT id FROM cities WHERE name='Yogyakarta'),1000000, 1000000, 400000, 200000, 400000, 3800000, 'sedang', 1),
((SELECT id FROM cities WHERE name='Medan'),     1100000, 1100000, 450000, 200000, 400000, 4200000, 'sedang', 1),
((SELECT id FROM cities WHERE name='Makassar'),  1100000, 1100000, 450000, 200000, 400000, 4300000, 'sedang', 1),
((SELECT id FROM cities WHERE name='Denpasar'),  1600000, 1400000, 600000, 250000, 600000, 4800000, 'sedang', 1),
((SELECT id FROM cities WHERE name='Semarang'),  1200000, 1100000, 450000, 200000, 400000, 4300000, 'sedang', 1),
((SELECT id FROM cities WHERE name='Malang'),    1100000, 1000000, 400000, 200000, 400000, 4000000, 'sedang', 1),
((SELECT id FROM cities WHERE name='Bekasi'),    1600000, 1400000, 600000, 250000, 500000, 5200000, 'tinggi', 1),
((SELECT id FROM cities WHERE name='Depok'),     1500000, 1300000, 550000, 250000, 500000, 5000000, 'tinggi', 1),
((SELECT id FROM cities WHERE name='Tangerang'), 1600000, 1400000, 600000, 250000, 500000, 5300000, 'tinggi', 1),
((SELECT id FROM cities WHERE name='Bogor'),     1300000, 1200000, 500000, 200000, 450000, 4700000, 'sedang', 1);

-- ---------------------------------------------------------
-- DEMO ADMIN & DEMO USER ACCOUNT
-- Password hashes are NOT hardcoded here on purpose (a hash generated
-- outside your PHP environment cannot be guaranteed to verify correctly).
-- After importing this file, run database/seed_accounts.php ONCE in your
-- browser (http://localhost/life-decision-simulator/database/seed_accounts.php)
-- to create:
--   Admin   -> username: admin        password: admin123
--   Demo    -> email: demo@lifedecision.id   password: demo1234
-- Delete seed_accounts.php after running it.
-- ---------------------------------------------------------

INSERT INTO articles (title, slug, content, category, published) VALUES
('Cara Menghitung Dana Darurat yang Ideal', 'cara-menghitung-dana-darurat', 'Dana darurat idealnya menutup 3-6 bulan pengeluaran wajib bulananmu. Mulai dengan menghitung total pengeluaran wajib, lalu tetapkan target berdasarkan stabilitas pekerjaanmu.', 'Keuangan', 1),
('Gaji Besar vs Biaya Hidup Tinggi: Mana yang Lebih Penting?', 'gaji-besar-vs-biaya-hidup', 'Gaji nominal yang lebih besar tidak selalu berarti kondisi finansial yang lebih baik. Effective income setelah dikurangi biaya hidup adalah angka yang sebenarnya perlu kamu bandingkan.', 'Karier', 1);
