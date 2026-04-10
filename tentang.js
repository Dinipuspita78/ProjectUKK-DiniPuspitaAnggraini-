// File: assets/js/about.js
document.addEventListener('DOMContentLoaded', function() {
    // FAQ Accordion
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', function() {
            // Close other items
            faqItems.forEach(otherItem => {
                if (otherItem !== item) {
                    otherItem.classList.remove('active');
                }
            });
            
            // Toggle current item
            item.classList.toggle('active');
        });
    });
    
    // Gallery Lightbox
    const galleryItems = document.querySelectorAll('.gallery-item');
    const lightbox = document.createElement('div');
    lightbox.className = 'lightbox';
    lightbox.innerHTML = `
        <div class="lightbox-content">
            <span class="lightbox-close">&times;</span>
            <img src="" alt="">
            <div class="lightbox-caption"></div>
            <button class="lightbox-prev"><i class="fas fa-chevron-left"></i></button>
            <button class="lightbox-next"><i class="fas fa-chevron-right"></i></button>
        </div>
    `;
    document.body.appendChild(lightbox);
    
    let currentImageIndex = 0;
    const images = [];
    
    galleryItems.forEach((item, index) => {
        const img = item.querySelector('img');
        images.push({
            src: img.src,
            alt: img.alt
        });
        
        item.addEventListener('click', function() {
            currentImageIndex = index;
            showLightbox();
        });
    });
    
    function showLightbox() {
        const lightboxImg = lightbox.querySelector('img');
        const caption = lightbox.querySelector('.lightbox-caption');
        
        lightboxImg.src = images[currentImageIndex].src;
        caption.textContent = images[currentImageIndex].alt;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    // Lightbox controls
    lightbox.querySelector('.lightbox-close').addEventListener('click', function() {
        lightbox.classList.remove('active');
        document.body.style.overflow = 'auto';
    });
    
    lightbox.querySelector('.lightbox-prev').addEventListener('click', function() {
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        showLightbox();
    });
    
    lightbox.querySelector('.lightbox-next').addEventListener('click', function() {
        currentImageIndex = (currentImageIndex + 1) % images.length;
        showLightbox();
    });
    
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            lightbox.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Keyboard navigation for lightbox
    document.addEventListener('keydown', function(e) {
        if (lightbox.classList.contains('active')) {
            if (e.key === 'Escape') {
                lightbox.classList.remove('active');
                document.body.style.overflow = 'auto';
            } else if (e.key === 'ArrowLeft') {
                currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
                showLightbox();
            } else if (e.key === 'ArrowRight') {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                showLightbox();
            }
        }
    });
    
    // Team member modal
    const teamCards = document.querySelectorAll('.team-card');
    const teamModal = document.createElement('div');
    teamModal.className = 'team-modal';
    document.body.appendChild(teamModal);
    
    teamCards.forEach(card => {
        card.addEventListener('click', function() {
            const name = this.querySelector('h3').textContent;
            const role = this.querySelector('.team-role').textContent;
            const desc = this.querySelector('.team-desc').textContent;
            const imgSrc = this.querySelector('img').src;
            
            teamModal.innerHTML = `
                <div class="team-modal-content">
                    <span class="team-modal-close">&times;</span>
                    <div class="team-modal-image">
                        <img src="${imgSrc}" alt="${name}">
                    </div>
                    <div class="team-modal-info">
                        <h2>${name}</h2>
                        <p class="team-modal-role">${role}</p>
                        <p class="team-modal-desc">${desc}</p>
                        <div class="team-modal-social">
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                        <div class="team-modal-bio">
                            <h3>Biografi</h3>
                            <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                        </div>
                    </div>
                </div>
            `;
            
            teamModal.classList.add('active');
            document.body.style.overflow = 'hidden';
            
            // Close modal
            teamModal.querySelector('.team-modal-close').addEventListener('click', function() {
                teamModal.classList.remove('active');
                document.body.style.overflow = 'auto';
            });
            
            teamModal.addEventListener('click', function(e) {
                if (e.target === teamModal) {
                    teamModal.classList.remove('active');
                    document.body.style.overflow = 'auto';
                }
            });
        });
    });
    
    // Add hover effect to location cards
    const locationCards = document.querySelectorAll('.location-card');
    locationCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px) scale(1.02)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Add animation to value cards on scroll
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate');
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.value-card').forEach(card => {
        observer.observe(card);
    });
});

// Styles for lightbox and modals
const modalStyles = document.createElement('style');
modalStyles.textContent = `
.lightbox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.lightbox.active {
    display: flex;
}

.lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
}

.lightbox-content img {
    max-width: 100%;
    max-height: 80vh;
    border-radius: 10px;
}

.lightbox-close {
    position: absolute;
    top: -40px;
    right: 0;
    color: white;
    font-size: 2rem;
    cursor: pointer;
}

.lightbox-caption {
    color: white;
    text-align: center;
    margin-top: 15px;
    font-size: 1.1rem;
}

.lightbox-prev, .lightbox-next {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 50%;
    font-size: 1.2rem;
    cursor: pointer;
    transition: background 0.3s ease;
}

.lightbox-prev:hover, .lightbox-next:hover {
    background: rgba(255, 255, 255, 0.3);
}

.lightbox-prev {
    left: -70px;
}

.lightbox-next {
    right: -70px;
}

.team-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
}

.team-modal.active {
    display: flex;
}

.team-modal-content {
    background: white;
    border-radius: var(--radius);
    max-width: 800px;
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

.team-modal-close {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 1.8rem;
    cursor: pointer;
    color: var(--text);
    z-index: 1;
}

.team-modal-image {
    height: 300px;
    overflow: hidden;
}

.team-modal-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-modal-info {
    padding: 30px;
}

.team-modal-info h2 {
    color: var(--dark);
    margin-bottom: 10px;
    font-size: 2rem;
}

.team-modal-role {
    color: var(--primary);
    font-weight: 600;
    margin-bottom: 20px;
    font-size: 1.1rem;
}

.team-modal-desc {
    color: var(--text);
    line-height: 1.6;
    margin-bottom: 25px;
    font-size: 1.1rem;
}

.team-modal-social {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}

.team-modal-social a {
    width: 40px;
    height: 40px;
    background: var(--light);
    color: var(--primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: all 0.3s ease;
}

.team-modal-social a:hover {
    background: var(--primary);
    color: white;
    transform: translateY(-3px);
}

.team-modal-bio h3 {
    color: var(--dark);
    margin-bottom: 15px;
    font-size: 1.3rem;
}

.team-modal-bio p {
    color: var(--text);
    line-height: 1.6;
}

/* Animation for value cards */
.value-card.animate {
    animation: cardPop 0.6s ease forwards;
}

@keyframes cardPop {
    0% {
        opacity: 0;
        transform: scale(0.8) translateY(30px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@media (max-width: 768px) {
    .lightbox-prev {
        left: 10px;
    }
    
    .lightbox-next {
        right: 10px;
    }
    
    .team-modal-content {
        max-height: 95vh;
    }
    
    .team-modal-image {
        height: 200px;
    }
}
`;
document.head.appendChild(modalStyles);