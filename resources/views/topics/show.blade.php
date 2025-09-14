@extends('layouts.app')

@section('content')
    <div class="bg-white shadow rounded-lg">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <a href="{{ route('subjects.units.show', ['subject' => $subject->id, 'unit' => $unit->id]) }}" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <div class="flex items-center space-x-3 mb-1">
                            <div class="w-4 h-4 rounded-full" style="background-color: {{ $subject->color ?? '#6B7280' }}"></div>
                            <span class="text-sm text-gray-600">{{ $subject->name }} → {{ $unit->title }}</span>
                        </div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $topic->title }}</h1>
                    </div>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('topics.edit', ['topic' => $topic->id]) }}" 
                       class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg inline-flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        {{ __('Edit Topic') }}
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            @include('topics.partials.topic-details', compact('topic', 'unit', 'subject'))
        </div>
    </div>
@endsection