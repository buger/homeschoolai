<!-- Modal Overlay -->
<div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50"
     x-data="{ open: true }"
     x-show="open"
     data-testid="topic-modal"
     style="display: flex !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-6xl mx-4 relative z-50 max-h-[90vh] overflow-y-auto"
         data-testid="modal-content"
         @click.stop>
        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-medium text-gray-900">{{ __('Add New Topic') }}</h3>
            <button type="button" @click="open = false; document.getElementById('topic-modal').innerHTML = '';" class="text-gray-400 hover:text-gray-600" data-testid="close-modal">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Create Form -->
        <form hx-post="{{ route('units.topics.store', $unit->id) }}"
              hx-target="#topics-list"
              hx-swap="innerHTML"
              hx-on::after-request="if(event.detail.successful) { document.getElementById('topic-modal').innerHTML = ''; }"
              data-testid="topic-create-form"
              class="space-y-6">
            @csrf

            <!-- Basic Information -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Topic Title') }}</label>
                    <input type="text"
                           id="title"
                           name="title"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           data-testid="topic-title-input"
                           required>
                </div>

                <!-- Estimated Minutes -->
                <div>
                    <label for="estimated_minutes" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Estimated Time (minutes)') }}</label>
                    <input type="number"
                           id="estimated_minutes"
                           name="estimated_minutes"
                           value="30"
                           min="1"
                           max="480"
                           class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           data-testid="estimated-minutes-input">
                </div>
            </div>

            <!-- Short Description -->
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Short Description') }}</label>
                <textarea id="description"
                          name="description"
                          rows="2"
                          class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          data-testid="topic-description-input"
                          placeholder="{{ __('Brief overview of this topic...') }}"></textarea>
                <p class="text-xs text-gray-500 mt-1">{{ __('Optional: A brief summary that appears in topic lists') }}</p>
            </div>

            <!-- Required Topic Toggle -->
            <div class="flex items-center">
                <input type="checkbox"
                       id="required"
                       name="required"
                       value="1"
                       checked
                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                       data-testid="required-checkbox">
                <label for="required" class="ml-2 block text-sm text-gray-700">
                    {{ __('Required Topic') }}
                    <span class="text-gray-500 text-xs block">{{ __('Students must complete this topic to progress') }}</span>
                </label>
            </div>

            <!-- Unified Markdown Content Editor -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">{{ __('Learning Content') }}</label>
                <div class="border border-gray-300 rounded-lg overflow-hidden">
                    @include('topics.partials.unified-markdown-editor', [
                        'content' => '',
                        'topicId' => null,
                        'fieldName' => 'learning_content'
                    ])
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    {{ __('Use markdown to format text. Drag & drop files to upload. Paste URLs for videos.') }}
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                <button type="button"
                        @click="open = false; document.getElementById('topic-modal').innerHTML = '';"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        data-testid="cancel-button">
                    {{ __('Cancel') }}
                </button>

                <div class="flex space-x-3">
                    <button type="submit"
                            class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                            data-testid="save-button">
                        {{ __('Create Topic') }}
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>