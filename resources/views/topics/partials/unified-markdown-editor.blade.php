<!-- Unified Markdown Editor with Real-time Live Preview - Phase 4 -->
<div x-data="unifiedMarkdownEditor()"
     x-init="init()"
     data-topic-id="{{ $topic->id }}"
     data-content="{{ $topic->getUnifiedContent() }}"
     data-format="markdown"
     class="space-y-4"
     x-ref="editorContainer">

    <!-- Enhanced Editor Header -->
    <div class="flex items-center justify-between bg-gradient-to-r from-gray-50 to-gray-100 px-4 py-3 border border-gray-200 rounded-t-lg shadow-sm">
        <!-- Mobile Mode Toggles -->
        <div class="flex md:hidden" x-show="isMobile">
            <button @click="toggleMobileMode('write')"
                    :class="!isPreviewMode ? 'bg-white border-gray-300 text-gray-900 shadow-sm' : 'bg-transparent border-transparent text-gray-500'"
                    class="px-4 py-2 text-sm font-medium border-r border-gray-200 rounded-l-md transition-all duration-200">
                ✏️ Write
            </button>
            <button @click="toggleMobileMode('preview')"
                    :class="isPreviewMode ? 'bg-white border-gray-300 text-gray-900 shadow-sm' : 'bg-transparent border-transparent text-gray-500'"
                    class="px-4 py-2 text-sm font-medium rounded-r-md transition-all duration-200">
                👁️ Preview
            </button>
        </div>

        <!-- Desktop Controls -->
        <div class="hidden md:flex items-center space-x-6">
            <div class="flex items-center space-x-2">
                <span class="text-sm font-semibold text-gray-800">Unified Markdown Editor</span>
                <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full" x-text="`${getPerformanceMode().mode} mode`"></span>
            </div>

            <div class="flex items-center space-x-3">
                <!-- Split View Toggle -->
                <button @click="toggleSplitView()"
                        :class="showSplitView ? 'text-blue-600 bg-blue-50' : 'text-gray-500 hover:text-blue-600'"
                        class="flex items-center space-x-1 px-3 py-1 text-sm rounded-md transition-all duration-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span x-text="showSplitView ? 'Hide Preview' : 'Show Preview'"></span>
                </button>

                <!-- Scroll Sync Toggle -->
                <button @click="toggleScrollSync()"
                        :class="scrollSyncEnabled ? 'text-green-600 bg-green-50' : 'text-gray-500'"
                        class="flex items-center space-x-1 px-3 py-1 text-sm rounded-md transition-all duration-200"
                        title="Toggle scroll synchronization">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2v0a2 2 0 01-2-2v-5H8z"/>
                    </svg>
                    <span x-show="scrollSyncEnabled">🔗</span>
                    <span x-show="!scrollSyncEnabled">🔗</span>
                </button>

                <!-- Performance Info -->
                <div class="text-xs text-gray-500" x-show="previewCache.size > 0">
                    📦 <span x-text="previewCache.size"></span> cached
                </div>
            </div>
        </div>

        <!-- Upload Progress & Status -->
        <div class="flex items-center space-x-4">
            <!-- Active Uploads -->
            <div x-show="hasActiveUploads()" class="flex items-center space-x-2">
                <svg class="w-4 h-4 text-blue-500 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm text-blue-600">Uploading files...</span>
            </div>

            <!-- Preview Status -->
            <div x-show="isPreviewLoading" class="flex items-center space-x-1">
                <svg class="w-3 h-3 animate-spin text-gray-500" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-xs text-gray-500">Rendering...</span>
            </div>
        </div>
    </div>

    <!-- Enhanced Editor Content with Variable Split -->
    <div :class="showSplitView ? 'grid gap-4' : ''"
         :style="showSplitView ? `grid-template-columns: ${100 - previewWidth}% ${previewWidth}%` : ''"
         class="border border-gray-200 rounded-b-lg bg-white min-h-[500px] overflow-hidden">

        <!-- Editor Panel -->
        <div :class="isPreviewMode && isMobile ? 'hidden' : ''"
             class="relative flex flex-col">

            <!-- Enhanced Markdown Toolbar -->
            <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 px-3 py-2">
                <div class="flex flex-wrap items-center gap-1">
                    <!-- Text Formatting Group -->
                    <div class="flex items-center bg-white rounded border border-gray-200 p-1">
                        <button @click="insertMarkdown('bold')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Bold (Ctrl+B)">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M5 4v12h4.5c2.5 0 4.5-1.5 4.5-4 0-1.5-.5-2.5-1.5-3 1-.5 1.5-1.5 1.5-3 0-2.5-2-4-4.5-4H5zm3 5h2c1 0 1.5.5 1.5 1.5S11 12 10 12H8V9zm0 6h2.5c1.5 0 2.5.5 2.5 2s-1 2-2.5 2H8v-4z"/>
                            </svg>
                        </button>
                        <button @click="insertMarkdown('italic')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Italic (Ctrl+I)">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M8 4v1h2l-2 10H6v1h6v-1h-2l2-10h2V4H8z"/>
                            </svg>
                        </button>
                        <button @click="insertMarkdown('code')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Inline Code">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6.5 7.5l-3 3 3 3M13.5 7.5l3 3-3 3"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Headers Group -->
                    <div class="flex items-center bg-white rounded border border-gray-200 p-1">
                        <button @click="insertMarkdown('heading1')"
                                class="px-2 py-1 text-xs font-bold rounded hover:bg-gray-100 transition-colors"
                                title="Heading 1">H1</button>
                        <button @click="insertMarkdown('heading2')"
                                class="px-2 py-1 text-xs font-bold rounded hover:bg-gray-100 transition-colors"
                                title="Heading 2">H2</button>
                        <button @click="insertMarkdown('heading3')"
                                class="px-2 py-1 text-xs font-bold rounded hover:bg-gray-100 transition-colors"
                                title="Heading 3">H3</button>
                    </div>

                    <!-- Lists Group -->
                    <div class="flex items-center bg-white rounded border border-gray-200 p-1">
                        <button @click="insertMarkdown('list')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Bullet List">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 4a1 1 0 100 2 1 1 0 000-2zM6 4h11a1 1 0 110 2H6a1 1 0 110-2zM3 9a1 1 0 100 2 1 1 0 000-2zM6 9h11a1 1 0 110 2H6a1 1 0 110-2zM3 14a1 1 0 100 2 1 1 0 000-2zM6 14h11a1 1 0 110 2H6a1 1 0 110-2z"/>
                            </svg>
                        </button>
                        <button @click="insertMarkdown('numberedList')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Numbered List">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M3 2v2h1v1H3v1h2V5h1V2H3zM3 8v1h1v1H3v1h2v-1h1V8H3zM3 14v1h1v1H3v1h2v-1h1v-1H3zM7 3h11a1 1 0 110 2H7a1 1 0 110-2zM7 8h11a1 1 0 110 2H7a1 1 0 110-2zM7 13h11a1 1 0 110 2H7a1 1 0 110-2z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Links and Media Group -->
                    <div class="flex items-center bg-white rounded border border-gray-200 p-1">
                        <button @click="insertMarkdown('link')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Link (Ctrl+K)">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                        </button>
                        <button @click="insertMarkdown('image')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Image">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Advanced Group -->
                    <div class="flex items-center bg-white rounded border border-gray-200 p-1 toolbar-advanced">
                        <button @click="insertMarkdown('codeBlock')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Code Block">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                            </svg>
                        </button>
                        <button @click="insertMarkdown('table')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Table">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0V4a1 1 0 011-1h16a1 1 0 011 1v16a1 1 0 01-1 1H5a1 1 0 01-1-1V10z"/>
                            </svg>
                        </button>
                        <button @click="insertMarkdown('quote')"
                                class="p-1 rounded hover:bg-gray-100 tooltip transition-colors"
                                title="Quote">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM16 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Mobile Quick Actions -->
                    <div class="md:hidden flex items-center bg-blue-50 rounded border border-blue-200 p-1">
                        <button @click="insertMarkdown('bold')"
                                class="px-2 py-1 text-xs font-medium text-blue-800 rounded hover:bg-blue-100 transition-colors">
                            B
                        </button>
                        <button @click="insertMarkdown('italic')"
                                class="px-2 py-1 text-xs font-medium text-blue-800 rounded hover:bg-blue-100 transition-colors">
                            I
                        </button>
                        <button @click="insertMarkdown('list')"
                                class="px-2 py-1 text-xs font-medium text-blue-800 rounded hover:bg-blue-100 transition-colors">
                            •
                        </button>
                        <button @click="insertMarkdown('link')"
                                class="px-2 py-1 text-xs font-medium text-blue-800 rounded hover:bg-blue-100 transition-colors">
                            🔗
                        </button>
                    </div>

                    <!-- Learning Content Group -->
                    <div class="flex items-center bg-green-50 rounded border border-green-200 p-1">
                        <button @click="insertMarkdown('collapsible')"
                                class="px-2 py-1 text-xs font-medium text-green-800 rounded hover:bg-green-100 transition-colors"
                                title="Collapsible Section">📁 Collapse</button>
                        <button @click="insertMarkdown('callout')"
                                class="px-2 py-1 text-xs font-medium text-green-800 rounded hover:bg-green-100 transition-colors"
                                title="Callout Box">💡 Callout</button>
                    </div>
                </div>
            </div>

            <!-- Drop Zone Overlay -->
            <div class="absolute inset-0 bg-blue-50 border-2 border-dashed border-blue-300 hidden items-center justify-center z-10 drag-over-indicator">
                <div class="text-center">
                    <svg class="mx-auto h-16 w-16 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                    <p class="mt-4 text-lg font-medium text-blue-600">Drop files here to upload</p>
                    <p class="text-sm text-blue-500">Images, documents, videos, and audio files supported</p>
                </div>
            </div>

            <!-- Enhanced Text Editor -->
            <div class="flex-1 relative">
                <textarea x-ref="markdownEditor"
                          x-model="content"
                          class="w-full h-full p-4 border-0 focus:ring-0 focus:outline-none resize-none font-mono text-sm leading-relaxed"
                          placeholder="# Start writing your content...

