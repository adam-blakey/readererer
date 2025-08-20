<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class EndpointAccessTest extends TestCase
{
    use RefreshDatabase;

    protected User $guestUser; // Not logged in; used only for reference
    protected User $ensembleUser;
    protected User $memberUser;
    protected User $moderatorUser;
    protected User $adminUser;
    protected Ensemble $ensemble;
    protected Term $term;

    protected function setUp(): void
    {
        parent::setUp();

        // Minimal fixtures: an ensemble and a term
        $this->ensemble = Ensemble::factory()->create();
        $this->term = Term::factory()->create();

        // Ensure there is at least one instrument family for pivot constraints
        $instrumentFamilyId = \App\Models\InstrumentFamily::query()->first()?->id
            ?? \App\Models\InstrumentFamily::create(['name' => 'Test Family'])->id;

        // Users per role
        $this->ensembleUser = User::factory()->create(['role' => UserRole::Ensemble]);
        $this->memberUser = User::factory()->create(['role' => UserRole::Member]);
        $this->moderatorUser = User::factory()->create(['role' => UserRole::Moderator]);
        $this->adminUser = User::factory()->create(['role' => UserRole::Admin]);

        // Attach ensemble user to the ensemble (so they can view that ensemble per EnsemblePolicy)
        $this->ensembleUser->ensembles()->attach($this->ensemble->id, [
            'instrument_family_id' => $instrumentFamilyId,
            'seat_column' => null,
            'seat_row' => null,
        ]);

        // Also attach member to ensemble to simulate a regular member of ensemble
        $this->memberUser->ensembles()->attach($this->ensemble->id, [
            'instrument_family_id' => $instrumentFamilyId,
            'seat_column' => null,
            'seat_row' => null,
        ]);
    }

    public function test_home_is_public(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_dashboard_access_control(): void
    {
        // Guests: should be forbidden by can middleware (no auth user)
        $this->get('/dashboard')->assertStatus(403);

        // Authenticated: any role can access (Gate::define('view.dashboard') returns true)
        $this->actingAs($this->ensembleUser)->get('/dashboard')->assertOk();
        $this->actingAs($this->memberUser)->get('/dashboard')->assertOk();
        $this->actingAs($this->moderatorUser)->get('/dashboard')->assertOk();
        $this->actingAs($this->adminUser)->get('/dashboard')->assertOk();
    }

    public function test_resource_indexes_require_auth(): void
    {
        $resourceIndexes = [
            '/composers',
            '/ensembles',
            '/pieces',
            '/setlists',
            '/terms',
            '/users',
        ];

        foreach ($resourceIndexes as $uri) {
            // Guest should be redirected to login (auth middleware)
            $this->get($uri)->assertRedirect('/login');

            // Authenticated can reach route; for ensembles index, EnsemblePolicy->viewAny requires Member+
            $this->actingAs($this->ensembleUser)->get($uri)
                ->assertStatus(Str::contains($uri, '/ensembles') ? 403 : 200);

            $this->actingAs($this->memberUser)->get($uri)->assertOk();
            $this->actingAs($this->moderatorUser)->get($uri)->assertOk();
            $this->actingAs($this->adminUser)->get($uri)->assertOk();
        }
    }

    public function test_ensemble_show_follows_policy(): void
    {
        $showUrl = route('ensembles.show', $this->ensemble);

        // Guest -> redirect to login because of auth middleware on resource
        $this->get($showUrl)->assertRedirect('/login');

        // Ensemble user attached to the same ensemble -> allowed by EnsemblePolicy::view
        $this->actingAs($this->ensembleUser)->get($showUrl)->assertOk();

        // Member attached to ensemble but policy requires Moderator+ (unless contains) -> denied
        // Our policy allows Moderator+ OR (Ensemble role AND in ensemble)
        // Since this is a Member (not Ensemble role), they should be forbidden
        $this->actingAs($this->memberUser)->get($showUrl)->assertStatus(403);

        // Moderator+ -> allowed
        $this->actingAs($this->moderatorUser)->get($showUrl)->assertOk();
        $this->actingAs($this->adminUser)->get($showUrl)->assertOk();
    }

    public function test_attendance_index_requires_moderator_plus(): void
    {
        $url = route('attendance.index');

        // Guests forbidden (no auth, can middleware results in 403)
        $this->get($url)->assertStatus(403);

        // Ensemble and Member -> AttendancePolicy::viewAny requires Moderator+
        $this->actingAs($this->ensembleUser)->get($url)->assertStatus(403);
        $this->actingAs($this->memberUser)->get($url)->assertStatus(403);

        // Moderator+ allowed
        $this->actingAs($this->moderatorUser)->get($url)->assertOk();
        $this->actingAs($this->adminUser)->get($url)->assertOk();
    }

    public function test_attendance_poll_get_requires_view_ensemble(): void
    {
        $url = route('attendance.poll', ['ensemble' => $this->ensemble->slug, 'term' => $this->term->slug]);

        // Guests forbidden by can('view', 'ensemble') (no user)
        $this->get($url)->assertStatus(403);

        // Ensemble user who belongs to this ensemble -> allowed
        $this->actingAs($this->ensembleUser)->get($url)->assertOk();

        // Member who belongs to ensemble but not Ensemble role -> policy requires Moderator+ or Ensemble role with membership
        $this->actingAs($this->memberUser)->get($url)->assertStatus(403);

        // Moderator+ -> allowed
        $this->actingAs($this->moderatorUser)->get($url)->assertOk();
        $this->actingAs($this->adminUser)->get($url)->assertOk();
    }

    public function test_attendance_poll_post_requires_create_attendance_and_view_ensemble(): void
    {
        $url = route('attendance.poll-store', ['ensemble' => $this->ensemble->slug, 'term' => $this->term->slug]);

        // Guests forbidden by can middleware
        $this->post($url, ['_token' => csrf_token()])->assertStatus(403);

        // Ensemble/Member -> AttendancePolicy::create requires Admin
        $this->actingAs($this->ensembleUser)->post($url, ['_token' => csrf_token()])->assertStatus(403);
        $this->actingAs($this->memberUser)->post($url, ['_token' => csrf_token()])->assertStatus(403);
        $this->actingAs($this->moderatorUser)->post($url, ['_token' => csrf_token()])->assertStatus(403);

        // Admin -> allowed to hit route; controller also calls authorize('view', $ensemble) which Admin passes
        $this->actingAs($this->adminUser)->post($url, ['_token' => csrf_token()])->assertRedirect();
    }
}
