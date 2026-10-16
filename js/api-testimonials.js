// Testimonials API Integration

// Load testimonials for homepage
async function loadTestimonials() {
    console.log('=== TESTIMONIALS LOADING START ===');
    const container = document.getElementById('testimonialsContainer');
    if (!container) {
        console.error('❌ Testimonials container not found!');
        return;
    }
    console.log('✓ Container found:', container);

    try {
        const url = API_BASE_URL + '/testimonials/list.php';
        console.log('Fetching from:', url);
        
        const response = await API.get('/testimonials/list.php');
        console.log('Response received:', response);

        if (response.success && response.data && response.data.length > 0) {
            console.log('✓ Success! Displaying', response.data.length, 'testimonials');
            console.log('Testimonials data:', response.data);
            displayTestimonials(response.data);
        } else {
            console.warn('⚠ No testimonials found or error. Keeping static testimonials.');
            console.log('Response details:', response);
        }
    } catch (error) {
        console.error('❌ Error loading testimonials:', error);
    }
    console.log('=== TESTIMONIALS LOADING END ===');
}

// Display testimonials
function displayTestimonials(testimonials) {
    const container = document.getElementById('testimonialsContainer');
    if (!container) return;

    container.innerHTML = testimonials.map((testimonial, index) => `
        <div class="testimonial-card ${index === 0 ? 'active' : ''}">
            <div class="quote-icon">
                <i class="fas fa-quote-left"></i>
            </div>
            <div class="rating">
                ${generateStars(testimonial.rating)}
            </div>
            <p class="testimonial-text">"${testimonial.testimonial_text}"</p>
            <div class="testimonial-author">
                ${testimonial.photo_url ? 
                    `<img src="../backend/${testimonial.photo_url}" alt="${testimonial.parent_name}">` :
                    `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(testimonial.parent_name)}&background=1e4d9f&color=fff&size=100" alt="${testimonial.parent_name}">`
                }
                <div class="author-info">
                    <h4>${testimonial.parent_name}</h4>
                    <p>${testimonial.relationship}</p>
                </div>
            </div>
        </div>
    `).join('');
    
    console.log('Testimonials displayed successfully!');
    
    // Reinitialize carousel after loading testimonials
    setTimeout(() => {
        initTestimonialsCarousel();
    }, 100);
}

// Initialize testimonials carousel
function initTestimonialsCarousel() {
    const testimonialsCarousel = document.querySelector('.testimonials-carousel');
    if (!testimonialsCarousel) return;
    
    const testimonialCards = testimonialsCarousel.querySelectorAll('.testimonial-card');
    const prevBtn = document.getElementById('prevTestimonial');
    const nextBtn = document.getElementById('nextTestimonial');
    
    if (testimonialCards.length === 0) return;
    
    let currentTestimonialIndex = 0;
    
    function showTestimonial(index) {
        testimonialCards.forEach((card, i) => {
            card.classList.remove('active');
            if (i === index) {
                card.classList.add('active');
            }
        });
        
        const cardWidth = testimonialCards[0].offsetWidth;
        const gap = 30;
        const scrollPosition = (cardWidth + gap) * index;
        
        testimonialsCarousel.scrollTo({
            left: scrollPosition,
            behavior: 'smooth'
        });
    }
    
    function autoScrollTestimonials() {
        currentTestimonialIndex = (currentTestimonialIndex + 1) % testimonialCards.length;
        showTestimonial(currentTestimonialIndex);
    }
    
    let testimonialsInterval = setInterval(autoScrollTestimonials, 5000);
    
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentTestimonialIndex = (currentTestimonialIndex - 1 + testimonialCards.length) % testimonialCards.length;
            showTestimonial(currentTestimonialIndex);
            clearInterval(testimonialsInterval);
            testimonialsInterval = setInterval(autoScrollTestimonials, 5000);
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentTestimonialIndex = (currentTestimonialIndex + 1) % testimonialCards.length;
            showTestimonial(currentTestimonialIndex);
            clearInterval(testimonialsInterval);
            testimonialsInterval = setInterval(autoScrollTestimonials, 5000);
        });
    }
    
    testimonialsCarousel.addEventListener('mouseenter', () => {
        clearInterval(testimonialsInterval);
    });
    
    testimonialsCarousel.addEventListener('mouseleave', () => {
        testimonialsInterval = setInterval(autoScrollTestimonials, 5000);
    });
    
    console.log('Testimonials carousel initialized with', testimonialCards.length, 'cards');
}

// Generate star rating
function generateStars(rating) {
    let stars = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            stars += '<i class="fas fa-star"></i>';
        } else {
            stars += '<i class="far fa-star"></i>';
        }
    }
    return stars;
}

// Initialize testimonials on homepage
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('testimonialsSlider') || document.querySelector('.testimonials-slider') || document.getElementById('testimonialsList')) {
        loadTestimonials();
    }
});
