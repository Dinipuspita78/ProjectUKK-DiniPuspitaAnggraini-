<?php
require_once 'components/database.php';
require_once 'components/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle contact form submission
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $nama = mysqli_real_escape_string($conn, $_POST['nama'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['email'] ?? '');
    $telepon = mysqli_real_escape_string($conn, $_POST['telepon'] ?? '');
    $subjek = mysqli_real_escape_string($conn, $_POST['subjek'] ?? '');
    $pesan = mysqli_real_escape_string($conn, $_POST['pesan'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($nama)) {
        $errors[] = 'Nama wajib diisi';
    } elseif (strlen($nama) < 2) {
        $errors[] = 'Nama minimal 2 karakter';
    }
    
    if (empty($email)) {
        $errors[] = 'Email wajib diisi';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email tidak valid';
    }
    
    if (empty($telepon)) {
        $errors[] = 'Telepon wajib diisi';
    } elseif (!preg_match('/^[0-9+\-\s()]{10,15}$/', $telepon)) {
        $errors[] = 'Format telepon tidak valid';
    }
    
    if (empty($subjek)) {
        $errors[] = 'Subjek wajib diisi';
    } elseif (strlen($subjek) < 5) {
        $errors[] = 'Subjek minimal 5 karakter';
    }
    
    if (empty($pesan)) {
        $errors[] = 'Pesan wajib diisi';
    } elseif (strlen($pesan) < 10) {
        $errors[] = 'Pesan minimal 10 karakter';
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            // Cek dulu apakah kolom subjek ada
            $check_column = mysqli_query($conn, "SHOW COLUMNS FROM pesan_kontak LIKE 'subjek'");
            
            if (mysqli_num_rows($check_column) > 0) {
                // Kolom subjek ada
                $insert_query = "INSERT INTO pesan_kontak (nama, email, telepon, subjek, pesan) 
                                VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "sssss", $nama, $email, $telepon, $subjek, $pesan);
            } else {
                // Kolom subjek tidak ada, insert tanpa subjek
                $insert_query = "INSERT INTO pesan_kontak (nama, email, telepon, pesan) 
                                VALUES (?, ?, ?, ?)";
                $stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($stmt, "ssss", $nama, $email, $telepon, $pesan);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Pesan Anda berhasil dikirim! Kami akan membalas dalam 1-2 hari kerja.';
                $_POST = array();
            } else {
                $error = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
            }
        } catch (mysqli_sql_exception $e) {
            $error = 'Error database: ' . $e->getMessage();
        }
    } else {
        $error = implode('<br>', $errors);
    }
}

// ... sisa kode HTML tetap sama


