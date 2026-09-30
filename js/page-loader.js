/**
 * St. Lawrence Junior School Kabowa — Page Loader Script
 * Optimized Institutional Loader Implementation
 * Eliminates artificial delay: dismisses smoothly upon document/window load.
 */

(function () {
    'use strict';

    let loader = document.getElementById('pageLoader');
    let progressBar = document.getElementById('loaderProgressBar');
    let isHidden = false;
    let animFrameId = null;
    let progress = 0;
    let isPageLoaded = (document.readyState === 'complete');

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

    // Dismiss loader smoothly
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
            }, 200);
        }, 200);
    }

    // Quick progressive animation while page resources are being fetched
    function tickProgress() {
        if (isHidden) return;
        if (isPageLoaded) {
            setProgress(100);
            hideLoader();
            return;
        }

        // Incrementally advance to ~85% while waiting for window load event
        if (progress < 85) {
            progress += 5;
            setProgress(progress);
            setTimeout(function() {
                animFrameId = requestAnimationFrame(tickProgress);
            }, 25);
        }
    }

    // Dismiss when window load event fires
    function onWindowLoaded() {
        isPageLoaded = true;
        setProgress(100);
        setTimeout(hideLoader, 100);
    }

    if (document.readyState === 'complete') {
        onWindowLoaded();
    } else {
        animFrameId = requestAnimationFrame(tickProgress);
        window.addEventListener('load', onWindowLoaded, { once: true });
    }

    // Fallback safety timeout (maximum 1.5s in case external assets hang)
    setTimeout(function () {
        if (!isHidden) {
            onWindowLoaded();
        }
    }, 1500);

    // Global error safety fallback
    window.addEventListener('error', function () {
        setTimeout(function () { hideLoader(); }, 500);
    });

})();
