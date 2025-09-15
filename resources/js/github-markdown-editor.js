// GitHub-style Markdown Editor Component for Alpine.js
// Enhanced with drag-drop upload, clipboard paste, and live preview

window.githubMarkdownEditor = () => ({
    // Component state
    content: '',
    format: 'markdown',
    isPreviewMode: false,
    isMobile: false,
    topicId: null,

    // Split view state
    showSplitView: true,
    splitViewDirection: 'horizontal', // 'horizontal' or 'vertical'

    // Upload state
    uploadProgress: {},
    uploadQueue: [],

    // Preview state
    previewHtml: '',
    isPreviewLoading: false,

    // Editor state
    editorElement: null,

    // Initialize the component
    init() {
        this.topicId = this.$el.dataset.topicId;
        this.content = this.$el.dataset.content || '';
        this.format = this.$el.dataset.format || 'markdown';

        // Check if mobile
        this.isMobile = window.innerWidth < 768;

        // Set initial split view state
        this.showSplitView = !this.isMobile;

        // Watch for window resize
        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth < 768;
            if (this.isMobile) {
                this.showSplitView = false;
                this.isPreviewMode = false;
            }
        });

        this.$nextTick(() => {
            this.setupEditor();
            this.setupDragDrop();
            this.setupClipboardPaste();
            this.updatePreview();
        });
    },

    // Setup the editor
    setupEditor() {
        this.editorElement = this.$refs.markdownEditor;

        if (!this.editorElement) return;

        // Auto-resize functionality
        this.editorElement.addEventListener('input', () => {
            this.content = this.editorElement.value;
            this.debounceUpdatePreview();
            this.autoResize();
        });

        // Handle tab key for indentation
        this.editorElement.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                e.preventDefault();
                this.insertAtCursor('  '); // 2 spaces
            }
        });

        this.autoResize();
    },

    // Auto-resize textarea
    autoResize() {
        if (!this.editorElement) return;

        this.editorElement.style.height = 'auto';
        this.editorElement.style.height = Math.max(200, this.editorElement.scrollHeight) + 'px';
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
        const placeholder = `![Uploading ${this.uploadProgress[uploadId].filename}...](uploading:${uploadId})`;
        this.content = this.content.replace(placeholder, newContent);
        this.editorElement.value = this.content;
        this.updatePreview();
    },

    // Update placeholder with progress
    updatePlaceholderProgress(uploadId, progress) {
        const oldPlaceholder = `![Uploading ${this.uploadProgress[uploadId].filename}...](uploading:${uploadId})`;
        const newPlaceholder = `![Uploading ${this.uploadProgress[uploadId].filename}... ${progress}%](uploading:${uploadId})`;

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

    // Debounced preview update
    debounceUpdatePreview: null,

    // Update preview (with debouncing)
    updatePreview() {
        if (this.debounceUpdatePreview) {
            clearTimeout(this.debounceUpdatePreview);
        }

        this.debounceUpdatePreview = setTimeout(() => {
            this.renderPreview();
        }, 300);
    },

    // Render markdown preview
    async renderPreview() {
        if (!this.content || !this.showSplitView) return;

        this.isPreviewLoading = true;

        try {
            const response = await fetch('/topics/content/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: this.content,
                    format: 'markdown'
                })
            });

            if (response.ok) {
                const html = await response.text();
                this.previewHtml = html;
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
            hr: '\n---\n'
        };

        const text = formats[type];
        if (text) {
            this.insertAtCursor(text);
        }
    },

    // Show notification
    showNotification(message, type = 'info') {
        // Simple notification - in production you might use a toast library
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg z-50 transition-all duration-300 ${
            type === 'error' ? 'bg-red-500 text-white' :
            type === 'success' ? 'bg-green-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
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

    // Cleanup function
    destroy() {
        if (this.debounceUpdatePreview) {
            clearTimeout(this.debounceUpdatePreview);
        }
    }
});