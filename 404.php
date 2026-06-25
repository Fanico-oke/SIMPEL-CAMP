<?php
// 404.php — Page Not Found
require_once 'config/constants.php';
$page_title = "Halaman Tidak Ditemukan";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - <?= APP_NAME ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        :root {
            --dark: #0F2B1E;
            --primary: #1B4332;
            --primary-light: #2D6A4F;
            --primary-lighter: #52B788;
            --accent: #D4A373;
            --accent2: #E9C46A;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            position: relative;
            background:
                linear-gradient(165deg,
                    rgba(15, 43, 30, 0.93) 0%,
                    rgba(22, 56, 40, 0.88) 50%,
                    rgba(15, 43, 30, 0.95) 100%
                ),
                url('https://images.unsplash.com/photo-1478827536114-da961b7f86d2?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover no-repeat;
            color: white;
        }

        /* Decorative elements */
        body::before {
            content: '';
            position: absolute;
            top: 20%;
            left: 5%;
            width: 400px;
            height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(82,183,136,0.06), transparent 70%);
            pointer-events: none;
        }

        body::after {
            content: '';
            position: absolute;
            bottom: 10%;
            right: 10%;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(212,163,115,0.05), transparent 70%);
            pointer-events: none;
        }

        .error-container {
            text-align: center;
            position: relative;
            z-index: 1;
            padding: 2rem;
            max-width: 550px;
            animation: fadeUp 0.8s ease forwards;
        }

        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .error-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
            animation: bounce 2s ease-in-out infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-12px); }
        }

        .error-code {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(5rem, 15vw, 9rem);
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .error-title {
            font-family: 'Outfit', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.75rem;
        }

        .error-desc {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.55);
            line-height: 1.7;
            margin-bottom: 2rem;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #1a1a1a;
            font-weight: 700;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            padding: 0.85rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 10px 30px rgba(212, 163, 115, 0.3);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 18px 40px rgba(212, 163, 115, 0.45);
            color: #1a1a1a;
        }

        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: transparent;
            color: rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-weight: 500;
            font-size: 0.85rem;
            padding: 0.85rem 2rem;
            border-radius: 50px;
            text-decoration: none;
            transition: all 0.3s;
        }

        .btn-back:hover {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.35);
            color: white;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <span class="error-icon">🏕️</span>
        <div class="error-code">404</div>
        <h1 class="error-title">Sepertinya Anda Tersesat!</h1>
        <p class="error-desc">
            Halaman yang Anda cari tidak ditemukan. Mungkin jalur pendakiannya sudah berubah, atau halaman ini sudah dipindahkan.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="<?= BASE_URL ?>/" class="btn-home">
                <i class="bi bi-house"></i> Kembali ke Beranda
            </a>
            <a href="javascript:history.back()" class="btn-back">
                <i class="bi bi-arrow-left"></i> Halaman Sebelumnya
            </a>
        </div>
    </div>
</body>
</html>
