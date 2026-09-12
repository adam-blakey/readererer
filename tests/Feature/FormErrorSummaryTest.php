<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

/**
 * The session's error bag, as a failed validation pass would leave it.
 */
function error_session(array $errors): array
{
    return ['errors' => (new ViewErrorBag)->put('default', new MessageBag($errors))];
}

/**
 * Render the summary component directly with a bag of errors against it.
 */
function render_error_summary(array $errors, array $fields): string
{
    $bag = new ViewErrorBag;
    $bag->put('default', new MessageBag($errors));

    View::share('errors', $bag);

    return Blade::render('<x-forms.error-summary :fields="$fields" />', ['fields' => $fields]);
}

// The component

test('an error keyed on a field the form renders is left to that field', function () {
    $html = render_error_summary(['name' => ['The name field is required.']], ['name']);

    expect($html)->not->toContain('alert-danger');
});

test('an error with no field to sit next to is summarised', function () {
    $html = render_error_summary(['password' => ['The password is rubbish.']], ['name']);

    expect($html)->toContain('alert alert-danger')
        ->toContain('The password is rubbish.')
        ->toContain('There is a problem with this form');
});

test('the summary heading is pluralised by the number of messages it carries', function () {
    $html = render_error_summary([
        'password' => ['The password is rubbish.'],
        'image' => ['The image is rubbish.'],
    ], ['name']);

    expect($html)->toContain('There are problems with this form')
        ->toContain('The password is rubbish.')
        ->toContain('The image is rubbish.');
});

test('a repeated row is matched by a wildcard, so its own messages are not duplicated', function () {
    $html = render_error_summary(
        ['term_dates.3.start_datetime' => ['The start is required.']],
        ['name', 'term_dates.*.start_datetime'],
    );

    expect($html)->not->toContain('alert-danger');
});

test('a message is escaped rather than rendered as markup', function () {
    $html = render_error_summary(['password' => ['<b>nope</b>']], ['name']);

    expect($html)->toContain('&lt;b&gt;nope&lt;/b&gt;')
        ->not->toContain('<b>nope</b>');
});

// The forms it is wired into

test('the generic form summarises an error on a field the controller removed', function () {
    $admin = make_user(UserRole::Admin);

    // UserController@create drops password and image from the generic form, so
    // an error against either has nowhere of its own to be rendered.
    $response = $this->actingAs($admin)
        ->withSession(error_session(['password' => ['The password is rubbish.']]))
        ->get(route('users.create'));

    $response->assertOk()
        ->assertSee('The password is rubbish.')
        ->assertSee('alert alert-danger', false);
});

test('the generic form leaves an error on a rendered field to that field', function () {
    $admin = make_user(UserRole::Admin);

    $response = $this->actingAs($admin)
        ->withSession(error_session(['first_name' => ['The first name field is required.']]))
        ->get(route('users.create'));

    $response->assertOk()
        ->assertSee('The first name field is required.')
        ->assertDontSee('alert alert-danger', false);
});

test('the term form summarises an error that has no field of its own', function () {
    $admin = make_user(UserRole::Admin);
    $term = Term::factory()->create();

    $response = $this->actingAs($admin)
        ->withSession(error_session(['term_dates' => ['A term needs at least one date.']]))
        ->get(route('terms.edit', $term));

    $response->assertOk()
        ->assertSee('A term needs at least one date.')
        ->assertSee('alert alert-danger', false);
});

test('the user edit form summarises an error from the add-to-ensemble form', function () {
    $admin = make_user(UserRole::Admin);

    $response = $this->actingAs($admin)
        ->withSession(error_session(['ensemble_id' => ['This user is already a member.']]))
        ->get(route('users.edit', $admin));

    $response->assertOk()
        ->assertSee('This user is already a member.')
        ->assertSee('alert alert-danger', false);
});

test('the ensemble edit form summarises an error that has no field of its own', function () {
    $admin = make_user(UserRole::Admin);
    $ensemble = Ensemble::factory()->create();

    $response = $this->actingAs($admin)
        ->withSession(error_session(['seating_plan_enabled' => ['The seating plan cannot be turned off.']]))
        ->get(route('ensembles.edit', $ensemble));

    $response->assertOk()
        ->assertSee('The seating plan cannot be turned off.')
        ->assertSee('alert alert-danger', false);
});
