/**
 * Testimonials Section - Clean & Classic JS
 * St. Lawrence Junior School, Kabowa
 * 100% Strictly Dynamic Database-Driven Implementation
 */

(function () {
    let testimonialsData = [];
    let currentIndex = 0;
    let autoRotateTimer = null;

    const roleMap = {
        'old_student': 'Alumni',
        'parent': 'Parent',
        'student': 'Student'
    };

    async function fetchTestimonials() {
        const container = document.getElementById('testCardContainer');
        const dotsWrap = document.getElementById('testDotsWrap');
        if (!container) return;

        try {
            const res = await fetch('../backend/api/public/get_testimonials.php');
            if (!res.ok) throw new Error('Network response was not ok');
            const json = await res.json();

            if (json.success && Array.isArray(json.data) && json.data.length > 0) {
                testimonialsData = json.data.map(item => {
                    let photo = '../img/testimonials/testimonial_1768153115_6963e01b367a3.jpg'; // default if missing
                    if (item.photo_url) {
                        photo = item.photo_url.startsWith('http') ? item.photo_url : (item.photo_url.startsWith('../') ? item.photo_url : '../' + item.photo_url);
                    }

                    // Strip any accidental leading/trailing quotes from DB text for clean display
                    let text = item.testimonial_text || '';
                    text = text.replace(/^["'\s]+|["'\s]+$/g, '');

                    return {
                        id: item.id,
                        name: item.parent_name || 'Community Member',
                        role: roleMap[item.parent_role] || item.parent_role || 'Community Member',
                        text: text,
                        photo: photo
                    };
                });

                renderDots();
                renderSlide(0);
                startAutoRotate();
            } else {
                renderEmptyState();
            }
        } catch (err) {
            console.warn('Testimonials target: Error loading testimonials:', err);
            renderEmptyState();
        }
    }

    function renderSlide(index) {
        if (!testimonialsData || testimonialsData.length === 0) return;

        if (index < 0) {
            index = testimonialsData.length - 1;
        } else if (index >= testimonialsData.length) {
            index = 0;
        }

        currentIndex = index;
        const currentItem = testimonialsData[currentIndex];

        const card = document.querySelector('.test-editorial-card');
        if (card) {
            card.style.opacity = '0';
            card.style.transform = 'translateY(4px)';

            setTimeout(() => {
                const photoEl = document.getElementById('testPhoto');
                const quoteEl = document.getElementById('testQuoteText');
                const nameEl = document.getElementById('testAuthorName');
                const roleEl = document.getElementById('testAuthorRole');

                if (photoEl) {
                    photoEl.src = currentItem.photo;
                    photoEl.alt = currentItem.name;
                }
                if (quoteEl) {
                    quoteEl.textContent = `"${currentItem.text}"`;
                }
                if (nameEl) {
                    nameEl.textContent = currentItem.name;
                }
                if (roleEl) {
                    roleEl.textContent = currentItem.role;
                }

                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 180);
        }

        updateDots();
    }

    function renderDots() {
        const dotsWrap = document.getElementById('testDotsWrap');
        if (!dotsWrap) return;

        dotsWrap.innerHTML = testimonialsData.map((_, i) => `
            <button class="test-dot ${i === 0 ? 'active' : ''}" data-index="${i}" aria-label="Go to testimonial ${i + 1}"></button>
        `).join('');

        const dots = dotsWrap.querySelectorAll('.test-dot');
        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const idx = parseInt(dot.getAttribute('data-index'), 10);
                renderSlide(idx);
                resetAutoRotate();
            });
        });
    }

    function updateDots() {
        const dots = document.querySelectorAll('.test-dot');
        dots.forEach((dot, i) => {
            if (i === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }

    function renderEmptyState() {
        const container = document.getElementById('testCardContainer');
        if (!container) return;

        container.innerHTML = `
            <div class="test-editorial-card" style="justify-content: center; text-align: center; padding: 60px 24px;">
                <div style="max-width: 480px;">
                    <div style="font-size: 40px; color: #93c5fd; margin-bottom: 12px; font-family: Georgia, serif;">“</div>
                    <h3 style="font-family: 'Playfair Display', serif; font-size: 24px; color: #0b1a38; margin-bottom: 8px;">Community Voices</h3>
                    <p style="color: #64748b; font-size: 15px; margin: 0;">Stories and reflections from our parents, students and alumni will be displayed here soon.</p>
                </div>
            </div>
        `;
    }

    function startAutoRotate() {
        stopAutoRotate();
        if (testimonialsData.length > 1) {
            autoRotateTimer = setInterval(() => {
                renderSlide(currentIndex + 1);
            }, 6500);
        }
    }

    function stopAutoRotate() {
        if (autoRotateTimer) {
            clearInterval(autoRotateTimer);
            autoRotateTimer = null;
        }
    }

    function resetAutoRotate() {
        stopAutoRotate();
        startAutoRotate();
    }

    function initListeners() {
        const prevBtn = document.getElementById('testPrevBtn');
        const nextBtn = document.getElementById('testNextBtn');
        const wrapper = document.querySelector('.test-carousel-wrapper');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                renderSlide(currentIndex - 1);
                resetAutoRotate();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                renderSlide(currentIndex + 1);
                resetAutoRotate();
            });
        }

        if (wrapper) {
            wrapper.addEventListener('mouseenter', stopAutoRotate);
            wrapper.addEventListener('mouseleave', startAutoRotate);
        }

        // Swipe support on mobile
        let touchStartX = 0;
        let touchEndX = 0;

        if (wrapper) {
            wrapper.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            wrapper.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                const diff = touchEndX - touchStartX;
                if (Math.abs(diff) > 40) {
                    if (diff < 0) {
                        renderSlide(currentIndex + 1);
                    } else {
                        renderSlide(currentIndex - 1);
                    }
                    resetAutoRotate();
                }
            }, { passive: true });
        }
    }

    // Expose reload function
    window.refreshTargetTestimonials = fetchTestimonials;

    document.addEventListener('DOMContentLoaded', () => {
        initListeners();
        fetchTestimonials();
    });
})();
