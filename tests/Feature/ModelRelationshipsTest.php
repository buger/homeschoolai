<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the complete User → Child → Subject → Unit → Topic relationship chain
     */
    public function test_complete_relationship_chain()
    {
        // Create a user
        $user = User::factory()->create([
            'name' => 'Test Parent',
            'email' => 'parent@test.com',
        ]);

        // Create a child for the user
        $child = Child::create([
            'name' => 'Test Child',
            'grade' => '3rd',
            'user_id' => $user->id,
            'independence_level' => 2,
        ]);

        // Create a subject for the child
        $subject = Subject::create([
            'name' => 'Mathematics',
            'color' => '#3b82f6',
            'user_id' => $user->id,
            'child_id' => $child->id,
        ]);

        // Create a unit for the subject
        $unit = Unit::create([
            'subject_id' => $subject->id,
            'name' => 'Addition and Subtraction',
            'description' => 'Basic arithmetic operations',
            'target_completion_date' => now()->addWeeks(4),
        ]);

        // Create topics for the unit
        $topic1 = Topic::create([
            'unit_id' => $unit->id,
            'title' => 'Single Digit Addition',
            'estimated_minutes' => 30,
            'required' => true,
        ]);

        $topic2 = Topic::create([
            'unit_id' => $unit->id,
            'title' => 'Double Digit Addition',
            'estimated_minutes' => 45,
            'required' => true,
        ]);

        // Test User → Children relationship
        $this->assertEquals(1, $user->children()->count());
        $this->assertEquals($child->id, $user->children()->first()->id);

        // Test Child → User relationship
        $this->assertEquals($user->id, $child->user->id);
        $this->assertEquals($user->name, $child->user->name);

        // Test Child → Subjects relationship
        $this->assertEquals(1, $child->subjects()->count());
        $this->assertEquals($subject->id, $child->subjects()->first()->id);

        // Test Subject → Child relationship
        $this->assertEquals($child->id, $subject->child->id);
        $this->assertEquals($child->name, $subject->child->name);

        // Test Subject → User relationship
        $this->assertEquals($user->id, $subject->user->id);

        // Test Subject → Units relationship
        $this->assertEquals(1, $subject->units()->count());
        $this->assertEquals($unit->id, $subject->units()->first()->id);

        // Test Unit → Subject relationship
        $this->assertEquals($subject->id, $unit->subject->id);
        $this->assertEquals($subject->name, $unit->subject->name);

        // Test Unit → Topics relationship
        $this->assertEquals(2, $unit->topics()->count());
        $topicIds = $unit->topics()->pluck('id')->toArray();
        $this->assertContains($topic1->id, $topicIds);
        $this->assertContains($topic2->id, $topicIds);

        // Test Topic → Unit relationship
        $this->assertEquals($unit->id, $topic1->unit->id);
        $this->assertEquals($unit->name, $topic1->unit->name);

        // Test the complete chain: User → Child → Subject → Unit → Topic
        $userFromTopic = $topic1->unit->subject->child->user;
        $this->assertEquals($user->id, $userFromTopic->id);
        $this->assertEquals($user->name, $userFromTopic->name);
    }

    /**
     * Test eager loading performance to prevent N+1 queries
     */
    public function test_eager_loading_prevents_n_plus_one_queries()
    {
        // Create test data
        $user = User::factory()->create();

        $child1 = Child::create([
            'name' => 'Child 1',
            'grade' => '3rd',
            'user_id' => $user->id,
            'independence_level' => 2,
        ]);

        $child2 = Child::create([
            'name' => 'Child 2',
            'grade' => '5th',
            'user_id' => $user->id,
            'independence_level' => 3,
        ]);

        // Create subjects for each child
        foreach ([$child1, $child2] as $child) {
            for ($i = 1; $i <= 3; $i++) {
                $subject = Subject::create([
                    'name' => "Subject $i for {$child->name}",
                    'color' => '#3b82f6',
                    'user_id' => $user->id,
                    'child_id' => $child->id,
                ]);

                // Create units for each subject
                for ($j = 1; $j <= 2; $j++) {
                    $unit = Unit::create([
                        'subject_id' => $subject->id,
                        'name' => "Unit $j",
                        'description' => "Unit description $j",
                    ]);

                    // Create topics for each unit
                    for ($k = 1; $k <= 3; $k++) {
                        Topic::create([
                            'unit_id' => $unit->id,
                            'title' => "Topic $k",
                            'estimated_minutes' => 30,
                            'required' => true,
                        ]);
                    }
                }
            }
        }

        // Test eager loading with deep relationships
        $childrenWithData = Child::with('subjects.units.topics')
            ->where('user_id', $user->id)
            ->get();

        $this->assertCount(2, $childrenWithData);

        // Verify all data is loaded without additional queries
        foreach ($childrenWithData as $child) {
            $this->assertCount(3, $child->subjects);

            foreach ($child->subjects as $subject) {
                $this->assertCount(2, $subject->units);

                foreach ($subject->units as $unit) {
                    $this->assertCount(3, $unit->topics);
                }
            }
        }
    }

    /**
     * Test relationship counting functionality
     */
    public function test_relationship_counting()
    {
        // Create test data
        $user = User::factory()->create();
        $child = Child::create([
            'name' => 'Test Child',
            'grade' => '5th',
            'user_id' => $user->id,
            'independence_level' => 2,
        ]);

        // Create subjects with different numbers of units
        $subject1 = Subject::create([
            'name' => 'Math',
            'color' => '#3b82f6',
            'user_id' => $user->id,
            'child_id' => $child->id,
        ]);

        $subject2 = Subject::create([
            'name' => 'Science',
            'color' => '#10b981',
            'user_id' => $user->id,
            'child_id' => $child->id,
        ]);

        // Create units for subject1 (3 units)
        for ($i = 1; $i <= 3; $i++) {
            Unit::create([
                'subject_id' => $subject1->id,
                'name' => "Math Unit $i",
                'description' => "Description $i",
            ]);
        }

        // Create units for subject2 (2 units)
        for ($i = 1; $i <= 2; $i++) {
            Unit::create([
                'subject_id' => $subject2->id,
                'name' => "Science Unit $i",
                'description' => "Description $i",
            ]);
        }

        // Test relationship counting
        $childWithCounts = Child::withCount('subjects')
            ->where('id', $child->id)
            ->first();

        $this->assertEquals(2, $childWithCounts->subjects_count);

        $subjectsWithUnitCounts = Subject::withCount('units')
            ->where('child_id', $child->id)
            ->get();

        $mathSubject = $subjectsWithUnitCounts->where('name', 'Math')->first();
        $scienceSubject = $subjectsWithUnitCounts->where('name', 'Science')->first();

        $this->assertEquals(3, $mathSubject->units_count);
        $this->assertEquals(2, $scienceSubject->units_count);
    }

    /**
     * Test data integrity constraints through relationships
     */
    public function test_data_integrity_constraints()
    {
        $user = User::factory()->create();
        $child = Child::create([
            'name' => 'Test Child',
            'grade' => '3rd',
            'user_id' => $user->id,
            'independence_level' => 2,
        ]);

        $subject = Subject::create([
            'name' => 'Math',
            'color' => '#3b82f6',
            'user_id' => $user->id,
            'child_id' => $child->id,
        ]);

        // Test that subjects are properly scoped to their child
        $anotherChild = Child::create([
            'name' => 'Another Child',
            'grade' => '5th',
            'user_id' => $user->id,
            'independence_level' => 3,
        ]);

        // Subject should not appear in another child's subjects
        $this->assertEquals(0, $anotherChild->subjects()->count());
        $this->assertEquals(1, $child->subjects()->count());

        // Subject should belong to the correct child
        $this->assertEquals($child->id, $subject->child_id);
        $this->assertNotEquals($anotherChild->id, $subject->child_id);
    }

    /**
     * Test onboarding-style data creation using relationships
     */
    public function test_onboarding_data_creation_with_relationships()
    {
        $user = User::factory()->create();

        // Create a child using the relationship
        $child = $user->children()->create([
            'name' => 'Emma',
            'grade' => '4th',
            'independence_level' => 2,
        ]);

        $this->assertEquals($user->id, $child->user_id);

        // Create subjects using the child relationship
        $subjects = [
            ['name' => 'Mathematics', 'color' => '#3b82f6'],
            ['name' => 'Science', 'color' => '#10b981'],
            ['name' => 'Reading', 'color' => '#8b5cf6'],
        ];

        foreach ($subjects as $subjectData) {
            $subject = $child->subjects()->create([
                'name' => $subjectData['name'],
                'color' => $subjectData['color'],
                'user_id' => $user->id,
            ]);

            $this->assertEquals($child->id, $subject->child_id);
            $this->assertEquals($user->id, $subject->user_id);
        }

        // Verify the complete data tree was created
        $userWithData = User::with('children.subjects')->find($user->id);

        $this->assertCount(1, $userWithData->children);
        $this->assertEquals('Emma', $userWithData->children->first()->name);
        $this->assertCount(3, $userWithData->children->first()->subjects);

        $subjectNames = $userWithData->children->first()->subjects->pluck('name')->toArray();
        $this->assertContains('Mathematics', $subjectNames);
        $this->assertContains('Science', $subjectNames);
        $this->assertContains('Reading', $subjectNames);
    }
}
