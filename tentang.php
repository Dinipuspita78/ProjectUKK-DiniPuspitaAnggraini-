<?php
// tentang.php
session_start();
require_once 'components/database.php';

// Set page title
$page_title = "Tentang Kami";

// Check if user is logged in
$user_logged_in = isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'user';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MinShop - <?php echo $page_title; ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php 
    if ($user_logged_in) {
        include 'components/header_pengguna.php';
    } else {
        include 'components/header_guest.php';
    }
    ?>

<!-- Hero Section -->
<section class="about-hero">
    <div class="container">
        <div class="about-hero-content">
            <h1><i class="fas fa-paw"></i> Tentang MinShop</h1>
            <p>Solusi terbaik untuk semua kebutuhan hewan peliharaan Anda sejak 2010</p>
        </div>
    </div>
</section>

<!-- About Content -->
<section class="about-content">
    <div class="container">
        <!-- Our Story -->
        <div class="about-section">
            <div class="about-text">
                <h2><i class="fas fa-book-open"></i> Cerita Kami</h2>
                <p>MinShop didirikan pada tahun 2010 dengan misi sederhana: memberikan perawatan terbaik untuk hewan peliharaan. Berawal dari toko kecil di Jakarta, kami telah berkembang menjadi salah satu pet shop terpercaya di Indonesia.</p>
                <p>Dengan pengalaman lebih dari 10 tahun, kami memahami betul kebutuhan hewan peliharaan dan pemiliknya. Setiap produk yang kami jual melalui proses seleksi ketat untuk memastikan kualitas terbaik.</p>
                <div class="about-stats">
                    <div class="stat-item">
                        <div class="stat-number">10+</div>
                        <div class="stat-label">Tahun Pengalaman</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">5,000+</div>
                        <div class="stat-label">Pelanggan Setia</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">500+</div>
                        <div class="stat-label">Produk Berkualitas</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">50+</div>
                        <div class="stat-label">Merek Terkemuka</div>
                    </div>
                </div>
            </div>
            <div class="about-image">
                <img src="project images/ttg.jpg" alt="Our Story" onerror="this.src='project images/default.jpg'">
            </div>
        </div>

        <!-- Mission & Vision -->
        <div class="mission-vision">
            <div class="mission-card">
                <div class="mission-icon">
                    <i class="fas fa-bullseye"></i>
                </div>
                <h3>Misi Kami</h3>
                <p>Menyediakan produk dan layanan terbaik untuk kesehatan dan kebahagiaan hewan peliharaan, sambil memberikan edukasi kepada pemilik tentang perawatan yang tepat.</p>
            </div>
            
            <div class="vision-card">
                <div class="vision-icon">
                    <i class="fas fa-eye"></i>
                </div>
                <h3>Visi Kami</h3>
                <p>Menjadi pet shop terdepan di Indonesia yang dikenal dengan kualitas produk, pelayanan terbaik, dan komitmen terhadap kesejahteraan hewan.</p>
            </div>
        </div>

        <!-- Our Values -->
        <div class="values-section">
            <h2><i class="fas fa-star"></i> Nilai-nilai Kami</h2>
            <div class="values-grid">
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-heart"></i>
                    </div>
                    <h3>Kasih Sayang</h3>
                    <p>Kami percaya setiap hewan peliharaan layak mendapatkan kasih sayang dan perawatan terbaik.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Keamanan</h3>
                    <p>Semua produk kami aman, berkualitas, dan telah melalui uji kualitas ketat.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>Komunitas</h3>
                    <p>Kami membangun komunitas pecinta hewan yang saling mendukung dan berbagi informasi.</p>
                </div>
                
                <div class="value-card">
                    <div class="value-icon">
                        <i class="fas fa-leaf"></i>
                    </div>
                    <h3>Keberlanjutan</h3>
                    <p>Kami berkomitmen pada produk ramah lingkungan dan praktik bisnis berkelanjutan.</p>
                </div>
            </div>
        </div>

        <!-- Our Team -->
        <div class="team-section">
            <h2><i class="fas fa-users"></i> Tim Kami</h2>
            <p class="team-subtitle">Bertemu dengan tim yang penuh semangat di balik MinShop</p>
            
            <div class="team-grid">
                <div class="team-card">
                    <div class="team-image">
                        <img src="project images/pic-3.png" alt="Budi Santoso" onerror="this.src='project images/default.jpg'">
                    </div>
                    <div class="team-info">
                        <h3>Budi Santoso</h3>
                        <p class="team-role">Founder & CEO</p>
                        <p class="team-desc">Ahli gizi hewan dengan pengalaman 15 tahun di industri pet care.</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-image">
                        <img src="project images/pic-2.png" alt="Sari Dewi" onerror="this.src='project images/default.jpg'">
                    </div>
                    <div class="team-info">
                        <h3>Sari Dewi</h3>
                        <p class="team-role">Head Veterinarian</p>
                        <p class="team-desc">Dokter hewan lulusan IPB dengan spesialisasi nutrisi hewan peliharaan.</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-image">
                        <img src="project images/pic-5.png" alt="Ahmad Fauzi" onerror="this.src='project images/default.jpg'">
                    </div>
                    <div class="team-info">
                        <h3>Ahmad Fauzi</h3>
                        <p class="team-role">Product Manager</p>
                        <p class="team-desc">Bertanggung jawab atas seleksi dan pengujian kualitas semua produk kami.</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="team-card">
                    <div class="team-image">
                        <img src="project images/pic-4.png" alt="Maya Indah" onerror="this.src='project images/default.jpg'">
                    </div>
                    <div class="team-info">
                        <h3>Maya Indah</h3>
                        <p class="team-role">Customer Service Head</p>
                        <p class="team-desc">Siap membantu Anda dengan senyuman dan solusi terbaik.</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Store Locations -->
        <div class="locations-section">
            <h2><i class="fas fa-map-marker-alt"></i> Lokasi Toko Kami</h2>
            <div class="locations-grid">
                <div class="location-card">
                    <div class="location-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>Mojokerto</h3>
                    <p class="location-address">
                        Jl. Pahlawan No. 13, Mojokerto<br>
                        <i class="fas fa-phone"></i> (021) 1234-5678<br>
                        <i class="fas fa-clock"></i> 08:00 - 20:00 WIB
                    </p>
                </div>
                
                <div class="location-card">
                    <div class="location-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>Bandung</h3>
                    <p class="location-address">
                        Jl. Dago No. 25, Bandung<br>
                        <i class="fas fa-phone"></i> (022) 8765-4321<br>
                        <i class="fas fa-clock"></i> 08:00 - 20:00 WIB
                    </p>
                </div>
                
                <div class="location-card">
                    <div class="location-icon">
                        <i class="fas fa-store"></i>
                    </div>
                    <h3>Surabaya</h3>
                    <p class="location-address">
                        Jl. Tunjungan No. 50, Surabaya<br>
                        <i class="fas fa-phone"></i> (031) 5555-7777<br>
                        <i class="fas fa-clock"></i> 08:00 - 20:00 WIB
                    </p>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="about-cta">
            <div class="cta-content">
                <h2>Siap Memberikan yang Terbaik untuk Hewan Peliharaan Anda?</h2>
                <p>Bergabunglah dengan ribuan pelanggan puas yang telah mempercayakan kebutuhan hewan peliharaan mereka kepada kami.</p>
                <div class="cta-buttons">
                    <a href="produk.php" class="btn-primary">
                        <i class="fas fa-shopping-bag"></i> Belanja Sekarang
                    </a>
                    <a href="kontak.php" class="btn-secondary">
                        <i class="fas fa-envelope"></i> Hubungi Kami
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'components/footer.php'; ?>

