@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('weekly_calendar') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('manage_time_blocks_and_learning_schedules') }}</p>
            </div>

            <!-- Child Selector -->
            <div class="flex items-center space-x-3">
                <label for="child_id" class="text-sm font-medium text-gray-700">{{ __('child') }}:</label>
                <select id="child_id"
                        name="child_id"
                        hx-get="{{ route('calendar.index') }}"
                        hx-target="#calendar-container"
                        hx-swap="innerHTML"
                        hx-include="[name=child_id]"
                        class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">{{ __('select_a_child') }}</option>
                    @if($children->count() > 0)
                        @foreach($children as $child)
                        <option value="{{ $child->id }}" {{ $selectedChild && $selectedChild->id == $child->id ? 'selected' : '' }}>
                            {{ $child->name }}
                        </option>
                        @endforeach
                    @else
                        <option disabled>{{ __('no_children_available_create_a_child_first') }}</option>
                    @endif
                </select>
            </div>
        </div>
    </div>

    <div id="calendar-container">
        @if(!$selectedChild)
        <div class="bg-white rounded-lg shadow-sm p-12 text-center">
            <div class="mx-auto h-12 w-12 text-gray-400">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_child_selected') }}</h3>
            <p class="mt-1 text-sm text-gray-500">{{ __('select_a_child_from_the_dropdown_above_to_view_their_calendar') }}</p>
            <div class="mt-6">
                <a href="{{ route('children.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    {{ __('manage_children') }}
                </a>
            </div>
        </div>
        @else
        <div class="bg-white rounded-lg shadow-sm">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('calendar_for') }} {{ $selectedChild->name }}</h3>
                    <button
                        hx-get="{{ route('calendar.create') }}?child_id={{ $selectedChild->id }}"
                        hx-target="#time-block-form-modal"
                        hx-swap="innerHTML"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        <span>{{ __('add_time_block') }}</span>
                    </button>
                </div>
            </div>
            <!-- Calendar Content -->
            <div id="calendar-content" class="p-6">
                @include('calendar.partials.grid')
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Modal for Time Block Form -->
<div id="time-block-form-modal"></div>

<!-- Toast Notification Area -->
<div id="toast-area" class="fixed top-4 right-4 z-50"></div>

@endsection

@push('scripts')
<script>
    // Handle toast notifications
    document.body.addEventListener('timeBlockCreated', function() {
        showToast('{{ __('time_block_added_successfully') }}', 'success');
    });
    
    document.body.addEventListener('timeBlockUpdated', function() {
        showToast('{{ __('time_block_updated_successfully') }}', 'success');
    });
    
    document.body.addEventListener('timeBlockDeleted', function() {
        showToast('{{ __('time_block_deleted_successfully') }}', 'success');
    });

    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast-${type} mb-4 p-4 rounded-lg shadow-lg text-white transition-all duration-500 transform translate-x-full`;
        
        const bgColor = type === 'success' ? 'bg-green-600' : 'bg-blue-600';
        toast.classList.add(bgColor);
        
        toast.innerHTML = `
            <div class="flex items-center">
                <span>${message}</span>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-4">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        `;
        
        document.getElementById('toast-area').appendChild(toast);
        
        // Animate in
        setTimeout(() => toast.classList.remove('translate-x-full'), 100);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            toast.classList.add('translate-x-full');
            setTimeout(() => toast.remove(), 500);
        }, 5000);
    }
</script>
@endpush