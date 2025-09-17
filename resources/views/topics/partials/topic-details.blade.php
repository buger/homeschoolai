<div class="bg-white rounded-lg shadow-sm border">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold">{{ $topic->title }}</h2>
                <p class="text-gray-600">{{ $subject->name }} → {{ $unit->title }}</p>
                @if($topic->hasLearningMaterials())
                    <div class="flex items-center mt-2 space-x-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $topic->getLearningMaterialsCount() }} learning materials
                        </span>
                        @if($topic->required)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Required
                            </span>
                        @endif
                    </div>
                @elseif($topic->required)
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Required
                        </span>
                    </div>
                @endif
            </div>
            <div class="flex gap-2">
                <a href="{{ route('topics.edit', ['topic' => $topic->id]) }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                    Edit Topic
                </a>
                <form method="POST" action="{{ route('topics.destroy', ['topic' => $topic->id]) }}"
                      onsubmit="return confirm('Are you sure you want to delete this topic?')"
                      class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                        Delete
                    </button>
                </form>
            </div>
        </div>

        @if($topic->description)
            <div class="mb-6">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-medium text-gray-700">Content</h3>
                    @if($topic->hasRichContent())
                        <div class="flex items-center space-x-3 text-xs text-gray-500">
                            <span class="inline-flex items-center px-2 py-1 bg-blue-100 text-blue-800 rounded-full">
                                {{ ucfirst($topic->content_format) }}
                            </span>
                            @if($topic->getWordCount() > 0)
                                <span>{{ $topic->getWordCount() }} words</span>
                                <span>{{ $topic->getReadingTime() }}</span>
                            @endif
                        </div>
                    @endif
                </div>

                @if($topic->hasRichContent())
                    {{-- Render enhanced rich content --}}
                    <div class="bg-white border border-gray-200 rounded-lg">
                        @php
                            // Use unified content processing
                            $richContentService = app(App\Services\RichContentService::class);
                            $richContent = $richContentService->processUnifiedContent($topic->learning_content ?? '');
                        @endphp
                        @include('topics.partials.content-preview', [
                            'html' => $richContent['html'],
                            'metadata' => $richContent['metadata']
                        ])
                    </div>
                @else
                    {{-- Plain text content --}}
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-800 whitespace-pre-line">{{ $topic->description }}</p>
                    </div>
                @endif
            </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <h3 class="text-sm font-medium text-gray-700 mb-2">Estimated Duration</h3>
                <div class="flex items-center gap-2">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-600">{{ $topic->estimated_minutes }} min</span>
                </div>
            </div>
            @if($topic->prerequisites && count($topic->prerequisites) > 0)
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Prerequisites</h3>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                        <span class="text-gray-600">{{ count($topic->prerequisites) }} prerequisite(s)</span>
                    </div>
                </div>
            @endif
        </div>

        <!-- Learning Materials Section -->
        @if($topic->hasLearningMaterials())
            <div class="border-t pt-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Learning Materials</h3>
                    <a href="{{ route('topics.edit', ['topic' => $topic->id]) }}"
                       class="text-blue-600 hover:text-blue-700 text-sm font-medium">
                        Manage Materials
                    </a>
                </div>

                @include('topics.partials.learning-materials-display', ['topic' => $topic])
            </div>
        @endif

        <!-- Flashcards Section -->
        <div class="border-t pt-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Flashcards
                    <span class="ml-2 text-sm font-normal text-gray-600" id="topic-flashcard-count">
                        ({{ $topic->flashcards()->where('is_active', true)->count() }})
                    </span>
                </h3>
                @unless(session('kids_mode'))
                    <div class="flex space-x-3">
                        @php $topicFlashcardCount = $topic->flashcards()->where('is_active', true)->count() @endphp
                        @if($topicFlashcardCount > 0)
                            <!-- Preview button for topic flashcards -->
                            <a href="{{ route('topics.flashcards.preview.start', $topic->id) }}"
                               data-testid="preview-topic-flashcards-button"
                               class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center"
                               title="Preview flashcards for this topic">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Preview
                            </a>
                        @endif
                        <button
                            type="button"
                            data-testid="add-topic-flashcard-button"
                            hx-get="{{ route('topics.flashcards.create', $topic->id) }}"
                            hx-target="#flashcard-modal"
                            hx-swap="innerHTML"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg shadow-sm transition-colors flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                            </svg>
                            Add Flashcard
                        </button>
                    </div>
                @endunless
            </div>

            <!-- Topic Flashcards List -->
            <div id="topic-flashcards-list"
                 hx-get="{{ route('topics.flashcards.list', $topic->id) }}"
                 hx-trigger="load"
                 hx-swap="innerHTML">
                <!-- Loading state -->
                <div class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-6 w-6 border-b-2 border-gray-900"></div>
                    <p class="mt-2 text-sm text-gray-500">Loading flashcards...</p>
                </div>
            </div>
        </div>

        <div class="border-t pt-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold">Learning Sessions</h3>
                <a href="{{ route('planning.index') }}"
                   class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm transition-colors">
                    Schedule Sessions
                </a>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-gray-600 text-sm">
                    Use the Planning Board to schedule learning sessions for this topic.
                    Sessions can be adapted to different age levels and learning styles.
                </p>
            </div>
        </div>
    </div>
</div>