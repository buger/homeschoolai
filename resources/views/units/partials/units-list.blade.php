@if($units->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($units as $unit)
            <div id="unit-card-{{ $unit->id }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                <!-- Unit Header -->
                <div class="flex items-start justify-between mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900" data-unit-name="{{ $unit->name }}">{{ $unit->name }}</h3>
                        @if($unit->description)
                            <p class="text-sm text-gray-600 mt-1">{{ Str::limit($unit->description, 100) }}</p>
                        @endif
                    </div>
                    
                    <!-- Actions Dropdown -->
                    <div class="relative inline-block text-left" x-data="{ open: false }">
                        <button @click="open = !open" type="button" class="text-gray-400 hover:text-gray-600 p-1">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                            </svg>
                        </button>
                        <div x-show="open" x-cloak @click.away="open = false" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-10">
                            <div class="py-1">
                                <button 
                                    hx-get="{{ route('units.edit', $unit->id) }}"
                                    hx-target="#unit-modal"
                                    hx-swap="innerHTML"
                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    {{ __('edit') }}
                                </button>
                                <button 
                                    hx-delete="{{ route('units.destroy', $unit->id) }}"
                                    hx-target="#units-list"
                                    hx-swap="innerHTML"
                                    hx-confirm="{{ __('confirm_delete_unit') }}"
                                    class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                    {{ __('delete') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unit Stats -->
                <div class="text-sm text-gray-600 mb-4 space-y-1">
                    @php
                        $topicsCount = $unit->topics()->count();
                    @endphp
                    <p>{{ __('topics_count', ['count' => $topicsCount]) }}</p>
                    @if($unit->target_completion_date)
                        <p class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            {{ __('due_date', ['date' => $unit->target_completion_date->translatedFormat('M j, Y')]) }}
                            @if($unit->isOverdue())
                                <span class="ml-2 text-red-600 font-medium">({{ __('overdue') }})</span>
                            @endif
                        </p>
                    @endif
                </div>

                <!-- View Unit Button -->
                <a href="{{ route('subjects.units.show', [$subject->id, $unit->id]) }}" 
                   data-testid="view-unit-{{ $unit->name }}"
                   class="inline-flex items-center justify-center w-full px-4 py-2 text-sm font-medium text-white rounded-md shadow-sm hover:opacity-90 transition-opacity"
                   style="background-color: {{ $subject->color }}">
                    {{ __('view_unit') }}
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
        <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_units_yet') }}</h3>
        <p class="mt-1 text-sm text-gray-500">{{ __('get_started_creating_first_unit_in_subject') }}</p>
        <div class="mt-6">
            <button 
                type="button"
                data-testid="add-unit-button"
                hx-get="{{ route('units.create', $subject->id) }}"
                hx-target="#unit-modal"
                hx-swap="innerHTML"
                hx-indicator="next .loading"
                class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white hover:opacity-90 transition-opacity"
                style="background-color: {{ $subject->color }}">
                {{ __('add_unit') }}
            </button>
            <div class="loading htmx-indicator">Loading...</div>
        </div>
    </div>
@endif