$page_title = "Kontak Kami";
include 'components/header_pengguna.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register </title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<!-- Hero Section -->
<section class="contact-hero">
    <div class="container">
        <div class="contact-hero-content">
            <h1><i class="fas fa-envelope"></i> Hubungi Kami</h1>
            <p>Punya pertanyaan? Kami siap membantu Anda 24/7</p>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="contact-section">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Information -->
            <div class="contact-info">
                <h2><i class="fas fa-info-circle"></i> Informasi Kontak</h2>
                <p class="contact-subtitle">Jangan ragu untuk menghubungi kami melalui berbagai cara di bawah ini:</p>
                
                <div class="contact-methods">
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Alamat Kantor</h3>
                            <p>Jl. MinShopNo. 123<br>Jakarta Pusat, 10110<br>Indonesia</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-phone"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Telepon & WhatsApp</h3>
                            <p>(021) 1234-5678<br>+62 812-3456-7890 (WhatsApp)</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Email</h3>
                            <p>info@MinShop.com<br>support@MinShop.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <div class="contact-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="contact-details">
                            <h3>Jam Operasional</h3>
                            <p>Senin - Jumat: 08:00 - 20:00 WIB<br>Sabtu - Minggu: 09:00 - 17:00 WIB</p>
                        </div>
                    </div>
                </div>
                
                <!-- Social Media -->
                <div class="contact-social">
                    <h3><i class="fas fa-share-alt"></i> Ikuti Kami</h3>
                    <div class="social-icons">
                        <a href="#" class="social-icon facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="social-icon twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="social-icon instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="social-icon youtube">
                            <i class="fab fa-youtube"></i>
                        </a>
                        <a href="#" class="social-icon tiktok">
                            <i class="fab fa-tiktok"></i>
                        </a>
                    </div>
                </div>
                
                <!-- Emergency Contact -->
                <div class="emergency-contact">
                    <div class="emergency-icon">
                        <i class="fas fa-ambulance"></i>
                    </div>
                    <div class="emergency-info">
                        <h3>Butuh Bantuan Cepat?</h3>
                        <p>Untuk keadaan darurat hewan peliharaan, hubungi:</p>
                        <p class="emergency-number"><i class="fas fa-phone"></i> +62 811-1111-2222</p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="contact-form-container">
                <h2><i class="fas fa-paper-plane"></i> Kirim Pesan</h2>
                <p class="form-subtitle">Isi formulir di bawah ini dan kami akan segera merespons</p>
                
                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="contactForm" class="contact-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="nama">
                                <i class="fas fa-user"></i> Nama Lengkap *
                            </label>
                            <input type="text" id="nama" name="nama" 
                                   class="form-control <?php echo isset($_POST['nama']) && empty($_POST['nama']) ? 'error' : ''; ?>"
                                   value="<?php echo $_POST['nama'] ?? ''; ?>"
                                   placeholder="Masukkan nama lengkap Anda" required>
                            <div class="error-message" id="namaError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                <i class="fas fa-envelope"></i> Email *
                            </label>
                            <input type="email" id="email" name="email" 
                                   class="form-control <?php echo isset($_POST['email']) && empty($_POST['email']) ? 'error' : ''; ?>"
                                   value="<?php echo $_POST['email'] ?? ''; ?>"
                                   placeholder="email@contoh.com" required>
                            <div class="error-message" id="emailError"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="telepon">
                                <i class="fas fa-phone"></i> Nomor Telepon *
                            </label>
                            <input type="tel" id="telepon" name="telepon" 
                                   class="form-control <?php echo isset($_POST['telepon']) && empty($_POST['telepon']) ? 'error' : ''; ?>"
                                   value="<?php echo $_POST['telepon'] ?? ''; ?>"
                                   placeholder="0812-3456-7890" required>
                            <div class="error-message" id="teleponError"></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="subjek">
                                <i class="fas fa-tag"></i> Subjek *
                            </label>
                            <input type="text" id="subjek" name="subjek" 
                                   class="form-control <?php echo isset($_POST['subjek']) && empty($_POST['subjek']) ? 'error' : ''; ?>"
                                   value="<?php echo $_POST['subjek'] ?? ''; ?>"
                                   placeholder="Apa yang bisa kami bantu?" required>
                            <div class="error-message" id="subjekError"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="pesan">
                            <i class="fas fa-comment"></i> Pesan Anda *
                        </label>
                        <textarea id="pesan" name="pesan" 
                                  class="form-control <?php echo isset($_POST['pesan']) && empty($_POST['pesan']) ? 'error' : ''; ?>"
                                  rows="6" 
                                  placeholder="Tulis pesan detail Anda di sini..." 
                                  required><?php echo $_POST['pesan'] ?? ''; ?></textarea>
                        <div class="char-count">
                            <span id="charCount">0</span>/500 karakter
                        </div>
                        <div class="error-message" id="pesanError"></div>
                    </div>
                    
                    <div class="form-group">
                        <div class="captcha-container">
                            <div class="captcha-question">
                                <span id="captchaText">5 + 3 = ?</span>
                                <input type="hidden" id="captchaAnswer" value="8">
                            </div>
                            <input type="text" id="captchaInput" 
                                   class="form-control captcha-input"
                                   placeholder="Masukkan jawaban" required>
                            <button type="button" class="btn-refresh-captcha" onclick="refreshCaptcha()">
                                <i class="fas fa-redo"></i>
                            </button>
                        </div>
                        <div class="error-message" id="captchaError"></div>
                    </div>
                    
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" id="agree" name="agree" required class="form-check-input">
                            <label for="agree" class="form-check-label">
                                Saya setuju dengan <a href="#" data-toggle="modal" data-target="#privacyModal">Kebijakan Privasi</a> dan 
                                <a href="#" data-toggle="modal" data-target="#termsModal">Syarat Layanan</a>
                            </label>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-paper-plane"></i> Kirim Pesan
                        <span class="loading-spinner" id="loadingSpinner"></span>
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Map Section -->
        <div class="map-section">
            <h2><i class="fas fa-map"></i> Temukan Kami di Google Maps</h2>
            <div class="map-container">
                <div class="map-placeholder" id="mapPlaceholder">
                    <div class="map-overlay">
                        <i class="fas fa-map-marker-alt"></i>
                        <h3>Lokasi MinShop</h3>
                        <p>Klik untuk melihat di Google Maps</p>
                        <button class="btn-view-map" onclick="openGoogleMaps()">
                            <i class="fas fa-external-link-alt"></i> Buka Google Maps
                        </button>
                    </div>
                </div>
                <div class="map-info">
                    <p><i class="fas fa-info-circle"></i> <strong>Petunjuk arah:</strong> Dari Bundaran HI, ambil arah ke Thamrin, lalu belok kiri di Jl. MinShop No. 123.</p>
                    <p><i class="fas fa-parking"></i> <strong>Parkir:</strong> Tersedia area parkir untuk 20 kendaraan.</p>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="contact-faq">
            <h2><i class="fas fa-question-circle"></i> Pertanyaan yang Sering Diajukan</h2>
            <div class="faq-grid">
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Berapa lama waktu respon untuk pesan yang dikirim?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Kami berusaha merespons semua pesan dalam waktu 24 jam pada hari kerja. Untuk pertanyaan mendesak, silakan hubungi nomor telepon kami.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Apakah tersedia layanan antar untuk produk?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Ya, kami menyediakan layanan pengiriman ke seluruh Indonesia. Biaya pengiriman bervariasi tergantung lokasi dan berat paket.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Bagaimana cara melakukan pengembalian produk?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Pengembalian produk dapat dilakukan dalam 7 hari setelah pembelian dengan syarat produk masih dalam kondisi baik dan kemasan asli.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <div class="faq-question">
                        <span>Apakah ada layanan konsultasi hewan peliharaan?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        <p>Ya, kami memiliki dokter hewan yang siap memberikan konsultasi gratis setiap Sabtu pukul 10:00 - 14:00 WIB.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Modals -->
