/**
 * Enhanced Markdown Learning Content JavaScript
 * Provides interactive functionality for video embeds, file previews, tables, and lightbox
 */

class EnhancedMarkdown {
    constructor() {
        this.lightboxOverlay = null;
        this.init();
    }

    init() {
        this.setupLightbox();
        this.setupEnhancedTables();
        this.setupFileEmbeds();
        this.setupVideoEmbeds();
        this.setupCollapsibleSections();
        this.setupKeyboardNavigation();
    }

    /**
     * Setup lightbox functionality for images
     */
    setupLightbox() {
        // Create lightbox overlay
        this.lightboxOverlay = document.createElement('div');
        this.lightboxOverlay.className = 'lightbox-overlay';
        this.lightboxOverlay.innerHTML = `
            <div class="lightbox-content">
                <span class="lightbox-close">&times;</span>
                <img class="lightbox-image" src="" alt="">
            </div>
        `;
        document.body.appendChild(this.lightboxOverlay);

        // Add click handlers for image zoom buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.image-zoom-button')) {
                e.preventDefault();
                const imageUrl = e.target.dataset.lightboxUrl;
                const altText = e.target.closest('.image-embed-wrapper').querySelector('img').alt;
                this.openLightbox(imageUrl, altText);
            }
        });

        // Close lightbox handlers
        this.lightboxOverlay.addEventListener('click', (e) => {
            if (e.target === this.lightboxOverlay || e.target.matches('.lightbox-close')) {
                this.closeLightbox();
            }
        });

        // Keyboard navigation for lightbox
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.lightboxOverlay.classList.contains('active')) {
                this.closeLightbox();
            }
        });
    }

    openLightbox(imageUrl, altText) {
        const lightboxImage = this.lightboxOverlay.querySelector('.lightbox-image');
        lightboxImage.src = imageUrl;
        lightboxImage.alt = altText;
        this.lightboxOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Focus on the close button for accessibility
        this.lightboxOverlay.querySelector('.lightbox-close').focus();
    }

    closeLightbox() {
        this.lightboxOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    /**
     * Setup enhanced table functionality
     */
    setupEnhancedTables() {
        document.addEventListener('click', (e) => {
            // Handle table sorting
            if (e.target.matches('.table-header-cell[data-sortable-column]')) {
                this.sortTable(e.target);
            }

            // Handle table reset
            if (e.target.matches('[data-table-reset]')) {
                this.resetTableSort(e.target);
            }
        });

        // Setup table search
        document.addEventListener('input', (e) => {
            if (e.target.matches('[data-table-search]')) {
                this.searchTable(e.target);
            }
        });
    }

    sortTable(headerCell) {
        const table = headerCell.closest('.enhanced-table');
        const tbody = table.querySelector('tbody');
        const columnIndex = Array.from(headerCell.parentElement.children).indexOf(headerCell);

        // Determine sort direction
        let direction = 'asc';
        if (headerCell.dataset.sort === 'asc') {
            direction = 'desc';
        }

        // Clear all sort indicators
        table.querySelectorAll('.table-header-cell').forEach(cell => {
            delete cell.dataset.sort;
        });

        // Set current sort
        headerCell.dataset.sort = direction;

        // Get all rows and sort them
        const rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const cellA = a.children[columnIndex]?.textContent.trim() || '';
            const cellB = b.children[columnIndex]?.textContent.trim() || '';

            // Try to parse as numbers
            const numA = parseFloat(cellA);
            const numB = parseFloat(cellB);

            if (!isNaN(numA) && !isNaN(numB)) {
                return direction === 'asc' ? numA - numB : numB - numA;
            }

            // Sort as strings
            return direction === 'asc'
                ? cellA.localeCompare(cellB)
                : cellB.localeCompare(cellA);
        });

        // Reorder rows in the table
        rows.forEach(row => tbody.appendChild(row));

        // Announce sort to screen readers
        this.announceToScreenReader(`Table sorted by ${headerCell.textContent} in ${direction}ending order`);
    }

    resetTableSort(resetButton) {
        const tableContainer = resetButton.closest('.enhanced-table-container');
        const table = tableContainer.querySelector('.enhanced-table');

        // Clear all sort indicators
        table.querySelectorAll('.table-header-cell').forEach(cell => {
            delete cell.dataset.sort;
        });

        // Reset table to original order (we'll need to store original order)
        // For now, just clear the sort indicators
        this.announceToScreenReader('Table sort reset');
    }

    searchTable(searchInput) {
        const tableContainer = searchInput.closest('.enhanced-table-container') ||
                              searchInput.closest('.table-controls').nextElementSibling;
        const table = tableContainer.querySelector('.enhanced-table');
        const tbody = table.querySelector('tbody');
        const searchTerm = searchInput.value.toLowerCase();

        let visibleRows = 0;
        tbody.querySelectorAll('tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            const isVisible = text.includes(searchTerm);
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleRows++;
        });

        // Update search results count
        this.updateSearchResults(searchInput, visibleRows);
    }

    updateSearchResults(searchInput, count) {
        const wrapper = searchInput.closest('.table-search-wrapper');
        let resultsElement = wrapper.querySelector('.search-results');

        if (!resultsElement) {
            resultsElement = document.createElement('div');
            resultsElement.className = 'search-results text-xs text-gray-600 mt-1';
            wrapper.appendChild(resultsElement);
        }

        resultsElement.textContent = count > 0 ? `${count} result(s) found` : 'No results found';
    }

    /**
     * Setup file embed functionality
     */
    setupFileEmbeds() {
        document.addEventListener('click', (e) => {
            // Handle PDF preview toggle
            if (e.target.matches('.pdf-preview-toggle')) {
                e.preventDefault();
                this.togglePdfPreview(e.target);
            }
        });
    }

    togglePdfPreview(button) {
        const pdfEmbed = button.closest('.pdf-embed');
        const previewWrapper = pdfEmbed.querySelector('.pdf-preview-wrapper');
        const isHidden = previewWrapper.style.display === 'none';

        if (isHidden) {
            previewWrapper.style.display = 'block';
            button.textContent = '👁️ Hide Preview';
        } else {
            previewWrapper.style.display = 'none';
            button.textContent = '👁️ Preview PDF';
        }
    }

    /**
     * Setup video embed functionality
     */
    setupVideoEmbeds() {
        // Add play/pause controls for video files
        document.addEventListener('click', (e) => {
            if (e.target.matches('.video-player')) {
                const video = e.target;
                if (video.paused) {
                    video.play();
                } else {
                    video.pause();
                }
            }
        });

        // Lazy load video embeds for better performance
        this.setupLazyLoading();
    }

    setupLazyLoading() {
        const videoContainers = document.querySelectorAll('.video-embed-container[data-video-id]');

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        this.loadVideoEmbed(entry.target);
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                rootMargin: '50px'
            });

            videoContainers.forEach(container => {
                observer.observe(container);
            });
        } else {
            // Fallback for browsers without IntersectionObserver
            videoContainers.forEach(container => {
                this.loadVideoEmbed(container);
            });
        }
    }

    loadVideoEmbed(container) {
        const iframe = container.querySelector('iframe');
        if (iframe && !iframe.src) {
            const videoType = container.dataset.videoType;
            const videoId = container.dataset.videoId;

            if (videoType === 'youtube') {
                iframe.src = `https://www.youtube.com/embed/${videoId}?rel=0`;
            } else if (videoType === 'vimeo') {
                iframe.src = `https://player.vimeo.com/video/${videoId}`;
            }
        }
    }

    /**
     * Setup collapsible sections
     */
    setupCollapsibleSections() {
        // Enhanced keyboard support for details elements
        document.addEventListener('keydown', (e) => {
            if (e.target.matches('.collapsible-section-title') && (e.key === 'Enter' || e.key === ' ')) {
                e.preventDefault();
                const details = e.target.closest('.collapsible-section');
                details.open = !details.open;
            }
        });

        // Smooth opening/closing animation
        document.addEventListener('toggle', (e) => {
            if (e.target.matches('.collapsible-section')) {
                const content = e.target.querySelector('.collapsible-section-content');
                if (e.target.open) {
                    this.slideDown(content);
                } else {
                    this.slideUp(content);
                }
            }
        });
    }

    slideDown(element) {
        element.style.height = '0px';
        element.style.overflow = 'hidden';
        element.style.transition = 'height 0.3s ease';

        requestAnimationFrame(() => {
            element.style.height = element.scrollHeight + 'px';

            element.addEventListener('transitionend', function onTransitionEnd() {
                element.style.height = 'auto';
                element.style.overflow = 'visible';
                element.removeEventListener('transitionend', onTransitionEnd);
            });
        });
    }

    slideUp(element) {
        element.style.height = element.scrollHeight + 'px';
        element.style.overflow = 'hidden';
        element.style.transition = 'height 0.3s ease';

        requestAnimationFrame(() => {
            element.style.height = '0px';
        });
    }

    /**
     * Setup keyboard navigation improvements
     */
    setupKeyboardNavigation() {
        // Improve tab navigation for interactive elements
        document.addEventListener('keydown', (e) => {
            // Navigate tables with arrow keys when focused
            if (e.target.matches('.table-header-cell')) {
                this.handleTableNavigation(e);
            }
        });
    }

    handleTableNavigation(e) {
        const currentCell = e.target;
        const row = currentCell.parentElement;
        const table = currentCell.closest('.enhanced-table');
        const cells = Array.from(row.children);
        const currentIndex = cells.indexOf(currentCell);

        let nextCell = null;

        switch (e.key) {
            case 'ArrowLeft':
                e.preventDefault();
                nextCell = cells[currentIndex - 1];
                break;
            case 'ArrowRight':
                e.preventDefault();
                nextCell = cells[currentIndex + 1];
                break;
            case 'ArrowUp':
                e.preventDefault();
                const prevRow = row.previousElementSibling;
                if (prevRow) {
                    nextCell = prevRow.children[currentIndex];
                }
                break;
            case 'ArrowDown':
                e.preventDefault();
                const nextRow = row.nextElementSibling;
                if (nextRow) {
                    nextCell = nextRow.children[currentIndex];
                }
                break;
        }

        if (nextCell) {
            nextCell.focus();
        }
    }

    /**
     * Announce messages to screen readers
     */
    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;

        document.body.appendChild(announcement);

        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    /**
     * Utility method to check if reduced motion is preferred
     */
    prefersReducedMotion() {
        return window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /**
     * Initialize analytics tracking for educational content
     */
    setupAnalytics() {
        // Track video interactions
        document.addEventListener('click', (e) => {
            if (e.target.matches('.video-embed-link')) {
                this.trackEvent('video_external_link_click', {
                    video_type: e.target.closest('[data-video-type]')?.dataset.videoType,
                    video_id: e.target.closest('[data-video-id]')?.dataset.videoId
                });
            }

            if (e.target.matches('.file-embed a[download]')) {
                this.trackEvent('file_download', {
                    file_type: e.target.closest('[data-file-type]')?.dataset.fileType,
                    file_name: e.target.download
                });
            }
        });

        // Track collapsible section usage
        document.addEventListener('toggle', (e) => {
            if (e.target.matches('.collapsible-section')) {
                this.trackEvent('collapsible_section_toggle', {
                    action: e.target.open ? 'open' : 'close',
                    title: e.target.querySelector('.collapsible-section-title')?.textContent
                });
            }
        });
    }

    trackEvent(eventName, properties = {}) {
        // Implement your analytics tracking here
        // Example: analytics.track(eventName, properties);
        console.log('Analytics Event:', eventName, properties);
    }
}

// Initialize enhanced markdown functionality when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new EnhancedMarkdown();
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = EnhancedMarkdown;
}