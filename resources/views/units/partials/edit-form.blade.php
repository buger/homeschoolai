<!-- Modal Overlay -->
<div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" x-data="{ open: true }" data-testid="unit-edit-modal">
    <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md mx-4 relative z-50" data-testid="modal-content">
        <!-- Modal Header -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Edit Unit</h3>
            <button type="button" @click="$event.target.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Form -->
        <form hx-put="{{ route('units.update', $unit->id) }}" hx-target="#units-list">
            @csrf
            @method('PUT')
            
            <!-- Unit Name -->
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Unit Name</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    value="{{ old('name', $unit->name) }}"
                    required 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('unit_name_example') }}">
            </div>

            <!-- Description -->
            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea 
                    id="description" 
                    name="description" 
                    rows="3"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                    placeholder="{{ __('unit_description_placeholder') }}">{{ old('description', $unit->description) }}</textarea>
            </div>

            <!-- Target Completion Date -->
            <div class="mb-6">
                <label for="target_completion_date" class="block text-sm font-medium text-gray-700 mb-2">Target Completion Date (Optional)</label>
                <input 
                    type="date" 
                    id="target_completion_date" 
                    name="target_completion_date" 
                    value="{{ old('target_completion_date', $unit->target_completion_date ? $unit->target_completion_date->format('Y-m-d') : '') }}"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Form Actions -->
            <div class="flex justify-end space-x-3">
                <button 
                    type="button" 
                    @click="$event.target.closest('.fixed').remove()"
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md">
                    Cancel
                </button>
                <button 
                    type="submit"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                    Update Unit
                </button>
            </div>
        </form>
    </div>
</div>