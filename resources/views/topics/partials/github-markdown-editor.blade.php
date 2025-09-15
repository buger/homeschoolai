<!-- GitHub-style Markdown Editor -->
<div x-data="githubMarkdownEditor()"
     x-init="init()"
     data-topic-id="{{ $topic->id }}"
     data-content="{{ $topic->getUnifiedContent() }}"
     data-format="markdown"
     class="space-y-4"
     x-ref="editorContainer">

    <!-- Editor Header -->
    <div class="flex items-center justify-between bg-gray-50 px-4 py-3 border border-gray-200 rounded-t-lg">
        <!-- Mobile Mode Toggles -->
        <div class="flex md:hidden" x-show="isMobile">
            <button @click="toggleMobileMode('write')"
                    :class="!isPreviewMode ? 'bg-white border-gray-300 text-gray-900' : 'bg-transparent border-transparent text-gray-500'"
                    class="px-3 py-1 text-sm font-medium border-r border-gray-200 rounded-l-md">
                Write
            </button>
            <button @click="toggleMobileMode('preview')"
                    :class="isPreviewMode ? 'bg-white border-gray-300 text-gray-900' : 'bg-transparent border-transparent text-gray-500'"
                    class="px-3 py-1 text-sm font-medium rounded-r-md">
                Preview
            </button>
        </div>

        <!-- Desktop Controls -->
        <div class="hidden md:flex items-center space-x-4">
            <span class="text-sm font-medium text-gray-700">Unified Markdown Content</span>
            <button @click="toggleSplitView()"
                    :class="showSplitView ? 'text-blue-600' : 'text-gray-500'"
                    class="flex items-center space-x-1 text-sm hover:text-blue-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <span x-text="showSplitView ? 'Hide Preview' : 'Show Preview'"></span>
            </button>
        </div>

        <!-- Upload Progress -->
        <div x-show="hasActiveUploads()" class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm text-blue-600">Uploading files...</span>
        </div>
    </div>

    <!-- Editor Content -->
    <div :class="showSplitView ? 'grid grid-cols-2 gap-4' : ''"
         class="border border-gray-200 rounded-b-lg bg-white min-h-[400px]">

        <!-- Editor Panel -->
        <div :class="isPreviewMode && isMobile ? 'hidden' : ''"
             class="relative">

            <!-- Markdown Toolbar -->
            <div class="border-b border-gray-200 bg-gray-50 px-3 py-2">
                <div class="flex flex-wrap items-center gap-1">
                    <!-- Text Formatting -->
                    <button @click="insertMarkdown('bold')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Bold">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M5 4v12h4.5c2.5 0 4.5-1.5 4.5-4 0-1.5-.5-2.5-1.5-3 1-.5 1.5-1.5 1.5-3 0-2.5-2-4-4.5-4H5zm3 5h2c1 0 1.5.5 1.5 1.5S11 12 10 12H8V9zm0 6h2.5c1.5 0 2.5.5 2.5 2s-1 2-2.5 2H8v-4z"/>
                        </svg>
                    </button>
                    <button @click="insertMarkdown('italic')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Italic">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 4v1h2l-2 10H6v1h6v-1h-2l2-10h2V4H8z"/>
                        </svg>
                    </button>
                    <div class="border-l border-gray-300 h-4 mx-1"></div>

                    <!-- Headers -->
                    <button @click="insertMarkdown('heading1')"
                            class="px-2 py-1 text-xs font-medium rounded hover:bg-gray-200"
                            title="Heading 1">H1</button>
                    <button @click="insertMarkdown('heading2')"
                            class="px-2 py-1 text-xs font-medium rounded hover:bg-gray-200"
                            title="Heading 2">H2</button>
                    <button @click="insertMarkdown('heading3')"
                            class="px-2 py-1 text-xs font-medium rounded hover:bg-gray-200"
                            title="Heading 3">H3</button>
                    <div class="border-l border-gray-300 h-4 mx-1"></div>

                    <!-- Lists -->
                    <button @click="insertMarkdown('list')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Bullet List">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 4a1 1 0 100 2 1 1 0 000-2zM6 4h11a1 1 0 110 2H6a1 1 0 110-2zM3 9a1 1 0 100 2 1 1 0 000-2zM6 9h11a1 1 0 110 2H6a1 1 0 110-2zM3 14a1 1 0 100 2 1 1 0 000-2zM6 14h11a1 1 0 110 2H6a1 1 0 110-2z"/>
                        </svg>
                    </button>
                    <button @click="insertMarkdown('numberedList')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Numbered List">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M3 2v2h1v1H3v1h2V5h1V2H3zM3 8v1h1v1H3v1h2v-1h1V8H3zM3 14v1h1v1H3v1h2v-1h1v-1H3zM7 3h11a1 1 0 110 2H7a1 1 0 110-2zM7 8h11a1 1 0 110 2H7a1 1 0 110-2zM7 13h11a1 1 0 110 2H7a1 1 0 110-2z"/>
                        </svg>
                    </button>
                    <div class="border-l border-gray-300 h-4 mx-1"></div>

                    <!-- Links and Media -->
                    <button @click="insertMarkdown('link')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Link">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                    </button>
                    <button @click="insertMarkdown('image')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Image">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </button>
                    <div class="border-l border-gray-300 h-4 mx-1"></div>

                    <!-- Code -->
                    <button @click="insertMarkdown('code')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Inline Code">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6.5 7.5l-3 3 3 3M13.5 7.5l3 3-3 3"/>
                        </svg>
                    </button>
                    <button @click="insertMarkdown('codeBlock')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Code Block">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                    </button>

                    <!-- More tools -->
                    <div class="border-l border-gray-300 h-4 mx-1"></div>
                    <button @click="insertMarkdown('quote')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Quote">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </button>
                    <button @click="insertMarkdown('table')"
                            class="p-1 rounded hover:bg-gray-200 tooltip"
                            title="Table">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0V4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H5a1 1 0 01-1-1V10z"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Drop Zone Overlay -->
            <div class="absolute inset-0 bg-blue-50 border-2 border-dashed border-blue-300 hidden items-center justify-center z-10 drag-over-indicator">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="mt-2 text-sm text-blue-600">Drop files here to upload</p>
                    <p class="text-xs text-blue-500">Images, documents, videos, and audio files supported</p>
                </div>
            </div>

            <!-- Text Editor -->
            <textarea x-ref="markdownEditor"
                      x-model="content"
                      class="w-full p-4 border-0 focus:ring-0 focus:outline-none resize-none font-mono text-sm leading-relaxed"
                      placeholder="Write your content in Markdown...