<style>
/* About Hero */
.about-hero {
    background: linear-gradient(135deg, #4a90e2 0%, #50c878 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
    margin-bottom: 50px;
}

.about-hero-content h1 {
    font-size: 3rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.about-hero-content p {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

/* About Content */
.about-content {
    padding: 20px 0 60px;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Our Story Section */
.about-section {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 50px;
    align-items: center;
    margin-bottom: 80px;
    padding: 40px;
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.about-text h2 {
    color: #4a90e2;
    margin-bottom: 20px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.about-text p {
    color: #666;
    line-height: 1.8;
    margin-bottom: 20px;
    font-size: 1.1rem;
}

.about-image img {
    width: 100%;
    height: 400px;
    object-fit: cover;
    border-radius: 10px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.about-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 40px;
}

.stat-item {
    text-align: center;
    padding: 20px;
    background: #f0f7ff;
    border-radius: 10px;
    transition: transform 0.3s ease;
}

.stat-item:hover {
    transform: translateY(-5px);
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #4a90e2;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #666;
}

/* Mission & Vision */
.mission-vision {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 80px;
}

.mission-card, .vision-card {
    background: white;
    padding: 40px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.mission-card:hover, .vision-card:hover {
    transform: translateY(-10px);
}

.mission-card {
    border-top: 5px solid #4a90e2;
}

.vision-card {
    border-top: 5px solid #ff6b6b;
}

.mission-icon, .vision-icon {
    width: 80px;
    height: 80px;
    background: #4a90e2;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    margin: 0 auto 25px;
}

.vision-icon {
    background: #ff6b6b;
}

.mission-card h3, .vision-card h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.5rem;
}

.mission-card p, .vision-card p {
    color: #666;
    line-height: 1.6;
}

/* Values Section */
.values-section {
    margin-bottom: 80px;
}

.values-section h2 {
    text-align: center;
    color: #4a90e2;
    margin-bottom: 50px;
    font-size: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.values-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.value-card {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-align: center;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.value-card:hover {
    border-color: #4a90e2;
    transform: translateY(-10px);
}

.value-icon {
    width: 70px;
    height: 70px;
    background: #f0f7ff;
    color: #4a90e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 20px;
}

.value-card h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.3rem;
}

.value-card p {
    color: #666;
    line-height: 1.6;
    font-size: 0.95rem;
}

/* Team Section */
.team-section {
    margin-bottom: 80px;
}

.team-section h2 {
    text-align: center;
    color: #4a90e2;
    margin-bottom: 15px;
    font-size: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.team-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 50px;
    font-size: 1.1rem;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 30px;
}

.team-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.team-card:hover {
    transform: translateY(-10px);
}

.team-image {
    height: 250px;
    overflow: hidden;
}

.team-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.team-card:hover .team-image img {
    transform: scale(1.1);
}

.team-info {
    padding: 25px;
    text-align: center;
}

.team-info h3 {
    color: #333;
    margin-bottom: 5px;
    font-size: 1.3rem;
}

.team-role {
    color: #4a90e2;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.team-desc {
    color: #666;
    line-height: 1.6;
    margin-bottom: 20px;
    font-size: 0.9rem;
}

.team-social {
    display: flex;
    justify-content: center;
    gap: 15px;
}

.team-social a {
    width: 35px;
    height: 35px;
    background: #f0f7ff;
    color: #4a90e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
}

.team-social a:hover {
    background: #4a90e2;
    color: white;
    transform: translateY(-3px);
}

/* Locations Section */
.locations-section {
    margin-bottom: 80px;
}

.locations-section h2 {
    text-align: center;
    color: #4a90e2;
    margin-bottom: 50px;
    font-size: 2.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.locations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
}

.location-card {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
    border: 2px solid transparent;
}

.location-card:hover {
    transform: translateY(-10px);
    border-color: #4a90e2;
}

.location-icon {
    width: 70px;
    height: 70px;
    background: #f0f7ff;
    color: #4a90e2;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 20px;
}

.location-card h3 {
    color: #333;
    margin-bottom: 15px;
    font-size: 1.3rem;
}

.location-address {
    color: #666;
    line-height: 1.8;
}

.location-address i {
    color: #4a90e2;
    margin-right: 8px;
    width: 20px;
}

/* CTA Section */
.about-cta {
    background: linear-gradient(135deg, #4a90e2 0%, #50c878 100%);
    color: white;
    padding: 60px 40px;
    border-radius: 10px;
    text-align: center;
    margin-top: 40px;
}

.cta-content h2 {
    font-size: 2.2rem;
    margin-bottom: 20px;
}

.cta-content p {
    font-size: 1.1rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto 30px;
    line-height: 1.6;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn-primary, .btn-secondary {
    padding: 15px 30px;
    border-radius: 50px;
    text-decoration: none;
    font-weight: 600;
    font-size: 1rem;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.btn-primary {
    background: white;
    color: #4a90e2;
}

.btn-primary:hover {
    background: #f0f7ff;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

.btn-secondary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-secondary:hover {
    background: white;
    color: #4a90e2;
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.2);
}

/* Responsive Design */
@media (max-width: 992px) {
    .about-section {
        grid-template-columns: 1fr;
        text-align: center;
    }
    
    .about-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .mission-vision {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .about-hero-content h1 {
        font-size: 2.2rem;
        flex-direction: column;
        gap: 10px;
    }
    
    .about-stats {
        grid-template-columns: 1fr;
    }
    
    .values-grid,
    .team-grid,
    .locations-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn-primary, .btn-secondary {
        width: 100%;
        max-width: 300px;
        justify-content: center;
    }
    
    .about-section {
        padding: 25px;
    }
    
    .mission-card, .vision-card {
        padding: 30px 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Count animation for stats
    const statNumbers = document.querySelectorAll('.stat-number');
    
    if (statNumbers.length > 0) {
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    statNumbers.forEach(stat => {
                        const target = parseInt(stat.textContent.replace('+', ''));
                        let current = 0;
                        const increment = target / 50;
                        const timer = setInterval(() => {
                            current += increment;
                            if (current >= target) {
                                clearInterval(timer);
                                stat.textContent = target + '+';
                            } else {
                                stat.textContent = Math.floor(current) + '+';
                            }
                        }, 30);
                    });
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        
        const statsSection = document.querySelector('.about-stats');
        if (statsSection) {
            observer.observe(statsSection);
        }
    }
});
</script>
</body>
</html>