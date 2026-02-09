/**
 * Admin PWA Install Prompt Handler
 * Captures the beforeinstallprompt event and shows an install button/banner
 */

let deferredPrompt = null;

// Check if app is already installed
function isAdminPWAInstalled() {
    // Check if already installed via localStorage (set after installation)
    if (localStorage.getItem('admin-pwa-installed') === 'true') {
        return true;
    }
    
    // Check if running in standalone mode
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return true;
    }
    
    // Check iOS standalone
    if (window.navigator.standalone === true) {
        return true;
    }
    
    // Check if referrer suggests app installation (Android)
    if (document.referrer.includes('android-app://')) {
        return true;
    }
    
    return false;
}

// Initialize PWA install handling
function initAdminPWAInstall() {
    // Clear old localStorage data (version check)
    const storedVersion = localStorage.getItem('admin-pwa-install-version');
    const currentVersion = '2.0';
    if (storedVersion !== currentVersion) {
        localStorage.removeItem('admin-pwa-install-dismissed');
        localStorage.setItem('admin-pwa-install-version', currentVersion);
    }

    // Don't show install prompt if already installed
    if (isAdminPWAInstalled()) {
        console.log('Admin PWA is already installed');
        return;
    }

    // Listen for the beforeinstallprompt event
    window.addEventListener('beforeinstallprompt', (e) => {
        console.log('Admin PWA install prompt captured');
        
        // Prevent Chrome 67 and earlier from automatically showing the prompt
        e.preventDefault();
        
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        
        // Show the install button/banner
        showAdminInstallPrompt();
    });

    // Listen for successful installation
    window.addEventListener('appinstalled', (e) => {
        console.log('Admin PWA was installed successfully');
        
        // Set localStorage flag to remember installation
        localStorage.setItem('admin-pwa-installed', 'true');
        
        // Hide the install prompt
        hideAdminInstallPrompt();
        
        // Clear the deferred prompt
        deferredPrompt = null;
        
        // Show success message
        showAdminInstallSuccessMessage();
    });

    // Also check for standalone mode on load (in case app was installed before)
    if (window.matchMedia('(display-mode: standalone)').matches) {
        localStorage.setItem('admin-pwa-installed', 'true');
    }
    
    // Listen for display-mode changes (in case user opens app in standalone later)
    window.matchMedia('(display-mode: standalone)').addEventListener('change', (e) => {
        if (e.matches) {
            localStorage.setItem('admin-pwa-installed', 'true');
            hideAdminInstallPrompt();
        }
    });
}

