// assets/js/main.js
document.addEventListener('DOMContentLoaded', function() {
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const navMenu = document.getElementById('navMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            navMenu.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
        });
        
        mobileOverlay.addEventListener('click', function() {
            navMenu.classList.remove('active');
            mobileOverlay.classList.remove('active');
        });
    }
    
    // Search Functionality
    const searchInput = document.getElementById('searchInput');
    const productCards = document.querySelectorAll('.product-card');
    
    if (searchInput && productCards.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            productCards.forEach(card => {
                const title = card.querySelector('.product-title').textContent.toLowerCase();
                const category = card.querySelector('.product-category').textContent.toLowerCase();
                
                if (title.includes(searchTerm) || category.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
    
    // Add to Cart Animation
    const addToCartButtons = document.querySelectorAll('.btn-add-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
            // Add animation
            this.innerHTML = '<i class="fas fa-check"></i> Ditambahkan';
            this.style.backgroundColor = 'var(--success)';
            
            // Reset button after 2 seconds
            setTimeout(() => {
                this.innerHTML = '<i class="fas fa-cart-plus"></i> Tambah ke Keranjang';
                this.style.backgroundColor = '';
            }, 2000);
            
            // Send AJAX request to add to cart
            addToCart(productId);
        });
    });
    
    // Quantity Buttons in Cart
    const minusButtons = document.querySelectorAll('.quantity-minus');
    const plusButtons = document.querySelectorAll('.quantity-plus');
    
    minusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.nextElementSibling;
            let value = parseInt(input.value);
            if (value > 1) {
                input.value = value - 1;
                updateCartItem(this.dataset.itemId, value - 1);
            }
        });
    });
    
    plusButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            let value = parseInt(input.value);
            input.value = value + 1;
            updateCartItem(this.dataset.itemId, value + 1);
        });
    });
    
    // Contact Form Validation
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('name').value.trim();
            const email = document.getElementById('email').value.trim();
            const message = document.getElementById('message').value.trim();
            let isValid = true;
            
            // Reset errors
            document.querySelectorAll('.error').forEach(el => el.remove());
            
            // Validate name
            if (name.length < 2) {
                showError('name', 'Nama harus minimal 2 karakter');
                isValid = false;
            }
            
            // Validate email
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showError('email', 'Email tidak valid');
                isValid = false;
            }
            
            // Validate message
            if (message.length < 10) {
                showError('message', 'Pesan harus minimal 10 karakter');
                isValid = false;
            }
            
            if (isValid) {
                // Submit form via AJAX
                submitContactForm(this);
            }
        });
    }
    
    // Helper Functions
    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error';
        errorDiv.style.color = 'var(--danger)';
        errorDiv.style.fontSize = '0.9rem';
        errorDiv.style.marginTop = '0.25rem';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    }
    
    async function addToCart(productId) {
        try {
            const response = await fetch('../components/tambah_keranjang.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=add`
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update cart count
                const cartCount = document.querySelector('.cart-count');
                if (cartCount) {
                    cartCount.textContent = result.cart_count;
                } else {
                    // Create cart count if doesn't exist
                    const cartIcon = document.querySelector('.cart-icon a');
                    const countSpan = document.createElement('span');
                    countSpan.className = 'cart-count';
                    countSpan.textContent = result.cart_count;
                    cartIcon.appendChild(countSpan);
                }
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    async function updateCartItem(itemId, quantity) {
        try {
            const response = await fetch('../components/tambah_keranjang.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `item_id=${itemId}&quantity=${quantity}&action=update`
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Update total price
                document.getElementById('subtotal').textContent = 'Rp ' + result.subtotal.toLocaleString();
                document.getElementById('total').textContent = 'Rp ' + result.total.toLocaleString();
                document.getElementById(`item-total-${itemId}`).textContent = 'Rp ' + result.item_total.toLocaleString();
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    async function submitContactForm(form) {
        const formData = new FormData(form);
        
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Show success message
                const successDiv = document.createElement('div');
                successDiv.className = 'alert alert-success';
                successDiv.textContent = 'Pesan berhasil dikirim!';
                form.prepend(successDiv);
                form.reset();
                
                // Remove success message after 5 seconds
                setTimeout(() => {
                    successDiv.remove();
                }, 5000);
            } else {
                // Show error message
                const errorDiv = document.createElement('div');
                errorDiv.className = 'alert alert-danger';
                errorDiv.textContent = result.message;
                form.prepend(errorDiv);
            }
        } catch (error) {
            console.error('Error:', error);
        }
    }
    
    // Image Preview for Product Upload
    const imageUpload = document.getElementById('imageUpload');
    const imagePreview = document.getElementById('imagePreview');
    
    if (imageUpload && imagePreview) {
        imageUpload.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    }
});
// Toggle password visibility
document.addEventListener('DOMContentLoaded', function() {
    // Password toggle functionality
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('password');
    
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
        });
    }
    
    // Form validation on submit
    const loginForm = document.querySelector('.login-form');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            let isValid = true;
            
            // Simple email validation
            if (!email.value || !email.value.includes('@')) {
                email.classList.add('invalid');
                isValid = false;
            } else {
                email.classList.remove('invalid');
                email.classList.add('valid');
            }
            
            // Password validation
            if (!password.value || password.value.length < 6) {
                password.classList.add('invalid');
                isValid = false;
            } else {
                password.classList.remove('invalid');
                password.classList.add('valid');
            }
            
            if (!isValid) {
                e.preventDefault();
                // Show error message if not already present
                if (!document.querySelector('.alert-danger')) {
                    const alertDiv = document.createElement('div');
                    alertDiv.className = 'alert alert-danger';
                    alertDiv.textContent = 'Silakan periksa kembali email dan password Anda.';
                    loginForm.insertBefore(alertDiv, loginForm.firstChild);
                    
                    // Remove after 5 seconds
                    setTimeout(() => {
                        alertDiv.remove();
                    }, 5000);
                }
            } else {
                // Add loading state to button
                const submitBtn = this.querySelector('.btn-primary');
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            }
        });
    }
    
    // Real-time validation
    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');
    
    if (emailInput) {
        emailInput.addEventListener('input', function() {
            if (this.value.includes('@')) {
                this.classList.remove('invalid');
                this.classList.add('valid');
            } else {
                this.classList.remove('valid');
                this.classList.add('invalid');
            }
        });
    }
    
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            if (this.value.length >= 6) {
                this.classList.remove('invalid');
                this.classList.add('valid');
            } else {
                this.classList.remove('valid');
                this.classList.add('invalid');
            }
        });
    }
});
// products.js atau tambahkan ke main.js
document.addEventListener('DOMContentLoaded', function() {
    // Add to cart functionality
    const addToCartButtons = document.querySelectorAll('.btn-add-cart');
    
    addToCartButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            
            // Show loading state
            const originalHTML = this.innerHTML;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menambahkan...';
            this.disabled = true;
            
            // Simulate API call
            setTimeout(() => {
                // Show success message
                showNotification('success', `${productName} berhasil ditambahkan ke keranjang!`);
                
                // Reset button
                this.innerHTML = originalHTML;
                this.disabled = false;
                
                // Update cart count
                updateCartCount();
                
                // Add animation
                this.classList.add('added');
                setTimeout(() => {
                    this.classList.remove('added');
                }, 1000);
                
            }, 800);
        });
    });
    
    // Price range slider functionality
    const priceMin = document.getElementById('harga_min');
    const priceMax = document.getElementById('harga_max');
    
    if (priceMin && priceMax) {
        // Format currency display
        const formatCurrency = (value) => {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        };
        
        // Update display values
        priceMin.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            if (priceMax.value && value > parseInt(priceMax.value)) {
                this.value = priceMax.value;
            }
        });
        
        priceMax.addEventListener('input', function() {
            const value = parseInt(this.value) || 0;
            if (priceMin.value && value < parseInt(priceMin.value)) {
                this.value = priceMin.value;
            }
        });
    }
    
    // Filter form validation
    const filterForm = document.querySelector('.filter-form');
    if (filterForm) {
        filterForm.addEventListener('submit', function(e) {
            const hargaMin = document.getElementById('harga_min');
            const hargaMax = document.getElementById('harga_max');
            
            if (hargaMin.value && hargaMax.value) {
                const min = parseInt(hargaMin.value);
                const max = parseInt(hargaMax.value);
                
                if (min > max) {
                    e.preventDefault();
                    showNotification('error', 'Harga minimum tidak boleh lebih besar dari harga maksimum!');
                    hargaMin.focus();
                    return false;
                }
            }
            
            // Show loading
            const submitBtn = this.querySelector('.btn-primary');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
            submitBtn.disabled = true;
            
            // Reset after 2 seconds if still loading
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 2000);
        });
    }
    
    // Notification function
    function showNotification(type, message) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => {
            notification.remove();
        });
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
            <button class="notification-close"><i class="fas fa-times"></i></button>
        `;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    // Update cart count (mock function)
    function updateCartCount() {
        const cartCount = document.querySelector('.cart-count');
        if (cartCount) {
            const currentCount = parseInt(cartCount.textContent) || 0;
            cartCount.textContent = currentCount + 1;
            cartCount.classList.add('updated');
            setTimeout(() => {
                cartCount.classList.remove('updated');
            }, 500);
        }
    }
});

// Tambahkan style untuk notification
const notificationStyles = `
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        z-index: 9999;
        transform: translateX(120%);
        transition: transform 0.3s ease;
        max-width: 350px;
    }
    
    .notification.show {
        transform: translateX(0);
    }
    
    .notification-success {
        border-left: 4px solid #4cc9f0;
    }
    
    .notification-error {
        border-left: 4px solid #f72585;
    }
    
    .notification i {
        font-size: 1.2rem;
    }
    
    .notification-success i {
        color: #4cc9f0;
    }
    
    .notification-error i {
        color: #f72585;
    }
    
    .notification span {
        flex: 1;
        font-size: 0.95rem;
    }
    
    .notification-close {
        background: none;
        border: none;
        color: #999;
        cursor: pointer;
        padding: 0;
        font-size: 1rem;
        transition: color 0.3s ease;
    }
    
    .notification-close:hover {
        color: #666;
    }
    
    /* Cart count animation */
    .cart-count.updated {
        animation: bounce 0.5s ease;
    }
    
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
`;

// Inject notification styles
const styleSheet = document.createElement('style');
styleSheet.textContent = notificationStyles;
document.head.appendChild(styleSheet);
// Register page specific functionality
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.querySelector('.register-form');
    
    if (registerForm) {
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.querySelector('.strength-bar');
        const strengthText = document.querySelector('.strength-text');
        
        if (passwordInput && strengthBar && strengthText) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 8) strength++;
                if (/[A-Z]/.test(password)) strength++;
                if (/[0-9]/.test(password)) strength++;
                if (/[^A-Za-z0-9]/.test(password)) strength++;
                
                // Update strength bar
                strengthBar.className = 'strength-bar';
                strengthBar.classList.add(`strength-${strength}`);
                
                // Update strength text
                const messages = [
                    'Sangat Lemah',
                    'Lemah',
                    'Cukup',
                    'Kuat',
                    'Sangat Kuat'
                ];
                strengthText.textContent = messages[strength];
            });
        }
        
        // Password visibility toggle
        const passwordToggle = document.querySelector('.password-toggle');
        if (passwordToggle) {
            passwordToggle.addEventListener('click', function() {
                const passwordField = this.closest('.password-container').querySelector('input[type="password"], input[type="text"]');
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
            });
        }
        
        // Confirm password validation
        const confirmPassword = document.getElementById('confirm_password');
        if (confirmPassword) {
            confirmPassword.addEventListener('input', function() {
                const password = document.getElementById('password').value;
                const matchMessage = document.createElement('div');
                matchMessage.className = 'validation-message';
                
                if (this.value === '') {
                    matchMessage.remove();
                } else if (this.value === password) {
                    matchMessage.innerHTML = '<i class="fas fa-check-circle"></i> Password cocok';
                    matchMessage.classList.add('valid');
                } else {
                    matchMessage.innerHTML = '<i class="fas fa-times-circle"></i> Password tidak cocok';
                    matchMessage.classList.add('invalid');
                }
                
                // Remove old message if exists
                const oldMessage = this.parentNode.querySelector('.validation-message');
                if (oldMessage) oldMessage.remove();
                
                // Add new message
                if (this.value !== '') {
                    this.parentNode.appendChild(matchMessage);
                }
            });
        }
        
        // Form submission with loading state
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms');
            
            // Validate password match
            if (password !== confirmPassword) {
                e.preventDefault();
                showNotification('error', 'Password tidak cocok!');
                return false;
            }
            
            // Validate password strength
            if (password.length < 6) {
                e.preventDefault();
                showNotification('error', 'Password minimal 6 karakter!');
                return false;
            }
            
            // Validate terms
            if (!terms.checked) {
                e.preventDefault();
                showNotification('error', 'Anda harus menyetujui Syarat & Ketentuan!');
                return false;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.btn-register');
            submitBtn.classList.add('loading');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '';
            
            // Show progress bar
            const progressContainer = document.querySelector('.progress-container');
            const progressFill = document.querySelector('.progress-fill');
            if (progressContainer && progressFill) {
                progressContainer.style.display = 'block';
                
                let width = 0;
                const interval = setInterval(() => {
                    if (width >= 100) {
                        clearInterval(interval);
                    } else {
                        width += 10;
                        progressFill.style.width = width + '%';
                    }
                }, 100);
            }
        });
    }
    
    // Notification function
    function showNotification(type, message) {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notification => {
            notification.remove();
        });
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        // Insert after header
        const header = document.querySelector('.register-header');
        header.parentNode.insertBefore(notification, header.nextSibling);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateY(-10px)';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
});