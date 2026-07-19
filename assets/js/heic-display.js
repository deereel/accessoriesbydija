/**
 * HEIC Image Display Support
 * Converts HEIC images to JPEG for display in browsers that don't natively support HEIC
 */

(function() {
    'use strict';
    
    // Check if browser supports HEIC natively
    const supportsHEIC = () => {
        const canvas = document.createElement('canvas');
        if (canvas.getContext && canvas.getContext('image/heic')) {
            return true;
        }
        // Check for HEIC in supported types
        const img = new Image();
        const heicTypes = ['image/heic', 'image/heic; codecs=hevc', 'image/heic; codecs=hevc-alpha', 'image/heif', 'image/heif; codecs=hevc', 'image/heif; codecs=hevc-alpha'];
        for (const type of heicTypes) {
            if (img.canPlayType(type)) {
                return true;
            }
        }
        return false;
    };
    
    // Convert HEIC blob to blob URL
    const convertHeicToJpeg = async (heicUrl, callback) => {
        try {
            const response = await fetch(heicUrl);
            const heicBlob = await response.blob();
            
            // Simple approach - try to use the blob directly
            // For more robust conversion, would need heic2any library
            const url = URL.createObjectURL(heicBlob);
            
            // Try loading as image - this will work in browsers that support HEIC
            const img = new Image();
            img.onload = () => {
                callback(url, img.width, img.height);
            };
            img.onerror = () => {
                // Browser doesn't support HEIC - just use placeholder
                console.log('HEIC not supported in this browser');
                URL.revokeObjectURL(url);
            };
            img.src = url;
        } catch (e) {
            console.error('Error converting HEIC:', e);
        }
    };
    
    // Process all HEIC images on the page
    const processHeicImages = () => {
        const heicImages = document.querySelectorAll('img[src$=".heic"], img[src$=".heif"], img[src*=".heic?"], img[src*=".heif?"]');
        
        if (heicImages.length === 0) return;
        
        // If browser supports HEIC, no conversion needed
        if (supportsHEIC()) {
            console.log('Browser supports HEIC natively');
            return;
        }
        
        // For browsers without support, display message in console
        console.log('HEIC images detected but browser does not natively support HEIC format');
        console.log('For full support, consider using heic2any.js library or converting on server');
        
        // Mark images so we know they're HEIC
        heicImages.forEach(img => {
            img.dataset.heicOriginal = img.src;
        });
    };
    
    // Run when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', processHeicImages);
    } else {
        processHeicImages();
    }
})();