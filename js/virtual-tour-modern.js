/**
 * Virtual Tour Modern - Video Player Functionality
 * Handles play button click, video loading, and close functionality
 */

(function() {
    'use strict';

    // ============================================
    // VIDEO CONFIGURATION
    // ============================================
    
    // OPTION 1: Local Video File (Authentic St. Lawrence School Footage)
    const LOCAL_VIDEO_PATH = '../img/students walking.mp4';
    
    // OPTION 2: YouTube Video URL (If official YouTube link is provided later)
    const YOUTUBE_VIDEO_ID = '';
    const YOUTUBE_URL = YOUTUBE_VIDEO_ID ? `https://www.youtube.com/embed/${YOUTUBE_VIDEO_ID}?autoplay=1&rel=0&modestbranding=1` : '';
    
    // Default video source: 'local' for real school footage
    const VIDEO_SOURCE = 'local';
    
    // ============================================

    // DOM Elements
    const playBtn = document.getElementById('playBtnModern');
    const videoThumbnail = document.getElementById('videoThumbnailModern');
    const videoPlayer = document.getElementById('videoPlayerModern');
    const videoIframe = document.getElementById('videoIframeModern');
    const videoElement = document.getElementById('videoElementModern'); // For local videos
    const closeBtn = document.getElementById('closeVideoModern');

    // Check if essential elements exist
    if (!playBtn || !videoThumbnail || !videoPlayer) {
        console.warn('Virtual Tour: Essential elements not found');
        return;
    }

    /**
     * Show video player and load video
     */
    function showVideo() {
        // Hide thumbnail
        videoThumbnail.style.display = 'none';
        
        // Show video player frame
        videoPlayer.style.display = 'block';
        
        // Load video based on source type
        if (VIDEO_SOURCE === 'local' && videoElement) {
            videoElement.style.display = 'block';
            if (videoIframe) videoIframe.style.display = 'none';
            if (!videoElement.src || videoElement.src === window.location.href) {
                videoElement.src = LOCAL_VIDEO_PATH;
            }
            videoElement.play().catch(function(err) {
                console.log('Autoplay caught or prevented:', err);
            });
        } else if (VIDEO_SOURCE === 'youtube' && videoIframe && YOUTUBE_URL) {
            videoIframe.style.display = 'block';
            if (videoElement) videoElement.style.display = 'none';
            videoIframe.src = YOUTUBE_URL;
        }
    }

    /**
     * Hide video player and stop video
     */
    function hideVideo() {
        // Show thumbnail
        videoThumbnail.style.display = 'block';
        
        // Hide video player
        videoPlayer.style.display = 'none';
        
        // Stop video playback
        if (videoElement) {
            videoElement.pause();
        }
        if (videoIframe) {
            videoIframe.src = '';
        }
    }

    // Event Listeners
    playBtn.addEventListener('click', showVideo);
    closeBtn.addEventListener('click', hideVideo);

    // Close video on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && videoPlayer.style.display === 'block') {
            hideVideo();
        }
    });

    // Optional: Close video when clicking outside the iframe
    videoPlayer.addEventListener('click', function(e) {
        if (e.target === videoPlayer) {
            hideVideo();
        }
    });

})();

