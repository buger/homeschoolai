@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('my_children') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('manage_childrens_profiles_homeschool_planning') }}</p>
            </div>
            <button
                hx-get="{{ route('children.create') }}"
                hx-target="#child-form-modal"
                hx-swap="innerHTML"
                hx-on::after-request="htmx.process(document.getElementById('child-form-modal'))"
                class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
                data-testid="header-add-child-btn"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>{{ __('add_child') }}</span>
            </button>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-blue-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-600">{{ __('total_children') }}</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $children->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-green-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-600">{{ __('age_range') }}</p>
                        <p class="text-2xl font-bold text-green-900">
                            @if($children->count() > 0)
                                {{ __('grade_range', ['min' => $children->pluck('grade')->sort()->first(), 'max' => $children->pluck('grade')->sort()->last()]) }}
                            @else
                                --
                            @endif
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-purple-50 rounded-lg p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-purple-600">{{ __('learning_plans') }}</p>
                        <p class="text-2xl font-bold text-purple-900">{{ __('active') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Children List -->
    <div id="children-list" class="space-y-4">
        @include('children.partials.list')
    </div>
</div>

<!-- Modal for Child Form -->
<div id="child-form-modal" data-testid="child-form-modal"></div>

<!-- Toast Notification Area -->
<div id="toast-area" class="fixed top-4 right-4 z-50"></div>

@endsection

@push('scripts')
<script>
    // Handle toast notifications
    document.body.addEventListener('childCreated', function() {
        showToast('{{ __('Child added successfully!') }}', 'success');
    });
    
    document.body.addEventListener('childUpdated', function() {
        showToast('{{ __('Child updated successfully!') }}', 'success');
    });
    
    document.body.addEventListener('childDeleted', function() {
        showToast('{{ __('Child deleted successfully!') }}', 'success');
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