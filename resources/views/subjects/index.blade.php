@extends('layouts.app')

@section('content')
<div class="bg-white shadow rounded-lg">
    <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('subjects') }}</h2>
                <p class="text-sm text-gray-600 mt-1">{{ __('manage_curriculum_subjects_organize_units') }}</p>
            </div>
            @if($selectedChild)
            <button 
                hx-get="{{ route('subjects.create') }}?child_id={{ $selectedChild->id }}"
                hx-target="#subject-modal"
                hx-swap="innerHTML"
                data-child-id="{{ $selectedChild->id }}"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center"
            >
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                {{ __('add_subject') }}
            </button>
            @endif
        </div>
        
        <!-- Child Selector -->
        @if($children->count() > 0)
        <div class="mt-4 flex items-center space-x-4">
            <label for="child-selector" class="block text-sm font-medium text-gray-700">
                {{ __('select_child') }}:
            </label>
            <select 
                id="child-selector" 
                name="child_id"
                hx-get="{{ route('subjects.index') }}"
                hx-target="#subjects-list"
                hx-swap="innerHTML"
                hx-include="this"
                class="block w-64 pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md"
            >
                <option value="">{{ __('select_a_child') }}</option>
                @foreach($children as $child)
                    <option value="{{ $child->id }}" {{ $selectedChild && $selectedChild->id == $child->id ? 'selected' : '' }}>
                        {{ $child->name }} ({{ $child->grade }} {{ __('Grade') }})
                    </option>
                @endforeach
            </select>
            @if($selectedChild)
                <span class="text-sm text-gray-600">
                    {{ __('showing_subjects_for') }} <strong>{{ $selectedChild->name }}</strong>
                </span>
            @endif
        </div>
        @else
        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        {{ __('no_children_found') }}
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>{{ __('please_add_children_first_before_managing_subjects') }}</p>
                    </div>
                    <div class="mt-4">
                        <div class="-mx-2 -my-1.5 flex">
                            <a href="{{ route('children.index') }}" class="bg-yellow-50 px-2 py-1.5 rounded-md text-sm font-medium text-yellow-800 hover:bg-yellow-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-yellow-50 focus:ring-yellow-600">
                                {{ __('manage_children') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Subjects List -->
    <div class="p-6">
        <div id="subjects-list" hx-get="{{ route('subjects.index') }}" hx-trigger="load" hx-swap="innerHTML">
            @include('subjects.partials.subjects-list', ['subjects' => $subjects])
        </div>
    </div>
</div>

<!-- Subject Modal -->
<div id="subject-modal">
    <!-- {{ __('modal_content_loaded_by_htmx') }} -->
</div>

<!-- Quick Start Modal -->
<div id="quick-start-modal"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show modal when content is loaded
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.target.id === 'subject-modal' && event.detail.xhr.status === 200) {
            // Modal content is now loaded and visible
        }
    });
    
    // Hide modal and refresh list after successful form submission
    document.body.addEventListener('htmx:afterRequest', function(event) {
        if (event.detail.target.closest('#subject-modal') && event.detail.xhr.status === 200 && event.detail.target.tagName === 'FORM') {
            // Clear modal content to hide it
            document.getElementById('subject-modal').innerHTML = '';
            document.getElementById('subject-modal').classList.add('hidden');
            
            // Refresh subjects list
            htmx.trigger('#subjects-list', 'refresh');
        }
    });
    
    // Show modal when HTMX loads content into it
    document.body.addEventListener('htmx:beforeSwap', function(event) {
        if (event.detail.target.id === 'subject-modal') {
            document.getElementById('subject-modal').classList.remove('hidden');
        }
    });
});
</script>
@endsection