You can drag and drop files directly into this editor, or paste images from your clipboard.

**Enhanced features:**
- Real-time preview with scroll sync
- Smart indentation with Tab key
- Keyboard shortcuts (Ctrl+B for bold, Ctrl+I for italic, etc.)
- Performance optimizations for large content
- Advanced markdown support with collapsible sections

## Getting Started

1. Type markdown naturally
2. Use the toolbar for quick formatting
3. Drag & drop files for instant embedding
4. Watch the live preview update as you type

> 💡 **Tip:** Press Ctrl+/ to toggle preview, Alt+P for split view"
                          style="min-height: 400px;"></textarea>

                <!-- Syntax Highlighting Overlay (Future Enhancement) -->
                <div x-show="syntaxHighlighting && false" class="absolute inset-0 pointer-events-none opacity-50 z-10">
                    <!-- Syntax highlighting will be implemented here -->
                </div>
            </div>

            <!-- Upload Progress Indicators -->
            <div x-show="getUploadProgress().length > 0"
                 class="absolute bottom-4 right-4 bg-white border border-gray-200 rounded-lg shadow-lg p-3 max-w-sm z-20">
                <div class="mb-2 text-xs font-medium text-gray-700">File Uploads</div>
                <template x-for="upload in getUploadProgress()" :key="upload.filename">
                    <div class="flex items-center space-x-2 mb-2 last:mb-0">
                        <div class="flex-1">
                            <div class="text-xs font-medium truncate" x-text="upload.filename"></div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 mt-1">
                                <div class="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                                     :style="`width: ${upload.progress}%`"></div>
                            </div>
                        </div>
                        <div class="text-xs text-gray-500 min-w-0" x-text="`${upload.progress}%`"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Enhanced Preview Panel with Resizable Border -->
        <div x-show="(showSplitView && !isMobile) || (isPreviewMode && isMobile)"
             class="relative flex flex-col"
             x-ref="previewPanel">

            <!-- Preview Header with Controls -->
            <div class="border-b border-gray-200 bg-gradient-to-r from-gray-50 to-gray-100 px-3 py-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-2">
                        <span class="text-sm font-medium text-gray-700">Live Preview</span>
                        <span x-show="!isMobile" class="text-xs text-gray-500">
                            🔗 <span x-text="scrollSyncEnabled ? 'Scroll sync on' : 'Scroll sync off'"></span>
                        </span>
                    </div>

                    <div class="flex items-center space-x-2">
                        <!-- Performance Indicator -->
                        <div class="flex items-center space-x-1">
                            <div :class="{
                                'w-2 h-2 rounded-full': true,
                                'bg-green-400': !isPreviewLoading,
                                'bg-yellow-400 animate-pulse': isPreviewLoading
                            }"></div>
                            <span class="text-xs text-gray-500" x-text="isPreviewLoading ? 'Rendering...' : 'Ready'"></span>
                        </div>

                        <!-- Refresh Preview -->
                        <button @click="renderPreview()"
                                class="p-1 rounded hover:bg-gray-200 tooltip"
                                title="Refresh Preview">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Preview Content with Enhanced Styling -->
            <div class="flex-1 overflow-auto p-4 prose prose-sm max-w-none bg-white"
                 x-html="previewHtml || '<div class=\'text-gray-500 italic text-center py-8\'><svg class=\'mx-auto w-12 h-12 mb-4 text-gray-300\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z\'></path></svg><p>Start typing to see a live preview...</p><p class=\'text-xs mt-2\'>Your content will appear here with enhanced markdown rendering</p></div>'">
            </div>

            <!-- Resizable Border -->
            <div x-show="!isMobile && showSplitView"
                 class="absolute left-0 top-0 bottom-0 w-1 bg-gray-300 hover:bg-blue-400 cursor-col-resize transition-colors"
                 @mousedown="startResize($event)">
            </div>
        </div>
    </div>

    <!-- Enhanced Content Statistics & Save Section -->
    <div class="bg-gradient-to-r from-gray-50 to-gray-100 border border-gray-200 rounded-lg p-4">
        <div class="flex items-center justify-between">
            <!-- Real-time Content Stats -->
            <div class="flex items-center space-x-6 text-sm text-gray-600">
                <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span x-text="`${getCharacterCount()} chars`"></span>
                </div>
                <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                    </svg>
                    <span x-text="`${getWordCount()} words`"></span>
                </div>
                <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span x-text="`${getReadingTime()} min read`"></span>
                </div>
                <div x-show="content.length > 0" class="flex items-center space-x-1 text-green-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>Auto-saving</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center space-x-3">
                <!-- Performance Indicator -->
                <div class="text-xs text-gray-500" x-show="getPerformanceMode().cacheSize > 0">
                    <span x-text="`Cache: ${getPerformanceMode().cacheSize}`"></span>
                    <button @click="clearPreviewCache()" class="ml-1 text-blue-600 hover:text-blue-800">Clear</button>
                </div>

                <!-- Keyboard Shortcuts Help -->
                <button @click="showNotification('Keyboard shortcuts: Ctrl+B (bold), Ctrl+I (italic), Ctrl+K (link), Ctrl+S (save), Ctrl+/ (toggle preview)', 'info')"
                        class="text-gray-500 hover:text-gray-700"
                        title="Show keyboard shortcuts">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </button>

                <!-- Save Button -->
                <form x-ref="saveForm"
                      hx-put="{{ route('topics.update', ['topic' => $topic->id]) }}"
                      hx-target="#topics-list"
                      hx-include="[name='unified_content'], [name='name'], [name='estimated_minutes'], [name='required']"
                      @submit.prevent="saveContent()">
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

                    <button type="submit"
                            :disabled="hasActiveUploads()"
                            class="px-6 py-2 bg-green-600 text-white rounded-lg text-sm font-medium hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all duration-200 shadow-sm hover:shadow">
                        <span x-show="!hasActiveUploads()">💾 Save Content</span>
                        <span x-show="hasActiveUploads()">⏳ Uploading...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Drag and Drop & Editor Styles -->
