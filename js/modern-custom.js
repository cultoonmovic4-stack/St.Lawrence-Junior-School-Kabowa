/**
 * Modern Custom JS for St. Lawrence Junior School
 */

document.addEventListener("DOMContentLoaded", function () {
    // --- Carousel Logic ---
    (function () {
        const carouselContainer = document.querySelector('.carousel-container');
        if (!carouselContainer) return;

        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.dot');
        const prevButton = document.querySelector('.prev');
        const nextButton = document.querySelector('.next');
        const liveRegion = document.getElementById('carousel-live');
        const pausePlayBtn = document.querySelector('.carousel-pause-play');

        let currentIndex = 0;
        let progressInterval;
        let isAnimating = false;
        let isPaused = false;

        const slideTitles = [
            'Welcome to St. Lawrence Junior School',
            'Excellence in Education',
            'Shaping Future Leaders',
            'Discover Your Potential'
        ];

        function updateCarousel(announce = true) {
            if (isAnimating) return;
            isAnimating = true;

            slides.forEach((slide, index) => {
                slide.classList.toggle('active', index === currentIndex);
                slide.setAttribute('tabindex', index === currentIndex ? '0' : '-1');

                // Animate captions
                const caption = slide.querySelector('.caption-content');
                if (caption) {
                    if (index === currentIndex) {
                        caption.classList.add('animated-caption');
                    } else {
                        caption.classList.remove('animated-caption');
                    }
                }

                // Video logic
                const video = slide.querySelector('video');
                if (video) {
                    if (index === currentIndex) {
                        video.currentTime = 0;
                        video.play().catch(e => console.log("Autoplay prevented:", e));
                    } else {
                        video.pause();
                    }
                }
            });

            dots.forEach((dot, index) => {
                dot.classList.toggle('active', index === currentIndex);
                dot.setAttribute('aria-selected', index === currentIndex ? 'true' : 'false');
                dot.setAttribute('tabindex', '0');
            });

            // Announce for screen readers
            if (announce && liveRegion) {
                liveRegion.textContent = slideTitles[currentIndex || 0] + ` (${currentIndex + 1} of ${slides.length})`;
            }

            // Animate track
            const offset = -currentIndex * 100;
            const track = document.querySelector('.carousel-track');
            if (track) track.style.transform = `translateX(${offset}vw)`;

            setTimeout(() => { isAnimating = false; }, 800);
        }

        function moveToSlide(index) {
            if (index >= slides.length) index = 0;
            if (index < 0) index = slides.length - 1;
            currentIndex = index;
            updateCarousel();
        }

        if (prevButton) prevButton.addEventListener('click', () => { if (!isAnimating) moveToSlide(currentIndex - 1); });
        if (nextButton) nextButton.addEventListener('click', () => { if (!isAnimating) moveToSlide(currentIndex + 1); });

        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => { if (!isAnimating) moveToSlide(index); });
            dot.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); moveToSlide(index); }
            });
        });

        // Keyboard navigation
        carouselContainer.addEventListener('keydown', e => {
            if (isAnimating) return;
            if (e.key === 'ArrowLeft') { moveToSlide(currentIndex - 1); }
            if (e.key === 'ArrowRight') { moveToSlide(currentIndex + 1); }
        });

        // Pause/Play logic
        function updatePauseBtn() {
            if (!pausePlayBtn) return;
            const icon = pausePlayBtn.querySelector('i');
            if (isPaused) {
                icon.className = 'fas fa-play';
                pausePlayBtn.setAttribute('aria-label', 'Play carousel');
            } else {
                icon.className = 'fas fa-pause';
                pausePlayBtn.setAttribute('aria-label', 'Pause carousel');
            }
        }

        if (pausePlayBtn) {
            pausePlayBtn.addEventListener('click', () => {
                isPaused = !isPaused;
                updatePauseBtn();
            });
        }

        // Auto-advance if not paused
        setInterval(() => {
            if (!isPaused && !isAnimating) {
                moveToSlide(currentIndex + 1);
            }
        }, 6000);

        // Initial state
        updateCarousel();
        updatePauseBtn();
    })();

    // --- Volume Toggle Logic ---
    const volumeToggle = document.getElementById('volumeToggle');
    if (volumeToggle) {
        volumeToggle.addEventListener('click', function () {
            const video = document.getElementById('bgVideo');
            if (video) {
                video.muted = !video.muted;
                const icon = this.querySelector('i');
                icon.className = video.muted
                    ? 'fas fa-volume-mute fa-2x'
                    : 'fas fa-volume-up fa-2x';
            }
        });
    }

    // --- Counter Animation ---
    const counters = document.querySelectorAll(".counter");
    const observerOptions = {
        threshold: 0.5
    };

    const observer = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const counter = entry.target;
                const target = +counter.getAttribute("data-target");
                const speed = 2000; // Total animation time

                const updateCounter = () => {
                    const count = +counter.innerText;
                    const increment = target / (speed / 16);

                    if (count < target) {
                        counter.innerText = Math.ceil(count + increment);
                        setTimeout(updateCounter, 16);
                    } else {
                        counter.innerText = target;
                    }
                };

                updateCounter();
                observer.unobserve(counter);
            }
        });
    }, observerOptions);

    counters.forEach(counter => {
        observer.observe(counter);
    });
});

    // --- Premium Alumni Carousel ---
    if (typeof $ !== 'undefined' && $.fn.owlCarousel) {
        $('.premium-alumni-carousel').owlCarousel({
            items: 1,
            loop: true,
            autoplay: true,
            autoplayTimeout: 6000,
            smartSpeed: 1000,
            nav: true,
            dots: true,
            navText: ["<i class='fa fa-chevron-left'></i>", "<i class='fa fa-chevron-right'></i>"],
            responsive: {
                0: { items: 1, nav: false },
                768: { items: 1, nav: true }
            }
        });
    }

    // --- AOS Init ---
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800,
            easing: 'slide',
            once: true
        });
    }

    // --- Back to Top Button ---
    const backToTopBtn = document.getElementById('backToTopBtn');
    if (backToTopBtn) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 0) {
                backToTopBtn.classList.remove('d-none');
            } else {
                backToTopBtn.classList.add('d-none');
            }
        });

        backToTopBtn.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
