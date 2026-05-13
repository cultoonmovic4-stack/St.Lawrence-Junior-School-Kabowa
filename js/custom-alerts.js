// Custom Modern Alert System (SweetAlert-style)

function acquireBodyScrollLock(source, scrollY) {
    if (!window.__scrollLockState) {
        window.__scrollLockState = {
            count: 0,
            ownerMap: {}
        };
    }

    const state = window.__scrollLockState;
    if (state.ownerMap[source]) return;

    if (state.count === 0) {
        document.body.style.overflow = 'hidden';
    }

    if (source === 'alert' && !state.ownerMap.alert) {
        window.originalScrollY = scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = `-${scrollY}px`;
        document.body.style.width = '100%';
    }

    state.ownerMap[source] = true;
    state.count += 1;
}

function releaseBodyScrollLock(source) {
    const state = window.__scrollLockState;
    if (!state || !state.ownerMap[source]) return;

    delete state.ownerMap[source];
    state.count = Math.max(0, state.count - 1);

    if (source === 'alert') {
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
    }

    if (state.count === 0) {
        document.body.style.overflow = '';
    }
}

// Show success alert
function showSuccessAlert(title, message, callback) {
    console.log('showSuccessAlert called - NEW VERSION 2.0');
    
    // Store current scroll position
    const currentScrollY = window.scrollY;
    console.log('Current scroll position:', currentScrollY);
    
    const alertHTML = `
        <div class="custom-alert-overlay" id="customAlert">
            <div class="custom-alert-box success-alert">
                <div class="alert-icon-wrapper">
                    <div class="alert-icon success-icon">
                        <div class="success-icon-circle">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                </div>
                <h2 class="alert-title">${title}</h2>
                <p class="alert-message">${message}</p>
                <button class="alert-btn success-btn" onclick="closeCustomAlert(${callback ? 'true' : 'false'})">
                    <span>OK</span>
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // Store callback if provided
    if (callback) {
        window.customAlertCallback = callback;
    }
    
    // Store original scroll position and prevent body scroll
    acquireBodyScrollLock('alert', currentScrollY);
    
    console.log('Body styles applied - position fixed, top:', `-${currentScrollY}px`);
    
    // Animate in
    setTimeout(() => {
        document.getElementById('customAlert').classList.add('show');
        console.log('Alert should now be visible');
    }, 10);
}

// Show error alert
function showErrorAlert(title, message) {
    // Store current scroll position
    const currentScrollY = window.scrollY;
    
    const alertHTML = `
        <div class="custom-alert-overlay" id="customAlert">
            <div class="custom-alert-box error-alert">
                <div class="alert-icon-wrapper">
                    <div class="alert-icon error-icon">
                        <div class="error-icon-circle">
                            <i class="fas fa-times"></i>
                        </div>
                    </div>
                </div>
                <h2 class="alert-title">${title}</h2>
                <p class="alert-message">${message}</p>
                <button class="alert-btn error-btn" onclick="closeCustomAlert()">
                    <span>Try Again</span>
                    <i class="fas fa-redo"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // Store original scroll position and prevent body scroll
    acquireBodyScrollLock('alert', currentScrollY);
    
    // Animate in
    setTimeout(() => {
        document.getElementById('customAlert').classList.add('show');
    }, 10);
}

