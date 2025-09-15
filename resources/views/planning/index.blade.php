@extends('layouts.app')

@section('content')
<div class="space-y-6">
  <!-- Header -->
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ __('topic_planning_board') }}</h2>
        <p class="text-gray-600 mt-1">{{ __('plan_and_schedule_learning_sessions_for_your_children') }}</p>
      </div>

      <!-- Child Selector -->
      <div class="flex items-center space-x-3">
        <label for="child_id" class="text-sm font-medium text-gray-700">{{ __('child') }}:</label>
        <select id="child_id"
                name="child_id"
                hx-get="{{ route('planning.index') }}"
                hx-target="#planning-content"
                hx-swap="innerHTML"
                hx-include="[name=child_id]"
                class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
          <option value="">{{ __('select_a_child') }}</option>
          @if($children->count() > 0)
            @foreach($children as $child)
            <option value="{{ $child->id }}" {{ $selectedChild && $selectedChild->id == $child->id ? 'selected' : '' }}>
              {{ $child->name }}
            </option>
            @endforeach
          @else
            <option disabled>{{ __('no_children_available_create_a_child_first') }}</option>
          @endif
        </select>
      </div>
    </div>
  </div>

  <div id="planning-content">
    @if(!$selectedChild)
    <div class="bg-white rounded-lg shadow-sm p-12 text-center">
      <div class="mx-auto h-12 w-12 text-gray-400">
        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">{{ __('no_child_selected') }}</h3>
      <p class="mt-1 text-sm text-gray-500">
        @if($children->count() === 0)
          {{ __('you_need_to_add_a_child_first') }}
        @else
          {{ __('select_a_child_from_the_dropdown_above_to_start_planning_their_learning_sessions') }}
        @endif
      </p>
      <div class="mt-6">
        <a href="{{ route('children.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
          {{ __('manage_children') }}
        </a>
      </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm">
      <div class="p-6 border-b border-gray-200">
        <div class="flex justify-between items-center">
          <h3 class="text-lg font-medium text-gray-900">{{ __('sessions_for') }} {{ $selectedChild->name }}</h3>
          <button
            type="button"
            hx-get="{{ route('planning.create-session') }}?child_id={{ $selectedChild->id }}"
            hx-target="#modal-container"
            hx-swap="innerHTML"
            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition flex items-center space-x-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>{{ __('create_session') }}</span>
          </button>
        </div>
      </div>

      <!-- Capacity Meter -->
      <div class="p-6">
        @include('planning.partials.capacity-meter', ['capacityData' => $capacityData, 'child' => $selectedChild])
      </div>

      <!-- Planning Board -->
      <div id="planning-board" class="p-6">
        @include('planning.partials.board', [
          'sessionsByStatus' => $sessionsByStatus,
          'selectedChild' => $selectedChild,
          'availableTopics' => $availableTopics,
          'capacityData' => $capacityData,
          'catchUpSessions' => $catchUpSessions
        ])
      </div>
    </div>
    @endif
  </div>

  <!-- Toast Container -->
  <div id="toast-container" class="fixed top-4 right-4 z-50 space-y-2"></div>

  <!-- Modal Container -->
  <div id="modal-container"></div>
</div>

@push('styles')
<style>
  .kanban-column {
    min-height: 500px;
  }
  
  .session-card {
    transition: all 0.2s ease;
  }
  
  .session-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  }
  
  .drag-over {
    border: 2px dashed #3b82f6;
    background-color: #eff6ff;
  }
  
  .capacity-bar {
    transition: all 0.3s ease;
  }
  
  .status-green { background-color: #10b981; }
  .status-yellow { background-color: #f59e0b; }
  .status-red { background-color: #ef4444; }
</style>
@endpush

@push('scripts')
<script>
  // HTMX event handlers
  document.addEventListener('htmx:afterRequest', function(event) {
    if (event.detail.xhr.status >= 400) {
      showToast(window.__('Error:') + ' ' + event.detail.xhr.statusText, 'error');
    }
  });

  // Toast notification system
  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `px-4 py-3 rounded-md shadow-lg transform transition-all duration-300 translate-x-full ${
      type === 'success' ? 'bg-green-600 text-white' : 
      type === 'error' ? 'bg-red-600 text-white' : 
      'bg-blue-600 text-white'
    }`;
    toast.textContent = message;
    
    const container = document.getElementById('toast-container');
    container.appendChild(toast);
    
    // Slide in
    setTimeout(() => {
      toast.classList.remove('translate-x-full');
    }, 100);
    
    // Remove after 4 seconds
    setTimeout(() => {
      toast.classList.add('translate-x-full');
      setTimeout(() => {
        if (container.contains(toast)) {
          container.removeChild(toast);
        }
      }, 300);
    }, 4000);
  }

  // Listen for HTMX trigger events
  document.addEventListener('sessionCreated', () => showToast(window.__('session_created_successfully')));
  document.addEventListener('sessionUpdated', () => showToast(window.__('session_updated_successfully')));
  document.addEventListener('sessionDeleted', () => showToast(window.__('session_deleted_successfully')));
  document.addEventListener('sessionScheduled', () => showToast(window.__('session_scheduled_successfully')));
  document.addEventListener('sessionUnscheduled', () => showToast(window.__('session_unscheduled_successfully')));

  // Skip Day Modal Functions
  function openSkipDayModal(sessionId, scheduledDate) {
    // Get the skip day modal content via HTMX
    htmx.ajax('GET', '/planning/skip-day-modal/' + sessionId + '?date=' + scheduledDate, {
      target: '#modal-container',
      swap: 'innerHTML'
    });
  }

  window.openSkipDayModal = openSkipDayModal;

  // Enable drag and drop for Kanban columns
  function enableDragAndDrop() {
    // Make session cards draggable
    document.querySelectorAll('.session-card').forEach(card => {
      card.draggable = true;
      
      card.addEventListener('dragstart', function(e) {
        e.dataTransfer.setData('text/plain', card.dataset.sessionId);
        e.dataTransfer.setData('text/status', card.dataset.status);
        card.classList.add('opacity-50');
      });
      
      card.addEventListener('dragend', function(e) {
        card.classList.remove('opacity-50');
      });
    });

    // Make columns droppable
    document.querySelectorAll('.kanban-column').forEach(column => {
      column.addEventListener('dragover', function(e) {
        e.preventDefault();
        column.classList.add('drag-over');
      });
      
      column.addEventListener('dragleave', function(e) {
        column.classList.remove('drag-over');
      });
      
      column.addEventListener('drop', function(e) {
        e.preventDefault();
        column.classList.remove('drag-over');
        
        const sessionId = e.dataTransfer.getData('text/plain');
        const oldStatus = e.dataTransfer.getData('text/status');
        const newStatus = column.dataset.status;
        
        if (oldStatus !== newStatus) {
          // Send HTMX request to update session status
          htmx.ajax('PUT', `/planning/sessions/${sessionId}/status`, {
            values: { status: newStatus },
            target: '#planning-board',
            swap: 'innerHTML'
          });
        }
      });
    });
  }

  // Initialize drag and drop on page load and after HTMX updates
  document.addEventListener('DOMContentLoaded', enableDragAndDrop);
  document.addEventListener('htmx:afterSettle', enableDragAndDrop);
</script>
@endpush
@endsection