<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\Term;
use App\Models\TermDate;

function hidden_term_setup(): array
{
    $ensemble = Ensemble::factory()->create();
    $term = Term::factory()->create(['name' => 'Draft season', 'slug' => 'draft-season', 'hidden' => true]);
    TermDate::forceCreate(['term_id' => $term->id, 'start_datetime' => now()->addWeek(), 'end_datetime' => now()->addWeek()->addHours(2)]);
    $member = make_user(UserRole::Member);
    join_ensemble($member, $ensemble);

    return [$ensemble, $term, $member];
}

test('members cannot see or reply to the poll of a hidden term', function () {
    [$ensemble, $term, $member] = hidden_term_setup();
    $poll = ['ensemble' => $ensemble->slug, 'term' => $term->slug];

    $this->actingAs($member)->get(route('home'))->assertOk()->assertDontSee('Draft season');
    $this->get(route('ensembles.show', $ensemble))->assertDontSee('Draft season');
    $this->get(route('attendance.poll.show', $poll))->assertNotFound();
    $this->post(route('attendance.poll.store', $poll))->assertNotFound();
    $this->get(route('attendance.poll.history', $poll + ['user' => $member]))->assertNotFound();
});

test('moderators see a hidden term marked as hidden and can open its poll', function () {
    [$ensemble, $term] = hidden_term_setup();

    $this->actingAs(make_user(UserRole::Moderator))->get(route('home'))
        ->assertOk()
        ->assertSeeInOrder(['Draft season', 'Hidden']);
    $this->get(route('attendance.poll.show', ['ensemble' => $ensemble->slug, 'term' => $term->slug]))->assertOk();
});

test('an admin can hide and unhide a term', function () {
    $term = Term::factory()->create();
    $this->actingAs(make_user(UserRole::Admin));

    $this->patch(route('terms.update', $term), ['name' => $term->name, 'slug' => $term->slug, 'hidden' => '1'])->assertSessionHasNoErrors();
    expect($term->fresh()->hidden)->toBeTrue();

    $this->patch(route('terms.update', $term), ['name' => $term->name, 'slug' => $term->slug, 'hidden' => '0']);
    expect($term->fresh()->hidden)->toBeFalse();
});
