<?php

use App\Enums\UserRole;
use App\Features\ConcertDetails;
use App\Models\DressCode;
use App\Models\Ensemble;
use App\Models\Term;
use Laravel\Pennant\Feature;

function enable_concert_details(): void
{
    config(['app.readererer_feature_flags' => [ConcertDetails::class => ['enabled' => true]]]);
    Feature::flushCache();
}

test('dress codes are managed like locations once the flag is on', function () {
    $dressCode = DressCode::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))->get(route('dresscodes.index'))->assertBadRequest();

    enable_concert_details();

    $this->actingAs(make_user(UserRole::Member))->get(route('dresscodes.index'))->assertForbidden();
    $this->actingAs(make_user(UserRole::Moderator))->get(route('dresscodes.index'))->assertOk()->assertSee($dressCode->name);
    $this->actingAs(make_user(UserRole::Moderator))->post(route('dresscodes.store'), ['name' => 'Blacks'])->assertForbidden();
    $this->actingAs(make_user(UserRole::Admin))->get(route('dresscodes.create'))->assertOk();
    $this->actingAs(make_user(UserRole::Admin))->get(route('dresscodes.edit', $dressCode))->assertOk();

    $this->actingAs(make_user(UserRole::Admin))
        ->post(route('dresscodes.store'), ['name' => 'Concert blacks', 'description' => 'Black shirt and trousers'])
        ->assertRedirect();

    expect(DressCode::firstWhere('name', 'Concert blacks')->description)->toBe('Black shirt and trousers');
});

test('the term form saves each date\'s description, dress code and concert order only while the flag is on', function () {
    $term = Term::factory()->create();
    $date = make_term_date($term);
    $date->forceFill(['description' => 'Kept', 'concert_order' => 'Kept order'])->save();
    $dressCode = DressCode::factory()->create();
    $row = fn () => [
        'name' => $term->name,
        'slug' => $term->slug,
        'term_dates' => [[
            'id' => $date->id,
            'start_datetime' => '2026-03-20 19:30:00',
            'end_datetime' => '2026-03-20 22:00:00',
            'description' => 'Arrive by 6pm',
            'concert_order' => "Overture\nInterval\nSymphony",
            'dress_code_id' => $dressCode->id,
        ]],
    ];

    $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.update', $term), $row())->assertRedirect();
    expect($date->fresh()->only('description', 'concert_order', 'dress_code_id'))->toBe(['description' => 'Kept', 'concert_order' => 'Kept order', 'dress_code_id' => null]);

    enable_concert_details();

    $this->actingAs(make_user(UserRole::Admin))->get(route('terms.edit', $term))->assertOk()->assertSee($dressCode->name)->assertSee(__('Concert order'));
    $this->actingAs(make_user(UserRole::Admin))->patch(route('terms.update', $term), $row())->assertRedirect();
    expect($date->fresh()->only('description', 'concert_order', 'dress_code_id'))->toBe(['description' => 'Arrive by 6pm', 'concert_order' => "Overture\nInterval\nSymphony", 'dress_code_id' => $dressCode->id]);
});

test('members can open a date\'s page for its details once the flag is on, without seeing attendance', function () {
    $ensemble = Ensemble::factory()->create();
    $member = make_user(UserRole::Member);
    $bandmate = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);
    join_ensemble($bandmate, $ensemble);
    $date = make_term_date(Term::factory()->create(), $ensemble);
    $date->forceFill([
        'description' => 'Arrive by 6pm',
        'concert_order' => 'Overture',
        'dress_code_id' => DressCode::factory()->create(['name' => 'Concert blacks'])->id,
    ])->save();

    $this->actingAs($member)->get(route('term-dates.show', $date))->assertForbidden();

    enable_concert_details();

    $this->actingAs($member)->get(route('term-dates.show', $date))->assertOk()
        ->assertSee('Arrive by 6pm')->assertSee('Concert blacks')->assertSee('Overture')
        ->assertDontSee(__('Emails sent'))->assertDontSee($bandmate->name);
    $this->actingAs(make_user(UserRole::Moderator))->get(route('term-dates.show', $date))->assertOk()
        ->assertSee(__('Emails sent'))->assertSee($bandmate->name)->assertSee('Concert blacks');

    $date->term->update(['hidden' => true]);
    $this->actingAs($member)->get(route('term-dates.show', $date))->assertForbidden();
});

test('poll date headings link to the date\'s page once the flag is on', function () {
    $ensemble = Ensemble::factory()->create();
    join_ensemble(make_user(UserRole::Member), $ensemble);
    $term = Term::factory()->create();
    $date = make_term_date($term);
    $poll = route('attendance.poll.show', ['ensemble' => $ensemble, 'term' => $term]);

    $this->actingAs(make_user(UserRole::Admin))->get($poll)->assertOk()->assertDontSee(route('term-dates.show', $date));

    enable_concert_details();

    $this->actingAs(make_user(UserRole::Admin))->get($poll)->assertOk()->assertSee(route('term-dates.show', $date));
});
