/**
 * Regional formatting helpers for JavaScript
 * Provides client-side formatting based on user preferences
 */

class RegionalFormatter {
    constructor(options = {}) {
        this.options = {
            dateFormat: 'm/d/Y',
            timeFormat: 'g:i A',
            dateTimeFormat: 'm/d/Y g:i A',
            weekStartsMonday: false,
            use24Hour: false,
            regionFormat: 'us',
            ...options
        };

        // Convert PHP date format to JavaScript options
        this.jsDateOptions = this.convertPhpFormatToJs(this.options.dateFormat);
        this.jsTimeOptions = this.convertPhpTimeFormatToJs(this.options.timeFormat);
    }

    /**
     * Set new formatting options
     */
    setOptions(options) {
        this.options = { ...this.options, ...options };
        this.jsDateOptions = this.convertPhpFormatToJs(this.options.dateFormat);
        this.jsTimeOptions = this.convertPhpTimeFormatToJs(this.options.timeFormat);
    }

    /**
     * Format a date according to user preferences
     */
    formatDate(date) {
        if (!date) return '';

        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return '';

        // Use browser's locale formatting based on user preferences
        if (this.options.regionFormat === 'us') {
            return d.toLocaleDateString('en-US');
        } else if (this.options.regionFormat === 'eu') {
            return d.toLocaleDateString('de-DE');
        }

        return d.toLocaleDateString();
    }

    /**
     * Format a time according to user preferences
     */
    formatTime(date) {
        if (!date) return '';

        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return '';

        const timeOptions = {
            hour: '2-digit',
            minute: '2-digit',
            hour12: !this.options.use24Hour
        };

        return d.toLocaleTimeString(undefined, timeOptions);
    }

    /**
     * Format a datetime according to user preferences
     */
    formatDateTime(date) {
        if (!date) return '';

        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return '';

        return this.formatDate(d) + ' ' + this.formatTime(d);
    }

    /**
     * Format date for display with relative time
     */
    formatRelativeDate(date) {
        if (!date) return '';

        const d = date instanceof Date ? date : new Date(date);
        if (isNaN(d.getTime())) return '';

        const now = new Date();
        const diffInDays = Math.floor((d - now) / (1000 * 60 * 60 * 24));

        if (diffInDays === 0) {
            return 'Today ' + this.formatTime(d);
        } else if (diffInDays === 1) {
            return 'Tomorrow ' + this.formatTime(d);
        } else if (diffInDays === -1) {
            return 'Yesterday ' + this.formatTime(d);
        } else if (Math.abs(diffInDays) <= 7) {
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            return dayNames[d.getDay()] + ' ' + this.formatTime(d);
        }

        return this.formatDateTime(d);
    }

    /**
     * Get week days array starting from user's preferred week start
     */
    getWeekDays(short = false) {
        const longDays = this.options.weekStartsMonday
            ? ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']
            : ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

        const shortDays = this.options.weekStartsMonday
            ? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
            : ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

        return short ? shortDays : longDays;
    }

    /**
     * Get start of week for a given date
     */
    getWeekStart(date = null) {
        const d = date ? new Date(date) : new Date();
        const day = d.getDay();
        const diff = this.options.weekStartsMonday
            ? (day === 0 ? -6 : 1 - day) // Monday = 1
            : -day; // Sunday = 0

        const weekStart = new Date(d);
        weekStart.setDate(d.getDate() + diff);
        weekStart.setHours(0, 0, 0, 0);
        return weekStart;
    }

    /**
     * Get end of week for a given date
     */
    getWeekEnd(date = null) {
        const weekStart = this.getWeekStart(date);
        const weekEnd = new Date(weekStart);
        weekEnd.setDate(weekStart.getDate() + 6);
        weekEnd.setHours(23, 59, 59, 999);
        return weekEnd;
    }

    /**
     * Format a date range
     */
    formatDateRange(startDate, endDate) {
        if (!startDate || !endDate) return '';

        const start = startDate instanceof Date ? startDate : new Date(startDate);
        const end = endDate instanceof Date ? endDate : new Date(endDate);

        if (isNaN(start.getTime()) || isNaN(end.getTime())) return '';

        // If same day, show date once with time range
        if (start.toDateString() === end.toDateString()) {
            return this.formatDate(start) + ' ' + this.formatTime(start) + ' - ' + this.formatTime(end);
        }

        // Different days, show full date range
        return this.formatDateTime(start) + ' - ' + this.formatDateTime(end);
    }

    /**
     * Parse a date string and convert to user's timezone
     */
    parseDate(dateString) {
        if (!dateString) return null;
        return new Date(dateString);
    }

    /**
     * Convert PHP date format to JavaScript Intl options (simplified)
     */
    convertPhpFormatToJs(phpFormat) {
        // This is a simplified conversion for common formats
        const formatMap = {
            'm/d/Y': { month: '2-digit', day: '2-digit', year: 'numeric' },
            'd/m/Y': { day: '2-digit', month: '2-digit', year: 'numeric' },
            'd.m.Y': { day: '2-digit', month: '2-digit', year: 'numeric' },
            'Y-m-d': { year: 'numeric', month: '2-digit', day: '2-digit' }
        };

        return formatMap[phpFormat] || { month: '2-digit', day: '2-digit', year: 'numeric' };
    }

    /**
     * Convert PHP time format to JavaScript Intl options (simplified)
     */
    convertPhpTimeFormatToJs(phpFormat) {
        if (phpFormat.includes('A') || phpFormat.includes('a')) {
            return { hour: '2-digit', minute: '2-digit', hour12: true };
        } else {
            return { hour: '2-digit', minute: '2-digit', hour12: false };
        }
    }
}

// Global formatter instance
let regionalFormatter = null;

/**
 * Initialize regional formatting with user preferences
 */
function initRegionalFormatting(options = {}) {
    regionalFormatter = new RegionalFormatter(options);

    // Make it available globally
    window.regionalFormatter = regionalFormatter;

    // Add helper functions to window for easy access
    window.formatDate = (date) => regionalFormatter.formatDate(date);
    window.formatTime = (date) => regionalFormatter.formatTime(date);
    window.formatDateTime = (date) => regionalFormatter.formatDateTime(date);
    window.formatRelativeDate = (date) => regionalFormatter.formatRelativeDate(date);
    window.formatDateRange = (start, end) => regionalFormatter.formatDateRange(start, end);
    window.getWeekDays = (short) => regionalFormatter.getWeekDays(short);
    window.getWeekStart = (date) => regionalFormatter.getWeekStart(date);
    window.getWeekEnd = (date) => regionalFormatter.getWeekEnd(date);
}

/**
 * Update regional formatting options
 */
function updateRegionalFormatting(options) {
    if (regionalFormatter) {
        regionalFormatter.setOptions(options);
    } else {
        initRegionalFormatting(options);
    }
}

// Auto-initialize when DOM is ready if options are available
document.addEventListener('DOMContentLoaded', function() {
    // Check if user format options are available in a global variable
    if (typeof window.userFormatOptions !== 'undefined') {
        initRegionalFormatting(window.userFormatOptions);
    }
});

// Export for ES6 modules
export { RegionalFormatter, initRegionalFormatting, updateRegionalFormatting };