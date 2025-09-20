@extends('layouts.app')

@section('content')
    <!-- Preview Mode Header -->
    <div class="bg-purple-100 border-2 border-purple-300 rounded-lg p-6 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-purple-800">{{ __('PREVIEW MODE') }}</h1>
                    <p class="text-purple-700 text-sm">{{ __('Exploring flashcards from') }} <strong>{{ $unit->name }}</strong></p>
                    <p class="text-purple-600 text-xs">{{ __('This won\'t affect your child\'s learning progress') }}</p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Progress -->
                <div class="text-center">
                    <div class="text-2xl font-bold text-purple-800" id="current-card-number">{{ $currentIndex + 1 }}</div>
                    <div class="text-sm text-purple-600">{{ __('of') }} {{ $totalCards }}</div>
                </div>
                <!-- Exit Preview -->
                <a href="{{ route('units.show', $unit->id) }}" 
                   class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition-colors flex items-center space-x-2"
                   onclick="return confirm('{{ __('Exit preview mode?') }}')">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>{{ __('Exit Preview') }}</span>
                </a>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div class="mt-4">
            <div class="w-full bg-purple-200 rounded-full h-3">
                <div class="bg-purple-600 h-3 rounded-full transition-all duration-300" 
                     id="progress-bar" 
                     style="width: {{ round(($currentIndex / $totalCards) * 100, 1) }}%"></div>
            </div>
        </div>
    </div>

    <!-- Flashcard Container -->
    <div class="max-w-4xl mx-auto">
        <div id="flashcard-container" class="bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Flashcard Content -->
            <div id="flashcard-content" class="p-8">
                @if($currentFlashcard)
                    @php
                        $flashcard = $currentFlashcard;
                        $kidsMode = false; // Preview is always in parent mode
                    @endphp
                    @include('reviews.partials.flashcard-types.' . $flashcard->card_type, [
                        'flashcard' => $flashcard,
                        'kidsMode' => false,
                        'isPreview' => true
                    ])
                @else
                    <div class="text-center py-12">
                        <div class="text-6xl mb-4">🎉</div>
                        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ __('Preview Complete!') }}</h2>
                        <p class="text-gray-600">{{ __('You\'ve seen all the flashcards in this unit') }}</p>
                    </div>
                @endif
            </div>
            
            <!-- Action Buttons -->
            <div class="bg-gray-50 px-8 py-6 border-t">
                <div class="flex justify-between items-center">
                    <!-- Show Answer / Next Card Button -->
                    <div class="flex space-x-4">
                        <button type="button" 
                                id="show-answer-btn" 
                                onclick="showAnswer()" 
                                class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition-colors">
                            {{ __('Show Answer') }}
                        </button>
                        
                        <button type="button" 
                                id="next-card-btn" 
                                onclick="nextCard()" 
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors" 
                                style="display: none;">
                            {{ __('Next Card') }}
                        </button>
                    </div>
                    
                    <!-- Preview Info -->
                    <div class="text-sm text-gray-600">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>{{ __('Preview mode - no learning data saved') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
// Define functions globally first
window.showAnswer = function() {
    if (window.preview) {
        window.preview.showAnswer();
    }
}

window.nextCard = function() {
    if (window.preview) {
        window.preview.nextCard();
    }
}

class FlashcardPreview {
    constructor(sessionId, totalCards) {
        this.sessionId = sessionId;
        this.totalCards = totalCards;
        this.currentIndex = {{ $currentIndex }};
        this.startTime = Date.now();
        this.cardStartTime = Date.now();
        
        // Track preview session in localStorage (no server state)
        this.updateLocalProgress();
    }
    
    updateLocalProgress() {
        const previewData = {
            sessionId: this.sessionId,
            currentIndex: this.currentIndex,
            totalCards: this.totalCards,
            startTime: this.startTime,
            cardStartTime: this.cardStartTime
        };
        
        localStorage.setItem('flashcard_preview_' + this.sessionId, JSON.stringify(previewData));
    }
    
    updateProgressBar() {
        const progressPercent = Math.round((this.currentIndex / this.totalCards) * 100);
        document.getElementById('progress-bar').style.width = progressPercent + '%';
        document.getElementById('current-card-number').textContent = this.currentIndex + 1;
    }
    
    showAnswer() {
        // Show answer content
        const answerContent = document.querySelector('.answer-content');
        if (answerContent) {
            answerContent.style.display = 'block';
        }
        
        // Show feedback for interactive cards
        const feedbackContent = document.querySelector('.feedback-content');
        if (feedbackContent) {
            feedbackContent.style.display = 'block';
        }
        
        // Switch buttons
        document.getElementById('show-answer-btn').style.display = 'none';
        document.getElementById('next-card-btn').style.display = 'inline-block';
    }
    
    async nextCard() {
        const timeSpent = Date.now() - this.cardStartTime;
        this.currentIndex++;
        
        // Update progress
        this.updateProgressBar();
        
        if (this.currentIndex >= this.totalCards) {
            // Session complete
            window.location.href = '{{ route("flashcards.preview.end", ":sessionId") }}'.replace(':sessionId', this.sessionId);
            return;
        }
        
        try {
            // Submit current answer (if any) - stored in session only
            const response = await fetch('{{ route("flashcards.preview.answer", ":sessionId") }}'.replace(':sessionId', this.sessionId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify({
                    time_spent: timeSpent,
                    is_correct: window.flashcardAnswered?.isCorrect,
                    user_answer: window.flashcardAnswered?.userAnswer,
                    selected_choices: window.flashcardAnswered?.selectedChoices,
                    cloze_answers: window.flashcardAnswered?.clozeAnswers
                })
            });
            
            if (!response.ok) {
                throw new Error('Failed to submit preview answer');
            }
            
            // Get next card
            const nextResponse = await fetch('{{ route("flashcards.preview.next", ":sessionId") }}'.replace(':sessionId', this.sessionId));
            
            if (!nextResponse.ok) {
                throw new Error('Failed to load next card');
            }
            
            const nextData = await nextResponse.json();
            
            if (nextData.session_complete) {
                // Session complete
                window.location.href = '{{ route("flashcards.preview.end", ":sessionId") }}'.replace(':sessionId', this.sessionId);
                return;
            }
            
            // Load new flashcard
            await this.loadFlashcard(nextData.flashcard);
            
            // Reset UI
            document.getElementById('show-answer-btn').style.display = 'inline-block';
            document.getElementById('next-card-btn').style.display = 'none';
            
            // Reset answer tracking
            window.flashcardAnswered = null;
            
            this.cardStartTime = Date.now();
            this.updateLocalProgress();
            
        } catch (error) {
            console.error('Error loading next card:', error);
            alert('{{ __("Error loading next card. Please try again.") }}');
        }
    }
    
    async loadFlashcard(flashcardData) {
        // This would dynamically load the flashcard content
        // For now, we'll reload the page - in a production app you'd want to load via AJAX
        window.location.reload();
    }
}

// Initialize preview globally
window.preview = new FlashcardPreview('{{ $sessionId }}', {{ $totalCards }});

// Prevent accidental navigation away
window.addEventListener('beforeunload', function(e) {
    if (window.preview && window.preview.currentIndex < window.preview.totalCards) {
        e.preventDefault();
        e.returnValue = '{{ __("Are you sure you want to exit the flashcard preview?") }}';
    }
});

// Clean up on window unload
window.addEventListener('unload', function() {
    // Clean up localStorage
    if (window.preview) {
        localStorage.removeItem('flashcard_preview_' + window.preview.sessionId);
    }
});
</script>
@endpush

@push('styles')
<style>
/* Preview mode specific styling */
.preview-mode {
    background: linear-gradient(135deg, #f3e8ff 0%, #e0e7ff 100%);
}

.preview-card {
    border: 3px solid #a855f7;
    box-shadow: 0 10px 30px rgba(168, 85, 247, 0.2);
}

.preview-badge {
    background: linear-gradient(45deg, #a855f7, #3b82f6);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}
</style>
@endpush