<style>
/* Drag and Drop Enhancement */
.drag-over .drag-over-indicator {
    display: flex !important;
}

/* Enhanced Tooltip Styling */
.tooltip {
    position: relative;
}

.tooltip:hover::after {
    content: attr(title);
    position: absolute;
    bottom: calc(100% + 8px);
    left: 50%;
    transform: translateX(-50%);
    background: #1f2937;
    color: white;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    white-space: nowrap;
    z-index: 1000;
    pointer-events: none;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.tooltip:hover::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 2px);
    left: 50%;
    transform: translateX(-50%);
    border: 4px solid transparent;
    border-top-color: #1f2937;
    z-index: 1000;
    pointer-events: none;
}

/* Enhanced Prose Styling for Preview */
.prose {
    max-width: none !important;
}

.prose h1 { @apply text-3xl font-bold mt-8 mb-6 text-gray-900 border-b border-gray-200 pb-2; }
.prose h2 { @apply text-2xl font-bold mt-6 mb-4 text-gray-900; }
.prose h3 { @apply text-xl font-bold mt-5 mb-3 text-gray-900; }
.prose h4 { @apply text-lg font-semibold mt-4 mb-2 text-gray-900; }
.prose p { @apply mb-4 text-gray-700 leading-relaxed; }
.prose ul, .prose ol { @apply mb-4 pl-6; }
.prose li { @apply mb-2 text-gray-700; }
.prose blockquote {
    @apply border-l-4 border-blue-300 pl-4 italic text-gray-600 mb-4 bg-blue-50 py-2 rounded-r;
}
.prose code {
    @apply bg-gray-100 px-2 py-1 rounded text-sm font-mono text-red-600 border;
}
.prose pre {
    @apply bg-gray-900 text-green-400 p-4 rounded-lg overflow-x-auto mb-4 border border-gray-700;
}
.prose pre code {
    @apply bg-transparent px-0 py-0 text-green-400 border-0;
}
.prose table {
    @apply border-collapse border border-gray-300 mb-4 w-full;
}
.prose th, .prose td {
    @apply border border-gray-300 px-4 py-2 text-left;
}
.prose th {
    @apply bg-gray-100 font-semibold text-gray-900;
}
.prose img {
    @apply max-w-full h-auto rounded-lg shadow-md mb-4 border border-gray-200;
}
.prose a {
    @apply text-blue-600 hover:text-blue-800 underline font-medium;
}
.prose hr {
    @apply border-gray-300 my-8;
}

