<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Support\Facades\Route;

test('every named GET page has a breadcrumb trail', function () {
    $without_breadcrumbs = [
        'login',
        'google.redirect',
        'google.callback',
        'password.request',
        'password.reset',
        'password.confirm',
        'verification.notice',
        'verification.verify',
        'ensembles.seating-plan.download',
        'health',
        'attendance.poll.legacy',
        'manifest',
        'emails.show',
    ];

    $missing = collect(Route::getRoutes())
        ->filter(fn ($route) => in_array('GET', $route->methods(), true))
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->reject(fn (string $name) => in_array($name, $without_breadcrumbs, true))
        ->reject(fn (string $name) => Breadcrumbs::exists($name))
        ->values();

    expect($missing->all())->toBe([]);
});

test('an index page is a child of home', function () {
    $trail = Breadcrumbs::generate('composers.index');

    expect($trail->pluck('title')->all())->toBe(['Home', 'Composers'])
        ->and($trail->pluck('url')->all())->toBe([route('home'), route('composers.index')]);
});

test('a record sits under its index, and its edit form under the record', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Concert Band']);

    expect(Breadcrumbs::generate('ensembles.show', $ensemble)->pluck('title')->all())
        ->toBe(['Home', 'Ensembles', 'Concert Band'])
        ->and(Breadcrumbs::generate('ensembles.edit', $ensemble)->pluck('title')->all())
        ->toBe(['Home', 'Ensembles', 'Concert Band', 'Edit']);

});

test('an ensemble page nests under the ensemble it belongs to', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Concert Band']);

    expect(Breadcrumbs::generate('ensembles.members.index', $ensemble)->pluck('title')->all())
        ->toBe(['Home', 'Ensembles', 'Concert Band', 'Members'])
        ->and(Breadcrumbs::generate('ensembles.seating-plan.show', $ensemble)->pluck('title')->all())
        ->toBe(['Home', 'Ensembles', 'Concert Band', 'Seating plan']);

});

test('a poll and a register hang off their own index', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Concert Band']);
    $term = Term::factory()->create(['name' => 'Autumn 2026']);
    $term_date = make_term_date($term);

    expect(Breadcrumbs::generate('attendance.poll.show', $ensemble, $term)->pluck('title')->all())
        ->toBe(['Home', 'Attendance updates', 'Concert Band: Autumn 2026'])
        ->and(Breadcrumbs::generate('attendance.register.show', $ensemble, $term_date)->pluck('title')->all())
        ->toBe(['Home', 'Upcoming rehearsals', 'Concert Band: '.$term_date->date_label]);

});

test('the page header renders the trail for the current route', function () {
    $response = $this->actingAs(make_user(UserRole::Moderator))
        ->get(route('terms.index'));

    $response->assertOk();
    $response->assertSee('breadcrumb', false);
    $response->assertSeeInOrder(['>Home</a>', '>Terms</a>'], false);
});

test('a page without a trail renders no breadcrumbs', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertDontSee('breadcrumb', false);
});

test('an unknown URL renders the 404 page', function () {
    $this->get('/no-such-page')
        ->assertNotFound()
        ->assertSee('Not found');
});

test('an error on an unnamed route renders the error page', function () {
    Route::post('/_unnamed-failure', fn () => abort(500));

    $this->post('/_unnamed-failure')
        ->assertInternalServerError()
        ->assertSee('Server error');
});
