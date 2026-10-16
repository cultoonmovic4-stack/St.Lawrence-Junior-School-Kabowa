// Library API Integration

// Load library resources
async function loadLibraryResources(classLevel = '', subject = '') {
    const container = document.getElementById('libraryContainer');
    if (!container) return;

    showLoading('libraryContainer');

    let endpoint = '/library/list.php?';
    if (classLevel) endpoint += `class_level=${encodeURIComponent(classLevel)}&`;
    if (subject) endpoint += `subject=${encodeURIComponent(subject)}&`;

    const response = await API.get(endpoint);

    if (response.success && response.data && response.data.length > 0) {
        displayLibraryResources(response.data);
    } else if (response.success && response.data.length === 0) {
        showNoData('libraryContainer', 'No resources found for this selection');
    } else {
        showError('libraryContainer', 'Failed to load library resources. Please try again.');
    }
}

// Display library resources
function displayLibraryResources(resources) {
    const container = document.getElementById('libraryContainer');
    if (!container) return;

    container.innerHTML = resources.map(resource => {
        // Detect class for filtering
        let classMatch = resource.class_level ? resource.class_level.match(/([1-7])/) : null;
        let className = classMatch ? `p${classMatch[1]}` : 'all';
        
        // Get file icon based on file type
        const fileIcon = getFileIconClass(resource.file_type);
        
        // Format class level display
        const classDisplay = resource.class_level === 'all' ? 'All Classes' : `P.${resource.class_level.replace('p', '')}`;
        
        // Get category badge color
        const categoryColor = getCategoryBadgeColor(resource.category);
        
        return `
        <div class="library-pdf-card ${className}" data-aos="fade-up">
            <div class="pdf-card-header" style="background: ${categoryColor};">
                <div class="pdf-card-icon">
                    <i class="fas fa-${fileIcon}"></i>
                </div>
                <span class="pdf-card-badge">${resource.category.replace('_', ' ')}</span>
            </div>
            <div class="pdf-card-body">
                <h3 class="pdf-card-title">${resource.title}</h3>
                ${resource.description ? `<p class="pdf-card-description">${resource.description}</p>` : ''}
                <div class="pdf-card-meta">
                    <span><i class="fas fa-graduation-cap"></i> ${classDisplay}</span>
                    ${resource.subject ? `<span><i class="fas fa-book"></i> ${resource.subject}</span>` : ''}
                </div>
                <div class="pdf-card-stats">
                    <span><i class="fas fa-hdd"></i> ${formatFileSize(resource.file_size)}</span>
                    <span><i class="fas fa-download"></i> ${resource.download_count || 0}</span>
                    <span><i class="fas fa-calendar"></i> ${formatDate(resource.upload_date)}</span>
                </div>
            </div>
            <a href="../backend/api/library/download.php?id=${resource.id}" 
               class="btn-download-pdf">
                <i class="fas fa-download"></i> Download ${resource.file_type.toUpperCase()}
            </a>
        </div>
    `;
    }).join('');
    
    // Refresh AOS animations
    if (window.AOS) window.AOS.refresh();
}

// Get file icon class
function getFileIconClass(fileType) {
    const icons = {
        'pdf': 'file-pdf',
        'doc': 'file-word',
        'docx': 'file-word',
        'xls': 'file-excel',
        'xlsx': 'file-excel',
        'ppt': 'file-powerpoint',
        'pptx': 'file-powerpoint',
        'jpg': 'file-image',
        'jpeg': 'file-image',
        'png': 'file-image',
        'mp4': 'file-video'
    };
    return icons[fileType.toLowerCase()] || 'file';
}

// Get category badge color
function getCategoryBadgeColor(category) {
    const colors = {
        'assignment': '#0066cc',
        'reading': '#10b981',
        'past_exam': '#dc3545',
        'revision': '#f59e0b',
        'multimedia': '#8b5cf6',
        'study_guide': '#06b6d4'
    };
    return colors[category] || '#6c757d';
}

// Track PDF download
async function trackDownload(resourceId) {
    await API.get(`/library/download.php?id=${resourceId}`);
}

// Filter library by class
function filterLibraryByClass(classLevel) {
    document.querySelectorAll('.class-filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    const subject = document.getElementById('subjectFilter')?.value || '';
    loadLibraryResources(classLevel, subject);
}

// Filter library by subject
function filterLibraryBySubject() {
    const subject = document.getElementById('subjectFilter').value;
    const activeClass = document.querySelector('.class-filter-btn.active');
    const classLevel = activeClass ? activeClass.dataset.class : '';
    
    loadLibraryResources(classLevel, subject);
}

// Search library
function searchLibrary() {
    const searchInput = document.getElementById('librarySearch');
    if (!searchInput) return;

    const searchTerm = searchInput.value.toLowerCase();
    const pdfCards = document.querySelectorAll('.pdf-card');

    pdfCards.forEach(card => {
        const title = card.querySelector('.pdf-title').textContent.toLowerCase();
        const description = card.querySelector('.pdf-description')?.textContent.toLowerCase() || '';

        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Helper: Format file size
function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
}

// Helper: Format date
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// Initialize library page
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('libraryGrid') || /library/i.test(window.location.pathname)) {
        loadLibraryResources();
    }
});