<div class="modal" id="privacyModal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2><i class="fas fa-shield-alt"></i> Kebijakan Privasi</h2>
        <div class="modal-body">
            <p>Kami menghargai privasi Anda. Informasi yang Anda berikan melalui formulir kontak hanya akan digunakan untuk:</p>
            <ul>
                <li>Merespons pertanyaan Anda</li>
                <li>Meningkatkan layanan kami</li>
                <li>Mengirim informasi penting terkait pesanan Anda</li>
            </ul>
            <p>Kami tidak akan membagikan informasi Anda kepada pihak ketiga tanpa izin Anda.</p>
        </div>
    </div>
</div>

<div class="modal" id="termsModal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <h2><i class="fas fa-file-contract"></i> Syarat Layanan</h2>
        <div class="modal-body">
            <p>Dengan menggunakan layanan kami, Anda setuju dengan syarat-syarat berikut:</p>
            <ul>
                <li>Informasi yang diberikan harus akurat dan benar</li>
                <li>Pesan yang dikirim tidak mengandung konten yang melanggar hukum</li>
                <li>Kami berhak menolak pesan yang tidak sesuai dengan kebijakan kami</li>
                <li>Respon kami bersifat informatif dan tidak mengikat</li>
            </ul>
        </div>
    </div>
</div>

