<!-- Modal Overlay -->
<div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
     x-data="{
         open: true,
         activeTab: 'basic',
         showVideoForm: false,
         showLinkForm: false,
         showFileForm: false
     }"
     x-show="open"
     data-testid="topic-edit-modal"
     style="display: flex !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-4xl mx-4 relative z-50"
         data-testid="modal-content"
         @click.stop>
        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">Edit Topic: {{ $topic->title }}</h3>
            <button type="button" @click="open = false; document.getElementById('topic-modal').innerHTML = '';" class="text-gray-400 hover:text-gray-600" data-testid="close-modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Tab Navigation -->
        <div class="mb-6 border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button @click="activeTab = 'basic'"
                        :class="activeTab === 'basic' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Basic Info
                </button>
                <button @click="activeTab = 'content'"
                        :class="activeTab === 'content' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Rich Content
                    @if($topic->hasRichContent())
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            {{ $topic->getWordCount() }} words
                        </span>
                    @endif
                </button>
                <button @click="activeTab = 'materials'"
                        :class="activeTab === 'materials' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2 px-1 border-b-2 font-medium text-sm transition-colors">
                    Learning Materials
                    @if($topic->hasLearningMaterials())
                        <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $topic->getLearningMaterialsCount() }}
                        </span>
                    @endif
                </button>
            </nav>
        </div>

        <!-- Basic Info Tab -->
        <div x-show="activeTab === 'basic'" class="space-y-6">
            <form hx-put="{{ route('topics.update', ['topic' => $topic->id]) }}" hx-target="#topics-list">
                @csrf
                @method('PUT')

                <!-- Topic Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Topic Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', $topic->title) }}"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="e.g., Introduction to Fractions">
                </div>

                <!-- Basic Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                        Basic Description
                        <span class="text-xs text-gray-500">(Use Rich Content tab for advanced formatting)</span>
                    </label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Brief description of this topic...">{{ old('description', $topic->content_format === 'plain' ? $topic->description : '') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">
                        @if($topic->content_format !== 'plain')
                            Rich content is enabled. Switch to Rich Content tab for full editing.
                        @else
                            Plain text description. Switch to Rich Content tab for formatting options.
                        @endif
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Estimated Minutes -->
                    <div>
                        <label for="estimated_minutes" class="block text-sm font-medium text-gray-700 mb-2">Estimated Duration (Minutes)</label>
                        <input
                            type="number"
                            id="estimated_minutes"
                            name="estimated_minutes"
                            value="{{ old('estimated_minutes', $topic->estimated_minutes) }}"
                            required
                            min="5"
                            max="480"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <p class="text-xs text-gray-500 mt-1">5-480 minutes</p>
                    </div>

                    <!-- Required -->
                    <div class="flex items-center">
                        <div>
                            <label class="flex items-center">
                                <input
                                    type="checkbox"
                                    name="required"
                                    value="1"
                                    {{ old('required', $topic->required) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Required topic</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1">Must be completed for unit completion</p>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-3 pt-4">
                    <button
                        type="button"
                        @click="open = false; document.getElementById('topic-modal').innerHTML = '';"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                        Update Topic
                    </button>
                </div>
            </form>
        </div>

        <!-- Enhanced Markdown Content Tab -->
        <div x-show="activeTab === 'content'" class="space-y-6">
            @if($topic->isUnified())
                <!-- GitHub-style Unified Markdown Editor -->
                @include('topics.partials.github-markdown-editor', ['topic' => $topic])
            @else
                <!-- Legacy Rich Content Editor -->
                <div x-data="richContentEditor()"
                     x-init="init()"
                     data-topic-id="{{ $topic->id }}"
                     data-content="{{ $topic->description }}"
                     data-format="{{ $topic->content_format }}">

                    <!-- Migration Notice -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">
                                    Enhanced Markdown System Available
                                </h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>This topic can be upgraded to the new unified markdown system with drag-drop file uploads and live preview.</p>
                                </div>
                                <div class="mt-4">
                                    <form method="POST" action="{{ route('topics.migrate', $topic->id) }}">
                                        @csrf
                                        <button type="submit" class="bg-blue-100 hover:bg-blue-200 text-blue-800 text-sm font-medium px-3 py-2 rounded">
                                            Upgrade to Enhanced Editor
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Content Format Selection -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-3">Content Format</label>
                        <div class="flex space-x-4">
                            <button @click="switchFormat('plain')"
                                    :class="format === 'plain' ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-white border-gray-300 text-gray-700'"
                                    class="px-4 py-2 border rounded-md text-sm font-medium transition-colors">
                                Plain Text
                            </button>
                            <button @click="switchFormat('markdown')"
                                    :class="format === 'markdown' ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-white border-gray-300 text-gray-700'"
                                    class="px-4 py-2 border rounded-md text-sm font-medium transition-colors">
                                Markdown
                            </button>
                            <button @click="switchFormat('html')"
                                    :class="format === 'html' ? 'bg-blue-100 border-blue-300 text-blue-700' : 'bg-white border-gray-300 text-gray-700'"
                                    class="px-4 py-2 border rounded-md text-sm font-medium transition-colors">
                                Rich HTML
                            </button>
                        </div>
                    </div>

                    <!-- Editor Actions -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center space-x-4">
                            <button @click="togglePreview()"
                                    :class="isPreviewMode ? 'bg-green-100 text-green-700' : 'bg-white text-gray-700'"
                                    class="px-3 py-1 border border-gray-300 rounded text-sm hover:bg-gray-50">
                                <span x-text="isPreviewMode ? 'Edit' : 'Preview'"></span>
                            </button>

                            <div class="text-sm text-gray-500" x-show="wordCount > 0">
                                <span x-text="wordCount"></span> words •
                                <span x-text="getReadingTimeDisplay()"></span>
                            </div>
                        </div>

                        <!-- Save Button -->
                        <form hx-put="{{ route('topics.update', ['topic' => $topic->id]) }}"
                              hx-target="#topics-list"
                              hx-include="[x-data='richContentEditor()'] input, [x-data='richContentEditor()'] textarea, [x-data='richContentEditor()'] select">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="content_format" :value="format">
                            <input type="hidden" name="description" :value="content">
                            <input type="hidden" name="name" value="{{ $topic->title }}">
                            <input type="hidden" name="estimated_minutes" value="{{ $topic->estimated_minutes }}">
                            <input type="hidden" name="required" value="{{ $topic->required ? '1' : '0' }}">

                            <button type="submit"
                                    class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                Save Rich Content
                            </button>
                        </form>
                    </div>

                    <!-- Loading Indicator -->
                    <div x-show="isLoading" class="text-center py-4">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </div>
                    </div>

                    <!-- Content Editor -->
                    <div x-show="!isPreviewMode && !isLoading">
                        <!-- Plain Text Editor -->
                        <div x-show="format === 'plain'" class="space-y-4">
                            <textarea x-model="content"
                                      @input="updateMetadata()"
                                      rows="12"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                      placeholder="Enter your content here..."></textarea>
                        </div>

                        <!-- Basic Markdown Editor -->
                        <div x-show="format === 'markdown'" class="space-y-4">
                            <div class="border border-gray-300 rounded-md overflow-hidden">
                                <div class="bg-gray-50 px-3 py-2 border-b border-gray-300">
                                    <div class="flex items-center space-x-2 text-sm">
                                        <span class="text-gray-600">Basic Markdown Editor</span>
                                        <button type="button" @click="insertMarkdown('**Bold**')" class="px-2 py-1 text-xs bg-white border rounded">Bold</button>
                                        <button type="button" @click="insertMarkdown('*Italic*')" class="px-2 py-1 text-xs bg-white border rounded">Italic</button>
                                        <button type="button" @click="insertMarkdown('## Heading')" class="px-2 py-1 text-xs bg-white border rounded">Heading</button>
                                        <button type="button" @click="insertMarkdown('- List item')" class="px-2 py-1 text-xs bg-white border rounded">List</button>
                                        <button type="button" @click="insertMarkdown('[Link text](url)')" class="px-2 py-1 text-xs bg-white border rounded">Link</button>
                                    </div>
                                </div>
                                <textarea x-model="content"
                                          @input="updateMetadata()"
                                          rows="12"
                                          class="w-full px-3 py-2 border-0 focus:ring-0 font-mono text-sm"
                                          placeholder="# Your Heading

Write your **markdown** content here...

- List item 1
- List item 2

[Link text](https://example.com)

![Alt text](image-url)"></textarea>
                            </div>
                        </div>

                        <!-- Rich HTML Editor (TinyMCE) -->
                        <div x-show="format === 'html'" class="space-y-4">
                            <textarea :id="`rich-content-editor-${topicId}`"
                                      x-model="content"
                                      class="w-full min-h-[400px]"></textarea>
                        </div>
                    </div>

                    <!-- Content Preview -->
                    <div x-show="isPreviewMode && !isLoading">
                        <div :id="`preview-${topicId}`" class="border border-gray-300 rounded-md p-6 bg-white">
                            <!-- Preview content will be loaded here -->
                        </div>
                    </div>

                    <!-- Image Gallery -->
                    <div x-show="!isLoading" class="mt-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">Content Images</h4>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" x-data="{ images: [] }" x-init="loadImages()">
                            <template x-for="(image, index) in images" :key="index">
                                <div class="relative group">
                                    <img :src="image.thumbnail_url || image.url"
                                         :alt="image.alt_text"
                                         class="w-full h-24 object-cover rounded border">
                                    <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded flex items-center justify-center">
                                        <button @click="insertImageMarkdown(image)"
                                                class="text-white text-xs px-2 py-1 bg-blue-600 rounded mr-1">Insert</button>
                                        <button @click="deleteImage(index)"
                                                class="text-white text-xs px-2 py-1 bg-red-600 rounded">Delete</button>
                                    </div>
                                </div>
                            </template>

                            <!-- Upload new image -->
                            <label class="w-full h-24 border-2 border-dashed border-gray-300 rounded flex items-center justify-center cursor-pointer hover:border-gray-400">
                                <div class="text-center">
                                    <svg class="w-6 h-6 text-gray-400 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span class="text-xs text-gray-500">Add Image</span>
                                </div>
                                <input type="file"
                                       accept="image/*"
                                       class="hidden"
                                       @change="uploadImage($event)">
                            </label>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Learning Materials Tab -->
        <div x-show="activeTab === 'materials'" class="space-y-6">
            <!-- Add Material Buttons -->
            <div class="flex flex-wrap gap-3">
                <button @click="showVideoForm = !showVideoForm; showLinkForm = false; showFileForm = false"
                        :class="showVideoForm ? 'bg-red-50 border-red-200 text-red-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                        class="inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Add Video
                </button>

                <button @click="showLinkForm = !showLinkForm; showVideoForm = false; showFileForm = false"
                        :class="showLinkForm ? 'bg-blue-50 border-blue-200 text-blue-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                        class="inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                    </svg>
                    Add Link
                </button>

                <button @click="showFileForm = !showFileForm; showVideoForm = false; showLinkForm = false"
                        :class="showFileForm ? 'bg-green-50 border-green-200 text-green-700' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
                        class="inline-flex items-center px-4 py-2 border rounded-md text-sm font-medium transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Upload File
                </button>
            </div>

            <!-- Video Form -->
            <div x-show="showVideoForm" x-transition class="bg-red-50 border border-red-200 rounded-lg p-4">
                <form hx-post="{{ route('topics.materials.video', $topic->id) }}" hx-target="#materials-section" hx-swap="outerHTML">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Video URL</label>
                            <input type="url" name="video_url" required
                                   placeholder="https://www.youtube.com/watch?v=..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                   data-testid="video-url">
                            <p class="text-xs text-gray-500 mt-1">YouTube, Vimeo, or Khan Academy links</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                                <input type="text" name="video_title"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                       data-testid="video-title">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                                <input type="text" name="video_description"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                       data-testid="video-description">
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700" data-testid="add-video-submit">
                            Add Video
                        </button>
                    </div>
                </form>
            </div>

            <!-- Link Form -->
            <div x-show="showLinkForm" x-transition class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <form hx-post="{{ route('topics.materials.link', $topic->id) }}" hx-target="#materials-section" hx-swap="outerHTML">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Link URL</label>
                            <input type="url" name="link_url" required
                                   placeholder="https://example.com/article"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                   data-testid="link-url">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                                <input type="text" name="link_title"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                       data-testid="link-title">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                                <input type="text" name="link_description"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                       data-testid="link-description">
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700" data-testid="add-link-submit">
                            Add Link
                        </button>
                    </div>
                </form>
            </div>

            <!-- File Form -->
            <div x-show="showFileForm" x-transition class="bg-green-50 border border-green-200 rounded-lg p-4">
                <form hx-post="{{ route('topics.materials.file', $topic->id) }}" hx-target="#materials-section" hx-swap="outerHTML" hx-encoding="multipart/form-data">
                    @csrf
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">File</label>
                            <input type="file" name="file" required accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.gif,.mp4,.mp3,.wav"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                   data-testid="file-upload">
                            <p class="text-xs text-gray-500 mt-1">Max 10MB. Allowed: PDF, DOC, Images, Audio, Video</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                            <input type="text" name="file_title"
                                   placeholder="Leave blank to use filename"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm"
                                   data-testid="file-title">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700" data-testid="add-file-submit">
                            Upload File
                        </button>
                    </div>
                </form>
            </div>

            <!-- Current Materials -->
            <div id="materials-section">
                @include('topics.partials.materials-section', ['topic' => $topic])
            </div>
        </div>
    </div>
</div>