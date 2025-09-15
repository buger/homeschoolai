// Rich Content Editor Component for Alpine.js
import tinymce from 'tinymce/tinymce';

// Import TinyMCE theme and plugins
import 'tinymce/themes/silver';
import 'tinymce/icons/default';
import 'tinymce/plugins/link';
import 'tinymce/plugins/image';
import 'tinymce/plugins/media';
import 'tinymce/plugins/table';
import 'tinymce/plugins/code';
import 'tinymce/plugins/codesample';
import 'tinymce/plugins/lists';
import 'tinymce/plugins/advlist';
import 'tinymce/plugins/preview';
import 'tinymce/plugins/wordcount';
import 'tinymce/plugins/fullscreen';
import 'tinymce/plugins/autoresize';

// Rich Content Editor Alpine.js Component
window.richContentEditor = () => ({
    // Component state
    content: '',
    format: 'plain',
    isPreviewMode: false,
    isLoading: false,
    wordCount: 0,
    readingTime: 0,
    editor: null,
    topicId: null,

    // Initialize the component
    init() {
        this.topicId = this.$el.dataset.topicId;
        this.content = this.$el.dataset.content || '';
        this.format = this.$el.dataset.format || 'plain';

        this.$nextTick(() => {
            this.initializeEditor();
        });
    },

    // Initialize TinyMCE editor
    async initializeEditor() {
        if (this.format === 'markdown' || this.format === 'html') {
            await this.setupRichEditor();
        }
    },

    // Setup TinyMCE for rich editing
    async setupRichEditor() {
        const selector = `#rich-content-editor-${this.topicId}`;

        await tinymce.init({
            selector,
            height: 400,
            menubar: false,
            plugins: [
                'link', 'image', 'media', 'table', 'code', 'codesample',
                'lists', 'advlist', 'preview', 'wordcount', 'fullscreen', 'autoresize'
            ],
            toolbar: [
                'undo redo | styles | bold italic underline | alignleft aligncenter alignright alignjustify',
                'bullist numlist outdent indent | link image media table | code codesample | preview fullscreen'
            ].join(' | '),
            content_style: `
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                    font-size: 14px;
                    line-height: 1.6;
                }
                .callout {
                    padding: 1rem;
                    margin: 1rem 0;
                    border-left: 4px solid #3b82f6;
                    background-color: #eff6ff;
                    border-radius: 0.375rem;
                }
                .callout.warning {
                    border-left-color: #f59e0b;
                    background-color: #fffbeb;
                }
                .callout.error {
                    border-left-color: #ef4444;
                    background-color: #fef2f2;
                }
                .callout.success {
                    border-left-color: #10b981;
                    background-color: #f0fdf4;
                }
            `,

            // Image upload configuration
            images_upload_url: `/topics/${this.topicId}/content/images`,
            images_upload_handler: this.handleImageUpload.bind(this),
            automatic_uploads: true,
            file_picker_types: 'image',

            // Setup and change handlers
            setup: (editor) => {
                this.editor = editor;

                editor.on('change keyup', () => {
                    this.content = editor.getContent();
                    this.updateMetadata();
                });

                editor.on('init', () => {
                    editor.setContent(this.content);
                });

                // Add custom toolbar buttons
                this.addCustomToolbarButtons(editor);
            },

            // Mobile responsiveness
            mobile: {
                theme: 'mobile',
                toolbar: ['bold', 'italic', 'underline', 'link', 'image', 'bullist', 'numlist']
            },

            // Security settings
            allow_unsafe_link_target: false,
            default_link_target: '_blank',
            link_target_list: [
                { title: 'New window', value: '_blank' },
                { title: 'Same window', value: '_self' }
            ]
        });
    },

    // Add custom toolbar buttons for educational content
    addCustomToolbarButtons(editor) {
        // Callout box button
        editor.ui.registry.addButton('callout', {
            text: 'Callout',
            icon: 'info',
            onAction: () => {
                editor.windowManager.open({
                    title: 'Insert Callout Box',
                    body: {
                        type: 'panel',
                        items: [
                            {
                                type: 'selectbox',
                                name: 'type',
                                label: 'Type',
                                items: [
                                    { text: 'Note', value: 'note' },
                                    { text: 'Tip', value: 'tip' },
                                    { text: 'Warning', value: 'warning' },
                                    { text: 'Error', value: 'error' },
                                    { text: 'Success', value: 'success' }
                                ]
                            },
                            {
                                type: 'input',
                                name: 'title',
                                label: 'Title',
                                placeholder: 'Enter callout title'
                            },
                            {
                                type: 'textarea',
                                name: 'content',
                                label: 'Content',
                                placeholder: 'Enter callout content'
                            }
                        ]
                    },
                    buttons: [
                        {
                            type: 'cancel',
                            text: 'Cancel'
                        },
                        {
                            type: 'submit',
                            text: 'Insert',
                            primary: true
                        }
                    ],
                    onSubmit: (api) => {
                        const data = api.getData();
                        const calloutHtml = this.generateCalloutHtml(data.type, data.title, data.content);
                        editor.insertContent(calloutHtml);
                        api.close();
                    }
                });
            }
        });

        // Math formula button (placeholder for future KaTeX integration)
        editor.ui.registry.addButton('formula', {
            text: 'Formula',
            icon: 'superscript',
            onAction: () => {
                const formula = prompt('Enter LaTeX formula (e.g., x = \\frac{-b \\pm \\sqrt{b^2 - 4ac}}{2a}):');
                if (formula) {
                    // For now, just insert as code - in production you'd integrate KaTeX
                    editor.insertContent(`<code class="math">${formula}</code>`);
                }
            }
        });
    },

    // Generate callout HTML
    generateCalloutHtml(type, title, content) {
        const icons = {
            note: '📝',
            tip: '💡',
            warning: '⚠️',
            error: '❌',
            success: '✅'
        };

        return `
            <div class="callout ${type}">
                <p><strong>${icons[type]} ${title}</strong></p>
                <p>${content}</p>
            </div>
        `;
    },

    // Handle image uploads
    async handleImageUpload(blobInfo, progress) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('image', blobInfo.blob(), blobInfo.filename());
            formData.append('alt_text', blobInfo.filename());

            // Add CSRF token
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (token) {
                formData.append('_token', token);
            }

            fetch(`/topics/${this.topicId}/content/images`, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    resolve(result.image.url);
                } else {
                    reject(result.error || 'Upload failed');
                }
            })
            .catch(error => {
                reject('Upload failed: ' + error.message);
            });
        });
    },

    // Switch content format
    async switchFormat(newFormat) {
        if (newFormat === this.format) return;

        this.isLoading = true;

        try {
            // Convert content if needed
            if (this.content && this.format !== newFormat) {
                const response = await fetch('/topics/content/convert', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        content: this.content,
                        from_format: this.format,
                        to_format: newFormat
                    })
                });

                if (response.ok) {
                    const result = await response.json();
                    this.content = result.content;
                }
            }

            this.format = newFormat;

            // Reinitialize editor for new format
            if (this.editor) {
                tinymce.remove(`#rich-content-editor-${this.topicId}`);
                this.editor = null;
            }

            await this.$nextTick();
            await this.initializeEditor();

        } catch (error) {
            console.error('Error switching format:', error);
            // Show error to user
        } finally {
            this.isLoading = false;
        }
    },

    // Toggle preview mode
    togglePreview() {
        this.isPreviewMode = !this.isPreviewMode;

        if (this.isPreviewMode) {
            this.renderPreview();
        }
    },

    // Render content preview
    async renderPreview() {
        if (!this.content) return;

        this.isLoading = true;

        try {
            const response = await fetch('/topics/content/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    content: this.content,
                    format: this.format
                })
            });

            if (response.ok) {
                const html = await response.text();
                const previewElement = document.getElementById(`preview-${this.topicId}`);
                if (previewElement) {
                    previewElement.innerHTML = html;
                }
            }
        } catch (error) {
            console.error('Error rendering preview:', error);
        } finally {
            this.isLoading = false;
        }
    },

    // Update content metadata
    updateMetadata() {
        // Simple word count and reading time calculation
        const plainText = this.getPlainText();
        this.wordCount = plainText.split(/\s+/).filter(word => word.length > 0).length;
        this.readingTime = Math.max(1, Math.ceil(this.wordCount / 200)); // 200 words per minute
    },

    // Get plain text from content
    getPlainText() {
        if (this.format === 'html') {
            const div = document.createElement('div');
            div.innerHTML = this.content;
            return div.textContent || div.innerText || '';
        } else if (this.format === 'markdown') {
            // Simple markdown to text conversion
            return this.content
                .replace(/#{1,6}\s+/g, '') // Remove headers
                .replace(/\*\*(.*?)\*\*/g, '$1') // Remove bold
                .replace(/\*(.*?)\*/g, '$1') // Remove italic
                .replace(/\[([^\]]+)\]\([^)]+\)/g, '$1') // Remove links
                .replace(/`([^`]+)`/g, '$1') // Remove inline code
                .replace(/```[\s\S]*?```/g, '') // Remove code blocks
                .replace(/!\[([^\]]*)\]\([^)]+\)/g, '$1'); // Remove images
        }
        return this.content;
    },

    // Get reading time display
    getReadingTimeDisplay() {
        if (this.readingTime <= 1) {
            return 'Less than 1 minute';
        }
        return `${this.readingTime} minute${this.readingTime > 1 ? 's' : ''}`;
    },

    // Insert markdown at cursor position
    insertMarkdown(markdown) {
        const textarea = document.querySelector(`[x-data="richContentEditor()"] textarea`);
        if (textarea) {
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const text = textarea.value;

            textarea.value = text.substring(0, start) + markdown + text.substring(end);
            textarea.focus();
            textarea.setSelectionRange(start + markdown.length, start + markdown.length);

            this.content = textarea.value;
            this.updateMetadata();
        }
    },

    // Load images for the gallery
    async loadImages() {
        try {
            const response = await fetch(`/topics/${this.topicId}/content/images`);
            if (response.ok) {
                const result = await response.json();
                this.images = result.images || [];
            }
        } catch (error) {
            console.error('Error loading images:', error);
        }
    },

    // Upload image via file input
    async uploadImage(event) {
        const file = event.target.files[0];
        if (!file) return;

        const formData = new FormData();
        formData.append('image', file);
        formData.append('alt_text', file.name);

        // Add CSRF token
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            formData.append('_token', token);
        }

        try {
            const response = await fetch(`/topics/${this.topicId}/content/images`, {
                method: 'POST',
                body: formData
            });

            if (response.ok) {
                const result = await response.json();
                this.images.push(result.image);

                // Clear the file input
                event.target.value = '';
            } else {
                const error = await response.json();
                alert(error.error || 'Upload failed');
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            alert('Upload failed: ' + error.message);
        }
    },

    // Insert image markdown
    insertImageMarkdown(image) {
        const markdown = `![${image.alt_text}](${image.url})`;

        if (this.format === 'markdown') {
            this.insertMarkdown(markdown);
        } else if (this.format === 'html' && this.editor) {
            this.editor.insertContent(`<img src="${image.url}" alt="${image.alt_text}" />`);
        } else {
            // Plain text - just insert the URL
            this.insertMarkdown(image.url);
        }
    },

    // Delete image
    async deleteImage(index) {
        if (!confirm('Are you sure you want to delete this image?')) return;

        try {
            const response = await fetch(`/topics/${this.topicId}/content/images/${index}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                }
            });

            if (response.ok) {
                this.images.splice(index, 1);
            } else {
                const error = await response.json();
                alert(error.error || 'Delete failed');
            }
        } catch (error) {
            console.error('Error deleting image:', error);
            alert('Delete failed: ' + error.message);
        }
    },

    // Clean up when component is destroyed
    destroy() {
        if (this.editor) {
            tinymce.remove(`#rich-content-editor-${this.topicId}`);
        }
    }
});

// Make TinyMCE available globally for direct use if needed
window.tinymce = tinymce;