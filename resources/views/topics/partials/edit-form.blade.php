<!-- Full Page Edit Form -->
<form method="POST" action="{{ route('topics.update', ['topic' => $topic->id]) }}" class="space-y-6">
    @csrf
    @method('PUT')

    <!-- Basic Information -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Title -->
        <div>
            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">{{ __('Topic Title') }}</label>
            <input type="text"
                   id="title"
                   name="title"
                   value="{{ $topic->title }}"
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
                   value="{{ $topic->estimated_minutes }}"
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
                  placeholder="{{ __('Brief overview of this topic...') }}">{{ $topic->description }}</textarea>
        <p class="text-xs text-gray-500 mt-1">{{ __('Optional: A brief summary that appears in topic lists') }}</p>
    </div>

    <!-- Required Topic Toggle -->
    <div class="flex items-center">
        <input type="checkbox"
               id="required"
               name="required"
               value="1"
               {{ $topic->required ? 'checked' : '' }}
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
                'content' => $topic->learning_content ?? '',
                'topicId' => $topic->id,
                'fieldName' => 'learning_content'
            ])
        </div>
        <p class="text-xs text-gray-500 mt-2">
            {{ __('Use markdown to format text. Drag & drop files to upload. Paste URLs for videos.') }}
        </p>
    </div>

    <!-- Action Buttons -->
    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
        <a href="{{ route('units.topics.show', [$unit->id, $topic->id]) }}"
           class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
           data-testid="cancel-button">
            {{ __('Cancel') }}
        </a>

        <div class="flex space-x-3">
            <button type="submit"
                    class="px-6 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                    data-testid="save-button">
                {{ __('Save Changes') }}
            </button>
        </div>
    </div>
</form>