/**
 * Virtual Tour Modern - Video Player Functionality
 * Handles play button click, video loading, and close functionality
 */

(function() {
    'use strict';

    // ============================================
    // VIDEO CONFIGURATION
    // ============================================
    
    // OPTION 1: YouTube Video
    const YOUTUBE_VIDEO_ID = 'EY_BfeDjZ9k';
    const YOUTUBE_URL = `https://www.youtube.com/embed/${YOUTUBE_VIDEO_ID}?autoplay=1&rel=0&modestbranding=1`;
    
    // OPTION 2: Local Video File
    // Upload your video to backend/uploads/videos/ folder
    const LOCAL_VIDEO_PATH = '../backend/uploads/videos/virtual-tour.mp4';
    
    // Choose video source: 'youtube' or 'local'
    const VIDEO_SOURCE = 'youtube'; // Change to 'local' to use local video file
    
    // ============================================

    // DOM Elements
    const playBtn = document.getElementById('playBtnModern');
    const videoThumbnail = document.getElementById('videoThumbnailModern');
    const videoPlayer = document.getElementById('videoPlayerModern');
    const videoIframe = document.getElementById('videoIframeModern');
    const videoElement = document.getElementById('videoElementModern'); // For local videos
    const closeBtn = document.getElementById('closeVideoModern');

    // Check if all elements exist
    if (!playBtn || !videoThumbnail || !videoPlayer || !videoIframe || !closeBtn) {
        console.warn('Virtual Tour: Some elements not found');
        return;
    }

    /**
     * Show video player and load video
     */
    function showVideo() {
        console.log('Showing video player...');
        console.log('Video source:', VIDEO_SOURCE);
        
        // Hide thumbnail using class
        videoThumbnail.classList.add('hidden');
        
        // Show video player
        videoPlayer.classList.add('active');
        videoPlayer.style.display = 'block';
        
        // Load video based on source type
        if (VIDEO_SOURCE === 'youtube') {
            // YouTube video - use iframe
            videoIframe.style.display = 'block';
            if (videoElement) videoElement.style.display = 'none';
            
            setTimeout(function() {
                videoIframe.src = YOUTUBE_URL;
                console.log('YouTube video loaded:', videoIframe.src);
            }, 100);
        } else {
            // Local video - use HTML5 video element
            videoIframe.style.display = 'none';
            if (videoElement) {
                videoElement.style.display = 'block';
                videoElement.src = LOCAL_VIDEO_PATH;
                videoElement.play();
                console.log('Local video loaded:', LOCAL_VIDEO_PATH);
            }
        }
        
        // Prevent body scroll when video is playing
        document.body.style.overflow = 'hidden';
    }

    /**
     * Hide video player and stop video
     */
    function hideVideo() {
        // Show thumbnail
        videoThumbnail.classList.remove('hidden');
        
        // Hide video player
        videoPlayer.classList.remove('active');
        videoPlayer.style.display = 'none';
        
        // Stop video based on source type
        if (VIDEO_SOURCE === 'youtube') {
            videoIframe.src = '';
        } else {
            if (videoElement) {
                videoElement.pause();
                videoElement.src = '';
            }
        }
        
        // Restore body scroll
        document.body.style.overflow = '';
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

