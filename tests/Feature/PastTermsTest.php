<?php

use App\Enums\UserRole;
use App\Models\Term;
use App\Models\TermDate;

function term_with_dates(string $name, array $starts): Term
{
    $term = Term::factory()->create(['name' => $name, 'slug' => str($name)->slug()]);
    foreach ($starts as $start) {
        TermDate::factory()->create(['term_id' => $term->id, 'start_datetime' => $start, 'end_datetime' => $start->copy()->addHours(2)]);
    }

    return $term;
}

test('the current scope hides only terms whose dates have all passed', function () {
    $future = term_with_dates('Future', [now()->addWeek()]);
    $past = term_with_dates('Past', [now()->subMonth(), now()->subWeek()]);
    $empty = term_with_dates('Empty', []);
    $mixed = term_with_dates('Mixed', [now()->subWeek(), now()->addWeek()]);
    $inProgress = term_with_dates('In progress', [now()->subHour()]);
    $deletedFuture = term_with_dates('Deleted future', [now()->subWeek(), now()->addWeek()]);
    $deletedFuture->term_dates->last()->delete();

    expect(Term::current()->pluck('id')->sort()->values()->all())
        ->toBe(collect([$future, $empty, $mixed, $inProgress])->pluck('id')->sort()->values()->all());
});

test('the terms index hides past terms unless asked to show them', function () {
    term_with_dates('Autumn Gone', [now()->subWeek()]);
    term_with_dates('Winter Ahead', [now()->addWeek()]);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('terms.index'))
        ->assertOk()
        ->assertSee(__('Show past terms'))
        ->assertSee('Winter Ahead')
        ->assertDontSee('Autumn Gone');

    $this->actingAs($admin)->get(route('terms.index', ['with_past' => 1]))
        ->assertOk()
        ->assertSee('Winter Ahead')
        ->assertSee('Autumn Gone');
});

test('a past term can still be opened directly', function () {
    $term = term_with_dates('Autumn Gone', [now()->subWeek()]);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('terms.show', $term))->assertOk()->assertSee('Autumn Gone');
    $this->actingAs($admin)->get(route('terms.edit', $term))->assertOk();
});
