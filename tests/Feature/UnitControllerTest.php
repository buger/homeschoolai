<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Child $child;

    protected Subject $subject;

    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->child = Child::factory()->create(['user_id' => $this->user->id]);
        $this->subject = Subject::factory()->create(['user_id' => $this->user->id]);
        $this->unit = Unit::factory()->create(['subject_id' => $this->subject->id]);

        $this->actingAs($this->user);
    }

    public function test_unit_can_be_deleted_direct_route(): void
    {
        // Verify unit exists
        $this->assertDatabaseHas('units', ['id' => $this->unit->id]);

        // Delete unit using direct route
        $response = $this->delete("/units/{$this->unit->id}");

        // Should redirect after successful deletion
        $response->assertRedirect(route('subjects.show', $this->subject->id));
        $response->assertSessionHas('success', 'Unit deleted successfully.');

        // Verify unit is deleted
        $this->assertDatabaseMissing('units', ['id' => $this->unit->id]);
    }

    public function test_unit_deletion_via_htmx_returns_updated_list(): void
    {
        // Create another unit to verify list is returned
        $anotherUnit = Unit::factory()->create(['subject_id' => $this->subject->id]);

        // Delete unit with HTMX request
        $response = $this->delete("/units/{$this->unit->id}", [], ['HX-Request' => true]);

        // Should return partial view with updated units list
        $response->assertSuccessful();
        $response->assertViewIs('units.partials.units-list');
        $response->assertViewHas('units');
        $response->assertViewHas('subject');

        // Verify unit is deleted but other unit remains
        $this->assertDatabaseMissing('units', ['id' => $this->unit->id]);
        $this->assertDatabaseHas('units', ['id' => $anotherUnit->id]);
    }

    public function test_unit_cannot_be_deleted_when_it_has_topics(): void
    {
        // Create a topic for this unit
        Topic::factory()->create(['unit_id' => $this->unit->id]);

        // Attempt to delete unit
        $response = $this->delete("/units/{$this->unit->id}");

        // Should redirect back with error
        $response->assertRedirect();
        $response->assertSessionHasErrors(['error' => 'Cannot delete unit with existing topics. Please delete all topics first.']);

        // Verify unit still exists
        $this->assertDatabaseHas('units', ['id' => $this->unit->id]);
    }

    public function test_unit_deletion_with_topics_via_htmx_returns_error(): void
    {
        // Create a topic for this unit
        Topic::factory()->create(['unit_id' => $this->unit->id]);

        // Attempt to delete unit with HTMX request
        $response = $this->delete("/units/{$this->unit->id}", [], ['HX-Request' => true]);

        // Should return error response
        $response->assertStatus(400);
        $response->assertSeeText('Cannot delete unit with existing topics. Please delete all topics first.');

        // Verify unit still exists
        $this->assertDatabaseHas('units', ['id' => $this->unit->id]);
    }

    public function test_cannot_delete_unit_belonging_to_different_user(): void
    {
        // Create another user and their unit
        $otherUser = User::factory()->create();
        $otherSubject = Subject::factory()->create(['user_id' => $otherUser->id]);
        $otherUnit = Unit::factory()->create(['subject_id' => $otherSubject->id]);

        // Attempt to delete other user's unit
        $response = $this->delete("/units/{$otherUnit->id}");

        // Should return 403 Forbidden
        $response->assertStatus(403);

        // Verify unit still exists
        $this->assertDatabaseHas('units', ['id' => $otherUnit->id]);
    }

    public function test_cannot_delete_nonexistent_unit(): void
    {
        $nonExistentId = 99999;

        // Attempt to delete non-existent unit
        $response = $this->delete("/units/{$nonExistentId}");

        // Should return 404
        $response->assertStatus(404);
    }

    public function test_unit_deletion_requires_authentication(): void
    {
        // Log out user
        auth()->logout();

        // Attempt to delete unit
        $response = $this->delete("/units/{$this->unit->id}");

        // Should redirect to login (Laravel's default behavior for unauthenticated requests)
        $response->assertRedirect(route('login'));

        // Verify unit still exists
        $this->assertDatabaseHas('units', ['id' => $this->unit->id]);
    }

    public function test_unit_edit_route_generates_correct_url(): void
    {
        $expectedUrl = "/units/{$this->unit->id}/edit";
        $actualUrl = route('units.edit', $this->unit->id);

        $this->assertStringEndsWith($expectedUrl, $actualUrl);
    }

    public function test_unit_update_route_generates_correct_url(): void
    {
        $expectedUrl = "/units/{$this->unit->id}";
        $actualUrl = route('units.update', $this->unit->id);

        $this->assertStringEndsWith($expectedUrl, $actualUrl);
    }

    public function test_unit_destroy_route_generates_correct_url(): void
    {
        $expectedUrl = "/units/{$this->unit->id}";
        $actualUrl = route('units.destroy', $this->unit->id);

        $this->assertStringEndsWith($expectedUrl, $actualUrl);
    }
}
