@extends('layouts.app') 

@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-bold text-gray-900">My Tasks</h2>
      <button
        hx-get="{{ route('tasks.create') }}"
        hx-target="#task-form-modal"
        hx-swap="innerHTML"
        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition"
      >
        Add Task
      </button>
    </div>

    <!-- Filters -->
    <div class="flex gap-4 flex-wrap" x-data="{ filters: {} }">
      <!-- Search -->
      <input
        type="text"
        placeholder="{{ __('search_tasks_placeholder') }}"
        name="search"
        hx-get="{{ route('tasks.index') }}"
        hx-target="#task-list"
        hx-trigger="keyup changed delay:500ms"
        hx-include="[name='priority'], [name='status']"
        class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      />

      <!-- Priority Filter -->
      <select
        name="priority"
        hx-get="{{ route('tasks.index') }}"
        hx-target="#task-list"
        hx-trigger="change"
        hx-include="[name='search'], [name='status']"
        class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option value="">All Priorities</option>
        <option value="urgent">Urgent</option>
        <option value="high">High</option>
        <option value="medium">Medium</option>
        <option value="low">Low</option>
      </select>

      <!-- Status Filter -->
      <select
        name="status"
        hx-get="{{ route('tasks.index') }}"
        hx-target="#task-list"
        hx-trigger="change"
        hx-include="[name='search'], [name='priority']"
        class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
      >
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="completed">Completed</option>
      </select>
    </div>
  </div>

  <!-- Task List -->
  <div id="task-list" class="space-y-2">@include('tasks.partials.list')</div>
</div>

<!-- Modal for Task Form -->
<div id="task-form-modal"></div>
</div>
@endsection
