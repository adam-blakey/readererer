<?php

use App\Enums\UserRole;
use App\Models\Composer;
use App\Models\Ensemble;
use App\Models\Term;
use Diglactic\Breadcrumbs\Breadcrumbs;
use Illuminate\Support\Facades\Route;

test('every named GET page has a breadcrumb trail', function () {
    $without_breadcrumbs = [
        'login',
        'register',
        'password.request',
        'password.reset',
        'password.confirm',
        'verification.notice',
        'verification.verify',
        'ensembles.seating-plan.download',
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
    $composer = Composer::factory()->create(['first_name' => 'Sergei', 'last_name' => 'Rachmaninoff']);

    expect(Breadcrumbs::generate('composers.show', $composer)->pluck('title')->all())
        ->toBe(['Home', 'Composers', 'Sergei Rachmaninoff'])
        ->and(Breadcrumbs::generate('composers.edit', $composer)->pluck('title')->all())
        ->toBe(['Home', 'Composers', 'Sergei Rachmaninoff', 'Edit']);

});

test('an ensemble page nests under the ensemble it belongs to', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Concert Band']);

    expect(Breadcrumbs::generate('ensembles.members', $ensemble)->pluck('title')->all())
        ->toBe(['Home', 'Ensembles', 'Concert Band', 'Members'])
        ->and(Breadcrumbs::generate('ensembles.seating-plan.show', $ensemble)->pluck('title')->all())
        ->toBe(['Home', 'Ensembles', 'Concert Band', 'Seating plan']);

});

test('a poll and a register hang off their own index', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Concert Band']);
    $term = Term::factory()->create(['name' => 'Autumn 2026']);
    $term_date = make_term_date($term);

    expect(Breadcrumbs::generate('attendance.poll', $ensemble, $term)->pluck('title')->all())
        ->toBe(['Home', 'Attendance updates', 'Concert Band: Autumn 2026'])
        ->and(Breadcrumbs::generate('attendance.register.show', $ensemble, $term_date)->pluck('title')->all())
        ->toBe(['Home', 'Attendance registers', 'Concert Band: '.$term_date->date_label]);

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
