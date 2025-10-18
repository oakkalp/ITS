<?php
// Demo uyarılarını göster
require_once 'demo_warnings.php';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>İşletme Takip Sistemi - Modern Muhasebe Yönetimi (DEMO)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #f093fb;
            --text-dark: #2d3748;
            --text-light: #718096;
            --bg-light: #f7fafc;
            --white: #ffffff;
            --shadow: 0 10px 25px rgba(0,0,0,0.1);
            --shadow-lg: 0 20px 40px rgba(0,0,0,0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary-color) !important;
        }

        .nav-link {
            font-weight: 500;
            color: var(--text-dark) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><pattern id="grid" width="50" height="50" patternUnits="userSpaceOnUse"><path d="M 50 0 L 0 0 0 50" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero h1 {
            font-size: 3.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero p {
            font-size: 1.25rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 2rem;
            max-width: 600px;
        }

        .btn-hero {
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--primary-color) 100%);
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            color: white;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
            color: white;
        }

        /* Features Section */
        .features {
            padding: 100px 0;
            background: var(--bg-light);
        }

        .section-title {
            text-align: center;
            margin-bottom: 4rem;
        }

        .section-title h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .section-title p {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
        }

        .feature-card {
            background: white;
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid rgba(0,0,0,0.05);
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: white;
            font-size: 2rem;
        }

        .feature-card h4 {
            font-size: 1.3rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .feature-card p {
            color: var(--text-light);
            line-height: 1.6;
        }

        /* Stats Section */
        .stats {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }

        .stat-item {
            text-align: center;
            padding: 2rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: white;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Benefits Section */
        .benefits {
            padding: 100px 0;
            background: white;
        }

        .benefit-item {
            display: flex;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            border-radius: 15px;
            transition: all 0.3s ease;
        }

        .benefit-item:hover {
            background: var(--bg-light);
            transform: translateX(10px);
        }

        .benefit-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-color) 0%, var(--primary-color) 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }

        .benefit-content h5 {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .benefit-content p {
            color: var(--text-light);
            margin: 0;
        }

        /* CTA Section */
        .cta {
            padding: 100px 0;
            background: linear-gradient(135deg, var(--bg-light) 0%, rgba(102, 126, 234, 0.05) 100%);
            text-align: center;
        }

        .cta h2 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 1rem;
        }

        .cta p {
            font-size: 1.1rem;
            color: var(--text-light);
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Footer */
        .footer {
            background: var(--text-dark);
            color: white;
            padding: 50px 0 30px;
        }

        .footer h5 {
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer p, .footer a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer a:hover {
            color: var(--primary-color);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .section-title h2 {
                font-size: 2rem;
            }
            
            .feature-card {
                margin-bottom: 2rem;
            }
            
            .benefit-item {
                flex-direction: column;
                text-align: center;
            }
            
            .benefit-icon {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Smooth scrolling */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="bi bi-building me-2"></i>İşletme Takibi
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Özellikler</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#benefits">Avantajlar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">İletişim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-primary ms-2 px-3" href="login.php">Giriş Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content animate-fade-in-up">
                        <h1>Modern İşletme Yönetimi</h1>
                        <p>Muhasebe, stok, fatura ve müşteri yönetimini tek platformda birleştiren kapsamlı işletme takip sistemi. İşletmenizi dijitalleştirin ve verimliliğinizi artırın.</p>
                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <a href="login.php" class="btn-hero">
                                <i class="bi bi-arrow-right me-2"></i>Sisteme Giriş Yap
                            </a>
                            <a href="https://prokonstarim.com.tr/isletmatakibv1.2.apk" class="btn-hero" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                                <i class="bi bi-download me-2"></i>Mobil Uygulamayı İndir
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center">
                        <div class="hero-image animate-fade-in-up" style="animation-delay: 0.3s;">
                            <i class="bi bi-graph-up" style="font-size: 15rem; color: rgba(255,255,255,0.2);"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-title">
                <h2>Sistem Özellikleri</h2>
                <p>İşletmenizin tüm ihtiyaçlarını karşılayan kapsamlı modüller</p>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-receipt"></i>
                        </div>
                        <h4>Fatura Yönetimi</h4>
                        <p>Alış ve satış faturalarını kolayca oluşturun, düzenleyin ve takip edin. Otomatik numaralandırma ve müşteri entegrasyonu.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-boxes"></i>
                        </div>
                        <h4>Stok Takibi</h4>
                        <p>Ürün stoklarınızı gerçek zamanlı takip edin. Kritik stok uyarıları ve otomatik güncellemeler.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4>Müşteri Yönetimi</h4>
                        <p>Cari hesapları ve müşteri bilgilerini merkezi olarak yönetin. Detaylı müşteri geçmişi ve analizleri.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-cash-coin"></i>
                        </div>
                        <h4>Kasa Yönetimi</h4>
                        <p>Gelir-gider takibi ve nakit akış yönetimi. Detaylı finansal raporlar ve analizler.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-file-earmark-text"></i>
                        </div>
                        <h4>Teklif Sistemi</h4>
                        <p>Profesyonel teklifler oluşturun ve müşterilere gönderin. Tekliften faturaya kolay geçiş.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="bi bi-graph-up-arrow"></i>
                        </div>
                        <h4>Raporlama</h4>
                        <p>Kapsamlı finansal raporlar, grafikler ve analizler. İşletmenizin performansını takip edin.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">100+</div>
                        <div class="stat-label">Aktif İşletme</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">50K+</div>
                        <div class="stat-label">İşlenen Fatura</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">99.9%</div>
                        <div class="stat-label">Sistem Uptime</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item">
                        <div class="stat-number">24/7</div>
                        <div class="stat-label">Destek Hizmeti</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Benefits Section -->
    <section id="benefits" class="benefits">
        <div class="container">
            <div class="section-title">
                <h2>Neden İşletme Takibi?</h2>
                <p>İşletmenizi büyütmek için ihtiyacınız olan tüm araçlar</p>
            </div>
            <div class="row">
                <div class="col-lg-6">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="bi bi-speedometer2"></i>
                        </div>
                        <div class="benefit-content">
                            <h5>Hızlı ve Kolay</h5>
                            <p>Sezgisel arayüz ile hızlıca öğrenin ve kullanmaya başlayın. Minimum eğitim süresi.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="benefit-content">
                            <h5>Güvenli ve Güvenilir</h5>
                            <p>Verileriniz SSL şifreleme ile korunur. Düzenli yedekleme ve güvenlik güncellemeleri.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="bi bi-phone"></i>
                        </div>
                        <div class="benefit-content">
                            <h5>Mobil Uyumlu</h5>
                            <p>Her cihazdan erişim sağlayın. Responsive tasarım ile mobil deneyim.</p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="bi bi-graph-up"></i>
                        </div>
                        <div class="benefit-content">
                            <h5>Verimlilik Artışı</h5>
                            <p>Otomatik işlemler ile %40'a kadar verimlilik artışı. Manuel işlemleri azaltın.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="bi bi-piggy-bank"></i>
                        </div>
                        <div class="benefit-content">
                            <h5>Maliyet Tasarrufu</h5>
                            <p>Kağıt ve manuel işlemleri azaltarak maliyetlerinizi düşürün.</p>
                        </div>
                    </div>
                    <div class="benefit-item">
                        <div class="benefit-icon">
                            <i class="bi bi-headset"></i>
                        </div>
                        <div class="benefit-content">
                            <h5>7/24 Destek</h5>
                            <p>Uzman ekibimizden 7/24 teknik destek alın. Hızlı çözüm garantisi.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section id="contact" class="cta">
        <div class="container">
            <h2>Hemen Başlayın</h2>
            <p>İşletmenizi dijitalleştirmek için bugün sisteme kaydolun. Ücretsiz deneme sürümü ile özellikleri keşfedin.</p>
            <div class="d-flex flex-column flex-sm-row gap-3 justify-content-center">
                <a href="login.php" class="btn-hero">
                    <i class="bi bi-rocket me-2"></i>Ücretsiz Deneyin
                </a>
                <a href="https://prokonstarim.com.tr/isletmatakibv1.2.apk" class="btn-hero" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <i class="bi bi-phone me-2"></i>Mobil Uygulamayı İndir
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><i class="bi bi-building me-2"></i>İşletme Takibi</h5>
                    <p>Modern işletme yönetimi için kapsamlı çözümler. Muhasebe, stok, fatura ve müşteri yönetimini tek platformda birleştirin.</p>
                </div>
                <div class="col-lg-2 mb-4">
                    <h5>Ürün</h5>
                    <p><a href="#features">Özellikler</a></p>
                    <p><a href="#benefits">Avantajlar</a></p>
                    <p><a href="login.php">Giriş Yap</a></p>
                    <p><a href="https://prokonstarim.com.tr/isletmatakibv1.2.apk">Mobil Uygulama</a></p>
                </div>
                <div class="col-lg-2 mb-4">
                    <h5>Destek</h5>
                    <p><a href="#contact">İletişim</a></p>
                    <p><a href="#">Yardım Merkezi</a></p>
                    <p><a href="#">Dokümantasyon</a></p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5>İletişim</h5>
                    <p><i class="bi bi-envelope me-2"></i>info@isletmetakibi.com</p>
                    <p><i class="bi bi-phone me-2"></i>+90 (212) 123 45 67</p>
                    <p><i class="bi bi-geo-alt me-2"></i>İstanbul, Türkiye</p>
                </div>
            </div>
            <hr style="border-color: rgba(255,255,255,0.2);">
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; 2024 İşletme Takibi. Tüm hakları saklıdır.</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observe elements for animation
        document.querySelectorAll('.feature-card, .benefit-item, .stat-item').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(el);
        });
    </script>
</body>
</html>
