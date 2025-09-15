// Unified Markdown Editor with Real-time Live Preview
// Phase 4: Enhanced performance, scroll sync, and seamless integration

window.unifiedMarkdownEditor = () => ({
    // Component state
    content: '',
    format: 'markdown',
    isPreviewMode: false,
    isMobile: false,
    topicId: null,

    // Split view state
    showSplitView: true,
    splitViewDirection: 'horizontal', // 'horizontal' or 'vertical'
    previewWidth: 50, // Percentage

    // Upload state
    uploadProgress: {},
    uploadQueue: [],

    // Preview state
    previewHtml: '',
    isPreviewLoading: false,
    lastPreviewContent: '',
    previewCache: new Map(),

    // Editor state
    editorElement: null,
    previewElement: null,
    scrollSyncEnabled: true,
    isScrollingEditor: false,
    isScrollingPreview: false,

    // Performance state
    renderDebouncer: null,
    renderDelay: 300, // Reduced from 500ms for better responsiveness
    performanceMode: 'auto', // 'auto', 'fast', 'quality'

    // Syntax highlighting state
    syntaxHighlighting: true,

    // Initialize the component
    init() {
        this.topicId = this.$el.dataset.topicId;
        this.content = this.$el.dataset.content || '';
        this.format = this.$el.dataset.format || 'markdown';

        // Detect performance mode based on content length
        this.detectPerformanceMode();

        // Check if mobile
        this.isMobile = window.innerWidth < 768;

        // Set initial split view state
        this.showSplitView = !this.isMobile;

        // Watch for window resize
        window.addEventListener('resize', this.handleResize.bind(this));

        this.$nextTick(() => {
            this.setupEditor();
            this.setupPreview();
            this.setupDragDrop();
            this.setupClipboardPaste();
            this.setupScrollSync();
            this.setupKeyboardShortcuts();
            this.updatePreview();
        });
    },

    // Handle window resize
    handleResize() {
        const wasMobile = this.isMobile;
        this.isMobile = window.innerWidth < 768;

        if (wasMobile !== this.isMobile) {
            if (this.isMobile) {
                this.showSplitView = false;
                this.isPreviewMode = false;
            } else {
                this.showSplitView = true;
            }
        }
    },

    // Detect optimal performance mode
    detectPerformanceMode() {
        const contentLength = this.content.length;

        if (contentLength > 10000) {
            this.performanceMode = 'fast';
            this.renderDelay = 500;
        } else if (contentLength > 5000) {
            this.performanceMode = 'auto';
            this.renderDelay = 300;
        } else {
            this.performanceMode = 'quality';
            this.renderDelay = 150;
        }
    },

    // Setup the editor with enhanced features
    setupEditor() {
        this.editorElement = this.$refs.markdownEditor;

        if (!this.editorElement) return;

        // Enhanced auto-resize functionality
        this.editorElement.addEventListener('input', (e) => {
            this.content = this.editorElement.value;
            this.detectPerformanceMode();
            this.debounceUpdatePreview();
            this.autoResize();
            this.updateContentStats();
        });

        // Handle tab key for indentation with smart indentation
        this.editorElement.addEventListener('keydown', (e) => {
            this.handleKeyDown(e);
        });

        // Handle scroll for sync
        this.editorElement.addEventListener('scroll', () => {
            if (this.scrollSyncEnabled && !this.isScrollingPreview) {
                this.isScrollingEditor = true;
                this.syncScrollToPreview();
                setTimeout(() => { this.isScrollingEditor = false; }, 100);
            }
        });

        this.autoResize();
    },

    // Setup preview panel
    setupPreview() {
        this.previewElement = this.$refs.previewPanel;

        if (!this.previewElement) return;

        // Handle scroll for sync
        this.previewElement.addEventListener('scroll', () => {
            if (this.scrollSyncEnabled && !this.isScrollingEditor) {
                this.isScrollingPreview = true;
                this.syncScrollToEditor();
                setTimeout(() => { this.isScrollingPreview = false; }, 100);
            }
        });
    },

    // Enhanced keyboard handling
    handleKeyDown(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            this.handleTabIndentation(e.shiftKey);
        } else if (e.ctrlKey || e.metaKey) {
            this.handleCtrlShortcuts(e);
        }
    },

    // Handle tab indentation with smart behavior
    handleTabIndentation(isShift) {
        const start = this.editorElement.selectionStart;
        const end = this.editorElement.selectionEnd;
        const value = this.editorElement.value;

        if (start === end) {
            // Single cursor - insert or remove indentation
            if (isShift) {
                // Remove indentation
                const lineStart = value.lastIndexOf('\n', start - 1) + 1;
                const linePrefix = value.substring(lineStart, start);
                if (linePrefix.startsWith('  ')) {
                    this.editorElement.setRangeText('', lineStart, lineStart + 2);
                    this.editorElement.setSelectionRange(start - 2, start - 2);
                }
            } else {
                // Add indentation
                this.insertAtCursor('  ');
            }
        } else {
            // Selection - indent/unindent multiple lines
            this.indentSelection(isShift);
        }
    },

    // Indent or unindent selected lines
    indentSelection(isUnindent) {
        const start = this.editorElement.selectionStart;
        const end = this.editorElement.selectionEnd;
        const value = this.editorElement.value;

        const lineStart = value.lastIndexOf('\n', start - 1) + 1;
        const lineEnd = value.indexOf('\n', end);
        const endPos = lineEnd === -1 ? value.length : lineEnd;

        const selectedLines = value.substring(lineStart, endPos);
        const lines = selectedLines.split('\n');

        const processedLines = lines.map(line => {
            if (isUnindent) {
                return line.startsWith('  ') ? line.substring(2) : line;
            } else {
                return '  ' + line;
            }
        });

        const newText = processedLines.join('\n');
        this.editorElement.setRangeText(newText, lineStart, endPos);

        const offset = isUnindent ? -2 : 2;
        this.editorElement.setSelectionRange(
            start + (isUnindent && value.substring(lineStart, start).startsWith('  ') ? -2 : 0),
            end + (offset * lines.length)
        );
    },

    // Handle Ctrl/Cmd shortcuts
    handleCtrlShortcuts(e) {
        switch (e.key.toLowerCase()) {
            case 'b':
                e.preventDefault();
                this.wrapSelection('**', '**');
                break;
            case 'i':
                e.preventDefault();
                this.wrapSelection('*', '*');
                break;
            case 'k':
                e.preventDefault();
                this.insertLink();
                break;
            case 's':
                e.preventDefault();
                this.saveContent();
                break;
            case '/':
                e.preventDefault();
                this.togglePreview();
                break;
        }
    },

    // Setup keyboard shortcuts
    setupKeyboardShortcuts() {
        // Additional shortcuts can be added here
        document.addEventListener('keydown', (e) => {
            if (e.altKey && e.key === 'p') {
                e.preventDefault();
                this.toggleSplitView();
            }
        });
    },

    // Wrap selected text with markdown syntax
    wrapSelection(prefix, suffix) {
        const start = this.editorElement.selectionStart;
        const end = this.editorElement.selectionEnd;
        const selectedText = this.editorElement.value.substring(start, end);
        const replacement = prefix + selectedText + suffix;

        this.editorElement.setRangeText(replacement, start, end);

        if (selectedText === '') {
            // Position cursor between the wrappers
            const newPos = start + prefix.length;
            this.editorElement.setSelectionRange(newPos, newPos);
        } else {
            // Select the wrapped text
            this.editorElement.setSelectionRange(start, start + replacement.length);
        }

        this.editorElement.focus();
        this.content = this.editorElement.value;
        this.debounceUpdatePreview();
    },

    // Insert link with prompt
    insertLink() {
        const selectedText = this.getSelectedText();
        const linkText = selectedText || 'Link text';
        const linkUrl = prompt('Enter URL:', 'https://');

        if (linkUrl) {
            const markdown = `[${linkText}](${linkUrl})`;
            this.insertAtCursor(markdown);
        }
    },

    // Get selected text
    getSelectedText() {
        const start = this.editorElement.selectionStart;
        const end = this.editorElement.selectionEnd;
        return this.editorElement.value.substring(start, end);
    },

    // Auto-resize textarea with improved calculation
    autoResize() {
        if (!this.editorElement) return;

        this.editorElement.style.height = 'auto';
        const scrollHeight = this.editorElement.scrollHeight;
        const minHeight = 300;
        const maxHeight = window.innerHeight * 0.6;

        this.editorElement.style.height = Math.min(Math.max(minHeight, scrollHeight), maxHeight) + 'px';
    },

    // Setup drag and drop functionality
    setupDragDrop() {
        const dropZone = this.$refs.editorContainer;

        if (!dropZone) return;

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });
        });

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('drag-over');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('drag-over');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer.files);
            this.handleFileUploads(files);
        });
    },

    // Setup clipboard paste functionality
    setupClipboardPaste() {
        this.editorElement?.addEventListener('paste', (e) => {
            const items = Array.from(e.clipboardData.items);
            const files = items
                .filter(item => item.type.startsWith('image/'))
                .map(item => item.getAsFile())
                .filter(file => file);

            if (files.length > 0) {
                e.preventDefault();
                this.handleFileUploads(files);
            }
        });
    },

    // Setup scroll synchronization
    setupScrollSync() {
        // Scroll sync will be implemented in the scroll event handlers
        // This is set up in setupEditor and setupPreview
    },

    // Synchronize editor scroll to preview
    syncScrollToPreview() {
        if (!this.previewElement || !this.editorElement) return;

        const editorScrollPercent = this.editorElement.scrollTop /
            (this.editorElement.scrollHeight - this.editorElement.clientHeight);

        const previewScrollTop = editorScrollPercent *
            (this.previewElement.scrollHeight - this.previewElement.clientHeight);

        this.previewElement.scrollTop = previewScrollTop;
    },

    // Synchronize preview scroll to editor
    syncScrollToEditor() {
        if (!this.previewElement || !this.editorElement) return;

        const previewScrollPercent = this.previewElement.scrollTop /
            (this.previewElement.scrollHeight - this.previewElement.clientHeight);

        const editorScrollTop = previewScrollPercent *
            (this.editorElement.scrollHeight - this.editorElement.clientHeight);

        this.editorElement.scrollTop = editorScrollTop;
    },

    // Toggle scroll synchronization
    toggleScrollSync() {
        this.scrollSyncEnabled = !this.scrollSyncEnabled;
        this.showNotification(
            `Scroll sync ${this.scrollSyncEnabled ? 'enabled' : 'disabled'}`,
            'info'
        );
    },

    // Handle multiple file uploads
    async handleFileUploads(files) {
        for (const file of files) {
            await this.uploadFile(file);
        }
    },

    // Upload a single file with progress tracking
    async uploadFile(file) {
        const uploadId = this.generateUploadId();
        const fileType = this.detectFileType(file);

        // Insert placeholder immediately
        const placeholder = this.generateUploadPlaceholder(file.name, uploadId);
        this.insertAtCursor(placeholder);

        // Initialize progress tracking
        this.uploadProgress[uploadId] = {
            progress: 0,
            status: 'uploading',
            filename: file.name
        };

        try {
            // Create FormData
            const formData = new FormData();
            formData.append('file', file);
            formData.append('type', fileType);

            // Add CSRF token
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                formData.append('_token', token);
            }

            // Upload with progress tracking
            const response = await this.uploadWithProgress(formData, uploadId);

            if (response.success) {
                // Replace placeholder with actual markdown
                this.replacePlaceholder(uploadId, response.markdown);
                this.uploadProgress[uploadId].status = 'completed';
                this.showNotification(`File uploaded: ${file.name}`, 'success');
            } else {
                throw new Error(response.error || 'Upload failed');
            }

        } catch (error) {
            console.error('Upload error:', error);
            this.uploadProgress[uploadId].status = 'error';

            // Replace placeholder with error message
            const errorMarkdown = `❌ **Upload failed:** ${file.name} (${error.message})`;
            this.replacePlaceholder(uploadId, errorMarkdown);

            // Show user notification
            this.showNotification('Upload failed: ' + error.message, 'error');
        }

        // Clean up progress tracking after a delay
        setTimeout(() => {
            delete this.uploadProgress[uploadId];
        }, 5000);
    },

    // Upload with progress tracking
    uploadWithProgress(formData, uploadId) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();

            // Progress tracking
            xhr.upload.addEventListener('progress', (e) => {
                if (e.lengthComputable) {
                    const progress = Math.round((e.loaded / e.total) * 100);
                    this.uploadProgress[uploadId].progress = progress;
                    this.updatePlaceholderProgress(uploadId, progress);
                }
            });

            // Response handling
            xhr.addEventListener('load', () => {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response);
                    } catch (e) {
                        reject(new Error('Invalid response format'));
                    }
                } else {
                    try {
                        const error = JSON.parse(xhr.responseText);
                        reject(new Error(error.error || 'Upload failed'));
                    } catch (e) {
                        reject(new Error(`HTTP ${xhr.status}: ${xhr.statusText}`));
                    }
                }
            });

            xhr.addEventListener('error', () => {
                reject(new Error('Network error'));
            });

            xhr.open('POST', `/topics/${this.topicId}/markdown-upload`);
            xhr.send(formData);
        });
    },

    // Detect file type for upload validation
    detectFileType(file) {
        const mimeType = file.type;

        if (mimeType.startsWith('image/')) return 'image';
        if (mimeType.startsWith('video/')) return 'video';
        if (mimeType.startsWith('audio/')) return 'audio';
        return 'document';
    },

    // Generate unique upload ID
    generateUploadId() {
        return 'upload_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    // Generate upload placeholder
    generateUploadPlaceholder(filename, uploadId) {
        return `\n![Uploading ${filename}...](uploading:${uploadId})\n`;
    },

    // Replace upload placeholder with actual content
    replacePlaceholder(uploadId, newContent) {
        const filename = this.uploadProgress[uploadId]?.filename || 'file';
        const placeholder = `![Uploading ${filename}...](uploading:${uploadId})`;
        this.content = this.content.replace(placeholder, newContent);
        this.editorElement.value = this.content;
        this.updatePreview();
    },

    // Update placeholder with progress
    updatePlaceholderProgress(uploadId, progress) {
        const filename = this.uploadProgress[uploadId]?.filename || 'file';
        const oldPlaceholder = `![Uploading ${filename}...](uploading:${uploadId})`;
        const newPlaceholder = `![Uploading ${filename}... ${progress}%](uploading:${uploadId})`;

        this.content = this.content.replace(oldPlaceholder, newPlaceholder);
        this.editorElement.value = this.content;
    },

    // Insert text at cursor position
    insertAtCursor(text) {
        if (!this.editorElement) return;

        const start = this.editorElement.selectionStart;
        const end = this.editorElement.selectionEnd;
        const currentValue = this.editorElement.value;

        const newValue = currentValue.substring(0, start) + text + currentValue.substring(end);
        this.editorElement.value = newValue;
        this.content = newValue;

        // Set cursor position after inserted text
        const newPosition = start + text.length;
        this.editorElement.focus();
        this.editorElement.setSelectionRange(newPosition, newPosition);

        this.autoResize();
        this.updatePreview();
    },

    // Debounced preview update with smart caching
    updatePreview() {
        if (this.renderDebouncer) {
            clearTimeout(this.renderDebouncer);
        }

        this.renderDebouncer = setTimeout(() => {
            this.renderPreview();
        }, this.renderDelay);
    },

    // Render markdown preview with caching and optimization
    async renderPreview() {
        if (!this.content || !this.showSplitView) {
            this.previewHtml = '<p class="text-gray-500 italic">Start typing to see a preview...</p>';
            return;
        }

        // Check cache first
        const cacheKey = this.generateCacheKey(this.content);
        if (this.previewCache.has(cacheKey)) {
            this.previewHtml = this.previewCache.get(cacheKey);
            return;
        }

        // Skip if content hasn't changed (additional safety)
        if (this.content === this.lastPreviewContent) {
            return;
        }

        this.isPreviewLoading = true;
        this.lastPreviewContent = this.content;

        try {
            const response = await fetch('/topics/content/preview-unified', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: this.content,
                    cache_key: cacheKey,
                    performance_mode: this.performanceMode
                })
            });

            if (response.ok) {
                const html = await response.text();
                this.previewHtml = html;

                // Update performance metadata from headers
                const performanceMode = response.headers.get('X-Performance-Mode');
                const contentLength = response.headers.get('X-Content-Length');
                const cacheKeyResponse = response.headers.get('X-Cache-Key');

                if (performanceMode) {
                    this.performanceMode = performanceMode;
                }

                // Cache the result
                this.previewCache.set(cacheKey, html);

                // Limit cache size to prevent memory issues
                if (this.previewCache.size > 50) {
                    const firstKey = this.previewCache.keys().next().value;
                    this.previewCache.delete(firstKey);
                }

                // Re-enable scroll sync after content update
                this.$nextTick(() => {
                    this.setupPreview();
                });

            } else {
                this.previewHtml = '<p class="text-red-500">Preview failed to load</p>';
            }
        } catch (error) {
            console.error('Error rendering preview:', error);
            this.previewHtml = '<p class="text-red-500">Preview error: ' + error.message + '</p>';
        } finally {
            this.isPreviewLoading = false;
        }
    },

    // Generate cache key for content
    generateCacheKey(content) {
        // Simple hash function for caching
        let hash = 0;
        for (let i = 0; i < content.length; i++) {
            const char = content.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32-bit integer
        }
        return hash.toString();
    },

    // Toggle between write and preview on mobile
    toggleMobileMode(mode) {
        if (!this.isMobile) return;

        this.isPreviewMode = mode === 'preview';
        if (this.isPreviewMode) {
            this.renderPreview();
        }
    },

    // Toggle split view on desktop
    toggleSplitView() {
        if (this.isMobile) return;

        this.showSplitView = !this.showSplitView;
        if (this.showSplitView) {
            this.renderPreview();
        }
    },

    // Toggle preview (alias for consistency)
    togglePreview() {
        this.toggleSplitView();
    },

    // Adjust split view ratio
    adjustSplitRatio(ratio) {
        this.previewWidth = Math.max(20, Math.min(80, ratio));
    },

    // Insert markdown formatting
    insertMarkdown(type) {
        const formats = {
            bold: '**Bold text**',
            italic: '*Italic text*',
            code: '`code`',
            heading1: '# Heading 1',
            heading2: '## Heading 2',
            heading3: '### Heading 3',
            list: '- List item',
            numberedList: '1. Numbered item',
            link: '[Link text](https://example.com)',
            image: '![Alt text](image-url)',
            quote: '> Quote text',
            codeBlock: '```\nCode block\n```',
            table: '| Header 1 | Header 2 |\n|----------|----------|\n| Cell 1   | Cell 2   |',
            hr: '\n---\n',
            collapsible: '!!! collapse "Title"\n\nContent here\n\n!!!',
            callout: '!!! note "Note"\n\nImportant information\n\n!!!'
        };

        const text = formats[type];
        if (text) {
            this.insertAtCursor(text);
        }
    },

    // Update content stats
    updateContentStats() {
        // This will be used by the template to show real-time stats
        this.detectPerformanceMode();
    },

    // Get word count
    getWordCount() {
        return this.content.split(/\s+/).filter(w => w.length > 0).length;
    },

    // Get character count
    getCharacterCount() {
        return this.content.length;
    },

    // Get reading time estimate
    getReadingTime() {
        const words = this.getWordCount();
        return Math.max(1, Math.ceil(words / 200)); // 200 words per minute
    },

    // Save content
    async saveContent() {
        // Trigger the form submission
        const form = this.$refs.saveForm;
        if (form) {
            // Update hidden field
            const hiddenField = form.querySelector('input[name="learning_content"]');
            if (hiddenField) {
                hiddenField.value = this.content;
            }

            // Submit form
            form.dispatchEvent(new Event('submit'));
            this.showNotification('Content saved!', 'success');
        }
    },

    // Show notification with enhanced styling
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg z-50 transition-all duration-300 shadow-lg ${
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'warning' ? 'bg-yellow-500 text-white' :
            'bg-blue-500 text-white'
        }`;

        notification.innerHTML = `
            <div class="flex items-center space-x-2">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="text-white hover:text-gray-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        document.body.appendChild(notification);

        // Auto-remove after delay
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, 5000);
    },

    // Get upload progress for template
    getUploadProgress() {
        return Object.values(this.uploadProgress);
    },

    // Check if any uploads are in progress
    hasActiveUploads() {
        return Object.values(this.uploadProgress).some(upload => upload.status === 'uploading');
    },

    // Get performance indicators for UI
    getPerformanceMode() {
        return {
            mode: this.performanceMode,
            delay: this.renderDelay,
            cacheSize: this.previewCache.size
        };
    },

    // Clear preview cache
    clearPreviewCache() {
        this.previewCache.clear();
        this.showNotification('Preview cache cleared', 'info');
    },

    // Export content in different formats
    async exportContent(format) {
        try {
            const response = await fetch('/topics/content/export', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: this.content,
                    from_format: 'markdown',
                    to_format: format
                })
            });

            if (response.ok) {
                const result = await response.text();
                this.showNotification(`Content exported to ${format}`, 'success');

                // Create download link
                const blob = new Blob([result], { type: response.headers.get('Content-Type') });
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `content.${format}`;
                a.click();
                window.URL.revokeObjectURL(url);

                return result;
            } else {
                throw new Error('Export failed');
            }
        } catch (error) {
            this.showNotification(`Export failed: ${error.message}`, 'error');
            return null;
        }
    },

    // Get video metadata for enhanced embedding
    async getVideoMetadata(url) {
        try {
            const response = await fetch('/topics/content/video-metadata', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ url })
            });

            if (response.ok) {
                return await response.json();
            } else {
                throw new Error('Failed to fetch video metadata');
            }
        } catch (error) {
            console.error('Video metadata error:', error);
            return { valid: false, error: error.message };
        }
    },

    // Cleanup function
    destroy() {
        if (this.renderDebouncer) {
            clearTimeout(this.renderDebouncer);
        }

        // Clear cache
        this.previewCache.clear();

        // Remove event listeners
        window.removeEventListener('resize', this.handleResize);
    }
});