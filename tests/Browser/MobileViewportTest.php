<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Location;
use App\Models\Term;

test('list and poll pages do not scroll sideways on a phone', function (string $path) {
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create();
    make_term_date($term);
    $member = make_user(UserRole::Member, ['first_name' => 'Jo', 'last_name' => 'Bloggs']);
    join_ensemble($member, $ensemble);

    $this->actingAs(make_user(UserRole::Admin));

    $path = str_replace(['{ensemble}', '{term}'], [$ensemble->slug, $term->slug], $path);

    visit($path)
        ->resize(375, 812)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth');
})->with([
    '/terms',
    '/users',
    '/attendance/poll/{ensemble}/{term}',
]);

test('the application log does not scroll sideways on a phone', function () {
    $this->actingAs(make_user(UserRole::Admin));

    visit('/settings/monitoring/logs')
        ->resize(375, 812)
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth');
});

test('pagination with many pages wraps on a phone instead of scrolling sideways', function () {
    Location::factory()->count(100)->create();

    $this->actingAs(make_user(UserRole::Admin));

    visit('/locations')
        ->resize(375, 812)
        ->assertSee('10')
        ->assertScript('document.documentElement.scrollWidth <= document.documentElement.clientWidth');
});
