@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('weekly_calendar') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('manage_time_blocks_and_learning_schedules') }}</p>
            </div>
            
            <div class="flex items-center space-x-4">
                <!-- Child Selector -->
                @if($children->count() > 1)
                <div>
                    <label for="child-select" class="block text-sm font-medium text-gray-700 mb-1">{{ __('select_child_colon') }}</label>
                    <select 
                        id="child-select"
                        hx-get="{{ route('calendar.index') }}"
                        hx-target="#calendar-content"
                        hx-include="this"
                        name="child_id"
                        class="block w-48 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">{{ __('all_children') }}</option>
                        @foreach($children as $child)
                            <option value="{{ $child->id }}" {{ $selectedChild && $selectedChild->id === $child->id ? 'selected' : '' }}>
                                {{ $child->name }} ({{ $child->grade }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Add Time Block Button -->
                <button
                    hx-get="{{ route('calendar.create') }}{{ $selectedChild ? '?child_id=' . $selectedChild->id : '' }}"
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

        @if($selectedChild)
        <!-- Selected Child Info -->
        <div class="flex items-center space-x-3 p-3 bg-blue-50 rounded-lg">
            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white font-bold">
                {{ substr($selectedChild->name, 0, 1) }}
            </div>
            <div>
                <p class="font-medium text-blue-900">{{ $selectedChild->name }}</p>
                <p class="text-sm text-blue-600">{{ __('grade_level', ['grade' => $selectedChild->grade]) }} • {{ ucfirst(str_replace('_', ' ', $selectedChild->getGradeGroup())) }}</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Calendar Content -->
    <div id="calendar-content">
        @include('calendar.partials.grid')
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