// Show install button/banner
function showAdminInstallPrompt() {
    // Double-check if already installed
    if (isAdminPWAInstalled()) {
        console.log('App is already installed, hiding prompt');
        return;
    }

    // Remove any existing install prompts first
    hideAdminInstallPrompt();

    // Create the install banner
    const banner = document.createElement('div');
    banner.id = 'admin-pwa-install-banner';
    banner.className = 'admin-pwa-install-banner';
    banner.innerHTML = `
        <div class="admin-pwa-install-content">
            <div class="admin-pwa-install-icon">
                <img src="/assets/images/admin-icon-192.png" alt="Dija Admin" width="48" height="48">
            </div>
            <div class="admin-pwa-install-text">
                <strong>Install Dija Admin</strong>
                <span>Add to home screen for quick access</span>
            </div>
            <div class="admin-pwa-install-actions">
                <button id="admin-pwa-install-btn" class="admin-pwa-install-button">Install</button>
                <button id="admin-pwa-dismiss-btn" class="admin-pwa-dismiss-button">×</button>
            </div>
        </div>
    `;

    // Add styles
    const style = document.createElement('style');
    style.textContent = `
        .admin-pwa-install-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, #333 0%, #222 100%);
            color: white;
            padding: 16px;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
            z-index: 9999;
            animation: adminSlideUp 0.4s ease;
        }
        
        @keyframes adminSlideUp {
            from {
                transform: translateY(100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .admin-pwa-install-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            gap: 12px;
        }
        
        .admin-pwa-install-icon img {
            border-radius: 8px;
            background: white;
            padding: 4px;
        }
        
        .admin-pwa-install-text {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .admin-pwa-install-text strong {
            font-size: 16px;
            font-weight: 600;
        }
        
        .admin-pwa-install-text span {
            font-size: 13px;
            opacity: 0.9;
        }
        
        .admin-pwa-install-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-pwa-install-button {
            background: #c487a5;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .admin-pwa-install-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        .admin-pwa-install-button:active {
            transform: translateY(0);
        }
        
        .admin-pwa-dismiss-button {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .admin-pwa-dismiss-button:hover {
            background: rgba(255,255,255,0.3);
        }
        
        @media (max-width: 600px) {
            .admin-pwa-install-content {
                flex-wrap: wrap;
            }
            
            .admin-pwa-install-text {
                order: 1;
                width: 100%;
                text-align: center;
            }
            
            .admin-pwa-install-icon {
                order: 2;
            }
            
            .admin-pwa-install-actions {
                order: 3;
                width: 100%;
                justify-content: center;
                margin-top: 8px;
            }
            
            .admin-pwa-dismiss-button {
                position: absolute;
                top: 8px;
                right: 8px;
            }
        }
    `;

    document.head.appendChild(style);
    document.body.appendChild(banner);

    // Add event listeners
    const installBtn = document.getElementById('admin-pwa-install-btn');
    const dismissBtn = document.getElementById('admin-pwa-dismiss-btn');

    if (installBtn) {
        installBtn.addEventListener('click', async () => {
            if (deferredPrompt) {
                // Show the install prompt
                deferredPrompt.prompt();
                
                // Wait for the user to respond
                const { outcome } = await deferredPrompt.userChoice;
                console.log('Install prompt outcome:', outcome);
                
                // If user accepted, mark as installed
                if (outcome === 'accepted') {
                    localStorage.setItem('admin-pwa-installed', 'true');
                }
                
                // Clear the deferred prompt
                deferredPrompt = null;
                
                // Hide the banner
                hideAdminInstallPrompt();
            }
        });
    }

    if (dismissBtn) {
        dismissBtn.addEventListener('click', () => {
            hideAdminInstallPrompt();
            // Save dismissal to localStorage to avoid showing again for a while
            localStorage.setItem('admin-pwa-install-dismissed', Date.now().toString());
        });
    }
}

// Hide install prompt
function hideAdminInstallPrompt() {
    const banner = document.getElementById('admin-pwa-install-banner');
    if (banner) {
        banner.style.animation = 'adminSlideDown 0.3s ease forwards';
        setTimeout(() => {
            banner.remove();
        }, 300);
    }
}

// Show install success message
function showAdminInstallSuccessMessage() {
    const message = document.createElement('div');
    message.className = 'admin-pwa-success-message';
    message.innerHTML = `
        <div class="admin-pwa-success-content">
            <span class="admin-pwa-success-icon">✓</span>
            <span>Admin app installed successfully!</span>
        </div>
    `;

    const style = document.createElement('style');
    style.textContent = `
        .admin-pwa-success-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #28a745;
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            z-index: 10000;
            animation: adminFadeInOut 3s ease forwards;
        }
        
        @keyframes adminFadeInOut {
            0% { opacity: 0; transform: translateY(-10px); }
            10% { opacity: 1; transform: translateY(0); }
            80% { opacity: 1; transform: translateY(0); }
            100% { opacity: 0; transform: translateY(-10px); }
        }
        
        .admin-pwa-success-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .admin-pwa-success-icon {
            font-size: 18px;
        }
    `;

    document.head.appendChild(style);
    document.body.appendChild(message);

    setTimeout(() => {
        message.remove();
    }, 3000);
}

// Check if we should show the install prompt (respect dismissal)
function shouldShowAdminInstallPrompt() {
    const dismissed = localStorage.getItem('admin-pwa-install-dismissed');
    if (dismissed) {
        // Don't show for 7 days after dismissal
        const sevenDays = 7 * 24 * 60 * 60 * 1000;
        if (Date.now() - parseInt(dismissed) < sevenDays) {
            return false;
        }
    }
    return true;
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Only initialize if we should show the prompt
    if (shouldShowAdminInstallPrompt()) {
        initAdminPWAInstall();
    }
});
