{{-- Individual Review Card for Session --}}
@php
    $topic = $review->topic(app(App\Services\SupabaseClient::class));
    $session = $review->session(app(App\Services\SupabaseClient::class));
@endphp

<div class="space-y-6" data-review-id="{{ $review->id }}">
    {{-- Topic Information --}}
    <div class="text-center">
        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $topic?->title ?? __('unknown_topic') }}</h3>
        <div class="flex items-center justify-center space-x-4 text-sm text-gray-600">
            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $review->getStatusColor() }}">
                {{ ucfirst($review->status) }}
            </span>
            <span>{{ __('repetitions_count', ['count' => $review->repetitions]) }}</span>
            <span>{{ __('interval_formatted', ['interval' => $review->getFormattedInterval()]) }}</span>
            @if($review->isOverdue())
                <span class="text-red-600 font-medium">{{ __('days_overdue', ['days' => abs($review->getDaysUntilDue())]) }}</span>
            @endif
        </div>
    </div>

    {{-- Topic Content/Question --}}
    <div class="review-question bg-gray-50 rounded-lg p-6">
        <div class="prose max-w-none">
            @if($topic?->content)
                {!! nl2br(e($topic->content)) !!}
            @else
                <p class="text-gray-600 italic">{{ __('review_this_topic_and_assess_your_understanding') }}</p>
            @endif
        </div>
        
        @if($session?->notes)
            <div class="mt-4 p-3 bg-blue-50 rounded border-l-4 border-blue-200">
                <h4 class="text-sm font-medium text-blue-900 mb-1">{{ __('session_notes') }}:</h4>
                <p class="text-sm text-blue-800">{{ $session->notes }}</p>
            </div>
        @endif

        {{-- Evidence from original session --}}
        @if($session?->hasEvidence())
            <div class="mt-4 p-3 bg-green-50 rounded border-l-4 border-green-200">
                <h4 class="text-sm font-medium text-green-900 mb-2">{{ __('learning_evidence') }}:</h4>
                
                @if($session->evidence_notes)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">{{ __('notes') }}:</span>
                        <p class="text-sm text-green-800">{{ $session->evidence_notes }}</p>
                    </div>
                @endif
                
                @if($session->evidence_photos && count($session->evidence_photos) > 0)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">{{ __('photos') }}:</span>
                        <div class="flex space-x-2 mt-1">
                            @foreach($session->evidence_photos as $photoUrl)
                                <img src="{{ asset($photoUrl) }}" alt="Evidence photo" class="w-16 h-16 object-cover rounded border">
                            @endforeach
                        </div>
                    </div>
                @endif
                
                @if($session->evidence_voice_memo)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">{{ __('voice_memo') }}:</span>
                        <audio controls class="w-full h-8 mt-1">
                            <source src="{{ asset($session->evidence_voice_memo) }}" type="audio/mpeg">
                            Your browser does not support the audio element.
                        </audio>
                    </div>
                @endif
                
                @if($session->evidence_attachments && count($session->evidence_attachments) > 0)
                    <div class="mb-2">
                        <span class="text-xs font-medium text-green-800">Files:</span>
                        <div class="mt-1">
                            @foreach($session->evidence_attachments as $attachmentUrl)
                                <a href="{{ asset($attachmentUrl) }}" target="_blank" class="text-xs text-green-700 hover:text-green-900 underline block">
                                    📎 {{ basename($attachmentUrl) }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Instructions --}}
    <div class="text-center text-sm text-gray-600">
        <p class="mb-4">{{ __('think_about_how_well_you_remember_this_topic_then_reveal_the_answer') }}</p>
        <button onclick="showAnswer(this)" 
                class="show-answer-btn mb-4 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            {{ __('show_answer') }}
        </button>
        <div class="answer-section" style="display: none;">
            <p class="mb-2 text-green-700 font-medium">{{ __('now_rate_your_recall') }}</p>
            <div class="flex justify-center space-x-4 text-xs">
                <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">1</kbd> {{ __('again') }}</span>
                <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">2</kbd> {{ __('hard') }}</span>
                <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">3</kbd> {{ __('good') }}</span>
                <span><kbd class="px-1 py-0.5 bg-gray-200 rounded">4</kbd> {{ __('easy') }}</span>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="grid grid-cols-4 gap-3">
        <button onclick="processReviewResult({{ $review->id }}, 'again')" 
                class="flex flex-col items-center justify-center p-4 border-2 border-red-200 rounded-lg text-red-700 hover:bg-red-50 hover:border-red-300 transition-colors">
            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
            <span class="text-sm font-medium">Again</span>
            <span class="text-xs text-gray-500">< 1 day</span>
        </button>

        <button onclick="processReviewResult({{ $review->id }}, 'hard')" 
                class="flex flex-col items-center justify-center p-4 border-2 border-orange-200 rounded-lg text-orange-700 hover:bg-orange-50 hover:border-orange-300 transition-colors">
            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <span class="text-sm font-medium">Hard</span>
            <span class="text-xs text-gray-500">< usual</span>
        </button>

        <button onclick="processReviewResult({{ $review->id }}, 'good')" 
                class="flex flex-col items-center justify-center p-4 border-2 border-green-200 rounded-lg text-green-700 hover:bg-green-50 hover:border-green-300 transition-colors">
            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm font-medium">Good</span>
            <span class="text-xs text-gray-500">Normal</span>
        </button>

        <button onclick="processReviewResult({{ $review->id }}, 'easy')" 
                class="flex flex-col items-center justify-center p-4 border-2 border-blue-200 rounded-lg text-blue-700 hover:bg-blue-50 hover:border-blue-300 transition-colors">
            <svg class="w-6 h-6 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <span class="text-sm font-medium">Easy</span>
            <span class="text-xs text-gray-500">Longer</span>
        </button>
    </div>

    {{-- SRS Algorithm Details (collapsible) --}}
    <details class="text-xs text-gray-500">
        <summary class="cursor-pointer hover:text-gray-700">SRS Details</summary>
        <div class="mt-2 p-3 bg-gray-50 rounded">
            <div class="grid grid-cols-2 gap-2">
                <div>Current interval: {{ $review->interval_days }} days</div>
                <div>Ease factor: {{ number_format($review->ease_factor, 2) }}</div>
                <div>Due date: {{ $review->due_date?->translatedFormat('M j, Y') }}</div>
                <div>Last reviewed: {{ $review->last_reviewed_at?->translatedFormat('M j, Y g:i A') ?? 'Never' }}</div>
            </div>
        </div>
    </details>
</div>

<script>
function showAnswer(button) {
    button.style.display = 'none';
    button.parentElement.querySelector('.answer-section').style.display = 'block';
}
</script>