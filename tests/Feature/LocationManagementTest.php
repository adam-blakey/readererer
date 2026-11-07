<?php

use App\Enums\UserRole;
use App\Models\Location;
use Illuminate\Support\Facades\Http;

test('moderators can view locations but only admins can change them', function () {
    $location = Location::factory()->create();

    $this->actingAs(make_user(UserRole::Member))->get(route('locations.index'))->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get(route('locations.index'))->assertOk()->assertSee($location->name);
    $this->actingAs(make_user(UserRole::Moderator))->get(route('locations.show', $location))->assertOk();
    $this->actingAs(make_user(UserRole::Moderator))->get(route('locations.create'))->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->post(route('locations.store'), ['name' => 'Hall'])->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->get(route('locations.create'))->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get(route('locations.edit', $location))->assertOk();
});

test('creating a location looks up its coordinates from the address on OpenStreetMap', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([['lat' => '51.5007292', 'lon' => '-0.1246254']])]);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('locations.store'), ['name' => 'Big Ben', 'address' => 'Westminster, London'])
        ->assertRedirect();

    $location = Location::firstWhere('name', 'Big Ben');
    expect($location->latitude)->toBe(51.5007292)
        ->and($location->longitude)->toBe(-0.1246254)
        ->and($location->map_url)->toBe('https://www.openstreetmap.org/?mlat=51.5007292&mlon=-0.1246254#map=17/51.5007292/-0.1246254');

    Http::assertSent(fn ($request) => $request['q'] === 'Westminster, London');
});

test('coordinates entered by hand are kept, and a failed lookup leaves them empty', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('locations.store'), ['name' => 'Pinned', 'address' => 'Somewhere', 'latitude' => '10.5', 'longitude' => '20.25']);
    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('locations.store'), ['name' => 'Unknown', 'address' => 'Nowhere at all']);

    expect(Location::firstWhere('name', 'Pinned')->only('latitude', 'longitude'))->toBe(['latitude' => 10.5, 'longitude' => 20.25]);

    $unknown = Location::firstWhere('name', 'Unknown');
    expect($unknown->latitude)->toBeNull()
        ->and($unknown->map_url)->toBe('https://www.openstreetmap.org/search?query=Nowhere+at+all');
    Http::assertSentCount(1);
});

test('changing the address looks the coordinates up again', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([['lat' => '1', 'lon' => '2']])]);
    $location = Location::factory()->create(['address' => 'Old address', 'latitude' => 50, 'longitude' => 0]);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('locations.update', $location), ['name' => $location->name, 'address' => 'New address', 'latitude' => '50', 'longitude' => '0'])
        ->assertRedirect(route('locations.show', $location));

    expect($location->fresh()->only('latitude', 'longitude'))->toBe(['latitude' => 1.0, 'longitude' => 2.0]);
});

test('the location pages show an OpenStreetMap map once the coordinates are known', function () {
    $pinned = Location::factory()->create(['latitude' => 51.5, 'longitude' => -0.1]);
    $unpinned = Location::factory()->create(['latitude' => null, 'longitude' => null]);
    $admin = make_user(UserRole::Admin);

    foreach (['locations.show', 'locations.edit'] as $route) {
        $response = $this->actingAs($admin)->get(route($route, $pinned))->assertOk()
            ->assertSee('src="https://tile.openstreetmap.org/16/32749/21794.png"', false)
            ->assertSee('OpenStreetMap contributors');

        expect($response->headers->get('Content-Security-Policy'))->toContain('https://tile.openstreetmap.org');
    }

    $this->actingAs($admin)->get(route('locations.show', $unpinned))->assertOk()->assertDontSee('tile.openstreetmap.org')->assertSee('No coordinates yet.');
});

test('a location can be added from the term form and comes back as JSON', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([])]);

    $this->actingAs(make_user(UserRole::Admin))
        ->postJson(route('locations.store'), ['name' => 'Village Hall'])
        ->assertCreated()
        ->assertExactJson(['id' => Location::firstWhere('name', 'Village Hall')->id, 'name' => 'Village Hall']);

    $this->actingAs(make_user(UserRole::Admin))
        ->postJson(route('locations.store'), ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');
});