# Example Heading

You can drag and drop files directly into this editor, or paste images from your clipboard.

**Bold text** and *italic text* work as expected.

- List items
- Are easy to create

[Links](https://example.com) and images work too!

```
Code blocks are supported
```

> Quote blocks for important information"
                      style="min-height: 300px;"></textarea>

            <!-- Upload Progress Indicators -->
            <div x-show="getUploadProgress().length > 0"
                 class="absolute bottom-4 right-4 bg-white border border-gray-200 rounded-lg shadow-lg p-3 max-w-sm">
                <template x-for="upload in getUploadProgress()" :key="upload.filename">
                    <div class="flex items-center space-x-2 mb-2 last:mb-0">
                        <div class="flex-1">
                            <div class="text-xs font-medium" x-text="upload.filename"></div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5">
                                <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                                     :style="`width: ${upload.progress}%`"></div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500" x-text="`${upload.progress}%`"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Preview Panel -->
        <div x-show="(showSplitView && !isMobile) || (isPreviewMode && isMobile)"
             class="border-l border-gray-200 relative">

            <!-- Preview Header -->
            <div class="border-b border-gray-200 bg-gray-50 px-3 py-2">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700">Preview</span>
                    <div x-show="isPreviewLoading" class="flex items-center space-x-1">
                        <svg class="w-3 h-3 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-xs text-gray-500">Updating...</span>
                    </div>
                </div>
            </div>

            <!-- Preview Content -->
            <div class="p-4 prose prose-sm max-w-none"
                 x-html="previewHtml || '<p class=\'text-gray-500 italic\'>Start typing to see a preview...</p>'">
            </div>
        </div>
    </div>

    <!-- Save Section -->
    <form hx-put="{{ route('topics.update', ['topic' => $topic->id]) }}"
          hx-target="#topics-list"
          hx-include="[name='unified_content'], [name='name'], [name='estimated_minutes'], [name='required']"
          class="flex items-center justify-between p-4 bg-gray-50 border border-gray-200 rounded-lg">
        @csrf
        @method('PUT')

        <!-- Hidden fields for form submission -->
        <input type="hidden" name="learning_content" :value="content">
        <input type="hidden" name="description" :value="content">
        <input type="hidden" name="content_format" value="markdown">
        <input type="hidden" name="migrated_to_unified" value="1">
        <input type="hidden" name="name" value="{{ $topic->title }}">
        <input type="hidden" name="estimated_minutes" value="{{ $topic->estimated_minutes }}">
        <input type="hidden" name="required" value="{{ $topic->required ? '1' : '0' }}">

        <!-- Content Stats -->
        <div class="flex items-center space-x-4 text-sm text-gray-600">
            <span x-text="`${content.length} characters`"></span>
            <span x-text="`${content.split(/\s+/).filter(w => w.length > 0).length} words`"></span>
            <span class="text-green-600" x-show="content.length > 0">✓ Auto-saving</span>
        </div>

        <!-- Save Button -->
        <button type="submit"
                :disabled="hasActiveUploads()"
                class="px-6 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
            Save Content
        </button>
    </form>
</div>

<!-- Drag and Drop Styles -->
<style>
.drag-over .drag-over-indicator {
    display: flex !important;
}

.tooltip {
    position: relative;
}

.tooltip:hover::after {
    content: attr(title);
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    pointer-events: none;
}

.tooltip:hover::before {
    content: '';
    position: absolute;
    bottom: 100%;
    left: 50%;
    transform: translateX(-50%) translateY(2px);
    border: 4px solid transparent;
    border-top-color: #1f2937;
    z-index: 1000;
    pointer-events: none;
}

/* Prose styling for preview */
.prose h1 { @apply text-2xl font-bold mt-6 mb-4; }
.prose h2 { @apply text-xl font-bold mt-5 mb-3; }
.prose h3 { @apply text-lg font-bold mt-4 mb-2; }
.prose p { @apply mb-4; }
.prose ul, .prose ol { @apply mb-4 pl-6; }
.prose li { @apply mb-1; }
.prose blockquote { @apply border-l-4 border-gray-300 pl-4 italic text-gray-600 mb-4; }
.prose code { @apply bg-gray-100 px-1 py-0.5 rounded text-sm; }
.prose pre { @apply bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto mb-4; }
.prose table { @apply border-collapse border border-gray-300 mb-4; }
.prose th, .prose td { @apply border border-gray-300 px-3 py-2; }
.prose th { @apply bg-gray-100 font-semibold; }
.prose img { @apply max-w-full h-auto rounded-lg shadow-sm; }
.prose a { @apply text-blue-600 hover:text-blue-800 underline; }
</style>