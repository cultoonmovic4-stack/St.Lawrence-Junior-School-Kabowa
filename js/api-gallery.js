// Gallery API Integration

// Load gallery images
async function loadGallery() {
    console.log('=== GALLERY LOADING START ===');
    const container = document.getElementById('galleryContainer');
    if (!container) {
        console.error('❌ Gallery container not found!');
        return;
    }
    console.log('✓ Container found:', container);

    try {
        const url = API_BASE_URL + '/gallery/list.php';
        console.log('Fetching from:', url);
        
        const response = await API.get('/gallery/list.php');
        console.log('Response received:', response);
        console.log('Number of images:', response.data ? response.data.length : 0);
        
        if (response.data) {
            console.log('First image data:', response.data[0]);
        }

        if (response.success && response.data && response.data.length > 0) {
            console.log('✓ Success! Displaying', response.data.length, 'images');
            displayGallery(response.data);
        } else {
            console.warn('⚠ No images found in database');
            container.innerHTML = `
                <div class="gallery-loader">
                    <i class="fas fa-images" style="font-size: 4rem; margin-bottom: 20px; opacity: 0.3; color: #999;"></i>
                    <h3 style="color: #666; margin-bottom: 10px;">No Gallery Images Yet</h3>
                    <p>Images uploaded through the admin dashboard will appear here.</p>
                </div>
            `;
        }
    } catch (error) {
        console.error('❌ Error loading gallery:', error);
        container.innerHTML = `
            <div class="gallery-loader">
                <i class="fas fa-exclamation-circle" style="font-size: 4rem; margin-bottom: 20px; color: #dc3545;"></i>
                <h3 style="color: #666; margin-bottom: 10px;">Error Loading Gallery</h3>
                <p style="color: #dc3545;">${error.message}</p>
            </div>
        `;
    }
    console.log('=== GALLERY LOADING END ===');
}

// Display gallery images
function displayGallery(images) {
    console.log('=== DISPLAY GALLERY START ===');
    console.log('Images to display:', images);
    
    const container = document.getElementById('galleryContainer');
    if (!container) {
        console.error('❌ Container not found!');
        return;
    }

    // Store images globally for lightbox
    window.galleryImages = images;

    // Build HTML for masonry layout
    let html = '';
    
    images.forEach((img, index) => {
        const imagePath = `../backend/${img.image_url}`;
        const category = (img.category || 'general').toLowerCase();
        console.log(`Image ${index}: ${imagePath}, Category: ${category}`);
        
        html += `
            <div class="gallery-item" data-category="${category}" data-index="${index}" onclick="openLightbox(${index})">
                <img src="${imagePath}" alt="${img.title || 'Gallery Image'}" loading="lazy">
                <div class="gallery-overlay">
                    <div class="gallery-info">
                        <h3>${img.title || 'Gallery Image'}</h3>
                        ${img.description ? `<p>${img.description}</p>` : ''}
                    </div>
                    <div class="gallery-zoom-icon">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
            </div>
        `;
    });

    console.log('Generated HTML length:', html.length);
    container.innerHTML = html;
    console.log('✓ Gallery HTML inserted into container');
    
    // Update filter counts
    updateFilterCounts(images);
    
    // Initialize filter functionality
    initializeFilters();
    
    console.log('=== DISPLAY GALLERY END ===');
    
    // Reinitialize AOS for new elements
    if (typeof AOS !== 'undefined') {
        AOS.refresh();
    }
}

// Update filter counts
function updateFilterCounts(images) {
    const counts = {
        all: images.length,
        academics: 0,
        sports: 0,
        events: 0,
        facilities: 0
    };
    
    images.forEach(img => {
        const category = (img.category || 'general').toLowerCase();
        if (counts.hasOwnProperty(category)) {
            counts[category]++;
        }
    });
    
    // Only update if elements exist
    const elAll = document.getElementById('countAll');
    const elAcademics = document.getElementById('countAcademics');
    const elSports = document.getElementById('countSports');
    const elEvents = document.getElementById('countEvents');
    const elFacilities = document.getElementById('countFacilities');

    if (elAll) elAll.textContent = counts.all;
    if (elAcademics) elAcademics.textContent = counts.academics;
    if (elSports) elSports.textContent = counts.sports;
    if (elEvents) elEvents.textContent = counts.events;
    if (elFacilities) elFacilities.textContent = counts.facilities;
}

// Initialize filter functionality
function initializeFilters() {
    const filterBtns = document.querySelectorAll('.pill-btn');
    
    filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const filter = btn.getAttribute('data-filter');
            
            // Update active button
            filterBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            // Filter items
            const galleryItems = document.querySelectorAll('.gallery-item');
            galleryItems.forEach((item, index) => {
                const category = item.getAttribute('data-category');
                const shouldShow = filter === 'all' || category === filter;
                
                if (shouldShow) {
                    item.style.display = 'block';
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateY(0)';
                    }, index * 30);
                } else {
                    item.style.opacity = '0';
                    item.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        item.style.display = 'none';
                    }, 300);
                }
            });
        });
    });
}

// Initialize gallery page
if (window.location.pathname.includes('Gallery-redesign.html')) {
    document.addEventListener('DOMContentLoaded', () => {
        loadGallery();
    });
}

