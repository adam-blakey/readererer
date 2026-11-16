<?php

use App\Enums\AttendanceStatus;
use App\Enums\UserRole;
use App\Features\HiddenEnsemblesGetNoEmails;
use App\Features\TermDateOverview;
use App\Mail\AttendanceChangedMail;
use App\Mail\AttendanceListMail;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\TermDate;
use Illuminate\Support\Facades\Mail;
use Laravel\Pennant\Feature;

function enable_flags(string ...$flags): void
{
    config(['app.readererer_feature_flags' => array_fill_keys($flags, ['enabled' => true])]);
    Feature::flushCache();
}

function soon_term_date(Ensemble ...$concertEnsembles): TermDate
{
    $termDate = make_term_date(null, ...$concertEnsembles);
    $termDate->update(['start_datetime' => now()->addHours(2), 'end_datetime' => now()->addHours(4)]);

    return $termDate;
}

test('the home page leaves out hidden ensembles', function () {
    Ensemble::factory()->create(['name' => 'Shown Band']);
    Ensemble::factory()->hidden()->create(['name' => 'Hidden Band']);

    $this->get(route('home'))->assertSee('Shown Band')->assertDontSee('Hidden Band');
});

test('the dashboard leaves out a hidden ensemble the user belongs to', function () {
    $member = make_user(UserRole::Member);
    join_ensemble($member, Ensemble::factory()->create(['name' => 'Shown Band']));
    join_ensemble($member, Ensemble::factory()->hidden()->create(['name' => 'Hidden Band']));

    $this->actingAs($member)->get(route('dashboard'))
        ->assertViewHas('ensembles', fn ($ensembles) => $ensembles->pluck('name')->all() === ['Shown Band']);
});

test('the term date overview leaves out hidden ensembles, even for admins', function () {
    enable_flags(TermDateOverview::class);
    $shown = Ensemble::factory()->create(['name' => 'Shown Band']);
    $hidden = Ensemble::factory()->hidden()->create(['name' => 'Hidden Band']);

    $this->actingAs(make_user(UserRole::Admin))->get(route('term-dates.show', make_term_date(null, $shown, $hidden)))
        ->assertSee('Concert (Shown Band)')
        ->assertDontSee('Hidden Band');
});

test('a concert whose ensembles are all hidden is labelled just a concert', function () {
    $termDate = make_term_date(null, Ensemble::factory()->hidden()->create());

    expect($termDate->concert_label)->toBe('Concert')
        ->and($termDate->is_concert)->toBeTrue();
});

test('the registers page leaves out hidden ensembles', function () {
    Ensemble::factory()->create(['name' => 'Shown Band']);
    Ensemble::factory()->hidden()->create(['name' => 'Hidden Band']);
    make_term_date();

    $this->actingAs(make_user(UserRole::Admin))->get(route('attendance.register.index'))
        ->assertSee('Shown Band')
        ->assertDontSee('Hidden Band');
});

test('the term form offers hidden ensembles only where a date already plays in them', function () {
    Ensemble::factory()->hidden()->create(['name' => 'Hidden Band']);
    $playing = Ensemble::factory()->hidden()->create(['name' => 'Playing Band']);
    $termDate = make_term_date(null, $playing);
    $admin = make_user(UserRole::Admin);

    $this->actingAs($admin)->get(route('terms.create'))->assertDontSee('Hidden Band')->assertDontSee('Playing Band');
    $this->actingAs($admin)->get(route('terms.edit', $termDate->term))->assertDontSee('Hidden Band')->assertSee('Playing Band');
});

test('an admin can hide an ensemble', function () {
    $ensemble = Ensemble::factory()->create();

    $this->actingAs(make_user(UserRole::Admin))->put(route('ensembles.update', $ensemble), [
        'name' => $ensemble->name,
        'slug' => $ensemble->slug,
        'show' => '0',
    ])->assertRedirect();

    expect($ensemble->fresh()->show)->toBeFalse();
});

describe('emails', function () {
    beforeEach(function () {
        Mail::fake();
        config(['app.readererer_reminder_minutes' => 24 * 60, 'app.readererer_setup_groups' => true]);

        $this->shown = Ensemble::factory()->create();
        $this->hidden = Ensemble::factory()->hidden()->create();
        $this->shownAdmin = make_user(UserRole::Member);
        $this->hiddenAdmin = make_user(UserRole::Member);
        $this->shown->admins()->attach($this->shownAdmin);
        $this->hidden->admins()->attach($this->hiddenAdmin);
    });

    test('a rehearsal\'s attendance list leaves out hidden ensembles but keeps members who are also in a shown one', function () {
        enable_flags(HiddenEnsemblesGetNoEmails::class);
        $both = make_user(UserRole::Member, ['first_name' => 'Bea', 'last_name' => 'Both']);
        $hiddenOnly = make_user(UserRole::Member, ['first_name' => 'Hal', 'last_name' => 'Hidden']);
        join_ensemble($both, $this->shown);
        join_ensemble($both, $this->hidden);
        join_ensemble($hiddenOnly, $this->hidden);
        soon_term_date();

        $this->artisan('reminders:send')->assertSuccessful();

        Mail::assertSentCount(1);
        Mail::assertSent(AttendanceListMail::class, function (AttendanceListMail $mail) {
            $html = $mail->render();

            return $mail->hasTo($this->shownAdmin->email) && str_contains($html, 'Bea Both') && ! str_contains($html, 'Hal Hidden');
        });
    });

    test('nothing is sent for a concert whose ensembles are all hidden', function () {
        enable_flags(HiddenEnsemblesGetNoEmails::class);
        $setupGroup = SetupGroup::factory()->create();
        make_user(UserRole::Member, ['setup_group_id' => $setupGroup->id]);
        $termDate = soon_term_date($this->hidden);
        $termDate->update(['setup_group_id' => $setupGroup->id]);

        $this->artisan('reminders:send')->assertSuccessful();
        $this->actingAs(make_user(UserRole::Admin))->post(route('term-dates.send-setup-reminder', $termDate))
            ->assertSessionHas('status', 'This date is only for hidden ensembles, so no emails are sent for it.');

        Mail::assertNothingSent();
        expect(EmailLog::count())->toBe(0);
    });

    test('a changed reply in a hidden ensemble\'s poll sends no follow-up', function () {
        enable_flags(HiddenEnsemblesGetNoEmails::class);
        config(['app.readererer_after_reminders' => 'follow_up']);
        $member = make_user(UserRole::Member);
        join_ensemble($member, $this->hidden);
        $termDate = soon_term_date();

        $this->actingAs($member)->post(
            route('attendance.poll.store', ['ensemble' => $this->hidden->slug, 'term' => $termDate->term->slug]),
            ["status-t{$termDate->id}m{$member->id}" => AttendanceStatus::Attending->value],
        );

        Mail::assertNotSent(AttendanceChangedMail::class);
    });

    test('hidden ensembles still get emails while the flag is off', function () {
        soon_term_date($this->hidden);

        $this->artisan('reminders:send')->assertSuccessful();

        Mail::assertSent(AttendanceListMail::class, fn ($mail) => $mail->hasTo($this->hiddenAdmin->email));
    });
});
