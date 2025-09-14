@extends('layouts.app')

@section('title', __('review_system'))

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ __('review_system') }}</h2>
                <p class="text-gray-600 mt-1">{{ __('spaced_repetition_learning_for_mastery') }}</p>
            </div>
            
            <!-- Child Selector -->
            <div class="flex items-center space-x-3">
                <label for="child_id" class="text-sm font-medium text-gray-700">{{ __('child') }}:</label>
                <select id="child_id" 
                        name="child_id"
                        hx-get="{{ route('reviews.index') }}" 
                        hx-target="#review-dashboard"
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
        <p class="mt-1 text-sm text-gray-500">{{ __('add_a_child_first_to_start_using_the_review_system') }}</p>
        <div class="mt-6">
            <a href="{{ route('children.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                {{ __('manage_children') }}
            </a>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm">
        <div id="review-dashboard">
            @include('reviews.partials.dashboard')
        </div>
    </div>
    @endif
</div>

<!-- Review Session Modal -->
<div id="review-session-modal" class="hidden fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50 relative" data-testid="review-session-modal">
    <!-- Modal content will be loaded here -->
</div>

@endsection

@push('scripts')
<script>
// Child selector change handler  
document.getElementById('child_id')?.addEventListener('change', function() {
    // No need to update hidden input since select itself has name="child_id"
});

// Modal handling
function openReviewSession(childId) {
    fetch(`/reviews/session/${childId}`)
        .then(response => response.text())
        .then(html => {
            document.getElementById('review-session-modal').innerHTML = html;
            document.getElementById('review-session-modal').classList.remove('hidden');
        });
}

function closeReviewSession() {
    document.getElementById('review-session-modal').classList.add('hidden');
    document.getElementById('review-session-modal').innerHTML = '';
    // Refresh dashboard
    htmx.trigger('#review-dashboard', 'refresh');
}

// Auto-refresh every 5 minutes to update due times
setInterval(() => {
    if (document.querySelector('#review-dashboard')) {
        htmx.trigger('#review-dashboard', 'refresh');
    }
}, 5 * 60 * 1000);

// Weekly/Monthly toggle functions
function showWeeklyStats() {
    document.querySelector('.weekly-stats').style.display = 'block';
    document.querySelector('.monthly-stats').style.display = 'none';
    
    // Update button styles
    document.getElementById('weekly-toggle').className = 'px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-l-lg hover:bg-blue-700';
    document.getElementById('monthly-toggle').className = 'px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-lg hover:bg-gray-100';
}

function showMonthlyStats() {
    document.querySelector('.weekly-stats').style.display = 'none';
    document.querySelector('.monthly-stats').style.display = 'block';
    
    // Update button styles
    document.getElementById('weekly-toggle').className = 'px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100';
    document.getElementById('monthly-toggle').className = 'px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-r-lg hover:bg-blue-700';
}
</script>
@endpush