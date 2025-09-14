@extends('layouts.app')

@section('title', __('review_slots') . ' - ' . $child->name)

@section('content')
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('review_slots') }}</h1>
                <p class="text-gray-600 mt-1">{{ __('manage_daily_review_time_slots_for_child', ['name' => $child->name]) }}</p>
            </div>
            
            <div class="flex items-center space-x-3">
                <a href="{{ route('reviews.index', ['child_id' => $child->id]) }}" 
                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ __('back_to_reviews') }}
                </a>
            </div>
        </div>
    </div>

    {{-- Weekly Review Slots Manager --}}
    <div id="slots-manager" class="bg-white rounded-lg shadow">
        @include('reviews.partials.slots-manager')
    </div>

    {{-- Add Slot Modal (initially hidden) --}}
    <div id="add-slot-modal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50" data-testid="add-slot-modal">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 relative z-50" data-testid="modal-content">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">{{ __('add_review_slot') }}</h3>
            </div>
            
            <form id="add-slot-form" 
                  hx-post="{{ route('reviews.slots.store') }}" 
                  hx-target="#slots-manager" 
                  hx-swap="outerHTML"
                  hx-on::after-request="closeAddSlotModal()">
                @csrf
                <div class="px-6 py-4 space-y-4">
                    <input type="hidden" name="child_id" value="{{ $child->id }}">
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('day_of_week') }}</label>
                        <select name="day_of_week" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">{{ __('select_day') }}</option>
                            <option value="1">{{ __('monday') }}</option>
                            <option value="2">{{ __('tuesday') }}</option>
                            <option value="3">{{ __('wednesday') }}</option>
                            <option value="4">{{ __('thursday') }}</option>
                            <option value="5">{{ __('friday') }}</option>
                            <option value="6">{{ __('saturday') }}</option>
                            <option value="7">{{ __('sunday') }}</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('start_time') }}</label>
                            <input type="time" name="start_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('end_time') }}</label>
                            <input type="time" name="end_time" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('slot_type') }}</label>
                        <select name="slot_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="micro">{{ __('micro_session_5_min') }}</option>
                            <option value="standard">{{ __('standard_session_1530_min') }}</option>
                        </select>
                    </div>
                </div>
                
                <div class="px-6 py-4 border-t border-gray-200 flex justify-end space-x-3">
                    <button type="button" onclick="closeAddSlotModal()" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        {{ __('cancel') }}
                    </button>
                    <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                        {{ __('add_slot') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

@endsection

@push('scripts')
<script>
function openAddSlotModal() {
    document.getElementById('add-slot-modal').classList.remove('hidden');
    console.log('Modal opened');
}

function closeAddSlotModal() {
    document.getElementById('add-slot-modal').classList.add('hidden');
    document.getElementById('add-slot-form').reset();
}

// Close modal on successful addition
document.body.addEventListener('reviewSlotCreated', function() {
    closeAddSlotModal();
});

// Also listen for HTMX success events
document.body.addEventListener('htmx:afterSwap', function(event) {
    if (event.detail.target.id === 'slots-manager') {
        setTimeout(() => closeAddSlotModal(), 100); // Small delay to ensure DOM is updated
    }
});

// Handle HTMX errors
document.body.addEventListener('htmx:responseError', function(event) {
    console.error('HTMX error:', event.detail);
    alert(window.__('Error creating review slot. Please try again.'));
});
</script>
@endpush