/**
 * St. Lawrence Junior School Kabowa
 * Official Digital Admission Form Controller
 * 8-Stage Flow, Canvas Signature, Dynamic Validation, Dual Downloads
 */

(function () {
    'use strict';

    let currentStage = 1;
    const totalStages = 8;
    let signatureCanvas, signatureCtx;
    let isDrawing = false;
    let strokes = [];
    let currentStroke = [];

    document.addEventListener('DOMContentLoaded', function () {
        initDynamicYears();
        initStageNavigation();
        initConditionalFields();
        initSignaturePad();
        initFormSubmission();
        initCustomDropdowns();
    });

    // 1. Dynamic Academic Year Setup
    function initDynamicYears() {
        const yearSelect = document.getElementById('academicYear');
        if (!yearSelect) return;

        const currentYear = new Date().getFullYear();
        const years = [
            `${currentYear}`,
            `${currentYear}/${currentYear + 1}`,
            `${currentYear + 1}`
        ];

        yearSelect.innerHTML = '<option value="">Select Year</option>';
        years.forEach(yr => {
            const opt = document.createElement('option');
            opt.value = yr;
            opt.textContent = yr;
            if (yr === `${currentYear}` || yr === `${currentYear}/${currentYear + 1}`) {
                // sensible default
                if (!yearSelect.value) opt.selected = true;
            }
            yearSelect.appendChild(opt);
        });

        if (window.syncCustomDropdown) {
            window.syncCustomDropdown('academicYear');
        }
    }

    // 2. Stage Navigation Setup
    function initStageNavigation() {
        const btnPrev = document.getElementById('btnPrevStage');
        const btnNext = document.getElementById('btnNextStage');
        const btnSubmit = document.getElementById('btnSubmitForm');

        if (btnPrev) {
            btnPrev.addEventListener('click', () => {
                if (currentStage > 1) {
                    goToStage(currentStage - 1, true);
                }
            });
        }

        if (btnNext) {
            btnNext.addEventListener('click', () => {
                if (validateStage(currentStage)) {
                    if (currentStage < totalStages) {
                        goToStage(currentStage + 1, true);
                    }
                }
            });
        }

        // Allow tapping on visited stage tabs for smooth mobile navigation
        document.querySelectorAll('.admission-stage-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                const targetStage = parseInt(tab.getAttribute('data-stage'));
                if (targetStage < currentStage) {
                    goToStage(targetStage, true);
                } else if (targetStage === currentStage + 1) {
                    if (validateStage(currentStage)) {
                        goToStage(targetStage, true);
                    }
                }
            });
        });

        // Explicitly initialize Stage 1 state on page load without auto-scrolling
        goToStage(1, false);
    }

    window.jumpToStage = function (stageNum) {
        if (stageNum >= 1 && stageNum <= totalStages) {
            goToStage(stageNum, true);
        }
    };

    function scrollToFormTop() {
        const container = document.getElementById('admission-application-section');
        if (!container) return;
        const headerEl = document.getElementById('header') || document.querySelector('.header');
        const headerOffset = headerEl ? headerEl.offsetHeight : 75;
        const rect = container.getBoundingClientRect();
        const targetY = rect.top + window.pageYOffset - headerOffset - 12;
        window.scrollTo({ top: Math.max(0, targetY), behavior: 'smooth' });
    }

    function goToStage(stageNum, shouldScroll = true) {
        currentStage = stageNum;

        // Update stage panels
        document.querySelectorAll('.admission-stage-panel').forEach(panel => {
            panel.classList.toggle('active', parseInt(panel.getAttribute('data-stage')) === currentStage);
        });

        // Update navigation tabs
        document.querySelectorAll('.admission-stage-tab').forEach(tab => {
            const num = parseInt(tab.getAttribute('data-stage'));
            tab.classList.toggle('active', num === currentStage);
            tab.classList.toggle('completed', num < currentStage);
        });

        // Auto-center active stage tab in horizontal navigation on mobile
        const activeTab = document.querySelector(`.admission-stage-tab[data-stage="${stageNum}"]`);
        if (activeTab) {
            activeTab.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
        }

        // Update action buttons and deterministic state classes
        const btnPrev = document.getElementById('btnPrevStage');
        const btnNext = document.getElementById('btnNextStage');
        const btnSubmit = document.getElementById('btnSubmitForm');
        const actionsBar = document.querySelector('.admission-actions-bar');

        const isFirst = currentStage === 1;
        const isFinal = currentStage === totalStages;

        if (btnPrev) {
            btnPrev.style.display = isFirst ? 'none' : 'inline-flex';
            btnPrev.classList.toggle('is-hidden', isFirst);
        }
        if (btnNext) {
            btnNext.style.display = isFinal ? 'none' : 'inline-flex';
            btnNext.classList.toggle('is-hidden', isFinal);
        }
        if (btnSubmit) {
            btnSubmit.style.display = isFinal ? 'inline-flex' : 'none';
            btnSubmit.classList.toggle('is-hidden', !isFinal);
        }

        if (actionsBar) {
            actionsBar.classList.toggle('is-stage-1', isFirst);
            actionsBar.classList.toggle('is-mid-stage', !isFirst && !isFinal);
            actionsBar.classList.toggle('is-final-stage', isFinal);
        }

        // Stage-specific actions
        if (currentStage === 7) {
            resizeSignatureCanvas();
        }
        if (currentStage === 8) {
            populateReviewSummary();
        }

        // Scroll to container top smoothly with header offset
        if (shouldScroll) {
            scrollToFormTop();
        }
    }

    // 3. Conditional UI Logic
    function initConditionalFields() {
        // School Type (Day vs Boarding)
        const typeRadios = document.querySelectorAll('input[name="admission_type"]');
        const bedCard = document.getElementById('bedWettingCard');
        typeRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                const isBoarding = document.querySelector('input[name="admission_type"]:checked')?.value === 'boarding';
                if (bedCard) {
                    bedCard.style.display = isBoarding ? 'block' : 'none';
                    if (isBoarding) bedCard.classList.add('conditional-animated');
                    if (!isBoarding) {
                        document.querySelectorAll('input[name="bed_wetting"]').forEach(r => r.checked = false);
                    }
                }
            });
        });

        // Has Attended School Before
        const attendedRadios = document.querySelectorAll('input[name="has_attended_school"]');
        const prevSchoolFields = document.getElementById('previousSchoolFields');
        const prevNameInput = document.getElementById('prevSchoolName');
        const prevLocInput = document.getElementById('prevSchoolLoc');

        attendedRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                const attended = document.querySelector('input[name="has_attended_school"]:checked')?.value === 'yes';
                if (prevSchoolFields) {
                    prevSchoolFields.style.display = attended ? 'grid' : 'none';
                    if (attended) prevSchoolFields.classList.add('conditional-animated');
                    if (prevNameInput) prevNameInput.required = attended;
                    if (prevLocInput) prevLocInput.required = attended;
                    if (!attended) {
                        if (prevNameInput) prevNameInput.value = '';
                        if (prevLocInput) prevLocInput.value = '';
                    }
                }
            });
        });

        // Health Handicap
        const handicapRadios = document.querySelectorAll('input[name="has_health_handicap"]');
        const handicapGroup = document.getElementById('handicapDetailsGroup');
        const handicapTextarea = document.getElementById('healthHandicapDetails');

        handicapRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                const hasHandicap = document.querySelector('input[name="has_health_handicap"]:checked')?.value === 'yes';
                if (handicapGroup) {
                    handicapGroup.style.display = hasHandicap ? 'flex' : 'none';
                    if (hasHandicap) handicapGroup.classList.add('conditional-animated');
                    if (handicapTextarea) {
                        handicapTextarea.required = hasHandicap;
                        if (!hasHandicap) handicapTextarea.value = '';
                    }
                }
            });
        });

        // Parents Cohabitation
        const liveTogetherRadios = document.querySelectorAll('input[name="parents_live_together"]');
        liveTogetherRadios.forEach(radio => {
            radio.addEventListener('change', function () {
                const together = document.querySelector('input[name="parents_live_together"]:checked')?.value === 'yes';
                const motherAddr = document.getElementById('motherAddress');
                if (motherAddr) {
                    motherAddr.placeholder = together ? 'Same as Father or shared residence' : "Mother's location / contact";
                }
            });
        });

        // Sync visual checked state on custom radio pill labels across all browsers
        function syncRadioPillLabels() {
            document.querySelectorAll('.radio-pill-label').forEach(label => {
                const radio = label.querySelector('input[type="radio"]');
                if (radio) {
                    label.classList.toggle('checked', radio.checked);
                }
            });
        }
        document.querySelectorAll('.radio-pill-group input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', syncRadioPillLabels);
        });
        syncRadioPillLabels();
    }

    // 4. HTML5 Canvas Signature Pad
    function initSignaturePad() {
        signatureCanvas = document.getElementById('signatureCanvas');
        if (!signatureCanvas) return;
        signatureCtx = signatureCanvas.getContext('2d');

        function getPos(e) {
            const rect = signatureCanvas.getBoundingClientRect();
            const clientX = e.touches ? e.touches[0].clientX : e.clientX;
            const clientY = e.touches ? e.touches[0].clientY : e.clientY;
            return {
                x: clientX - rect.left,
                y: clientY - rect.top
            };
        }

        function startDrawing(e) {
            e.preventDefault();
            isDrawing = true;
            currentStroke = [];
            const pos = getPos(e);
            currentStroke.push(pos);
            signatureCtx.beginPath();
            signatureCtx.moveTo(pos.x, pos.y);
        }

        function draw(e) {
            if (!isDrawing) return;
            e.preventDefault();
            const pos = getPos(e);
            currentStroke.push(pos);
            signatureCtx.lineWidth = 2.2;
            signatureCtx.lineCap = 'round';
            signatureCtx.lineJoin = 'round';
            signatureCtx.strokeStyle = '#0b2545';
            signatureCtx.lineTo(pos.x, pos.y);
            signatureCtx.stroke();
        }

        function stopDrawing(e) {
            if (!isDrawing) return;
            e.preventDefault();
            isDrawing = false;
            if (currentStroke.length > 0) {
                strokes.push([...currentStroke]);
            }
            updateSignatureState();
        }

        signatureCanvas.addEventListener('mousedown', startDrawing);
        signatureCanvas.addEventListener('mousemove', draw);
        window.addEventListener('mouseup', stopDrawing);

        signatureCanvas.addEventListener('touchstart', startDrawing, { passive: false });
        signatureCanvas.addEventListener('touchmove', draw, { passive: false });
        window.addEventListener('touchend', stopDrawing);

        // Clear button
        const clearBtn = document.getElementById('sigClearBtn');
        if (clearBtn) {
            clearBtn.addEventListener('click', () => {
                strokes = [];
                currentStroke = [];
                signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
                updateSignatureState();
            });
        }

        // Undo button
        const undoBtn = document.getElementById('sigUndoBtn');
        if (undoBtn) {
            undoBtn.addEventListener('click', () => {
                if (strokes.length > 0) {
                    strokes.pop();
                    redrawStrokes();
                    updateSignatureState();
                }
            });
        }

        window.addEventListener('resize', resizeSignatureCanvas);
    }

    function resizeSignatureCanvas() {
        if (!signatureCanvas) return;
        const wrapper = signatureCanvas.parentElement;
        const rect = wrapper.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
            signatureCanvas.width = rect.width;
            signatureCanvas.height = rect.height;
            redrawStrokes();
        }
    }

    function redrawStrokes() {
        if (!signatureCtx || !signatureCanvas) return;
        signatureCtx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
        signatureCtx.lineWidth = 2.2;
        signatureCtx.lineCap = 'round';
        signatureCtx.lineJoin = 'round';
        signatureCtx.strokeStyle = '#0b2545';

        strokes.forEach(stroke => {
            if (stroke.length === 0) return;
            signatureCtx.beginPath();
            signatureCtx.moveTo(stroke[0].x, stroke[0].y);
            for (let i = 1; i < stroke.length; i++) {
                signatureCtx.lineTo(stroke[i].x, stroke[i].y);
            }
            signatureCtx.stroke();
        });
    }

    function updateSignatureState() {
        const sigStatus = document.getElementById('sigStatus');
        const sigDataInput = document.getElementById('signatureData');
        const sigError = document.getElementById('signatureError');

        if (strokes.length > 0) {
            const dataUrl = signatureCanvas.toDataURL('image/png');
            if (sigDataInput) sigDataInput.value = dataUrl;
            if (sigStatus) {
                sigStatus.textContent = 'Signature Captured';
                sigStatus.classList.add('signed');
            }
            if (sigError) sigError.style.display = 'none';
        } else {
            if (sigDataInput) sigDataInput.value = '';
            if (sigStatus) {
                sigStatus.textContent = 'Awaiting signature';
                sigStatus.classList.remove('signed');
            }
        }
    }

    // 5. Stage Validation Logic
    function validateStage(stageNum) {
        const panel = document.querySelector(`.admission-stage-panel[data-stage="${stageNum}"]`);
        if (!panel) return true;

        let isValid = true;
        const requiredInputs = panel.querySelectorAll('input[required], select[required], textarea[required]');

        requiredInputs.forEach(input => {
            const formGroup = input.closest('.form-group') || input.parentElement;

            // Radio button validation
            if (input.type === 'radio') {
                const name = input.name;
                const checked = panel.querySelector(`input[name="${name}"]:checked`);
                if (!checked) {
                    isValid = false;
                    formGroup.classList.add('has-error');
                } else {
                    formGroup.classList.remove('has-error');
                }
            }
            // Checkbox validation
            else if (input.type === 'checkbox') {
                if (!input.checked) {
                    isValid = false;
                    const errEl = document.getElementById(input.id === 'rulesAcknowledged' ? 'rulesError' : 'declarationError');
                    if (errEl) errEl.style.display = 'block';
                } else {
                    const errEl = document.getElementById(input.id === 'rulesAcknowledged' ? 'rulesError' : 'declarationError');
                    if (errEl) errEl.style.display = 'none';
                }
            }
            // Text / Select / Textarea validation
            else {
                const wrap = input.closest('.sljk-dropdown-wrap');
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                    formGroup.classList.add('has-error');
                    if (wrap) wrap.classList.add('has-error');
                } else {
                    input.classList.remove('error');
                    formGroup.classList.remove('has-error');
                    if (wrap) wrap.classList.remove('has-error');
                }
            }
        });

        // Stage 3: Email format validation check
        if (stageNum === 3) {
            const emailInput = document.getElementById('responsibleEmail');
            if (emailInput) {
                const emailVal = emailInput.value.trim();
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                const formGroup = emailInput.closest('.form-group') || emailInput.parentElement;
                if (!emailVal || !emailRegex.test(emailVal)) {
                    isValid = false;
                    emailInput.classList.add('error');
                    if (formGroup) formGroup.classList.add('has-error');
                } else {
                    emailInput.classList.remove('error');
                    if (formGroup) formGroup.classList.remove('has-error');
                }
            }
        }

        // Stage 4: Boarding Bed-Wetting conditional check
        if (stageNum === 4) {
            const isBoarding = document.querySelector('input[name="admission_type"]:checked')?.value === 'boarding';
            if (isBoarding) {
                const bedWet = document.querySelector('input[name="bed_wetting"]:checked');
                const bedCard = document.getElementById('bedWettingCard');
                if (!bedWet && bedCard) {
                    isValid = false;
                    bedCard.querySelector('.form-group')?.classList.add('has-error');
                } else if (bedCard) {
                    bedCard.querySelector('.form-group')?.classList.remove('has-error');
                }
            }
        }

        // Stage 7: Signature validation check
        if (stageNum === 7) {
            const sigData = document.getElementById('signatureData')?.value;
            const sigError = document.getElementById('signatureError');
            if (!sigData || strokes.length === 0) {
                isValid = false;
                if (sigError) sigError.style.display = 'block';
            } else {
                if (sigError) sigError.style.display = 'none';
            }
        }

        return isValid;
    }

    // 6. Stage 08 Structured Review Population
    function populateReviewSummary() {
        const getVal = id => document.getElementById(id)?.value?.trim() || '';
        const getRadio = name => document.querySelector(`input[name="${name}"]:checked`)?.value || '';

        // 01 Details
        const yr = getVal('academicYear');
        const trm = getVal('term');
        const cls = getVal('classToJoin');
        const admType = getRadio('admission_type');
        document.getElementById('rYearTerm').textContent = `${yr} • ${trm}`;
        document.getElementById('rClass').textContent = cls || 'Not selected';
        document.getElementById('rSchoolType').textContent = admType === 'boarding' ? 'Boarder' : 'Day School';

        // 02 Child
        const sSurname = getVal('studentSurname');
        const sOther = getVal('studentOtherNames');
        const dob = getVal('dateOfBirth');
        const gender = getVal('gender');
        const rel = getVal('religion');
        const tribe = getVal('tribe');
        const pos = getVal('positionInFamily');
        const attended = getRadio('has_attended_school') === 'yes';
        const pSchool = attended ? `${getVal('prevSchoolName')} (${getVal('prevSchoolLoc')})` : 'First Time Entrant (None)';
        const l1 = getVal('language1');
        const l2 = getVal('language2');

        document.getElementById('rChildName').textContent = `${sSurname} ${sOther}`.trim();
        document.getElementById('rDobSex').textContent = `${dob} • ${gender ? gender.toUpperCase() : '-'}`;
        document.getElementById('rRelTribe').textContent = `${rel || '-'} • ${tribe || '-'}`;
        document.getElementById('rPosition').textContent = pos || '-';
        document.getElementById('rPrevSchool').textContent = pSchool;
        document.getElementById('rLanguages').textContent = l2 ? `${l1}, ${l2}` : l1;

        // 03 Responsible & Parents
        const rName = getVal('responsibleName');
        const rPhone = getVal('responsiblePhone');
        const rEmail = getVal('responsibleEmail');
        const rAddr = getVal('responsibleAddress');
        const liveTogether = getRadio('parents_live_together') === 'yes';
        const fName = getVal('fatherName');
        const fOcc = getVal('fatherOccupation');
        const mName = getVal('motherName');
        const mOcc = getVal('motherOccupation');

        document.getElementById('rResponsibleName').textContent = rName;
        document.getElementById('rResponsiblePhone').textContent = rPhone;
        const rEmailEl = document.getElementById('rResponsibleEmail');
        if (rEmailEl) rEmailEl.textContent = rEmail || '-';
        document.getElementById('rResponsibleAddress').textContent = rAddr;
        document.getElementById('rCohabitation').textContent = liveTogether ? 'Yes (Shared Residence)' : 'No (Living Separately)';
        document.getElementById('rFatherInfo').textContent = fName ? `${fName} (${fOcc || 'N/A'})` : '-';
        document.getElementById('rMotherInfo').textContent = mName ? `${mName} (${mOcc || 'N/A'})` : '-';

        // 04 Health & Emergency
        const kin = getVal('emergencyNextOfKin');
        const bed = getRadio('bed_wetting');
        const hasHandicap = getRadio('has_health_handicap') === 'yes';
        const hDetails = getVal('healthHandicapDetails');
        const immunized = getRadio('is_immunized') === 'yes';

        document.getElementById('rEmergencyKin').textContent = kin;
        document.getElementById('rBedWetting').textContent = admType === 'boarding' ? (bed === 'yes' ? 'Yes' : 'No') : 'N/A (Day Pupil)';
        document.getElementById('rHandicap').textContent = hasHandicap ? (hDetails || 'Yes') : 'None';
        document.getElementById('rImmunized').textContent = immunized ? 'Yes (Immunized)' : 'No';

        // 05-07 Signer & Preview
        const signerName = getVal('declarationName');
        document.getElementById('rSignerName').textContent = signerName || '-';

        const sigPreview = document.getElementById('rSigPreview');
        const sigData = document.getElementById('signatureData')?.value;
        if (sigPreview && sigData) {
            sigPreview.innerHTML = `<img src="${sigData}" alt="Digital Signature" style="max-height: 48px; border: 1px solid #cbd5e1; border-radius: 4px; padding: 2px 8px; background: #fff;">`;
        }
    }

    // 7. Form Submission Controller
    function initFormSubmission() {
        const form = document.getElementById('officialAdmissionForm');
        const btnSubmit = document.getElementById('btnSubmitForm');
        if (!form || !btnSubmit) return;

        btnSubmit.addEventListener('click', async function (e) {
            e.preventDefault();

            // Validate all stages prior to submit
            for (let s = 1; s <= 7; s++) {
                if (!validateStage(s)) {
                    goToStage(s);
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Incomplete Stage ' + s,
                            text: 'Please review and fill in all required fields in this stage.',
                            confirmButtonColor: '#0b2545'
                        });
                    }
                    return;
                }
            }

            // Gather Form Data
            const formData = new FormData(form);
            const payload = {};
            formData.forEach((val, key) => {
                payload[key] = val;
            });
            // Ensure signature is included
            payload['signature'] = document.getElementById('signatureData')?.value || '';

            // Disable button & show spinner
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting Application...';

            try {
                const response = await fetch('../backend/api/admissions/create-public-with-files.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });

                const result = await response.json();

                if (result.success) {
                    // Hide form and display confirmation card
                    form.style.display = 'none';
                    const confirmCard = document.getElementById('admissionConfirmationCard');
                    if (confirmCard) {
                        confirmCard.classList.add('active');
                    }

                    // Populate confirmed details
                    const refEl = document.getElementById('confirmedAppRef');
                    if (refEl) refEl.textContent = result.application_reference;

                    const downloadPdfBtn = document.getElementById('btnDownloadSubmittedPDF');
                    if (downloadPdfBtn && result.download_url) {
                        downloadPdfBtn.href = result.download_url;
                        downloadPdfBtn.setAttribute('download', `SLJK_Application_${result.application_reference || 'Submitted'}.pdf`);
                    }

                    const downloadBlankPdfBtn = document.getElementById('btnDownloadBlankPDF');
                    if (downloadBlankPdfBtn) {
                        downloadBlankPdfBtn.href = result.blank_form_url || '../backend/api/admissions/download-blank-pdf.php';
                        downloadBlankPdfBtn.setAttribute('download', 'SLJK_Blank_Admission_Application_Form.pdf');
                    }

                    // Copy ref button
                    const copyBtn = document.getElementById('btnCopyRef');
                    if (copyBtn) {
                        copyBtn.addEventListener('click', () => {
                            navigator.clipboard.writeText(result.application_reference).then(() => {
                                copyBtn.textContent = 'Copied!';
                                setTimeout(() => copyBtn.textContent = 'Copy Ref', 2000);
                            });
                        });
                    }

                    // Smooth scroll to confirmation
                    confirmCard.scrollIntoView({ behavior: 'smooth', block: 'start' });

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Application Submitted!',
                            text: `Reference: ${result.application_reference}. Please download your submitted application copy.`,
                            confirmButtonColor: '#0b2545'
                        });
                    }
                } else {
                    throw new Error(result.message || 'Submission failed. Please check your information and try again.');
                }
            } catch (error) {
                console.error('Admission submission error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Submission Error',
                        text: error.message || 'Unable to submit application at this time.',
                        confirmButtonColor: '#c9182b'
                    });
                } else {
                    alert('Submission error: ' + error.message);
                }
            } finally {
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Official Application';
            }
        });
    }

    // 8. Accessible Custom Dropdown Enhancer
    function initCustomDropdowns() {
        const form = document.getElementById('officialAdmissionForm');
        if (!form) return;

        const selects = form.querySelectorAll('select.form-control');
        selects.forEach(select => {
            setupCustomDropdown(select);
        });

        // Close dropdowns on outside click
        document.addEventListener('click', function (e) {
            if (!e.target.closest('.sljk-dropdown-wrap')) {
                closeAllDropdowns();
            }
        });

        // Close dropdowns on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeAllDropdowns();
            }
        });
    }

    function closeAllDropdowns(exceptWrap = null) {
        document.querySelectorAll('.sljk-dropdown-wrap.is-open').forEach(wrap => {
            if (wrap !== exceptWrap) {
                wrap.classList.remove('is-open');
                wrap.classList.remove('opens-up');
                const trigger = wrap.querySelector('.sljk-dropdown-trigger');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function setupCustomDropdown(select) {
        if (select.closest('.sljk-dropdown-wrap')) return;

        const wrap = document.createElement('div');
        wrap.className = 'sljk-dropdown-wrap';

        // Insert wrapper before select and move select inside
        select.parentNode.insertBefore(wrap, select);
        wrap.appendChild(select);

        // Create Trigger Button
        const trigger = document.createElement('button');
        trigger.type = 'button';
        trigger.className = 'sljk-dropdown-trigger';
        trigger.setAttribute('role', 'combobox');
        trigger.setAttribute('aria-haspopup', 'listbox');
        trigger.setAttribute('aria-expanded', 'false');
        trigger.setAttribute('aria-controls', `sljk-menu-${select.id || Math.random().toString(36).substr(2, 9)}`);

        const textSpan = document.createElement('span');
        textSpan.className = 'sljk-dropdown-text';

        const chevron = document.createElement('span');
        chevron.className = 'sljk-dropdown-chevron';
        chevron.innerHTML = '<i class="fas fa-chevron-down" style="font-size: 0.75rem;"></i>';

        trigger.appendChild(textSpan);
        trigger.appendChild(chevron);
        wrap.appendChild(trigger);

        // Create Dropdown Menu
        const menu = document.createElement('div');
        menu.className = 'sljk-dropdown-menu';
        menu.id = trigger.getAttribute('aria-controls');
        menu.setAttribute('role', 'listbox');
        wrap.appendChild(menu);

        function rebuildItems() {
            menu.innerHTML = '';
            const selectedOpt = select.options[select.selectedIndex];
            const firstOpt = select.options[0];
            const placeholderText = select.getAttribute('data-placeholder') || (firstOpt && !firstOpt.value ? firstOpt.textContent : 'Select option');

            if (selectedOpt && selectedOpt.value) {
                textSpan.textContent = selectedOpt.textContent;
                textSpan.classList.remove('is-placeholder');
            } else {
                textSpan.textContent = placeholderText;
                textSpan.classList.add('is-placeholder');
            }

            const children = Array.from(select.children);
            children.forEach(child => {
                if (child.tagName === 'OPTGROUP') {
                    const groupLabel = document.createElement('div');
                    groupLabel.className = 'sljk-dropdown-optgroup';
                    groupLabel.textContent = child.label;
                    menu.appendChild(groupLabel);

                    Array.from(child.children).forEach(opt => {
                        createOptionItem(opt);
                    });
                } else if (child.tagName === 'OPTION') {
                    createOptionItem(child);
                }
            });
        }

        function createOptionItem(opt) {
            const item = document.createElement('div');
            item.className = 'sljk-dropdown-item';
            item.setAttribute('role', 'option');
            item.setAttribute('data-value', opt.value);
            item.setAttribute('tabindex', '-1');

            const isSelected = opt.selected && opt.value !== '';
            if (isSelected) {
                item.classList.add('is-selected');
                item.setAttribute('aria-selected', 'true');
            } else {
                item.setAttribute('aria-selected', 'false');
            }

            const itemText = document.createElement('span');
            itemText.textContent = opt.textContent;
            item.appendChild(itemText);

            const checkIcon = document.createElement('span');
            checkIcon.className = 'sljk-dropdown-item-check';
            checkIcon.innerHTML = '<i class="fas fa-check" style="font-size: 0.72rem;"></i>';
            item.appendChild(checkIcon);

            item.addEventListener('click', function (e) {
                e.stopPropagation();
                select.value = opt.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));
                rebuildItems();
                closeAllDropdowns();
                wrap.classList.remove('has-error');
                const group = wrap.closest('.form-group');
                if (group) group.classList.remove('has-error');
                trigger.focus();
            });

            menu.appendChild(item);
        }

        rebuildItems();

        // Toggle on click
        trigger.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const isOpen = wrap.classList.contains('is-open');
            if (isOpen) {
                wrap.classList.remove('is-open');
                wrap.classList.remove('opens-up');
                trigger.setAttribute('aria-expanded', 'false');
            } else {
                closeAllDropdowns(wrap);

                // Viewport boundary check: flip upward if near bottom
                const rect = trigger.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const menuHeight = 240;
                if (spaceBelow < menuHeight && rect.top > menuHeight) {
                    wrap.classList.add('opens-up');
                } else {
                    wrap.classList.remove('opens-up');
                }

                wrap.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');

                const selectedItem = menu.querySelector('.sljk-dropdown-item.is-selected') || menu.querySelector('.sljk-dropdown-item');
                if (selectedItem) {
                    selectedItem.focus();
                }
            }
        });

        // Keyboard navigation on trigger
        trigger.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp' || e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (!wrap.classList.contains('is-open')) {
                    trigger.click();
                }
            }
        });

        // Keyboard navigation inside menu
        menu.addEventListener('keydown', function (e) {
            const items = Array.from(menu.querySelectorAll('.sljk-dropdown-item'));
            const currentIndex = items.indexOf(document.activeElement);

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                const next = items[currentIndex + 1] || items[0];
                if (next) next.focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                const prev = items[currentIndex - 1] || items[items.length - 1];
                if (prev) prev.focus();
            } else if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                if (document.activeElement && document.activeElement.classList.contains('sljk-dropdown-item')) {
                    document.activeElement.click();
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                closeAllDropdowns();
                trigger.focus();
            } else if (e.key === 'Tab') {
                closeAllDropdowns();
            }
        });

        select.addEventListener('change', function () {
            rebuildItems();
            if (select.value) {
                wrap.classList.remove('has-error');
                const group = wrap.closest('.form-group');
                if (group) group.classList.remove('has-error');
            }
        });

        select._sljkSync = rebuildItems;
    }

    window.syncCustomDropdown = function (selectId) {
        const select = typeof selectId === 'string' ? document.getElementById(selectId) : selectId;
        if (select && typeof select._sljkSync === 'function') {
            select._sljkSync();
        }
    };

})();

