<?php
// Main Entry Point
require_once __DIR__ . '/includes/functions.php';

// If logged in, redirect to dashboard
if (is_logged_in()) {
    redirect('dashboard/index.php');
}

// Otherwise, show landing page or redirect to login
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AkademiX — Sistem Manajemen Tugas Kelompok</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
    <style>
        body {
            background: var(--bg-body);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        .navbar {
            padding: 20px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(17, 24, 39, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
        }

        .brand {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }

        .brand i {
            color: var(--primary);
        }

        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 120px 5% 60px;
            position: relative;
            overflow: hidden;
        }

        /* Abstract shapes */
        .hero::before, .hero::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            z-index: -1;
            opacity: 0.5;
            animation: pulse 8s infinite alternate;
        }

        .hero::before {
            width: 500px;
            height: 500px;
            background: rgba(102, 126, 234, 0.3);
            top: -100px;
            left: -100px;
        }

        .hero::after {
            width: 400px;
            height: 400px;
            background: rgba(118, 75, 162, 0.3);
            bottom: -50px;
            right: -50px;
            animation-delay: -4s;
        }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.3; }
            100% { transform: scale(1.2); opacity: 0.6; }
        }

        .hero h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 24px;
            background: linear-gradient(135deg, #f1f5f9 0%, #94a3b8 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            max-width: 800px;
        }

        .hero p {
            font-size: 1.25rem;
            color: var(--text-secondary);
            max-width: 600px;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .cta-buttons {
            display: flex;
            gap: 16px;
        }

        .btn-large {
            padding: 16px 32px;
            font-size: 1.1rem;
        }

        .features {
            padding: 80px 5%;
            background: rgba(255, 255, 255, 0.02);
            border-top: 1px solid var(--border);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 32px;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background: var(--bg-glass);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: 32px;
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            border-color: rgba(102, 126, 234, 0.5);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-md);
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 1.25rem;
            margin-bottom: 12px;
            color: var(--text-primary);
        }

        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        footer {
            text-align: center;
            padding: 32px;
            color: var(--text-muted);
            border-top: 1px solid var(--border);
            background: var(--bg-body);
        }

        @media (max-width: 768px) {
            .hero h1 { font-size: 2.5rem; }
            .hero p { font-size: 1.1rem; }
            .cta-buttons { flex-direction: column; width: 100%; max-width: 300px; }
            .btn-large { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand">
            <i class="fas fa-layer-group"></i> AkademiX
        </a>
        <div class="nav-links">
            <a href="auth/login.php" class="btn btn-ghost">Masuk</a>
            <a href="auth/register.php" class="btn btn-primary">Daftar</a>
        </div>
    </nav>

    <main class="hero">
        <h1>Kolaborasi Tugas Tanpa Batas.</h1>
        <p>Tingkatkan produktivitas kelompok Anda dengan platform manajemen tugas yang dirancang khusus untuk mahasiswa.</p>
        <div class="cta-buttons">
            <a href="auth/register.php" class="btn btn-primary btn-large">Mulai Sekarang</a>
            <a href="auth/login.php" class="btn btn-ghost btn-large">Masuk ke Akun</a>
        </div>
    </main>

    <section class="features">
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Manajemen Kelompok</h3>
                <p>Buat kelompok belajar, undang anggota, dan tentukan struktur peran (Ketua & Anggota) dengan mudah.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3>Pembagian Tugas</h3>
                <p>Bagikan sub-tugas secara adil dengan sistem checklist agar setiap anggota tahu apa yang harus dikerjakan.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Pantau Progress</h3>
                <p>Lihat perkembangan penyelesaian tugas secara real-time dengan visualisasi data interaktif.</p>
            </div>
        </div>
    </section>

    <footer>
        &copy; <?= date('Y') ?> AkademiX. Tugas Akhir Pemrograman Web.
    </footer>

</body>
</html>
