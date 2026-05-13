// Teachers API Integration

// Load all teachers
async function loadTeachers(department = '') {
    const container = document.getElementById('teachersContainer');
    if (!container) return;

    showLoading('teachersContainer');

    let endpoint = '/teachers/list.php';
    if (department) {
        endpoint += `?department=${encodeURIComponent(department)}`;
    }

    const response = await API.get(endpoint);

    if (response.success && response.data && response.data.length > 0) {
        displayTeachers(response.data);
    } else if (response.success && response.data.length === 0) {
        showNoData('teachersContainer', 'No teachers found in this department');
    } else {
        showError('teachersContainer', 'Failed to load teachers. Please try again.');
    }
}

// Display teachers with completely new minimal card design
function displayTeachers(teachers) {
    const container = document.getElementById('teachersContainer');
    if (!container) return;

    container.innerHTML = teachers.map((teacher, index) => {
        // Handle photo URL properly
        let photoUrl = '../img/user.jpg';
        if (teacher.photo_url && teacher.photo_url !== 'null' && teacher.photo_url.trim() !== '') {
            photoUrl = `../backend/${teacher.photo_url}`;
        }
        
        console.log('Teacher:', teacher.name, 'Photo URL:', photoUrl);
        
        // Store teacher data in a data attribute (JSON encoded)
        const teacherDataJson = JSON.stringify(teacher).replace(/"/g, '&quot;');
        
        return `
        <div class="teacher-card" 
             data-department="${teacher.department ? teacher.department.toLowerCase() : 'general'}" 
             data-teacher='${teacherDataJson}'
             onclick="handleTeacherCardClick(this)"
             data-aos="fade-up"
             data-aos-delay="${index * 50}">
            
            <div class="teacher-card-image">
                <div class="teacher-photo-circle">
                    <img src="${photoUrl}" 
                         alt="${teacher.name}" 
                         onerror="this.src='../img/user.jpg'">
                </div>
            </div>
            
            <div class="teacher-card-content">
                <h3 class="teacher-card-name">${teacher.name}</h3>
                <p class="teacher-card-position">${teacher.position || teacher.department || 'Teacher'}</p>
                
                <button class="teacher-view-btn" onclick="event.stopPropagation(); handleTeacherCardClick(this.closest('.teacher-card'))">
                    <span>View Profile</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
        `;
    }).join('');
}

// Filter teachers by department
function filterTeachers(department) {
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Load teachers
    loadTeachers(department);
}

// Search teachers
function searchTeachers() {
    const searchInput = document.getElementById('teacherSearch');
    if (!searchInput) return;

    const searchTerm = searchInput.value.toLowerCase();
    const teacherCards = document.querySelectorAll('.teacher-card');

    teacherCards.forEach(card => {
        const name = card.querySelector('.teacher-name').textContent.toLowerCase();
        const department = card.querySelector('.teacher-department').textContent.toLowerCase();
        const position = card.querySelector('.teacher-position').textContent.toLowerCase();

        if (name.includes(searchTerm) || department.includes(searchTerm) || position.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Handle teacher card click
function handleTeacherCardClick(cardElement) {
    try {
        const teacherDataJson = cardElement.getAttribute('data-teacher');
        const teacherData = JSON.parse(teacherDataJson.replace(/&quot;/g, '"'));
        
        // Call the modal function (defined in the main page)
        if (typeof window.openTeacherModal === 'function') {
            window.openTeacherModal(teacherData);
        }
    } catch (error) {
        console.error('Error opening teacher profile:', error);
    }
}

// Make function globally available
window.handleTeacherCardClick = handleTeacherCardClick;

// Initialize teachers page
if (window.location.pathname.includes('Teachers-redesign.html')) {
    document.addEventListener('DOMContentLoaded', () => {
        loadTeachers();
    });
}