/* Enhanced Video Embeds */
.prose .video-embed {
    @apply rounded-lg shadow-lg overflow-hidden mb-6 border border-gray-200;
}

/* Enhanced File Previews */
.prose .file-preview {
    @apply bg-gray-50 border border-gray-200 rounded-lg p-4 mb-4 flex items-center space-x-3;
}

/* Collapsible Content Styling */
.prose .collapse-content {
    @apply border border-gray-200 rounded-lg mb-4 overflow-hidden;
}

.prose .collapse-header {
    @apply bg-gray-50 px-4 py-3 cursor-pointer hover:bg-gray-100 transition-colors;
}

.prose .collapse-body {
    @apply px-4 py-3 border-t border-gray-200;
}

/* Callout Boxes */
.prose .callout {
    @apply border-l-4 px-4 py-3 mb-4 rounded-r;
}

.prose .callout.note {
    @apply border-blue-400 bg-blue-50;
}

.prose .callout.warning {
    @apply border-yellow-400 bg-yellow-50;
}

.prose .callout.tip {
    @apply border-green-400 bg-green-50;
}

.prose .callout.error {
    @apply border-red-400 bg-red-50;
}

/* Performance Mode Indicators */
.performance-fast {
    @apply text-yellow-600;
}

.performance-auto {
    @apply text-blue-600;
}

.performance-quality {
    @apply text-green-600;
}

/* Resizable Border Styling */
.cursor-col-resize:hover {
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.5), transparent);
}

/* Animation for notifications */
@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.notification {
    animation: slideInRight 0.3s ease-out;
}
</style>

<script>
// Enhanced resize functionality for split view
function startResize(event) {
    const startX = event.clientX;
    const container = event.target.closest('[x-data]');
    const startWidth = container.__x.$data.previewWidth;

    function onMouseMove(e) {
        const deltaX = e.clientX - startX;
        const containerWidth = container.offsetWidth;
        const deltaPercent = (deltaX / containerWidth) * 100;
        const newWidth = Math.max(20, Math.min(80, startWidth - deltaPercent));
        container.__x.$data.previewWidth = newWidth;
    }

    function onMouseUp() {
        document.removeEventListener('mousemove', onMouseMove);
        document.removeEventListener('mouseup', onMouseUp);
    }

    document.addEventListener('mousemove', onMouseMove);
    document.addEventListener('mouseup', onMouseUp);
}
</script>