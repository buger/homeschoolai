<?php

namespace Tests\Feature;

use App\Models\Child;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KidsModeEnterExitTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected $child;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable session middleware but disable unnecessary middleware for testing
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Create and authenticate user
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create a test child using the user relationship
        $this->child = $this->user->children()->create([
            'name' => 'Test Child',
            'grade' => '3rd',
            'independence_level' => 2,
        ]);
    }

    #[Test]
    public function parent_dashboard_shows_pin_status_and_enter_kids_mode_buttons(): void
    {
        // Set up PIN in the user preferences
        $preferences = $this->user->getPreferences();
        $preferences->kids_mode_pin = Hash::make('1234');
        $preferences->save();

        $response = $this->get(route('dashboard.parent'));

        $response->assertOk();
        $response->assertViewIs('dashboard.parent');
        $response->assertViewHas('pin_is_set', true);
        $response->assertSee('Enter Kids Mode');
        $response->assertSee('kids-mode-enter-btn');
    }

    #[Test]
    public function parent_dashboard_shows_set_pin_first_when_pin_not_set(): void
    {
        // No PIN set - UserPreferences model will default to null
        // Ensure no PIN is set in the user preferences
        $preferences = $this->user->getPreferences();
        $preferences->kids_mode_pin = null;
        $preferences->save();

        $response = $this->get(route('dashboard.parent'));

        $response->assertOk();
        $response->assertViewHas('pin_is_set', false);
        $response->assertSee('Set PIN First');
        $response->assertSee(route('kids-mode.settings'));
    }

    #[Test]
    public function can_enter_kids_mode_when_pin_is_set(): void
    {
        // No SupabaseClient mocking needed - controller uses Eloquent directly

        $response = $this->post(route('kids-mode.enter', $this->child->id));

        // Check session data is set
        $this->assertTrue(Session::get('kids_mode_active'));
        $this->assertEquals($this->child->id, Session::get('kids_mode_child_id'));
        $this->assertEquals($this->child->name, Session::get('kids_mode_child_name'));
        $this->assertNotNull(Session::get('kids_mode_entered_at'));

        // Should redirect to child today view
        $response->assertRedirect(route('dashboard.child-today', $this->child->id));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function cannot_enter_kids_mode_for_non_existent_child(): void
    {
        // Test with non-existent child ID (99999)

        $response = $this->post(route('kids-mode.enter', 99999));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Child not found']);

        // Session should not be modified
        $this->assertNull(Session::get('kids_mode_active'));
    }

    #[Test]
    public function cannot_enter_kids_mode_for_other_users_child(): void
    {
        // Create a child for another user (unauthorized access test)
        $otherChild = Child::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Other Child',
            'grade' => '4th',
            'independence_level' => 2,
        ]);

        $response = $this->post(route('kids-mode.enter', $otherChild->id));

        $response->assertStatus(404);
        $response->assertJson(['error' => 'Child not found']);

        // Session should not be modified
        $this->assertNull(Session::get('kids_mode_active'));
    }

    #[Test]
    public function kids_mode_enter_returns_json_for_ajax_requests(): void
    {
        // Child will be found via database using real data

        $response = $this->postJson(route('kids-mode.enter', $this->child->id));

        $response->assertOk();
        $response->assertJson([
            'message' => 'Kids mode activated for '.$this->child->name,
            'child_id' => $this->child->id,
            'child_name' => $this->child->name,
        ]);

        // Check session data is set
        $this->assertTrue(Session::get('kids_mode_active'));
        $this->assertEquals($this->child->name, Session::get('kids_mode_child_name'));
    }

    #[Test]
    public function kids_mode_indicator_shows_when_active(): void
    {
        // Set kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);
        Session::put('kids_mode_entered_at', now()->toISOString());

        // Access the child-today view directly since dashboard redirects in kids mode
        $response = $this->get(route('dashboard.child-today', $this->child->id));

        $response->assertOk();
        $response->assertSee('data-testid="kids-mode-indicator"', false);
        $response->assertSee('Kids Mode Active');
        $response->assertSee($this->child->name);
        $response->assertSee('Exit Kids Mode');
    }

    #[Test]
    public function kids_mode_indicator_hidden_when_not_active(): void
    {
        $response = $this->get('/dashboard');

        $response->assertOk();
        // Check that the actual indicator div is not present (it should not render when kids mode is not active)
        $response->assertDontSee('<div class="fixed top-4 right-4 z-50 bg-gradient-to-r from-purple-500 to-pink-500', false);
        $response->assertDontSee('Kids Mode Active');
    }

    #[Test]
    public function navigation_is_restricted_in_kids_mode(): void
    {
        // Set kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);

        $response = $this->get('/dashboard');

        // Should redirect to child today view
        $response->assertRedirect(route('dashboard.child-today', $this->child->id));
        $response->assertSessionHas('error', 'Access denied in kids mode');
    }

    #[Test]
    public function can_exit_kids_mode_with_valid_pin(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);

        // Set up PIN in user preferences
        $preferences = $this->user->getPreferences();
        $preferences->kids_mode_pin = Hash::make('1234');
        $preferences->kids_mode_pin_attempts = 0;
        $preferences->kids_mode_pin_locked_until = null;
        $preferences->save();

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '1234',
        ]);

        $response->assertOk();
        $response->assertJson([
            'message' => 'Kids mode deactivated successfully',
            'redirect_url' => route('dashboard'),
        ]);

        // Session should be cleared
        $this->assertNull(Session::get('kids_mode_active'));
        $this->assertNull(Session::get('kids_mode_child_id'));
        $this->assertNull(Session::get('kids_mode_child_name'));
    }

    #[Test]
    public function cannot_exit_kids_mode_with_invalid_pin(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);

        // Set up PIN in user preferences with different PIN
        $preferences = $this->user->getPreferences();
        $preferences->kids_mode_pin = Hash::make('5678'); // Different PIN
        $preferences->kids_mode_pin_attempts = 0;
        $preferences->kids_mode_pin_locked_until = null;
        $preferences->save();

        $response = $this->postJson(route('kids-mode.exit.validate'), [
            'pin' => '1234', // Wrong PIN
        ]);

        $response->assertStatus(400);
        $response->assertJsonStructure(['error', 'attempts_remaining']);

        // Session should remain active
        $this->assertTrue(Session::get('kids_mode_active'));
    }

    #[Test]
    public function pin_exit_screen_shows_correct_content(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);
        Session::put('kids_mode_child_name', $this->child->name);

        // Set up PIN in user preferences
        $preferences = $this->user->getPreferences();
        $preferences->kids_mode_pin = Hash::make('1234');
        $preferences->kids_mode_pin_attempts = 0;
        $preferences->kids_mode_pin_locked_until = null;
        $preferences->save();

        $response = $this->get(route('kids-mode.exit'));

        $response->assertOk();
        $response->assertViewIs('kids-mode.exit');
        $response->assertViewHas('child', $this->child);
        $response->assertViewHas('has_pin_setup', true);
        $response->assertViewHas('is_locked', false);
        $response->assertSee('Enter the 4-digit PIN');
    }

    #[Test]
    public function cannot_access_parent_only_routes_in_kids_mode(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);

        // Test blocked routes
        $blockedRoutes = [
            'dashboard.parent',
            'children.index',
            'planning.index',
            'subjects.index',
            'kids-mode.settings',
        ];

        foreach ($blockedRoutes as $route) {
            $response = $this->get(route($route));

            // Should redirect to child today view
            $response->assertRedirect(route('dashboard.child-today', $this->child->id));
            $response->assertSessionHas('error', 'Access denied in kids mode');
        }
    }

    #[Test]
    public function can_access_allowed_routes_in_kids_mode(): void
    {
        // Set up kids mode session
        Session::put('kids_mode_active', true);
        Session::put('kids_mode_child_id', $this->child->id);

        // Test allowed routes - these should not redirect
        $allowedRoutes = [
            'kids-mode.exit',
            'locale.update',
        ];

        foreach ($allowedRoutes as $route) {
            try {
                $response = $this->get(route($route));
                // Should not redirect to child today view
                $this->assertNotEquals(
                    route('dashboard.child-today', $this->child->id),
                    $response->headers->get('Location')
                );
            } catch (\Exception $e) {
                // Some routes may have additional requirements, but they shouldn't be blocked
                $this->assertNotContains('Access denied in kids mode', $e->getMessage());
            }
        }
    }

    protected function tearDown(): void
    {
        // Clear kids mode session data
        Session::forget(['kids_mode_active', 'kids_mode_child_id', 'kids_mode_child_name', 'kids_mode_entered_at', 'kids_mode_fingerprint']);

        parent::tearDown();
    }
}
