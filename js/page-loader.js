/**
 * St. Lawrence Junior School Kabowa — Page Loader Script
 * Option A Light Institutional Loader Implementation
 * Configured with a 3.5 second polished, institutional loading sequence.
 */

(function () {
    'use strict';

    const LOADER_DURATION_MS = 3500; // 3.5 Seconds duration as requested by user

    let loader = document.getElementById('pageLoader');
    let progressBar = document.getElementById('loaderProgressBar');
    let isHidden = false;
    let animFrameId = null;
    let startTime = null;

    if (!loader) return;

    // Prevent scrolling while loader is active
    document.body.classList.add('loader-active');

    // Update progress bar width
    function setProgress(percent) {
        if (!progressBar) progressBar = document.getElementById('loaderProgressBar');
        if (progressBar) {
            const clamped = Math.min(100, Math.max(0, percent));
            progressBar.style.width = clamped + '%';
        }
    }

    // Dismiss loader smoothly after full 3.5s sequence
    function hideLoader() {
        if (isHidden) return;
        isHidden = true;

        if (animFrameId) cancelAnimationFrame(animFrameId);

        setProgress(100);

        setTimeout(function () {
            loader.classList.add('hidden');
            document.body.classList.remove('loader-active');

            // Dispatch custom event for secondary scripts (counters, controls, etc.)
            setTimeout(function () {
                window.dispatchEvent(new Event('loaderHidden'));
            }, 300);
        }, 300);
    }

    // Smoothly animate progress over 3.5 seconds using requestAnimationFrame
    function animateProgress(timestamp) {
        if (!startTime) startTime = timestamp;
        const elapsed = timestamp - startTime;
        const progress = (elapsed / LOADER_DURATION_MS) * 100;

        if (progress < 100) {
            setProgress(progress);
            animFrameId = requestAnimationFrame(animateProgress);
        } else {
            setProgress(100);
            hideLoader();
        }
    }

    // Start 3.5-second smooth progress animation
    animFrameId = requestAnimationFrame(animateProgress);

    // Global error safety fallback: if JS breaks on page, still release eventually
    window.addEventListener('error', function () {
        setTimeout(function () { hideLoader(); }, LOADER_DURATION_MS);
    });

})();
