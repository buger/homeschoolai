<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OnboardingChildrenTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable session middleware but disable unnecessary middleware for testing
        $this->withoutMiddleware([
            \App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    #[Test]
    public function onboarding_children_endpoint_validates_required_fields()
    {
        // User is already authenticated in setUp()

        // Test empty children array
        $response = $this->postJson('/onboarding/children', [
            'children' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children']);
    }

    #[Test]
    public function onboarding_children_endpoint_validates_child_fields()
    {
        // User is already authenticated in setUp()

        // Test missing required fields
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => '', // Empty name
                    'grade' => '', // Empty grade
                    'independence_level' => '',
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'children.0.name',
            'children.0.grade',
            'children.0.independence_level',
        ]);
    }

    #[Test]
    public function onboarding_children_endpoint_validates_grade_options()
    {
        // User is already authenticated in setUp()

        // Test invalid grade values
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => 'Invalid', // Invalid grade
                    'independence_level' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children.0.grade']);

        // Test another invalid grade
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => '13th', // Invalid grade
                    'independence_level' => 1,
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children.0.grade']);
    }

    #[Test]
    public function onboarding_children_endpoint_validates_independence_level()
    {
        // User is already authenticated in setUp()

        // Test invalid independence level
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => '3rd',
                    'independence_level' => 5, // Invalid level
                ],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children.0.independence_level']);
    }

    #[Test]
    public function onboarding_children_endpoint_validates_maximum_children()
    {
        // User is already authenticated in setUp()

        // Create 6 children (exceeds max of 5)
        $children = [];
        for ($i = 1; $i <= 6; $i++) {
            $children[] = [
                'name' => "Child $i",
                'grade' => '3rd',
                'independence_level' => 1,
            ];
        }

        $response = $this->postJson('/onboarding/children', [
            'children' => $children,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['children']);
    }

    #[Test]
    public function onboarding_children_endpoint_requires_authentication()
    {
        // Override authentication for this test - logout the user
        auth()->logout();

        // Test without authentication - should redirect to login
        $response = $this->postJson('/onboarding/children', [
            'children' => [
                [
                    'name' => 'Test Child',
                    'grade' => '3rd',
                    'independence_level' => 1,
                ],
            ],
        ]);

        // For JSON requests without authentication, Laravel returns 401
        $response->assertStatus(401);
    }

    #[Test]
    public function onboarding_view_loads_successfully()
    {
        // User is already authenticated in setUp()

        $response = $this->get('/onboarding');

        $response->assertStatus(200);
        $response->assertViewIs('onboarding.index');
        $response->assertSeeText('Welcome to HomeLearnAI!');
        $response->assertSee('data-testid="step-1"', false);
        $response->assertSee('data-testid="step-2"', false);
        $response->assertSee('data-testid="children-form"', false);
    }

    #[Test]
    public function onboarding_form_includes_required_elements()
    {
        // User is already authenticated in setUp()

        $response = $this->get('/onboarding');

        $response->assertStatus(200);

        // Check for Alpine.js function
        $response->assertSee('function onboardingWizard()', false);

        // Check for form elements
        $response->assertSee('data-testid="add-another-child"', false);
        $response->assertSee('data-testid="next-button"', false);
        $response->assertSee('data-testid="previous-button"', false);

        // Check for children form fields
        $response->assertSee('child-name-', false);
        $response->assertSee('child-grade-', false);
        $response->assertSee('child-independence-', false);
    }
}
