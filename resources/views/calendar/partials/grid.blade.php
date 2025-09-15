@if($selectedChild)
    <!-- Calendar Grid -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Calendar Header -->
        <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">{{ __('childs_weekly_schedule', ['name' => $selectedChild->name]) }}</h3>
                <div class="flex items-center space-x-2 text-sm text-gray-600">
                    <span>{{ __('total_time_blocks_colon', ['count' => collect($timeBlocksByDay)->flatten()->count()]) }}</span>
                    <span>•</span>
                    <span>
                        {{ __('weekly_hours_colon') }} 
                        @php
                            $totalMinutes = collect($timeBlocksByDay)->flatten()->sum(function($block) {
                                return $block->getDurationMinutes();
                            });
                            echo round($totalMinutes / 60, 1) . 'h';
                        @endphp
                    </span>
                </div>
            </div>
        </div>

        <!-- Days of Week Header -->
        <div class="grid grid-cols-7 border-b border-gray-200">
            @php
                // Get user's preferred week order
                $user = auth()->user();
                $startsMonday = $user && $user->prefersMondayWeekStart();

                $weekDays = $startsMonday
                    ? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']
                    : ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];

                $weekDayNumbers = $startsMonday
                    ? [1, 2, 3, 4, 5, 6, 7] // Monday = 1
                    : [7, 1, 2, 3, 4, 5, 6]; // Sunday = 7, Monday = 1
            @endphp
            @foreach($weekDays as $dayKey)
                <div class="p-4 text-center font-medium text-gray-700 bg-gray-50 border-r border-gray-200 last:border-r-0">
                    <div class="hidden md:block">{{ __($dayKey) }}</div>
                    <div class="md:hidden">{{ substr(__($dayKey), 0, 3) }}</div>
                </div>
            @endforeach
        </div>

        <!-- Calendar Days -->
        <div class="grid grid-cols-7 min-h-96">
            @foreach($weekDayNumbers as $day)
                @include('calendar.partials.day-column', [
                    'day' => $day,
                    'timeBlocks' => $timeBlocksByDay[$day],
                    'reviewSlots' => $reviewSlotsByDay[$day] ?? collect([])
                ])
            @endforeach
        </div>
    </div>
@else
    <!-- No Child Selected -->
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
        @if($children->count() === 0)
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-gray-500 text-lg">{{ __('no_children_added_yet') }}</p>
            <p class="text-gray-400 text-sm mt-2">{{ __('add_children_first_create_schedules') }}</p>
            <a href="{{ route('children.index') }}" 
               class="mt-4 inline-flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                </svg>
                <span>{{ __('add_children') }}</span>
            </a>
        @else
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <p class="text-gray-500 text-lg">{{ __('select_child_view_calendar') }}</p>
            <p class="text-gray-400 text-sm mt-2">{{ __('choose_child_dropdown_manage_schedule') }}</p>
        @endif
    </div>
@endif