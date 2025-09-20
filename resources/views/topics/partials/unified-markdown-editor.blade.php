<!-- Simple Markdown Editor with Highlight.js -->
<div x-data="highlightMarkdownEditor()"
     x-init="init()"
     data-topic-id="{{ $topicId }}"
     data-content="{{ $content }}"
     class="simple-markdown-editor"
     x-cloak>

    <!-- Editor Header -->
    <div class="flex items-center justify-between bg-gray-50 px-3 py-2 border-b border-gray-200">
        <span class="text-sm font-medium text-gray-700">{{ __('Learning Content') }}</span>
        <button type="button"
                @click="togglePreview()"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
            <span x-text="showPreview ? 'Hide Preview' : 'Show Preview'"></span>
        </button>
    </div>

    <!-- Editor Area -->
    <div class="flex h-[500px]" :class="showPreview ? 'h-[600px]' : 'h-[500px]'">
        <!-- Markdown Input with Syntax Highlighting -->
        <div class="flex-1 relative bg-white">
            <textarea
                x-ref="editor"
                x-model="content"
                @input="updatePreview()"
                @scroll="syncScroll()"
                name="{{ $fieldName }}"
                placeholder="Write your content in markdown..."
                class="absolute inset-0 w-full h-full p-4 border-0 resize-none font-mono text-sm focus:outline-none focus:ring-0 bg-transparent text-transparent caret-black z-20"
                style="color: transparent;"
            ></textarea>

            <!-- Syntax Highlighting Background -->
            <pre x-ref="highlight"
                 class="absolute inset-0 w-full h-full p-4 font-mono text-sm pointer-events-none overflow-auto whitespace-pre-wrap break-words z-10 bg-white"
                 style="margin: 0; tab-size: 4;"><code x-html="highlightedContent" class="hljs language-markdown"></code></pre>
        </div>

        <!-- Preview Panel -->
        <div x-show="showPreview"
             class="flex-1 border-l border-gray-200 bg-white overflow-auto">
            <div class="p-4 prose prose-sm max-w-none" x-html="previewHtml">
                <div class="text-gray-500 italic text-center py-8">
                    <p>{{ __('Preview will appear here...') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Character Count -->
    <div class="px-3 py-2 bg-gray-50 border-t border-gray-200 text-xs text-gray-500">
        <span x-text="content.length"></span> {{ __('characters') }}
    </div>

    <!-- Hidden fields for form submission -->
    <input type="hidden" name="learning_content" :value="content">
    <input type="hidden" name="content_format" value="markdown">
</div>

<style>
.simple-markdown-editor {
    border: 1px solid #d1d5db;
    border-radius: 0.5rem;
    overflow: hidden;
}

/* Custom markdown highlighting styles */
.hljs-section { color: #0f172a; font-weight: 700; } /* Headers */
.hljs-strong { color: #374151; font-weight: 600; } /* Bold */
.hljs-emphasis { color: #6b7280; font-style: italic; } /* Italic */
.hljs-code { color: #dc2626; background-color: #fef2f2; padding: 2px 4px; border-radius: 3px; } /* Inline code */
.hljs-link { color: #2563eb; text-decoration: underline; } /* Links */
.hljs-bullet { color: #059669; font-weight: 600; } /* List bullets */
.hljs-quote { color: #6b7280; font-style: italic; border-left: 3px solid #d1d5db; padding-left: 12px; } /* Blockquotes */
.hljs-attr { color: #7c3aed; } /* Attributes */
.hljs-symbol { color: #059669; } /* Symbols */
</style>

<!-- Highlight.js CDN -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/languages/markdown.min.js"></script>

<script>
function highlightMarkdownEditor() {
    return {
        content: '',
        highlightedContent: '',
        previewHtml: '',
        showPreview: false,

        init() {
            this.content = this.$el.dataset.content || '';
            this.updateHighlight();
            this.updatePreview();
        },

        updateHighlight() {
            if (typeof hljs !== 'undefined') {
                try {
                    const highlighted = hljs.highlight(this.content, { language: 'markdown' });
                    this.highlightedContent = highlighted.value;
                } catch (e) {
                    // Fallback to plain text if highlighting fails
                    this.highlightedContent = this.escapeHtml(this.content);
                }
            } else {
                this.highlightedContent = this.escapeHtml(this.content);
            }
            this.syncScroll();
        },

        togglePreview() {
            this.showPreview = !this.showPreview;
            if (this.showPreview) {
                this.updatePreview();
            }
        },

        updatePreview() {
            this.updateHighlight();

            if (!this.showPreview) return;

            // Simple markdown to HTML conversion
            let html = this.content
                // Headers
                .replace(/^### (.*$)/gm, '<h3 class="text-lg font-semibold mt-4 mb-2">$1</h3>')
                .replace(/^## (.*$)/gm, '<h2 class="text-xl font-semibold mt-4 mb-2">$1</h2>')
                .replace(/^# (.*$)/gm, '<h1 class="text-2xl font-bold mt-4 mb-3">$1</h1>')
                // Bold
                .replace(/\*\*(.*?)\*\*/g, '<strong class="font-semibold">$1</strong>')
                // Italic
                .replace(/\*(.*?)\*/g, '<em class="italic">$1</em>')
                // Code
                .replace(/`(.*?)`/g, '<code class="bg-gray-100 px-1 py-0.5 rounded text-sm font-mono">$1</code>')
                // Links
                .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank" class="text-blue-600 underline hover:text-blue-800">$1</a>')
                // Unordered lists
                .replace(/^[\s]*[-*+]\s+(.+)$/gm, '<li class="ml-4">• $1</li>')
                // Ordered lists
                .replace(/^[\s]*\d+\.\s+(.+)$/gm, '<li class="ml-4">$1</li>')
                // Blockquotes
                .replace(/^>\s+(.+)$/gm, '<blockquote class="border-l-4 border-gray-300 pl-4 italic text-gray-600 my-2">$1</blockquote>')
                // Paragraphs (double line breaks)
                .replace(/\n\n/g, '</p><p class="mb-3">')
                // Single line breaks
                .replace(/\n/g, '<br>');

            // Wrap consecutive list items
            html = html.replace(/(<li class="ml-4">.*?<\/li>\s*)+/g, (match) => {
                return '<ul class="list-none mb-3 space-y-1">' + match + '</ul>';
            });

            if (html.trim()) {
                this.previewHtml = '<div class="prose-content"><p class="mb-3">' + html + '</p></div>';
            } else {
                this.previewHtml = '<div class="text-gray-500 italic text-center py-8"><p>Start typing to see preview...</p></div>';
            }
        },

        syncScroll() {
            // Sync scroll between editor and highlight
            if (this.$refs.editor && this.$refs.highlight) {
                this.$refs.highlight.scrollTop = this.$refs.editor.scrollTop;
                this.$refs.highlight.scrollLeft = this.$refs.editor.scrollLeft;
            }
        },

        escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    }
}
</script>