<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_visiting_an_undefined_route_returns_a_custom_404_page(): void
    {
        $response = $this->get('/this-route-does-not-exist');

        $response->assertStatus(404);
        $response->assertSee('404');
        $response->assertSee('Page not found');
    }

    public function test_route_model_binding_miss_returns_a_custom_404_page(): void
    {
        $user = User::factory()->create();

        // Project ID 999999 doesn't exist — route-model binding throws
        // ModelNotFoundException, which Laravel converts to a 404 just
        // like an undefined route does.
        $response = $this->actingAs($user)->get('/projects/999999');

        $response->assertStatus(404);
        $response->assertSee('404');
    }

    public function test_unauthorized_access_returns_a_custom_403_page(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $project = Project::factory()->create(['created_by' => $owner->id]);

        $response = $this->actingAs($stranger)->get('/projects/'.$project->id);

        $response->assertStatus(403);
        $response->assertSee('403');
        // false = don't HTML-escape the needle before matching; the
        // page's apostrophe is literal, unescaped static HTML (not
        // Blade {{ }} output), so the default escaped needle
        // ("don&#039;t") wouldn't match it.
        $response->assertSee("don't have permission", false);
    }

    public function test_debug_mode_off_does_not_expose_stack_traces_on_a_server_error(): void
    {
        config(['app.debug' => false]);

        // Force a real exception through the normal exception-handling
        // pipeline via a throwaway route, rather than asserting against
        // a page that doesn't actually throw — this proves the 500 view
        // is what actually renders when something breaks, not just that
        // the .blade.php file exists.
        Route::get('/__test-trigger-500', function () {
            throw new \RuntimeException('Deliberate test exception — should never be visible to the response.');
        });

        $response = $this->get('/__test-trigger-500');

        $response->assertStatus(500);
        $response->assertDontSee('Deliberate test exception');
        $response->assertDontSee('RuntimeException');
        $response->assertDontSee('stack trace', false);
        $response->assertSee('500');
    }
}
