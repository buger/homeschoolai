@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white shadow rounded-lg">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('subjects.show', $subject->id) }}" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center space-x-3 mb-1">
                            <div class="w-4 h-4 rounded-full" style="background-color: {{ $subject->color }}"></div>
                            <span class="text-sm text-gray-600">{{ $subject->name }}</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $unit->name }}</h1>
                        @if($unit->description)
                            <p class="text-sm text-gray-600 mt-1">{{ $unit->description }}</p>
                        @endif
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button 
                        type="button"
                        data-testid="add-topic-button"
                        hx-get="{{ route('topics.create', [$subject->id, $unit->id]) }}"
                        hx-target="#topic-modal"
                        hx-swap="innerHTML"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        {{ __('Add Topic') }}
                    </button>
                    <button 
                        type="button"
                        hx-get="{{ route('units.edit', [$subject->id, $unit->id]) }}"
                        hx-target="#unit-modal"
                        hx-swap="innerHTML"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('Edit Unit') }}
                    </button>
                </div>
            </div>

            <!-- Unit Info -->
            @if($unit->target_completion_date)
                <div class="bg-gray-50 rounded-lg p-4 mt-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-gray-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span class="text-sm text-gray-700">
                            {{ __('Target completion') }}: {{ $unit->target_completion_date->translatedFormat('M j, Y') }}
                            @if($unit->isOverdue())
                                <span class="ml-2 text-red-600 font-medium">({{ __('Overdue') }})</span>
                            @endif
                        </span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Topics List -->
        <div class="p-6">
            <div id="topics-list">
                @include('topics.partials.topics-list', compact('topics', 'unit', 'subject'))
            </div>

            <!-- Flashcards Section -->
            <div class="mt-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 flex items-center">
                <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                Flashcards 
                <span class="ml-2 text-sm font-normal text-gray-600" id="flashcard-count">
                    ({{ $unit->flashcards()->where('is_active', true)->count() }})
                </span>
            </h2>
            
            @unless(session('kids_mode'))
                <div class="flex space-x-3">
                    @php $flashcardCount = $unit->flashcards()->where('is_active', true)->count() @endphp
                    @if($flashcardCount > 0)
                        <a href="{{ route('units.flashcards.preview.start', $unit->id) }}" 
                           data-testid="preview-flashcards-button"
                           class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center"
                           title="{{ __('Preview flashcards without affecting learning progress') }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Preview
                        </a>
                    @endif
                    <button 
                        type="button"
                        data-testid="import-flashcard-button"
                        hx-get="{{ route('units.flashcards.import.show', $unit->id) }}"
                        hx-target="#flashcard-modal"
                        hx-swap="innerHTML"
                        hx-headers='{"X-CSRF-TOKEN": "{{ csrf_token() }}"}'
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Import
                    </button>
                    <button 
                        type="button"
                        data-testid="add-flashcard-button"
                        hx-get="{{ route('units.flashcards.create', $unit->id) }}"
                        hx-target="#flashcard-modal"
                        hx-swap="innerHTML"
                        class="bg-green-600 hover:bg-green-700 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Add Flashcard
                    </button>
                </div>
            @endunless
        </div>

            <!-- Flashcards List -->
            <div id="flashcards-list" 
                 hx-get="{{ route('units.flashcards.list', $unit->id) }}" 
                 hx-trigger="load" 
                 hx-swap="innerHTML">
                <!-- Loading state -->
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                    <p class="mt-2 text-sm text-gray-500">{{ __('Loading flashcards...') }}</p>
                </div>
            </div>
        </div>
    </div>
    </div>

    <!-- Modals -->
    <div id="topic-modal"></div>
    <div id="unit-modal"></div>
    <div id="flashcard-modal"></div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Ensure modals are properly displayed when loaded
    document.body.addEventListener('htmx:afterSwap', function(event) {
        if (event.detail.target.id === 'topic-modal') {
            // Force display of the modal container
            const modal = event.detail.target.querySelector('[data-testid="topic-modal"]');
            if (modal) {
                modal.style.display = 'flex';
                modal.style.visibility = 'visible';
            }
        }
    });
});
</script>
@endsection