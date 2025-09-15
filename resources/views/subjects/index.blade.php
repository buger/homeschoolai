@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('subjects') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('manage_curriculum_subjects_organize_units') }}</p>
            </div>

            <!-- Child Selector -->
            <div class="flex items-center space-x-3">
                <label for="child_id" class="text-sm font-medium text-gray-700">{{ __('child') }}:</label>
                <select id="child_id"
                        name="child_id"
                        hx-get="{{ route('subjects.index') }}"
                        hx-target="#subjects-content"
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

    @if(!$selectedChild)
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        <div class="mx-auto h-12 w-12 text-gray-400">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
            </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_child_selected') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __('select_a_child_from_the_dropdown_above_to_view_their_subjects') }}</p>
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
                <h3 class="text-lg font-medium text-gray-900">{{ __('subjects_for') }} {{ $selectedChild->name }}</h3>
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
            </div>
        </div>
        <div id="subjects-content" class="p-6">
            @include('subjects.partials.subjects-list', ['subjects' => $subjects])
        </div>
    </div>
    @endif
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