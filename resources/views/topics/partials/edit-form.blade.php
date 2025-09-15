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
            <button type="button" @click="open = false; document.getElementById('topic-modal').innerHTML = '';" class="text-gray-400 hover:text-gray-600">
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

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                        placeholder="Describe what this topic covers and any key concepts...">{{ old('description', $topic->description) }}</textarea>
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
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <p class="text-xs text-gray-500 mt-1">YouTube, Vimeo, or Khan Academy links</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                                <input type="text" name="video_title"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                                <input type="text" name="video_description"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700">
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
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                                <input type="text" name="link_title"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                                <input type="text" name="link_description"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            </div>
                        </div>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
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
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                            <p class="text-xs text-gray-500 mt-1">Max 10MB. Allowed: PDF, DOC, Images, Audio, Video</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Title (optional)</label>
                            <input type="text" name="file_title"
                                   placeholder="Leave blank to use filename"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
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