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
    chunkSize: 1024 * 1024, // 1MB chunks for large files
    maxFileSize: 100 * 1024 * 1024, // 100MB max file size
    retryAttempts: 3,

    // Advanced clipboard state
    clipboardTypes: {
        files: [],
        text: '',
        html: '',
        images: [],
        urls: []
    },

    // File processing state
    ocrEnabled: true,
    metadataExtraction: true,
    duplicateDetection: true,
    smartOptimization: true,

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

    // Enhanced drag and drop functionality with visual feedback
    setupDragDrop() {
        const dropZone = this.$refs.editorContainer;

        if (!dropZone) return;

        // Enhanced drag and drop event handling
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, (e) => {
                e.preventDefault();
                e.stopPropagation();
            });

            // Add global event listener to handle drag from outside
            document.addEventListener(eventName, (e) => {
                if (eventName === 'dragover' || eventName === 'drop') {
                    e.preventDefault();
                }
            });
        });

        // Enhanced drag enter/over with file type detection
        let dragCounter = 0;
        dropZone.addEventListener('dragenter', (e) => {
            dragCounter++;
            this.handleDragEnter(e);
        });

        dropZone.addEventListener('dragover', (e) => {
            this.handleDragOver(e);
        });

        dropZone.addEventListener('dragleave', (e) => {
            dragCounter--;
            if (dragCounter === 0) {
                this.handleDragLeave(e);
            }
        });

        dropZone.addEventListener('drop', (e) => {
            dragCounter = 0;
            this.handleDrop(e);
        });

        // Touch support for mobile drag and drop
        if ('ontouchstart' in window) {
            this.setupTouchDragDrop(dropZone);
        }
    },

    // Handle drag enter with file analysis
    handleDragEnter(e) {
        const dropZone = this.$refs.editorContainer;
        dropZone.classList.add('drag-over');

        // Analyze dragged items
        const items = Array.from(e.dataTransfer.items || []);
        const fileTypes = this.analyzeDraggedItems(items);

        // Update drag overlay with file type information
        this.updateDragOverlay(fileTypes);
    },

    // Handle drag over with visual feedback
    handleDragOver(e) {
        // Provide visual feedback for drop zones
        const rect = this.editorElement.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;

        // Calculate insertion point in editor
        this.updateDropCursor(x, y);
    },

    // Handle drag leave
    handleDragLeave(e) {
        const dropZone = this.$refs.editorContainer;
        dropZone.classList.remove('drag-over');
        this.clearDragOverlay();
    },

    // Enhanced drop handling
    async handleDrop(e) {
        const dropZone = this.$refs.editorContainer;
        dropZone.classList.remove('drag-over');
        this.clearDragOverlay();

        const files = Array.from(e.dataTransfer.files);
        const items = Array.from(e.dataTransfer.items);

        // Handle different drop types
        if (files.length > 0) {
            // File drop
            await this.handleFilesDrop(files, e);
        } else if (e.dataTransfer.getData('text/html')) {
            // HTML content drop
            const html = e.dataTransfer.getData('text/html');
            await this.handleHtmlDrop(html, e);
        } else if (e.dataTransfer.getData('text/plain')) {
            // Text content drop
            const text = e.dataTransfer.getData('text/plain');
            this.handleTextDrop(text, e);
        }
    },

    // Analyze dragged items to show appropriate feedback
    analyzeDraggedItems(items) {
        const types = {
            images: 0,
            videos: 0,
            documents: 0,
            audio: 0,
            folders: 0,
            total: items.length
        };

        items.forEach(item => {
            if (item.type.startsWith('image/')) types.images++;
            else if (item.type.startsWith('video/')) types.videos++;
            else if (item.type.startsWith('audio/')) types.audio++;
            else if (item.type.includes('application/')) types.documents++;
        });

        return types;
    },

    // Update drag overlay with file type information
    updateDragOverlay(fileTypes) {
        const overlay = this.$refs.editorContainer.querySelector('.drag-over-indicator');
        if (!overlay) return;

        let message = 'Drop files here to upload';
        let details = '';

        if (fileTypes.total > 1) {
            details = `${fileTypes.total} files: `;
            const typeParts = [];
            if (fileTypes.images > 0) typeParts.push(`${fileTypes.images} image${fileTypes.images > 1 ? 's' : ''}`);
            if (fileTypes.videos > 0) typeParts.push(`${fileTypes.videos} video${fileTypes.videos > 1 ? 's' : ''}`);
            if (fileTypes.documents > 0) typeParts.push(`${fileTypes.documents} document${fileTypes.documents > 1 ? 's' : ''}`);
            if (fileTypes.audio > 0) typeParts.push(`${fileTypes.audio} audio file${fileTypes.audio > 1 ? 's' : ''}`);
            details += typeParts.join(', ');
        } else if (fileTypes.images > 0) {
            message = 'Drop image to upload and embed';
            details = 'Will be automatically optimized and embedded';
        } else if (fileTypes.videos > 0) {
            message = 'Drop video to upload and embed';
            details = 'Will generate thumbnail and embed player';
        } else if (fileTypes.documents > 0) {
            message = 'Drop document to upload';
            details = 'Will create download link and preview if possible';
        }

        // Update overlay content
        const titleElement = overlay.querySelector('p:first-of-type');
        const detailElement = overlay.querySelector('p:last-of-type');
        if (titleElement) titleElement.textContent = message;
        if (detailElement) detailElement.textContent = details;
    },

    // Clear drag overlay
    clearDragOverlay() {
        // Reset to default state
        const overlay = this.$refs.editorContainer.querySelector('.drag-over-indicator');
        if (!overlay) return;

        const titleElement = overlay.querySelector('p:first-of-type');
        const detailElement = overlay.querySelector('p:last-of-type');
        if (titleElement) titleElement.textContent = 'Drop files here to upload';
        if (detailElement) detailElement.textContent = 'Images, documents, videos, and audio files supported';
    },

    // Update drop cursor position for precise insertion
    updateDropCursor(x, y) {
        // Calculate text position from coordinates
        const textPos = this.getTextPositionFromCoordinates(x, y);
        if (textPos !== null) {
            // Show insertion cursor at position
            this.showInsertionCursor(textPos);
        }
    },

    // Handle files drop with batch processing
    async handleFilesDrop(files, e) {
        // Calculate insertion position
        const insertPos = this.getInsertionPosition(e);

        // Sort files by type for optimal processing order
        const sortedFiles = this.sortFilesByProcessingPriority(files);

        // Show batch upload progress
        this.showBatchUploadProgress(sortedFiles.length);

        // Process files with smart batching
        await this.processBatchUpload(sortedFiles, insertPos);
    },

    // Handle HTML content drop
    async handleHtmlDrop(html, e) {
        try {
            const markdown = await this.convertHtmlToMarkdown(html);
            const insertPos = this.getInsertionPosition(e);
            this.insertAtPosition(markdown, insertPos);
            this.showNotification('HTML content converted to markdown', 'success');
        } catch (error) {
            this.showNotification('Failed to convert HTML content', 'error');
        }
    },

    // Handle text drop with smart formatting
    handleTextDrop(text, e) {
        const insertPos = this.getInsertionPosition(e);

        // Detect and handle URLs
        const urls = this.extractUrls(text);
        if (urls.length > 0) {
            // Handle URL drops with smart embedding
            urls.forEach(urlInfo => {
                this.handleSmartUrlInsertion(urlInfo, insertPos);
            });
        } else {
            // Insert as formatted text
            const formattedText = this.autoFormatText(text);
            this.insertAtPosition(formattedText, insertPos);
        }
    },

    // Setup touch drag and drop for mobile
    setupTouchDragDrop(dropZone) {
        let isDragging = false;
        let dragElement = null;

        dropZone.addEventListener('touchstart', (e) => {
            // Mobile file selection through touch
            if (e.touches.length === 1) {
                this.showMobileFileSelector();
            }
        });

        // Handle file input for mobile
        this.createMobileFileInput();
    },

    // Create mobile file input
    createMobileFileInput() {
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.multiple = true;
        fileInput.accept = 'image/*,video/*,audio/*,.pdf,.doc,.docx,.txt,.md';
        fileInput.style.display = 'none';
        fileInput.id = 'mobile-file-input';

        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            if (files.length > 0) {
                this.handleFileUploads(files);
            }
        });

        document.body.appendChild(fileInput);
    },

    // Show mobile file selector
    showMobileFileSelector() {
        const fileInput = document.getElementById('mobile-file-input');
        if (fileInput) {
            fileInput.click();
        }
    },

    // Enhanced clipboard paste functionality with smart detection
    setupClipboardPaste() {
        this.editorElement?.addEventListener('paste', async (e) => {
            const clipboardData = e.clipboardData;
            const items = Array.from(clipboardData.items);

            // Reset clipboard types
            this.clipboardTypes = {
                files: [],
                text: '',
                html: '',
                images: [],
                urls: []
            };

            // Analyze clipboard content
            await this.analyzeClipboardContent(items, clipboardData);

            // Handle different content types
            if (this.clipboardTypes.images.length > 0) {
                e.preventDefault();
                this.handleClipboardImages();
            } else if (this.clipboardTypes.files.length > 0) {
                e.preventDefault();
                this.handleClipboardFiles();
            } else if (this.clipboardTypes.urls.length > 0) {
                e.preventDefault();
                this.handleClipboardUrls();
            } else if (this.clipboardTypes.html) {
                // Handle rich text paste with smart formatting
                e.preventDefault();
                this.handleClipboardHtml();
            } else if (this.clipboardTypes.text) {
                // Let default text paste happen, but enhance with smart formatting
                this.handleSmartTextPaste();
            }
        });

        // Handle keyboard shortcuts for enhanced clipboard operations
        this.editorElement?.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'v') {
                e.preventDefault();
                this.showClipboardPreview();
            }
        });
    },

    // Analyze clipboard content to determine types
    async analyzeClipboardContent(items, clipboardData) {
        for (const item of items) {
            if (item.type.startsWith('image/')) {
                const file = item.getAsFile();
                if (file) {
                    this.clipboardTypes.images.push(file);
                }
            } else if (item.type === 'text/html') {
                this.clipboardTypes.html = await this.getClipboardText(item);
            } else if (item.type === 'text/plain') {
                this.clipboardTypes.text = await this.getClipboardText(item);
            } else if (item.kind === 'file') {
                const file = item.getAsFile();
                if (file) {
                    this.clipboardTypes.files.push(file);
                }
            }
        }

        // Detect URLs in text content
        if (this.clipboardTypes.text) {
            this.clipboardTypes.urls = this.extractUrls(this.clipboardTypes.text);
        }
    },

    // Get text from clipboard item
    getClipboardText(item) {
        return new Promise((resolve) => {
            item.getAsString(resolve);
        });
    },

    // Extract URLs from text
    extractUrls(text) {
        const urlRegex = /(https?:\/\/[^\s]+)/g;
        const matches = text.match(urlRegex);
        return matches ? matches.map(url => ({
            url: url,
            type: this.detectUrlType(url)
        })) : [];
    },

    // Detect URL type (video, image, document, etc.)
    detectUrlType(url) {
        const videoPatterns = [
            /youtube\.com\/watch/i,
            /youtu\.be\//i,
            /vimeo\.com\//i,
            /dailymotion\.com\//i,
            /twitch\.tv\//i
        ];

        const imagePatterns = [
            /\.(jpg|jpeg|png|gif|webp|svg)$/i,
            /imgur\.com\//i,
            /unsplash\.com\//i
        ];

        const documentPatterns = [
            /\.(pdf|doc|docx|ppt|pptx|xls|xlsx)$/i,
            /docs\.google\.com\//i,
            /drive\.google\.com\//i
        ];

        if (videoPatterns.some(pattern => pattern.test(url))) return 'video';
        if (imagePatterns.some(pattern => pattern.test(url))) return 'image';
        if (documentPatterns.some(pattern => pattern.test(url))) return 'document';
        return 'link';
    },

    // Handle clipboard images with OCR processing
    async handleClipboardImages() {
        this.showNotification(`Processing ${this.clipboardTypes.images.length} image(s)...`, 'info');

        for (const image of this.clipboardTypes.images) {
            // Process image with OCR if enabled
            if (this.ocrEnabled && image.type.startsWith('image/')) {
                await this.processImageWithOCR(image);
            } else {
                await this.uploadFile(image);
            }
        }
    },

    // Handle clipboard files
    async handleClipboardFiles() {
        this.showNotification(`Processing ${this.clipboardTypes.files.length} file(s)...`, 'info');

        for (const file of this.clipboardTypes.files) {
            if (file.size > this.maxFileSize) {
                this.showNotification(`File ${file.name} is too large (${this.formatFileSize(file.size)}). Max size: ${this.formatFileSize(this.maxFileSize)}`, 'error');
                continue;
            }

            if (file.size > this.chunkSize * 10) {
                // Use chunked upload for large files
                await this.uploadFileChunked(file);
            } else {
                await this.uploadFile(file);
            }
        }
    },

    // Handle clipboard URLs with smart embedding
    async handleClipboardUrls() {
        for (const urlInfo of this.clipboardTypes.urls) {
            await this.handleSmartUrlInsertion(urlInfo);
        }
    },

    // Handle HTML paste with markdown conversion
    async handleClipboardHtml() {
        if (this.clipboardTypes.html) {
            try {
                const markdown = await this.convertHtmlToMarkdown(this.clipboardTypes.html);
                this.insertAtCursor(markdown);
                this.showNotification('Rich content converted to markdown', 'success');
            } catch (error) {
                // Fallback to plain text
                this.insertAtCursor(this.clipboardTypes.text || '');
                this.showNotification('Pasted as plain text', 'info');
            }
        }
    },

    // Handle smart text paste with auto-formatting
    handleSmartTextPaste() {
        // This will enhance the default paste behavior
        setTimeout(() => {
            const cursorPos = this.editorElement.selectionStart;
            const textBefore = this.content.substring(0, cursorPos);
            const textAfter = this.content.substring(cursorPos);

            // Auto-format common patterns
            let formatted = this.content;
            formatted = this.autoFormatLists(formatted);
            formatted = this.autoFormatHeaders(formatted);
            formatted = this.autoFormatCodeBlocks(formatted);

            if (formatted !== this.content) {
                this.content = formatted;
                this.editorElement.value = formatted;
                this.updatePreview();
                this.showNotification('Text auto-formatted', 'info');
            }
        }, 100);
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

    // Enhanced file upload handling with smart processing
    async handleFileUploads(files) {
        // Pre-process files for smart handling
        const processedFiles = await this.preprocessFiles(files);

        // Sort by processing priority
        const sortedFiles = this.sortFilesByProcessingPriority(processedFiles);

        // Process with batch support
        await this.processBatchUpload(sortedFiles);
    },

    // Preprocess files for smart handling
    async preprocessFiles(files) {
        const processed = [];

        for (const file of files) {
            const fileInfo = {
                file: file,
                originalName: file.name,
                size: file.size,
                type: this.detectFileType(file),
                mimeType: file.type,
                needsChunking: file.size > this.chunkSize * 10,
                needsOptimization: this.shouldOptimizeFile(file),
                duplicate: await this.checkDuplicate(file),
                metadata: await this.extractFileMetadata(file)
            };

            processed.push(fileInfo);
        }

        return processed;
    },

    // Sort files by processing priority
    sortFilesByProcessingPriority(files) {
        return files.sort((a, b) => {
            // Priority order: images first, then documents, then videos/audio
            const priority = {
                'image': 1,
                'document': 2,
                'video': 3,
                'audio': 4
            };

            const aPriority = priority[a.type] || 5;
            const bPriority = priority[b.type] || 5;

            if (aPriority !== bPriority) {
                return aPriority - bPriority;
            }

            // Secondary sort by file size (smaller first)
            return a.size - b.size;
        });
    },

    // Process batch upload with progress tracking
    async processBatchUpload(fileInfos, insertPos = null) {
        const batchId = this.generateUploadId();
        const totalFiles = fileInfos.length;

        // Initialize batch progress
        this.uploadProgress[batchId] = {
            type: 'batch',
            totalFiles: totalFiles,
            completedFiles: 0,
            failedFiles: 0,
            progress: 0,
            files: {}
        };

        // Show batch progress notification
        this.showBatchProgressNotification(batchId, totalFiles);

        for (let i = 0; i < fileInfos.length; i++) {
            const fileInfo = fileInfos[i];

            try {
                if (fileInfo.duplicate && this.duplicateDetection) {
                    // Handle duplicate file
                    await this.handleDuplicateFile(fileInfo, batchId);
                } else if (fileInfo.needsChunking) {
                    // Use chunked upload for large files
                    await this.uploadFileChunked(fileInfo.file, batchId);
                } else {
                    // Standard upload
                    await this.uploadFileEnhanced(fileInfo, batchId, insertPos);
                }

                // Update batch progress
                this.uploadProgress[batchId].completedFiles++;
                this.uploadProgress[batchId].progress = (this.uploadProgress[batchId].completedFiles / totalFiles) * 100;

            } catch (error) {
                console.error('Batch upload error:', error);
                this.uploadProgress[batchId].failedFiles++;
                this.showNotification(`Failed to upload ${fileInfo.originalName}: ${error.message}`, 'error');
            }
        }

        // Complete batch upload
        this.completeBatchUpload(batchId);
    },

    // Enhanced file upload with smart processing
    async uploadFileEnhanced(fileInfo, batchId = null, insertPos = null) {
        const uploadId = this.generateUploadId();
        const file = fileInfo.file;

        // Insert placeholder immediately
        const placeholder = this.generateEnhancedPlaceholder(fileInfo, uploadId);
        if (insertPos !== null) {
            this.insertAtPosition(placeholder, insertPos);
        } else {
            this.insertAtCursor(placeholder);
        }

        // Initialize progress tracking
        this.uploadProgress[uploadId] = {
            progress: 0,
            status: 'uploading',
            filename: fileInfo.originalName,
            type: fileInfo.type,
            size: fileInfo.size,
            batchId: batchId
        };

        try {
            // Pre-process file if needed
            let processedFile = file;
            if (fileInfo.needsOptimization) {
                processedFile = await this.optimizeFile(file);
                this.updatePlaceholderProgress(uploadId, 25, 'Optimizing...');
            }

            // Create FormData with metadata
            const formData = new FormData();
            formData.append('file', processedFile);
            formData.append('type', fileInfo.type);
            formData.append('original_name', fileInfo.originalName);
            formData.append('metadata', JSON.stringify(fileInfo.metadata));

            // Add CSRF token
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                formData.append('_token', token);
            }

            // Upload with progress tracking
            const response = await this.uploadWithProgress(formData, uploadId);

            if (response.success) {
                // Generate enhanced markdown based on file type and metadata
                const markdown = await this.generateEnhancedMarkdown(response, fileInfo);
                this.replacePlaceholder(uploadId, markdown);
                this.uploadProgress[uploadId].status = 'completed';

                // Post-process if needed (OCR, metadata extraction, etc.)
                if (fileInfo.type === 'image' && this.ocrEnabled) {
                    this.performOCRPostProcessing(response, uploadId);
                }

                this.showNotification(`✅ ${fileInfo.originalName} uploaded successfully`, 'success');
            } else {
                throw new Error(response.error || 'Upload failed');
            }

        } catch (error) {
            console.error('Upload error:', error);
            await this.handleUploadError(error, fileInfo, uploadId);
        }

        // Clean up progress tracking after delay
        setTimeout(() => {
            delete this.uploadProgress[uploadId];
        }, 5000);
    },

    // Chunked upload for large files
    async uploadFileChunked(file, batchId = null) {
        const uploadId = this.generateUploadId();
        const totalChunks = Math.ceil(file.size / this.chunkSize);

        // Insert placeholder for chunked upload
        const placeholder = this.generateChunkedUploadPlaceholder(file.name, uploadId, file.size);
        this.insertAtCursor(placeholder);

        // Initialize chunked upload progress
        this.uploadProgress[uploadId] = {
            progress: 0,
            status: 'uploading',
            filename: file.name,
            type: 'chunked',
            totalChunks: totalChunks,
            completedChunks: 0,
            batchId: batchId
        };

        try {
            // Initiate chunked upload session
            const sessionResponse = await this.initiateChunkedUpload(file, uploadId);
            const sessionId = sessionResponse.session_id;

            // Upload chunks sequentially
            for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                const start = chunkIndex * this.chunkSize;
                const end = Math.min(start + this.chunkSize, file.size);
                const chunk = file.slice(start, end);

                await this.uploadChunk(chunk, chunkIndex, sessionId, uploadId);

                // Update progress
                this.uploadProgress[uploadId].completedChunks++;
                this.uploadProgress[uploadId].progress = (this.uploadProgress[uploadId].completedChunks / totalChunks) * 100;
                this.updatePlaceholderProgress(uploadId, this.uploadProgress[uploadId].progress, `Uploading chunk ${chunkIndex + 1}/${totalChunks}...`);
            }

            // Finalize chunked upload
            const finalResponse = await this.finalizeChunkedUpload(sessionId, uploadId);

            if (finalResponse.success) {
                const markdown = this.generateMarkdownForFile(finalResponse, this.detectFileType(file));
                this.replacePlaceholder(uploadId, markdown);
                this.uploadProgress[uploadId].status = 'completed';
                this.showNotification(`✅ ${file.name} uploaded successfully (chunked)`, 'success');
            } else {
                throw new Error(finalResponse.error || 'Chunked upload finalization failed');
            }

        } catch (error) {
            console.error('Chunked upload error:', error);
            await this.handleUploadError(error, { file, originalName: file.name }, uploadId);
        }

        // Clean up
        setTimeout(() => {
            delete this.uploadProgress[uploadId];
        }, 5000);
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

    // Smart file processing helper methods
    shouldOptimizeFile(file) {
        if (file.type.startsWith('image/')) {
            return file.size > 500 * 1024; // Optimize images larger than 500KB
        }
        return false;
    },

    async checkDuplicate(file) {
        if (!this.duplicateDetection) return false;

        try {
            // Calculate file hash for duplicate detection
            const hash = await this.calculateFileHash(file);

            // Check against uploaded files (this would need server-side implementation)
            // For now, just return false
            return false;
        } catch (error) {
            console.warn('Duplicate check failed:', error);
            return false;
        }
    },

    async extractFileMetadata(file) {
        const metadata = {
            name: file.name,
            size: file.size,
            type: file.type,
            lastModified: file.lastModified
        };

        try {
            if (file.type.startsWith('image/')) {
                metadata.dimensions = await this.getImageDimensions(file);
                metadata.exif = await this.extractImageExif(file);
            } else if (file.type.startsWith('video/')) {
                metadata.duration = await this.getVideoDuration(file);
                metadata.dimensions = await this.getVideoDimensions(file);
            } else if (file.type.startsWith('audio/')) {
                metadata.duration = await this.getAudioDuration(file);
            }
        } catch (error) {
            console.warn('Metadata extraction failed:', error);
        }

        return metadata;
    },

    async optimizeFile(file) {
        if (file.type.startsWith('image/')) {
            return await this.optimizeImage(file);
        }
        return file;
    },

    async optimizeImage(file) {
        return new Promise((resolve) => {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const img = new Image();

            img.onload = () => {
                // Calculate optimized dimensions
                const maxWidth = 1920;
                const maxHeight = 1080;
                let { width, height } = img;

                if (width > maxWidth) {
                    height = (height * maxWidth) / width;
                    width = maxWidth;
                }
                if (height > maxHeight) {
                    width = (width * maxHeight) / height;
                    height = maxHeight;
                }

                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob((blob) => {
                    // Create new file with optimized content
                    const optimizedFile = new File([blob], file.name, {
                        type: 'image/jpeg',
                        lastModified: Date.now()
                    });
                    resolve(optimizedFile);
                }, 'image/jpeg', 0.85);
            };

            img.onerror = () => resolve(file);
            img.src = URL.createObjectURL(file);
        });
    },

    async calculateFileHash(file) {
        const buffer = await file.arrayBuffer();
        const hashBuffer = await crypto.subtle.digest('SHA-256', buffer);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    },

    async getImageDimensions(file) {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = () => {
                resolve({ width: img.naturalWidth, height: img.naturalHeight });
            };
            img.onerror = () => resolve({ width: 0, height: 0 });
            img.src = URL.createObjectURL(file);
        });
    },

    async extractImageExif(file) {
        // This would require an EXIF library for full implementation
        // For now, return basic info
        return {
            extracted: false,
            message: 'EXIF extraction not implemented'
        };
    },

    async getVideoDuration(file) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = () => {
                resolve(video.duration);
            };
            video.onerror = () => resolve(0);
            video.src = URL.createObjectURL(file);
        });
    },

    async getVideoDimensions(file) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = () => {
                resolve({ width: video.videoWidth, height: video.videoHeight });
            };
            video.onerror = () => resolve({ width: 0, height: 0 });
            video.src = URL.createObjectURL(file);
        });
    },

    async getAudioDuration(file) {
        return new Promise((resolve) => {
            const audio = document.createElement('audio');
            audio.preload = 'metadata';
            audio.onloadedmetadata = () => {
                resolve(audio.duration);
            };
            audio.onerror = () => resolve(0);
            audio.src = URL.createObjectURL(file);
        });
    },

    // Enhanced placeholder generation
    generateEnhancedPlaceholder(fileInfo, uploadId) {
        const emoji = this.getFileTypeEmoji(fileInfo.type);
        const sizeStr = this.formatFileSize(fileInfo.size);

        if (fileInfo.needsChunking) {
            return `\n${emoji} ![Uploading ${fileInfo.originalName}... (${sizeStr}, chunked)](uploading:${uploadId})\n`;
        } else {
            return `\n${emoji} ![Uploading ${fileInfo.originalName}... (${sizeStr})](uploading:${uploadId})\n`;
        }
    },

    generateChunkedUploadPlaceholder(filename, uploadId, fileSize) {
        const emoji = '📦';
        const sizeStr = this.formatFileSize(fileSize);
        return `\n${emoji} ![Uploading ${filename}... (${sizeStr}, chunked upload)](uploading:${uploadId})\n`;
    },

    getFileTypeEmoji(type) {
        const emojis = {
            'image': '🖼️',
            'video': '🎥',
            'audio': '🎵',
            'document': '📄'
        };
        return emojis[type] || '📎';
    },

    formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    },

    // Update placeholder with enhanced progress
    updatePlaceholderProgress(uploadId, progress, message = null) {
        const uploadInfo = this.uploadProgress[uploadId];
        if (!uploadInfo) return;

        const filename = uploadInfo.filename || 'file';
        const emoji = this.getFileTypeEmoji(uploadInfo.type);
        const progressBar = this.generateProgressBar(progress);

        const oldPlaceholder = new RegExp(`${emoji} !\\[Uploading ${filename.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}[^\\]]*\\]\\(uploading:${uploadId}\\)`, 'g');
        const newPlaceholder = `${emoji} ![Uploading ${filename}... ${Math.round(progress)}% ${progressBar}${message ? ` - ${message}` : ''}](uploading:${uploadId})`;

        this.content = this.content.replace(oldPlaceholder, newPlaceholder);
        this.editorElement.value = this.content;
    },

    generateProgressBar(progress, length = 10) {
        const filled = Math.round((progress / 100) * length);
        const empty = length - filled;
        return '█'.repeat(filled) + '░'.repeat(empty);
    },

    // Enhanced markdown generation
    async generateEnhancedMarkdown(response, fileInfo) {
        const baseMarkdown = this.generateMarkdownForFile(response, fileInfo.type);

        // Add metadata if available
        if (fileInfo.metadata && Object.keys(fileInfo.metadata).length > 0) {
            let enhancedMarkdown = baseMarkdown;

            if (fileInfo.type === 'image' && fileInfo.metadata.dimensions) {
                const { width, height } = fileInfo.metadata.dimensions;
                enhancedMarkdown += `\n*${width}×${height} pixels*`;
            } else if (fileInfo.type === 'video' && fileInfo.metadata.duration) {
                const duration = Math.round(fileInfo.metadata.duration);
                enhancedMarkdown += `\n*Duration: ${Math.floor(duration / 60)}:${(duration % 60).toString().padStart(2, '0')}*`;
            }

            return enhancedMarkdown;
        }

        return baseMarkdown;
    },

    // Error handling with retry mechanism
    async handleUploadError(error, fileInfo, uploadId) {
        const uploadInfo = this.uploadProgress[uploadId];
        const currentAttempts = uploadInfo?.attempts || 0;

        if (currentAttempts < this.retryAttempts) {
            // Retry upload
            uploadInfo.attempts = currentAttempts + 1;
            uploadInfo.status = 'retrying';

            const retryDelay = Math.pow(2, currentAttempts) * 1000; // Exponential backoff
            this.updatePlaceholderProgress(uploadId, 0, `Retrying in ${retryDelay/1000}s... (${currentAttempts + 1}/${this.retryAttempts})`);

            setTimeout(async () => {
                try {
                    await this.uploadFileEnhanced(fileInfo, uploadInfo.batchId);
                } catch (retryError) {
                    await this.handleUploadError(retryError, fileInfo, uploadId);
                }
            }, retryDelay);
        } else {
            // Max retries reached
            uploadInfo.status = 'failed';
            const errorMarkdown = `❌ **Upload failed:** ${fileInfo.originalName} (${error.message})`;
            this.replacePlaceholder(uploadId, errorMarkdown);
            this.showNotification(`Upload failed after ${this.retryAttempts} attempts: ${fileInfo.originalName}`, 'error');
        }
    },

    // Batch upload helpers
    showBatchProgressNotification(batchId, totalFiles) {
        this.showNotification(`Starting batch upload of ${totalFiles} files...`, 'info');
    },

    completeBatchUpload(batchId) {
        const batchInfo = this.uploadProgress[batchId];
        if (!batchInfo) return;

        const { completedFiles, failedFiles, totalFiles } = batchInfo;

        if (failedFiles === 0) {
            this.showNotification(`✅ All ${totalFiles} files uploaded successfully!`, 'success');
        } else {
            this.showNotification(`⚠️ Batch upload completed: ${completedFiles} successful, ${failedFiles} failed`, 'warning');
        }

        // Clean up batch progress
        setTimeout(() => {
            delete this.uploadProgress[batchId];
        }, 3000);
    },

    async handleDuplicateFile(fileInfo, batchId) {
        // Show duplicate warning and options
        const action = await this.showDuplicateDialog(fileInfo);

        switch (action) {
            case 'skip':
                this.showNotification(`Skipped duplicate file: ${fileInfo.originalName}`, 'info');
                break;
            case 'replace':
                await this.uploadFileEnhanced(fileInfo, batchId);
                break;
            case 'rename':
                fileInfo.originalName = await this.generateUniqueFilename(fileInfo.originalName);
                await this.uploadFileEnhanced(fileInfo, batchId);
                break;
        }
    },

    async showDuplicateDialog(fileInfo) {
        // Simple implementation - in practice this would be a modal
        return confirm(`File "${fileInfo.originalName}" already exists. Replace it?`) ? 'replace' : 'skip';
    },

    async generateUniqueFilename(originalName) {
        const timestamp = Date.now();
        const ext = originalName.split('.').pop();
        const nameWithoutExt = originalName.substring(0, originalName.lastIndexOf('.'));
        return `${nameWithoutExt}_${timestamp}.${ext}`;
    },

    // Chunked upload implementation
    async initiateChunkedUpload(file, uploadId) {
        const response = await fetch(`/topics/${this.topicId}/chunked-upload/start`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                filename: file.name,
                size: file.size,
                type: file.type,
                chunks: Math.ceil(file.size / this.chunkSize)
            })
        });

        if (!response.ok) {
            throw new Error('Failed to initiate chunked upload');
        }

        return await response.json();
    },

    async uploadChunk(chunk, chunkIndex, sessionId, uploadId) {
        const formData = new FormData();
        formData.append('chunk', chunk);
        formData.append('session_id', sessionId);
        formData.append('chunk_index', chunkIndex);

        const response = await fetch(`/topics/${this.topicId}/chunked-upload/chunk`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (!response.ok) {
            throw new Error(`Failed to upload chunk ${chunkIndex}`);
        }

        return await response.json();
    },

    async finalizeChunkedUpload(sessionId, uploadId) {
        const response = await fetch(`/topics/${this.topicId}/chunked-upload/finalize`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                session_id: sessionId
            })
        });

        if (!response.ok) {
            throw new Error('Failed to finalize chunked upload');
        }

        return await response.json();
    },

    // Smart content processing
    async convertHtmlToMarkdown(html) {
        // Simple HTML to Markdown conversion
        // In practice, this would use a proper library like Turndown
        return html
            .replace(/<h([1-6])>/g, (match, level) => '#'.repeat(parseInt(level)) + ' ')
            .replace(/<\/h[1-6]>/g, '\n\n')
            .replace(/<strong>/g, '**').replace(/<\/strong>/g, '**')
            .replace(/<em>/g, '*').replace(/<\/em>/g, '*')
            .replace(/<p>/g, '').replace(/<\/p>/g, '\n\n')
            .replace(/<br\s*\/?>/g, '\n')
            .replace(/<[^>]*>/g, '') // Remove remaining HTML tags
            .trim();
    },

    async handleSmartUrlInsertion(urlInfo, insertPos = null) {
        let markdown = '';

        switch (urlInfo.type) {
            case 'video':
                markdown = await this.generateVideoEmbed(urlInfo.url);
                break;
            case 'image':
                markdown = `![Image](${urlInfo.url})`;
                break;
            case 'document':
                markdown = `[📄 Document](${urlInfo.url})`;
                break;
            default:
                markdown = `[${urlInfo.url}](${urlInfo.url})`;
        }

        if (insertPos !== null) {
            this.insertAtPosition(markdown, insertPos);
        } else {
            this.insertAtCursor(markdown);
        }

        this.showNotification(`Smart URL embedding: ${urlInfo.type}`, 'success');
    },

    async generateVideoEmbed(url) {
        // YouTube video detection and embed generation
        const youtubeMatch = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/);
        if (youtubeMatch) {
            const videoId = youtubeMatch[1];
            return `[![YouTube Video](https://img.youtube.com/vi/${videoId}/0.jpg)](${url})`;
        }

        // Vimeo detection
        const vimeoMatch = url.match(/vimeo\.com\/(\d+)/);
        if (vimeoMatch) {
            return `[🎥 Vimeo Video](${url})`;
        }

        // Generic video link
        return `[🎥 Video](${url})`;
    },

    // Text auto-formatting helpers
    autoFormatLists(text) {
        // Auto-format lines that start with - or * as bullet lists
        return text.replace(/^([^\n\r]*)\n([*\-])\s/gm, '$1\n\n$2 ');
    },

    autoFormatHeaders(text) {
        // Auto-format lines that look like headers
        return text.replace(/^([A-Z][^.\n]{3,})\n([=\-]{3,})\n/gm, (match, title, underline) => {
            const level = underline[0] === '=' ? 1 : 2;
            return '#'.repeat(level) + ' ' + title + '\n\n';
        });
    },

    autoFormatCodeBlocks(text) {
        // Auto-format code blocks
        return text.replace(/```([^`]+)```/g, '```\n$1\n```');
    },

    autoFormatText(text) {
        let formatted = text;
        formatted = this.autoFormatLists(formatted);
        formatted = this.autoFormatHeaders(formatted);
        formatted = this.autoFormatCodeBlocks(formatted);
        return formatted;
    },

    // Helper methods for position insertion
    getInsertionPosition(event) {
        if (!this.editorElement) return null;

        const rect = this.editorElement.getBoundingClientRect();
        const x = event.clientX - rect.left;
        const y = event.clientY - rect.top;

        return this.getTextPositionFromCoordinates(x, y);
    },

    getTextPositionFromCoordinates(x, y) {
        // Approximate text position calculation
        const lineHeight = 20; // Approximate line height
        const charWidth = 8;   // Approximate character width

        const line = Math.floor(y / lineHeight);
        const char = Math.floor(x / charWidth);

        const lines = this.content.split('\n');
        let position = 0;

        for (let i = 0; i < Math.min(line, lines.length); i++) {
            position += lines[i].length + 1; // +1 for newline
        }

        if (line < lines.length) {
            position += Math.min(char, lines[line].length);
        }

        return position;
    },

    insertAtPosition(text, position) {
        if (!this.editorElement || position === null) {
            this.insertAtCursor(text);
            return;
        }

        const currentValue = this.editorElement.value;
        const newValue = currentValue.substring(0, position) + text + currentValue.substring(position);

        this.editorElement.value = newValue;
        this.content = newValue;
        this.editorElement.focus();
        this.editorElement.setSelectionRange(position + text.length, position + text.length);

        this.autoResize();
        this.updatePreview();
    },

    showInsertionCursor(position) {
        // Visual indication of insertion point - simplified implementation
        if (this.editorElement) {
            this.editorElement.focus();
            this.editorElement.setSelectionRange(position, position);
        }
    },

    // OCR and advanced processing
    async processImageWithOCR(image) {
        try {
            // This would integrate with an OCR service
            this.showNotification('OCR processing not yet implemented', 'info');

            // For now, just upload the image normally
            await this.uploadFile(image);
        } catch (error) {
            console.error('OCR processing failed:', error);
            await this.uploadFile(image);
        }
    },

    async performOCRPostProcessing(response, uploadId) {
        // Post-process uploaded image with OCR
        // This would be implemented with an OCR service integration
        this.showNotification('OCR post-processing not yet implemented', 'info');
    },

    // Clipboard preview functionality
    showClipboardPreview() {
        // Show a preview of clipboard content before pasting
        this.showNotification('Clipboard preview: Ctrl+Shift+V', 'info');
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