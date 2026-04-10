// assets/js/admin.js
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle for admin panel
    const mobileMenuToggle = document.createElement('button');
    mobileMenuToggle.className = 'mobile-menu-toggle';
    mobileMenuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    document.querySelector('.admin-main').prepend(mobileMenuToggle);
    
    mobileMenuToggle.addEventListener('click', function() {
        document.querySelector('.sidebar').classList.toggle('active');
    });
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('.sidebar');
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnToggle = mobileMenuToggle.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth <= 768) {
            sidebar.classList.remove('active');
        }
    });
    
    // Form validation for admin forms
    const forms = document.querySelectorAll('form[method="POST"]');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'var(--danger)';
                    
                    // Add error message
                    if (!field.nextElementSibling || !field.nextElementSibling.classList.contains('error-msg')) {
                        const errorMsg = document.createElement('div');
                        errorMsg.className = 'error-msg';
                        errorMsg.textContent = 'Field ini wajib diisi';
                        errorMsg.style.color = 'var(--danger)';
                        errorMsg.style.fontSize = '0.85rem';
                        errorMsg.style.marginTop = '5px';
                        field.parentNode.appendChild(errorMsg);
                    }
                } else {
                    field.style.borderColor = '';
                    
                    // Remove error message
                    const errorMsg = field.parentNode.querySelector('.error-msg');
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                showNotification('Harap isi semua field yang wajib!', 'error');
            }
        });
    });
    
    // Image preview for upload
    const imageUploads = document.querySelectorAll('input[type="file"][accept="image/*"]');
    imageUploads.forEach(upload => {
        upload.addEventListener('change', function() {
            const previewId = this.dataset.preview || 'imagePreview';
            const previewContainer = document.getElementById(previewId);
            
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewContainer.innerHTML = `
                        <div class="image-preview-item">
                            <img src="${e.target.result}" alt="Preview">
                            <button type="button" class="remove-preview">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `;
                    
                    // Add remove functionality
                    previewContainer.querySelector('.remove-preview').addEventListener('click', function() {
                        previewContainer.innerHTML = '';
                        upload.value = '';
                    });
                };
                
                reader.readAsDataURL(this.files[0]);
            }
        });
    });
    
    // Table row actions
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Anda yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });
    
    // Search functionality in tables
    const searchInputs = document.querySelectorAll('.table-search');
    searchInputs.forEach(input => {
        input.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const table = this.closest('.card').querySelector('.data-table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    });
    
    // Status change in orders
    const statusSelects = document.querySelectorAll('.status-select');
    statusSelects.forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            const newStatus = this.value;
            
            // Send AJAX request to update status
            fetch('update_order_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `order_id=${orderId}&status=${newStatus}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification('Status berhasil diperbarui!', 'success');
                    
                    // Update status badge
                    const statusBadge = this.parentNode.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.textContent = data.new_status;
                        statusBadge.className = 'status-badge status-' + data.new_status;
                    }
                }
            });
        });
    });
    
    // Export data functionality
    const exportButtons = document.querySelectorAll('.btn-export');
    exportButtons.forEach(button => {
        button.addEventListener('click', function() {
            const type = this.dataset.type;
            const url = `export.php?type=${type}`;
            window.open(url, '_blank');
        });
    });
    
    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `admin-notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 
                                 type === 'error' ? 'exclamation-circle' : 
                                 type === 'warning' ? 'exclamation-triangle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">
                <i class="fas fa-times"></i>
            </button>
        `;
        
        document.body.appendChild(notification);
        
        // Show animation
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
    
    // Initialize charts if Chart.js is loaded
    if (typeof Chart !== 'undefined') {
        initializeCharts();
    }
    
    // Responsive table
    window.addEventListener('resize', function() {
        adjustTableResponsive();
    });
    
    function adjustTableResponsive() {
        const tables = document.querySelectorAll('.data-table');
        tables.forEach(table => {
            if (window.innerWidth <= 768) {
                table.classList.add('responsive');
            } else {
                table.classList.remove('responsive');
            }
        });
    }
    
    // Initialize
    adjustTableResponsive();
});

function initializeCharts() {
    // Sales chart
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Penjualan',
                    data: [12000000, 19000000, 15000000, 25000000, 22000000, 30000000],
                    borderColor: 'var(--primary)',
                    backgroundColor: 'rgba(109, 157, 197, 0.1)',
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    }
    
    // Category chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: ['Makanan Kucing', 'Makanan Anjing', 'Aksesoris', 'Mainan', 'Perawatan'],
                datasets: [{
                    data: [30, 25, 20, 15, 10],
                    backgroundColor: [
                        'var(--primary)',
                        'var(--secondary)',
                        'var(--accent)',
                        'var(--success)',
                        'var(--warning)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    }
                }
            }
        });
    }
}

// CSS for notifications
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
.admin-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    padding: 15px 20px;
    border-radius: var(--radius);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-width: 300px;
    max-width: 400px;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    z-index: 10000;
}

.admin-notification.show {
    transform: translateX(0);
}

.admin-notification .notification-content {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.notification-success {
    border-left: 4px solid var(--success);
}

.notification-error {
    border-left: 4px solid var(--danger);
}

.notification-warning {
    border-left: 4px solid var(--warning);
}

.notification-info {
    border-left: 4px solid var(--primary);
}

.admin-notification i {
    font-size: 1.2rem;
}

.notification-success i {
    color: var(--success);
}

.notification-error i {
    color: var(--danger);
}

.notification-warning i {
    color: var(--warning);
}

.notification-info i {
    color: var(--primary);
}

.notification-close {
    background: none;
    border: none;
    color: var(--text);
    cursor: pointer;
    padding: 0;
    margin-left: 10px;
}

@media (max-width: 768px) {
    .admin-notification {
        min-width: auto;
        max-width: calc(100vw - 40px);
    }
}
`;
document.head.appendChild(notificationStyles);