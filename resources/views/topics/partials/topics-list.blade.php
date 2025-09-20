@if($topics->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($topics as $topic)
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{{ $topic->title }}</h3>
                    
                    <!-- Actions Dropdown -->
                    <div class="relative inline-block text-left" x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                            <div class="py-1">
                                <a href="{{ route('topics.edit', ['topic' => $topic->id]) }}"
                                   class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ __('edit') }}
                                </a>
                                <button 
                                    hx-delete="{{ route('topics.destroy', ['topic' => $topic->id]) }}"
                                    hx-target="#topics-list"
                                    hx-swap="innerHTML"
                                    hx-confirm="{{ __('confirm_delete_topic') }}"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    {{ __('delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Topic Badges -->
                <div class="mb-3 flex flex-wrap gap-2">
                    @if($topic->required)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Required
                        </span>
                    @endif
                    @if($topic->hasLearningMaterials())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $topic->getLearningMaterialsCount() }} materials
                        </span>
                    @endif
                    @if(!$topic->required && !$topic->hasLearningMaterials())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            Optional
                        </span>
                    @endif
                </div>

                <!-- Topic Description -->
                @if($topic->description)
                    <div class="mb-3">
                        <p class="text-sm text-gray-600 line-clamp-2">
                            {{ Str::limit($topic->description, 120) }}
                        </p>
                    </div>
                @endif

                <!-- Topic Stats -->
                <div class="text-sm text-gray-600 space-y-1 mb-4">
                    <p class="flex items-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $topic->estimated_minutes }} min
                    </p>
                    @if($topic->flashcards && $topic->flashcards->count() > 0)
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            {{ __('flashcards_count', ['count' => $topic->flashcards->count()]) }}
                        </p>
                    @endif
                </div>
                
                <!-- View Topic Button -->
                <a href="{{ route('units.topics.show', [$unit->id, $topic->id]) }}" 
                   class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm hover:opacity-90 transition-opacity"
                   style="background-color: {{ $subject->color }}">
                    {{ __('view_topic') }}
                    <svg class="ml-2 -mr-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </a>
            </div>
        @endforeach
    </div>
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_topics_yet') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __('get_started_creating_first_topic_in_unit') }}</p>
        <div class="mt-6">
            <button 
                type="button"
                hx-get="{{ route('topics.create', [$subject->id, $unit->id]) }}"
                hx-target="#topic-modal"
                hx-swap="innerHTML"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white hover:opacity-90 transition-opacity"
                style="background-color: {{ $subject->color }}">
                {{ __('add_topic') }}
            </button>
        </div>
    </div>
@endif