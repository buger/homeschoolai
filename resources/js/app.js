import './bootstrap';
import 'htmx.org';
import { initRegionalFormatting } from './regional-formatting';
import './rich-content-editor';
import './github-markdown-editor';

import Alpine from 'alpinejs';

// Ensure Alpine is available on window before starting
window.Alpine = Alpine;

// Start Alpine and ensure it's ready
Alpine.start();

// JavaScript Translation Function
function createTranslationFunction() {
    let translations = {};
    let currentLocale = document.documentElement.lang || 'en';
    let translationsLoaded = false;
    
    // Load translations asynchronously
    async function loadTranslations() {
        try {
            const response = await fetch(`/lang/${currentLocale}.json`);
            if (response.ok) {
                translations = await response.json();
                translationsLoaded = true;
                console.log('Translations loaded:', Object.keys(translations).length, 'keys');
            } else {
                console.warn('Failed to load translations:', response.status);
            }
        } catch (error) {
            console.warn('Failed to load translations:', error);
        }
        // Update global references
        window.translations = translations;
    }
    
    // Start loading translations
    loadTranslations();
    
    // Expose translations object globally (initially empty)
    window.translations = translations;
    
    // Translation function
    window.__ = function(key, replacements = {}, fallback = null) {
        let translation = translations[key] || fallback || key;
        
        // Replace placeholders like :name, :email, etc.
        if (typeof translation === 'string' && replacements && typeof replacements === 'object') {
            Object.keys(replacements).forEach(placeholder => {
                const regex = new RegExp(`:${placeholder}|\\{${placeholder}\\}|\\{\\{\\s*${placeholder}\\s*\\}\\}`, 'gi');
                translation = translation.replace(regex, replacements[placeholder]);
            });
        }
        
        return translation;
    };
    
    // For testing: expose the loading status
    window.translationsLoaded = () => translationsLoaded;
}

// Initialize translation function
createTranslationFunction();

// Add HTMX configuration for Laravel CSRF
document.addEventListener('DOMContentLoaded', function() {
    // Initialize regional formatting if user format options are available
    if (typeof window.userFormatOptions !== 'undefined') {
        initRegionalFormatting(window.userFormatOptions);
        console.log('Regional formatting initialized with options:', window.userFormatOptions);
    }

    // Configure HTMX for Laravel
    document.body.addEventListener('htmx:configRequest', function(evt) {
        evt.detail.headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    });
    
    // Add debugging for HTMX requests
    document.body.addEventListener('htmx:beforeRequest', function(evt) {
        console.log('HTMX: Starting request to', evt.detail.requestConfig.url);
    });
    
    document.body.addEventListener('htmx:responseError', function(evt) {
        console.error('HTMX: Response error', evt.detail);
        // Handle authentication redirects
        if (evt.detail.xhr.status === 401 || evt.detail.xhr.responseURL?.includes('/login')) {
            console.warn('HTMX: Authentication required, redirecting to login');
            window.location.href = '/login';
        }
    });
    
    document.body.addEventListener('htmx:sendError', function(evt) {
        console.error('HTMX: Send error', evt.detail);
    });
    
    document.body.addEventListener('htmx:afterRequest', function(evt) {
        console.log('HTMX: Request completed', evt.detail.requestConfig.url, evt.detail.xhr.status);
    });
});