<style>
/* Contact Hero */
.contact-hero {
    background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
    margin-bottom: 50px;
}

.contact-hero-content h1 {
    font-size: 3rem;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 15px;
}

.contact-hero-content p {
    font-size: 1.2rem;
    opacity: 0.9;
    max-width: 600px;
    margin: 0 auto;
}

/* Contact Grid */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 50px;
    margin-bottom: 80px;
}

@media (max-width: 992px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}

/* Contact Information */
.contact-info {
    background: white;
    padding: 40px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.contact-info h2 {
    color: var(--primary);
    margin-bottom: 15px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-subtitle {
    color: var(--text);
    margin-bottom: 30px;
    line-height: 1.6;
}

.contact-methods {
    margin-bottom: 40px;
}

.contact-method {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 30px;
    padding-bottom: 30px;
    border-bottom: 1px solid var(--border);
}

.contact-method:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.contact-icon {
    width: 60px;
    height: 60px;
    background: var(--light);
    color: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.contact-details h3 {
    color: var(--dark);
    margin-bottom: 10px;
    font-size: 1.2rem;
}

.contact-details p {
    color: var(--text);
    line-height: 1.6;
}

/* Social Media */
.contact-social {
    margin-bottom: 40px;
    padding: 25px;
    background: var(--light);
    border-radius: var(--radius);
}

.contact-social h3 {
    color: var(--dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.social-icons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.social-icon {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    text-decoration: none;
    transition: all 0.3s ease;
}

.social-icon:hover {
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.social-icon.facebook { background: #1877f2; }
.social-icon.twitter { background: #1da1f2; }
.social-icon.instagram { background: #e4405f; }
.social-icon.youtube { background: #ff0000; }
.social-icon.tiktok { background: #000000; }

/* Emergency Contact */
.emergency-contact {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 25px;
    background: linear-gradient(135deg, #ff6b6b 0%, #ff8e8e 100%);
    border-radius: var(--radius);
    color: white;
}

.emergency-icon {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
}

.emergency-info h3 {
    margin-bottom: 5px;
    font-size: 1.3rem;
}

.emergency-info p {
    opacity: 0.9;
    margin-bottom: 5px;
    font-size: 0.95rem;
}

.emergency-number {
    font-size: 1.2rem !important;
    font-weight: 700;
    margin-top: 10px !important;
}

.emergency-number i {
    margin-right: 10px;
}

/* Contact Form */
.contact-form-container {
    background: white;
    padding: 40px;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
}

.contact-form-container h2 {
    color: var(--primary);
    margin-bottom: 15px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-subtitle {
    color: var(--text);
    margin-bottom: 30px;
    line-height: 1.6;
}

.alert {
    padding: 15px 20px;
    border-radius: var(--radius);
    margin-bottom: 25px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert i {
    font-size: 1.2rem;
    flex-shrink: 0;
}

.contact-form {
    display: grid;
    gap: 25px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 576px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}

.form-group {
    position: relative;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--dark);
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid var(--border);
    border-radius: var(--radius);
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(109, 157, 197, 0.2);
}

.form-control.error {
    border-color: var(--danger);
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.char-count {
    text-align: right;
    font-size: 0.85rem;
    color: var(--text);
    margin-top: 5px;
}

/* CAPTCHA */
.captcha-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.captcha-question {
    background: var(--light);
    padding: 12px 20px;
    border-radius: var(--radius);
    font-weight: 600;
    color: var(--primary);
    font-size: 1.1rem;
    min-width: 120px;
    text-align: center;
}

.captcha-input {
    flex: 1;
    max-width: 150px;
}

.btn-refresh-captcha {
    width: 45px;
    height: 45px;
    background: var(--light);
    border: none;
    border-radius: var(--radius);
    color: var(--primary);
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-refresh-captcha:hover {
    background: var(--primary);
    color: white;
}

/* Form Check */
.form-check {
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-check-input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-check-label {
    cursor: pointer;
    color: var(--text);
    font-size: 0.95rem;
}

.form-check-label a {
    color: var(--primary);
    text-decoration: none;
}

.form-check-label a:hover {
    text-decoration: underline;
}

/* Submit Button */
.btn-submit {
    width: 100%;
    padding: 16px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: var(--radius);
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.btn-submit:hover:not(:disabled) {
    background: var(--secondary);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(109, 157, 197, 0.3);
}

.btn-submit:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.loading-spinner {
    display: none;
    width: 20px;
    height: 20px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-radius: 50%;
    border-top-color: white;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Error Messages */
.error-message {
    color: var(--danger);
    font-size: 0.85rem;
    margin-top: 5px;
    display: none;
}

/* Map Section */
.map-section {
    margin-bottom: 80px;
}

.map-section h2 {
    color: var(--primary);
    margin-bottom: 30px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.map-container {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.map-placeholder {
    height: 400px;
    background: linear-gradient(45deg, #667eea 0%, #764ba2 100%);
    position: relative;
    cursor: pointer;
}

.map-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: white;
    text-align: center;
    padding: 20px;
}

.map-overlay i {
    font-size: 3rem;
    margin-bottom: 20px;
    color: var(--primary);
}

.map-overlay h3 {
    font-size: 1.8rem;
    margin-bottom: 10px;
}

.map-overlay p {
    opacity: 0.9;
    margin-bottom: 25px;
    font-size: 1.1rem;
}

.btn-view-map {
    padding: 12px 30px;
    background: var(--primary);
    color: white;
    border: none;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-view-map:hover {
    background: var(--secondary);
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.map-info {
    padding: 25px;
    background: var(--light);
    border-top: 1px solid var(--border);
}

.map-info p {
    margin-bottom: 10px;
    color: var(--text);
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.map-info p:last-child {
    margin-bottom: 0;
}

.map-info i {
    color: var(--primary);
    margin-top: 3px;
    flex-shrink: 0;
}

/* FAQ Section */
.contact-faq {
    margin-bottom: 80px;
}

.contact-faq h2 {
    color: var(--primary);
    margin-bottom: 40px;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.faq-grid {
    display: grid;
    gap: 15px;
}

.faq-item {
    background: white;
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.faq-question {
    padding: 20px 25px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: 600;
    color: var(--dark);
    transition: background-color 0.3s ease;
}

.faq-question:hover {
    background: var(--light);
}

.faq-question i {
    transition: transform 0.3s ease;
}

.faq-item.active .faq-question i {
    transform: rotate(180deg);
}

.faq-answer {
    padding: 0 25px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s ease;
}

.faq-item.active .faq-answer {
    padding: 0 25px 25px;
    max-height: 500px;
}

.faq-answer p {
    color: var(--text);
    line-height: 1.6;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal.active {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: var(--radius);
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
    animation: modalSlideIn 0.3s ease;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.close-modal {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 1.8rem;
    cursor: pointer;
    color: var(--text);
    z-index: 1;
}

.modal-content h2 {
    color: var(--primary);
    padding: 30px 30px 20px;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-body {
    padding: 0 30px 30px;
}

.modal-body p {
    color: var(--text);
    line-height: 1.6;
    margin-bottom: 15px;
}

.modal-body ul {
    color: var(--text);
    margin: 15px 0 15px 20px;
}

.modal-body li {
    margin-bottom: 8px;
    line-height: 1.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .contact-hero-content h1 {
        font-size: 2.2rem;
    }
    
    .contact-info,
    .contact-form-container {
        padding: 25px;
    }
    
    .map-placeholder {
        height: 300px;
    }
    
    .map-overlay h3 {
        font-size: 1.5rem;
    }
    
    .modal-content {
        max-height: 95vh;
    }
}

@media (max-width: 480px) {
    .contact-method {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .emergency-contact {
        flex-direction: column;
        text-align: center;
    }
    
    .social-icons {
        justify-content: center;
    }
    
    .captcha-container {
        flex-direction: column;
        align-items: stretch;
    }
    
    .captcha-question {
        min-width: auto;
    }
    
    .captcha-input {
        max-width: none;
    }
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.contact-info,
.contact-form-container,
.map-container,
.faq-item {
    animation: fadeIn 0.6s ease forwards;
}

.contact-info { animation-delay: 0.1s; }
.contact-form-container { animation-delay: 0.2s; }
.map-container { animation-delay: 0.3s; }
.faq-item:nth-child(1) { animation-delay: 0.4s; }
.faq-item:nth-child(2) { animation-delay: 0.5s; }
.faq-item:nth-child(3) { animation-delay: 0.6s; }
.faq-item:nth-child(4) { animation-delay: 0.7s; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize CAPTCHA
    generateCaptcha();
    
    // Character counter for message
    const messageInput = document.getElementById('pesan');
    const charCount = document.getElementById('charCount');
    
    messageInput.addEventListener('input', function() {
        const length = this.value.length;
        charCount.textContent = length;
        
        if (length > 500) {
            this.value = this.value.substring(0, 500);
            charCount.textContent = 500;
        }
        
        // Update character count color
        if (length > 450) {
            charCount.style.color = 'var(--danger)';
        } else if (length > 400) {
            charCount.style.color = 'var(--warning)';
        } else {
            charCount.style.color = 'var(--text)';
        }
    });
    
    // Form validation
    const form = document.getElementById('contactForm');
    const submitBtn = document.getElementById('submitBtn');
    const loadingSpinner = document.getElementById('loadingSpinner');
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validate form
        if (validateForm()) {
            // Show loading
            submitBtn.disabled = true;
            loadingSpinner.style.display = 'block';
            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Mengirim...<span class="loading-spinner" id="loadingSpinner"></span>';
            
            // Simulate form submission (in real app, this would be AJAX)
            setTimeout(() => {
                // Submit the form
                form.submit();
            }, 1500);
        }
    });
    
    // Real-time validation
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            // Clear error when user starts typing
            const errorElement = document.getElementById(this.id + 'Error');
            if (errorElement) {
                errorElement.style.display = 'none';
                this.classList.remove('error');
            }
        });
    });
    
    // CAPTCHA validation
    const captchaInput = document.getElementById('captchaInput');
    captchaInput.addEventListener('input', function() {
        const errorElement = document.getElementById('captchaError');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    });
    
    // FAQ accordion
    const faqItems = document.querySelectorAll('.faq-item');
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', function() {
            // Close other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item && otherItem.classList.contains('active')) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });
    
    // Modal functionality
    const modals = document.querySelectorAll('.modal');
    const closeButtons = document.querySelectorAll('.close-modal');
    
    // Open modal when privacy/terms links are clicked
    document.querySelectorAll('[data-toggle="modal"]').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = this.getAttribute('data-target');
            document.querySelector(target).classList.add('active');
            document.body.style.overflow = 'hidden';
        });
    });
    
    // Close modal
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            this.closest('.modal').classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    });
    
    // Close modal when clicking outside
    modals.forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.remove('active');
                document.body.style.overflow = 'auto';
            }
        });
    });
    
    // Add hover effect to contact methods
    const contactMethods = document.querySelectorAll('.contact-method');
    contactMethods.forEach(method => {
        method.addEventListener('mouseenter', function() {
            const icon = this.querySelector('.contact-icon');
            icon.style.transform = 'scale(1.1) rotate(5deg)';
            icon.style.background = 'var(--primary)';
            icon.style.color = 'white';
        });
        
        method.addEventListener('mouseleave', function() {
            const icon = this.querySelector('.contact-icon');
            icon.style.transform = 'scale(1) rotate(0)';
            icon.style.background = 'var(--light)';
            icon.style.color = 'var(--primary)';
        });
    });
    
    // Add animation on scroll
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.contact-method, .faq-item').forEach(el => {
        observer.observe(el);
    });
    
    // Add scroll to top button
    const scrollButton = document.createElement('button');
    scrollButton.className = 'scroll-to-top';
    scrollButton.innerHTML = '<i class="fas fa-chevron-up"></i>';
    scrollButton.style.cssText = `
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 50px;
        height: 50px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 4px 15px rgba(109, 157, 197, 0.3);
        z-index: 1000;
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.3s ease;
    `;
    
    document.body.appendChild(scrollButton);
    
    // Show/hide scroll button
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            scrollButton.style.opacity = '1';
            scrollButton.style.transform = 'translateY(0)';
        } else {
            scrollButton.style.opacity = '0';
            scrollButton.style.transform = 'translateY(20px)';
        }
    });
    
    // Scroll to top function
    scrollButton.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    // Initialize form with user data if available
    initializeFormWithUserData();
});

// Generate CAPTCHA
function generateCaptcha() {
    const num1 = Math.floor(Math.random() * 10) + 1;
    const num2 = Math.floor(Math.random() * 10) + 1;
    const operators = ['+', '-', '×'];
    const operator = operators[Math.floor(Math.random() * operators.length)];
    
    let answer;
    let displayOperator;
    
    switch(operator) {
        case '+':
            answer = num1 + num2;
            displayOperator = '+';
            break;
        case '-':
            answer = num1 - num2;
            displayOperator = '-';
            break;
        case '×':
            answer = num1 * num2;
            displayOperator = '×';
            break;
    }
    
    document.getElementById('captchaText').textContent = `${num1} ${displayOperator} ${num2} = ?`;
    document.getElementById('captchaAnswer').value = answer;
}

// Refresh CAPTCHA
function refreshCaptcha() {
    generateCaptcha();
    document.getElementById('captchaInput').value = '';
    document.getElementById('captchaInput').classList.remove('error');
    const errorElement = document.getElementById('captchaError');
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

// Validate form
function validateForm() {
    let isValid = true;
    
    // Validate all required fields
    const requiredFields = ['nama', 'email', 'telepon', 'subjek', 'pesan'];
    requiredFields.forEach(fieldId => {
        const field = document.getElementById(fieldId);
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    // Validate CAPTCHA
    const captchaInput = document.getElementById('captchaInput');
    const captchaAnswer = document.getElementById('captchaAnswer').value;
    const captchaError = document.getElementById('captchaError');
    
    if (!captchaInput.value.trim()) {
        captchaError.textContent = 'Silakan jawab pertanyaan CAPTCHA';
        captchaError.style.display = 'block';
        captchaInput.classList.add('error');
        isValid = false;
    } else if (parseInt(captchaInput.value) !== parseInt(captchaAnswer)) {
        captchaError.textContent = 'Jawaban CAPTCHA salah. Silakan coba lagi.';
        captchaError.style.display = 'block';
        captchaInput.classList.add('error');
        isValid = false;
    }
    
    // Validate agreement checkbox
    const agreeCheckbox = document.getElementById('agree');
    if (!agreeCheckbox.checked) {
        alert('Anda harus menyetujui Kebijakan Privasi dan Syarat Layanan');
        isValid = false;
    }
    
    return isValid;
}

// Validate individual field
function validateField(field) {
    const value = field.value.trim();
    const errorElement = document.getElementById(field.id + 'Error');
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous error
    if (errorElement) {
        errorElement.style.display = 'none';
        field.classList.remove('error');
    }
    
    // Check if empty
    if (!value) {
        errorMessage = 'Field ini wajib diisi';
        isValid = false;
    } else {
        // Field-specific validation
        switch(field.id) {
            case 'nama':
                if (value.length < 2) {
                    errorMessage = 'Nama minimal 2 karakter';
                    isValid = false;
                }
                break;
                
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(value)) {
                    errorMessage = 'Email tidak valid';
                    isValid = false;
                }
                break;
                
            case 'telepon':
                const phoneRegex = /^[0-9+\-\s()]{10,15}$/;
                if (!phoneRegex.test(value)) {
                    errorMessage = 'Format telepon tidak valid';
                    isValid = false;
                }
                break;
                
            case 'subjek':
                if (value.length < 5) {
                    errorMessage = 'Subjek minimal 5 karakter';
                    isValid = false;
                }
                break;
                
            case 'pesan':
                if (value.length < 10) {
                    errorMessage = 'Pesan minimal 10 karakter';
                    isValid = false;
                }
                break;
        }
    }
    
    // Show error if any
    if (!isValid && errorElement) {
        errorElement.textContent = errorMessage;
        errorElement.style.display = 'block';
        field.classList.add('error');
        
        // Scroll to error field
        field.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    
    return isValid;
}

// Open Google Maps
function openGoogleMaps() {
    const address = encodeURIComponent('Jl. MinShop No. 123, Jakarta Pusat, Indonesia');
    window.open(`https://www.google.com/maps/search/?api=1&query=${address}`, '_blank');
}

// Initialize form with user data from session
function initializeFormWithUserData() {
    <?php if (isset($_SESSION['user_id'])): ?>
    // Get user data (you would typically fetch this from database)
    const userData = {
        nama: '<?php echo addslashes($_SESSION['nama'] ?? ''); ?>',
        email: '<?php echo addslashes($_SESSION['email'] ?? ''); ?>',
        telepon: '' // You would fetch this from database
    };
    
    // Populate form fields if empty
    if (userData.nama && !document.getElementById('nama').value) {
        document.getElementById('nama').value = userData.nama;
    }
    
    if (userData.email && !document.getElementById('email').value) {
        document.getElementById('email').value = userData.email;
    }
    
    if (userData.telepon && !document.getElementById('telepon').value) {
        document.getElementById('telepon').value = userData.telepon;
    }
    <?php endif; ?>
}

// Additional styles for animations
const additionalStyles = document.createElement('style');
additionalStyles.textContent = `
.scroll-to-top:hover {
    background: var(--secondary) !important;
    transform: translateY(-3px) !important;
    box-shadow: 0 6px 20px rgba(109, 157, 197, 0.4) !important;
}

.animated {
    animation: fadeInUp 0.6s ease forwards;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Tooltip for social icons */
.social-icon {
    position: relative;
}

.social-icon::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-size: 0.8rem;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.social-icon:hover::after {
    opacity: 1;
    visibility: visible;
    transform: translateX(-50%) translateY(-5px);
}

/* Ripple effect for buttons */
.btn-submit {
    position: relative;
    overflow: hidden;
}

.btn-submit::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 5px;
    height: 5px;
    background: rgba(255, 255, 255, 0.5);
    opacity: 0;
    border-radius: 100%;
    transform: scale(1, 1) translate(-50%);
    transform-origin: 50% 50%;
}

.btn-submit:focus:not(:active)::after {
    animation: ripple 1s ease-out;
}

@keyframes ripple {
    0% {
        transform: scale(0, 0);
        opacity: 0.5;
    }
    100% {
        transform: scale(20, 20);
        opacity: 0;
    }
}

/* Success animation for form */
.form-success .form-control {
    border-color: var(--success) !important;
}

/* Focus states for accessibility */
.form-control:focus,
.btn-submit:focus,
.btn-view-map:focus,
.social-icon:focus {
    outline: 2px solid var(--primary);
    outline-offset: 2px;
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .contact-info,
    .contact-form-container,
    .faq-item,
    .map-container {
        background: #2d3748;
        color: #e2e8f0;
    }
    
    .contact-info h2,
    .contact-form-container h2,
    .map-section h2,
    .contact-faq h2 {
        color: #63b3ed;
    }
    
    .contact-details p,
    .form-subtitle,
    .char-count {
        color: #a0aec0;
    }
    
    .form-control {
        background: #4a5568;
        border-color: #718096;
        color: #e2e8f0;
    }
    
    .form-control:focus {
        border-color: #63b3ed;
        box-shadow: 0 0 0 3px rgba(99, 179, 237, 0.2);
    }
}
`;
document.head.appendChild(additionalStyles);
</script>

<?php include 'components/footer.php'; ?>