// Show warning alert
function showWarningAlert(title, message) {
    // Store current scroll position
    const currentScrollY = window.scrollY;
    
    const alertHTML = `
        <div class="custom-alert-overlay" id="customAlert">
            <div class="custom-alert-box warning-alert">
                <div class="alert-icon-wrapper">
                    <div class="alert-icon warning-icon">
                        <div class="warning-icon-circle">
                            <i class="fas fa-exclamation"></i>
                        </div>
                    </div>
                </div>
                <h2 class="alert-title">${title}</h2>
                <p class="alert-message">${message}</p>
                <button class="alert-btn warning-btn" onclick="closeCustomAlert()">
                    <span>OK</span>
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // Store original scroll position and prevent body scroll
    acquireBodyScrollLock('alert', currentScrollY);
    
    // Animate in
    setTimeout(() => {
        document.getElementById('customAlert').classList.add('show');
    }, 10);
}

// Show info alert
function showInfoAlert(title, message) {
    // Store current scroll position
    const currentScrollY = window.scrollY;
    
    const alertHTML = `
        <div class="custom-alert-overlay" id="customAlert">
            <div class="custom-alert-box info-alert">
                <div class="alert-icon-wrapper">
                    <div class="alert-icon info-icon">
                        <div class="info-icon-circle">
                            <i class="fas fa-info"></i>
                        </div>
                    </div>
                </div>
                <h2 class="alert-title">${title}</h2>
                <p class="alert-message">${message}</p>
                <button class="alert-btn info-btn" onclick="closeCustomAlert()">
                    <span>OK</span>
                    <i class="fas fa-check"></i>
                </button>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // Store original scroll position and prevent body scroll
    acquireBodyScrollLock('alert', currentScrollY);
    
    // Animate in
    setTimeout(() => {
        document.getElementById('customAlert').classList.add('show');
    }, 10);
}

// Show confirmation alert
function showConfirmAlert(title, message, onConfirm, onCancel) {
    // Store current scroll position
    const currentScrollY = window.scrollY;
    
    const alertHTML = `
        <div class="custom-alert-overlay" id="customAlert">
            <div class="custom-alert-box confirm-alert">
                <div class="alert-icon-wrapper">
                    <div class="alert-icon confirm-icon">
                        <div class="confirm-icon-circle">
                            <i class="fas fa-question"></i>
                        </div>
                    </div>
                </div>
                <h2 class="alert-title">${title}</h2>
                <p class="alert-message">${message}</p>
                <div class="alert-buttons-group">
                    <button class="alert-btn cancel-btn" onclick="handleConfirmCancel()">
                        <span>Cancel</span>
                        <i class="fas fa-times"></i>
                    </button>
                    <button class="alert-btn confirm-btn" onclick="handleConfirmOk()">
                        <span>Confirm</span>
                        <i class="fas fa-check"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', alertHTML);
    
    // Store callbacks
    window.confirmAlertCallbacks = {
        onConfirm: onConfirm,
        onCancel: onCancel
    };
    
    // Store original scroll position and prevent body scroll
    acquireBodyScrollLock('alert', currentScrollY);
    
    // Animate in
    setTimeout(() => {
        document.getElementById('customAlert').classList.add('show');
    }, 10);
}

// Close alert
function closeCustomAlert(executeCallback = false) {
    console.log('closeCustomAlert called - NEW VERSION 2.0');
    
    const alert = document.getElementById('customAlert');
    if (alert) {
        alert.classList.remove('show');
        
        setTimeout(() => {
            alert.remove();
            
            // Restore body styles for alert lock owner
            releaseBodyScrollLock('alert');
            
            console.log('Body styles restored');
            
            // Restore scroll position
            if (window.originalScrollY !== undefined) {
                window.scrollTo(0, window.originalScrollY);
                console.log('Scroll restored to:', window.originalScrollY);
                window.originalScrollY = undefined;
            }
            
            // Execute callback if provided
            if (executeCallback && window.customAlertCallback) {
                window.customAlertCallback();
                window.customAlertCallback = null;
            }
        }, 300);
    }
}

// Handle confirm OK
function handleConfirmOk() {
    if (window.confirmAlertCallbacks && window.confirmAlertCallbacks.onConfirm) {
        window.confirmAlertCallbacks.onConfirm();
    }
    closeCustomAlert();
    window.confirmAlertCallbacks = null;
}

// Handle confirm Cancel
function handleConfirmCancel() {
    if (window.confirmAlertCallbacks && window.confirmAlertCallbacks.onCancel) {
        window.confirmAlertCallbacks.onCancel();
    }
    closeCustomAlert();
    window.confirmAlertCallbacks = null;
}

// Close on overlay click
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('custom-alert-overlay')) {
        closeCustomAlert();
    }
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCustomAlert();
    }
});
