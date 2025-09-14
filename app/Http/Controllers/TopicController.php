<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TopicController extends Controller
{
    /**
     * Display a listing of topics for a unit.
     */
    public function index(Request $request, int $subjectId, int $unitId)
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return redirect()->route('login')->with('error', 'Please log in to continue.');
            }

            $subject = Subject::find($subjectId);
            if (! $subject || $subject->user_id != $userId) {
                return redirect()->route('subjects.index')->with('error', 'Subject not found.');
            }

            $unit = Unit::find($unitId);
            if (! $unit || $unit->subject_id !== $subjectId) {
                return redirect()->route('subjects.show', $subjectId)->with('error', 'Unit not found.');
            }

            $topics = Topic::forUnit($unitId);

            if ($request->expectsJson() || $request->header('HX-Request')) {
                return view('topics.partials.topics-list', compact('topics', 'unit', 'subject'));
            }

            return view('topics.index', compact('topics', 'unit', 'subject'));
        } catch (\Exception $e) {
            Log::error('Error fetching topics: '.$e->getMessage());

            if ($request->expectsJson() || $request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error loading topics. Please try again.').'</div>', 500);
            }

            return redirect()->route('subjects.show', $subjectId)->with('error', 'Unable to load topics. Please try again.');
        }
    }

    /**
     * Show the form for creating a new topic.
     */
    public function create(Request $request, string $subject, string $unit)
    {
        try {
            $subjectId = (int) $subject;
            $unitId = (int) $unit;

            $userId = auth()->id();
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subjectModel = Subject::find($subjectId);
            if (! $subjectModel || $subjectModel->user_id != $userId) {
                return response('Subject not found', 404);
            }

            $unitModel = Unit::find($unitId);
            if (! $unitModel || $unitModel->subject_id !== $subjectId) {
                return response('Unit not found', 404);
            }

            if ($request->header('HX-Request')) {
                return view('topics.partials.create-form', [
                    'unit' => $unitModel,
                    'subject' => $subjectModel,
                ]);
            }

            return view('topics.create', [
                'unit' => $unitModel,
                'subject' => $subjectModel,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading topic creation form: '.$e->getMessage());

            return response('Unable to load form.', 500);
        }
    }

    /**
     * Store a newly created topic in storage (unit-specific route).
     */
    public function storeForUnit(Request $request, int $unitId)
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $unit = Unit::find($unitId);
            if (! $unit) {
                return response('Unit not found', 404);
            }

            $subject = Subject::find($unit->subject_id);
            if (! $subject || $subject->user_id != $userId) {
                return response('Access denied', 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'estimated_minutes' => 'required|integer|min:5|max:480',
                'required' => 'boolean',
            ]);

            // Use 'name' field but store as 'title' in the model
            $topic = Topic::create([
                'unit_id' => $unitId,
                'title' => $validated['name'], // Store name as title
                'estimated_minutes' => $validated['estimated_minutes'],
                'required' => $validated['required'] ?? true,
                'prerequisites' => [], // Empty for now
            ]);

            if ($request->header('HX-Request')) {
                // Return updated topics list
                $topics = Topic::forUnit($unitId);

                return view('topics.partials.topics-list', compact('topics', 'unit', 'subject'));
            }

            return redirect()->route('subjects.units.show', [$subject->id, $unitId])->with('success', 'Topic created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating topic for unit: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error creating topic. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to create topic. Please try again.']);
        }
    }

    /**
     * Store a newly created topic in storage.
     */
    public function store(Request $request, int $subjectId, int $unitId)
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $subject = Subject::find($subjectId);
            if (! $subject || $subject->user_id != $userId) {
                return response('Subject not found', 404);
            }

            $unit = Unit::find($unitId);
            if (! $unit || $unit->subject_id !== $subjectId) {
                return response('Unit not found', 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'estimated_minutes' => 'required|integer|min:5|max:480',
                'required' => 'boolean',
            ]);

            // Use 'name' field but store as 'title' in the model
            $topic = Topic::create([
                'unit_id' => $unitId,
                'title' => $validated['name'], // Store name as title
                'estimated_minutes' => $validated['estimated_minutes'],
                'required' => $validated['required'] ?? true,
                'prerequisites' => [], // Empty for now
            ]);

            if ($request->header('HX-Request')) {
                // Return updated topics list
                $topics = Topic::forUnit($unitId);

                return view('topics.partials.topics-list', compact('topics', 'unit', 'subject'));
            }

            return redirect()->route('subjects.units.show', [$subjectId, $unitId])->with('success', 'Topic created successfully.');
        } catch (\Exception $e) {
            Log::error('Error creating topic: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error creating topic. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to create topic. Please try again.']);
        }
    }

    /**
     * Display the specified topic.
     */
    public function show(Request $request, int $unitId, int $id)
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return redirect()->route('login');
            }

            $unit = Unit::find($unitId);
            if (! $unit) {
                return redirect()->route('subjects.index')->with('error', 'Unit not found.');
            }

            $subject = Subject::find($unit->subject_id);
            if (! $subject || $subject->user_id != $userId) {
                return redirect()->route('subjects.index')->with('error', 'Access denied.');
            }

            $topic = Topic::find($id);
            if (! $topic || $topic->unit_id !== $unitId) {
                return redirect()->route('subjects.units.show', [$subject->id, $unitId])->with('error', 'Topic not found.');
            }

            if ($request->header('HX-Request')) {
                return view('topics.partials.topic-details', compact('topic', 'unit', 'subject'));
            }

            return view('topics.show', compact('topic', 'unit', 'subject'));
        } catch (\Exception $e) {
            Log::error('Error fetching topic: '.$e->getMessage());

            return redirect()->route('subjects.index')->with('error', 'Unable to load topic. Please try again.');
        }
    }

    /**
     * Show the form for editing the specified topic.
     */
    public function edit(Request $request, int $id)
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $topic = Topic::find($id);
            if (! $topic) {
                return response('Topic not found', 404);
            }

            $unit = Unit::find($topic->unit_id);
            if (! $unit) {
                return response('Unit not found', 404);
            }

            $subject = Subject::find($unit->subject_id);
            if (! $subject || $subject->user_id != $userId) {
                return response('Access denied', 403);
            }

            if ($request->header('HX-Request')) {
                return view('topics.partials.edit-form', compact('topic', 'unit', 'subject'));
            }

            return view('topics.edit', compact('topic', 'unit', 'subject'));
        } catch (\Exception $e) {
            Log::error('Error loading topic for edit: '.$e->getMessage());

            return response('Unable to load topic for editing.', 500);
        }
    }

    /**
     * Update the specified topic in storage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $topic = Topic::find($id);
            if (! $topic) {
                return response('Topic not found', 404);
            }

            $unit = Unit::find($topic->unit_id);
            if (! $unit) {
                return response('Unit not found', 404);
            }

            $subject = Subject::find($unit->subject_id);
            if (! $subject || $subject->user_id != $userId) {
                return response('Access denied', 403);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'estimated_minutes' => 'required|integer|min:5|max:480',
                'required' => 'boolean',
            ]);

            // Use 'name' field but store as 'title' in the model
            $topic->update([
                'title' => $validated['name'], // Store name as title
                'estimated_minutes' => $validated['estimated_minutes'],
                'required' => $validated['required'] ?? true,
            ]);

            if ($request->header('HX-Request')) {
                // Return updated topics list
                $topics = Topic::forUnit($unit->id);

                return view('topics.partials.topics-list', compact('topics', 'unit', 'subject'));
            }

            return redirect()->route('subjects.units.show', [$subject->id, $unit->id])->with('success', 'Topic updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating topic: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error updating topic. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to update topic. Please try again.']);
        }
    }

    /**
     * Remove the specified topic from storage.
     */
    public function destroy(Request $request, int $id)
    {
        try {
            $userId = auth()->id();
            if (! $userId) {
                return response('Unauthorized', 401);
            }

            $topic = Topic::find($id);
            if (! $topic) {
                return response('Topic not found', 404);
            }

            $unit = Unit::find($topic->unit_id);
            if (! $unit) {
                return response('Unit not found', 404);
            }

            $subject = Subject::find($unit->subject_id);
            if (! $subject || $subject->user_id != $userId) {
                return response('Access denied', 403);
            }

            // TODO: Check if topic has sessions - prevent deletion if it has active sessions
            // For now, allow deletion

            $topic->delete();

            if ($request->header('HX-Request')) {
                // Return updated topics list
                $topics = Topic::forUnit($unit->id);

                return view('topics.partials.topics-list', compact('topics', 'unit', 'subject'));
            }

            return redirect()->route('subjects.units.show', [$subject->id, $unit->id])->with('success', 'Topic deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting topic: '.$e->getMessage());

            if ($request->header('HX-Request')) {
                return response('<div class="text-red-500">'.__('Error deleting topic. Please try again.').'</div>', 500);
            }

            return back()->withErrors(['error' => 'Unable to delete topic. Please try again.']);
        }
    }
}
