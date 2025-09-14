<!-- Modal Overlay -->
<div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" 
     x-data="{ open: true }" 
     x-show="open"
     data-testid="topic-edit-modal"
     style="display: flex !important;">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 relative z-50" 
         data-testid="modal-content"
         @click.stop>
        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Topic</h3>
            <button type="button" @click="open = false; document.getElementById('topic-modal').innerHTML = '';" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <form hx-put="{{ route('topics.update', ['topic' => $topic->id]) }}" hx-target="#topics-list">
            @csrf
            @method('PUT')
            
            <!-- Topic Name -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Topic Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $topic->title) }}"
                    required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('topic_name_example') }}">
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('topic_description_placeholder') }}">{{ old('description', $topic->description) }}</textarea>
            </div>

            <!-- Estimated Minutes -->
            <div class="mb-4">
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
                <p class="text-xs text-gray-500 mt-1">How long you expect this topic to take (5-480 minutes)</p>
            </div>

            <!-- Required -->
            <div class="mb-6">
                <label class="flex items-center">
                    <input 
                        type="checkbox" 
                        name="required" 
                        value="1" 
                        {{ old('required', $topic->required) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <span class="ml-2 text-sm text-gray-700">Required topic</span>
                </label>
                <p class="text-xs text-gray-500 mt-1">Required topics must be completed for unit completion</p>
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
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
</div>