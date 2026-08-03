# Life Decision Simulator Indonesia

Personal Decision Support System (DSS) untuk membantu Gen Z & anak muda
Indonesia (18–30 tahun) membandingkan pilihan hidup — karier, keuangan,
tempat tinggal — berdasarkan data, perhitungan, dan simulasi, bukan tebakan.

Native PHP + MySQL (PDO) + Vanilla JS + Chart.js. Tidak ada Laravel, React,
atau Node.js — cukup XAMPP.

## Status project ini

Ini adalah **Phase 1 (Foundation) + Phase 2 (Core Decision Engine)** dari
roadmap 8-fase di brief. Semua yang ada di bawah ini **benar-benar
berfungsi** — bukan mockup:

- ✅ Autentikasi (register, login, logout, session, rate limiting, CSRF, password hashing)
- ✅ Onboarding (kondisi finansial, tujuan, toleransi risiko)
- ✅ Dashboard dengan skor kesehatan finansial/karier/lifestyle yang dihitung real, bukan random
- ✅ Simulator keputusan multi-step (2 pilihan, prioritas slider 100%, hasil explainable)
- ✅ Calculation Layer terpisah (`includes/calculations.php`) — deterministic, siap dipakai untuk SAW/TOPSIS/AHP
- ✅ Riwayat keputusan (filter, sort, hapus)
- ✅ Kalkulator: Cek Kemampuan Beli (Affordability), Dana Darurat
- ✅ Manajemen profil keuangan & akun
- ✅ Dark mode, responsive, error handling yang tidak membocorkan error PHP mentah
- ✅ Skema database lengkap (semua 22 tabel dari brief) siap dipakai fase-fase berikutnya

**Belum dibangun** (Phase 3–8 dari brief — akan menyusul):
five-year/what-if simulator UI, scenario comparison 3-arah, job offer
comparator, city comparison, kos/subscription/debt calculator, career
switch & freelance & business simulator, goals & transactions UI, admin
panel. Skema tabelnya sudah ada di `database.sql`, jadi fase berikutnya
tinggal membangun logic + halamannya.

Menu sidebar sengaja hanya menampilkan halaman yang benar-benar berfungsi
saat ini (sesuai aturan "jangan buat tombol/menu yang tidak berfungsi").

## Setup (XAMPP)

1. Copy folder `life-decision-simulator/` ke `C:\xampp\htdocs\`
2. Jalankan Apache & MySQL dari XAMPP Control Panel
3. Buka phpMyAdmin → Import → pilih `database/database.sql`, lalu `database/seed.sql`
4. Buka `http://localhost/life-decision-simulator/database/seed_accounts.php` di browser
   (ini membuat akun admin & demo dengan password hash yang valid untuk PHP-mu)
5. **Hapus file `database/seed_accounts.php`** setelah langkah di atas
6. Buka `http://localhost/life-decision-simulator/`

## Akun demo

- Demo user: `demo@lifedecision.id` / `demo1234`
- Admin: `admin` / `admin123` (panel admin belum dibangun di fase ini)

## Struktur Calculation Layer

Semua rumus skor ada di `includes/calculations.php`:
`calculateFinancialScore()`, `calculateCareerScore()`,
`calculateLifestyleScore()`, `calculateRiskScore()`,
`calculateOverallScore()`, `simulateFutureSavings()`,
`calculateAffordabilityScore()`, dll. Tidak ada skor yang di-hardcode atau
random — cocok untuk nilai akademik/skripsi (SAW/TOPSIS/AHP-ready).

## Keamanan

PDO prepared statements di semua query, `password_hash()`/`password_verify()`,
CSRF token di semua form POST, output di-escape lewat `e()`, session
httponly+samesite, rate limiting login sederhana, PHP error mentah tidak
pernah ditampilkan ke user (`display_errors=0`, dicatat via `error_log`).

## Data demo

Data kota & biaya hidup di `database/seed.sql` adalah **Data Contoh / Demo**
untuk keperluan pengembangan — bukan statistik resmi.
