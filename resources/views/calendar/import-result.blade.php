@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold">{{ __('Import Complete') }}</h1>
                <p class="text-gray-600 mt-2">{{ __('ICS import results for') }} {{ $child->name }}</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('calendar.index', ['child' => $child->id]) }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors">
                    {{ __('View Calendar') }}
                </a>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border">
            <div class="p-6">
                @if($result['success'])
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h2 class="text-xl font-semibold text-green-800">{{ __('Import Successful') }}</h2>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $result['imported'] ?? 0 }}</div>
                            <div class="text-sm text-green-700">{{ __('Events Imported') }}</div>
                        </div>
                        
                        @if(isset($result['skipped']) && $result['skipped'] > 0)
                            <div class="text-center p-4 bg-yellow-50 rounded-lg">
                                <div class="text-2xl font-bold text-yellow-600">{{ $result['skipped'] }}</div>
                                <div class="text-sm text-yellow-700">{{ __('Events Skipped') }}</div>
                            </div>
                        @endif

                        @if(isset($result['conflicts']) && count($result['conflicts']) > 0)
                            <div class="text-center p-4 bg-red-50 rounded-lg">
                                <div class="text-2xl font-bold text-red-600">{{ count($result['conflicts']) }}</div>
                                <div class="text-sm text-red-700">{{ __('Conflicts Found') }}</div>
                            </div>
                        @endif
                    </div>

                    @if(isset($result['conflicts']) && count($result['conflicts']) > 0)
                        <div class="mt-6">
                            <h3 class="text-lg font-medium text-red-800 mb-3">{{ __('Scheduling Conflicts') }}</h3>
                            <div class="space-y-2">
                                @foreach($result['conflicts'] as $conflict)
                                    <div class="p-3 bg-red-50 border-l-4 border-red-500 text-sm">
                                        <strong>{{ $conflict['title'] ?? 'Event' }}</strong>
                                        <span class="text-red-600 ml-2">{{ $conflict['message'] ?? 'Scheduling conflict detected' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @else
                    <div class="flex items-center mb-4">
                        <svg class="w-8 h-8 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <h2 class="text-xl font-semibold text-red-800">{{ __('Import Failed') }}</h2>
                    </div>
                    <p class="text-red-600">{{ $result['error'] ?? __('An unknown error occurred during import.') }}</p>
                @endif
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('calendar.import', ['child' => $child->id]) }}" 
               class="text-blue-600 hover:text-blue-700 transition-colors">
                {{ __('Import Another File') }}
            </a>
        </div>
    </div>
@endsection