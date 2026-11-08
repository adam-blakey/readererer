<?php

use App\Enums\AttendanceStatus;
use App\Enums\EmailStatus;
use App\Enums\RegisterStatus;
use App\Enums\UserRole;
use App\Models\Attendance;
use App\Models\EmailLog;
use App\Models\Ensemble;
use App\Models\RegisterEntry;
use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Mail::fake();
    Storage::fake('public');
});

function erase(User $admin, User $user, ?string $name = null)
{
    return test()->actingAs($admin)->delete(route('users.erase', $user), ['confirm_name' => $name ?? $user->name]);
}

test('erasing a user scrubs their personal data but keeps their attendance and register rows', function () {
    $admin = make_user(UserRole::Admin);
    $ensemble = Ensemble::factory()->create();
    $setupGroup = SetupGroup::factory()->create();
    $image = UploadedFile::fake()->image('face.png')->store('images/users', 'public');
    $user = make_user(UserRole::Member, ['image' => $image, 'setup_group_id' => $setupGroup->id, 'must_change_password' => true]);
    $email = $user->email;
    $passwordHash = $user->password;

    join_ensemble($user, $ensemble, make_instrument_family());
    $ensemble->admins()->attach($user->id);
    $setupGroup->van_drivers()->attach($user->id, ['sort' => 1]);
    $termDate = make_term_date();
    DB::table('term_dates')->where('id', $termDate->id)->update(['van_driver_id' => $user->id]);

    DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $user->id, 'ip_address' => '1.2.3.4', 'payload' => '', 'last_activity' => time()]);
    DB::table('password_reset_tokens')->insert(['email' => $email, 'token' => 'x']);

    $attendance = Attendance::create(['user_id' => $user->id, 'edit_user_id' => $user->id, 'term_date_id' => $termDate->id, 'ensemble_id' => $ensemble->id, 'status' => AttendanceStatus::Attending, 'edit_ip' => '1.2.3.4']);
    $edited = Attendance::create(['user_id' => $admin->id, 'edit_user_id' => $user->id, 'term_date_id' => $termDate->id, 'ensemble_id' => $ensemble->id, 'status' => AttendanceStatus::Attending, 'edit_ip' => '5.6.7.8']);
    $entry = RegisterEntry::create(['user_id' => $user->id, 'term_date_id' => $termDate->id, 'ensemble_id' => $ensemble->id, 'status' => RegisterStatus::Present, 'notes' => 'Broke a leg']);

    $log = EmailLog::create(['mailable_class' => 'X', 'status' => EmailStatus::Failed]);
    $recipient = $log->recipients()->create(['user_id' => $user->id, 'name' => $user->name, 'email' => $email, 'status' => EmailStatus::Failed, 'error_message' => "Rejected {$email}"]);

    erase($admin, $user)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.index'))
        ->assertSessionHas('status');

    $user = User::withTrashed()->find($user->id);
    expect($user->trashed())->toBeTrue()
        ->and($user->name)->toBe("Former member #{$user->id}")
        ->and($user->email)->toBeNull()
        ->and($user->username)->toBe("erased-{$user->id}")
        ->and($user->password)->not->toBe($passwordHash)
        ->and(Hash::check('password', $user->password))->toBeFalse()
        ->and($user->remember_token)->toBeNull()
        ->and($user->email_verified_at)->toBeNull()
        ->and($user->must_change_password)->toBeFalse()
        ->and($user->image)->toBeNull()
        ->and($user->setup_group_id)->toBeNull()
        ->and($user->phone_number)->toBeNull()
        ->and($user->address_line1)->toBeNull()
        ->and($user->address_post_code)->toBeNull()
        ->and($user->emergency_contact_name)->toBeNull()
        ->and($user->emergency_contact_number)->toBeNull()
        ->and($user->date_of_birth)->toBeNull();

    Storage::disk('public')->assertMissing($image);

    expect($user->ensembles()->count())->toBe(0)
        ->and($ensemble->admins()->count())->toBe(0)
        ->and($setupGroup->van_drivers()->count())->toBe(0)
        ->and($termDate->fresh()->van_driver_id)->toBeNull()
        ->and(DB::table('sessions')->where('user_id', $user->id)->exists())->toBeFalse()
        ->and(DB::table('password_reset_tokens')->where('email', $email)->exists())->toBeFalse()
        ->and($attendance->fresh()->edit_ip)->toBe('')
        ->and($edited->fresh()->edit_ip)->toBe('')
        ->and($entry->fresh()->notes)->toBeNull()
        ->and($recipient->fresh()->only(['name', 'email', 'error_message']))->toBe(['name' => null, 'email' => null, 'error_message' => null]);

    $user->load('attendances');
    expect(member_status_totals([$user], $termDate, $ensemble)['attending'])->toBe(1)
        ->and(register_status_totals(RegisterEntry::where('term_date_id', $termDate->id)->get(), 1)['present'])->toBe(1);
});

test('a wrong confirmation name is rejected', function () {
    $user = make_user(UserRole::Member);

    erase(make_user(UserRole::Admin), $user, 'Someone Else')->assertSessionHasErrors('confirm_name');

    expect($user->fresh()->trashed())->toBeFalse()
        ->and($user->fresh()->email)->not->toBeNull();
});

test('only admins can erase, and not themselves', function () {
    $user = make_user(UserRole::Member);
    $admin = make_user(UserRole::Admin);

    erase(make_user(UserRole::Moderator), $user)->assertForbidden();
    erase($admin, $admin)->assertForbidden();

    $this->actingAs(make_user(UserRole::Moderator))->get(route('users.show', $user))->assertDontSee(__('Erase personal data'));
    $this->actingAs($admin)->get(route('users.show', $user))->assertSee(__('Erase personal data'));
});

test('a soft-deleted user can be shown and erased', function () {
    $admin = make_user(UserRole::Admin);
    $user = make_user(UserRole::Member);
    $user->delete();

    $this->actingAs($admin)->get(route('users.index', ['with_trashed' => 1]))
        ->assertSee(route('users.show', [$user, 'with_trashed' => 1]), false);

    $this->actingAs($admin)->get(route('users.show', [$user, 'with_trashed' => 1]))
        ->assertOk()
        ->assertSee(__('Erase personal data'))
        ->assertSee(route('users.restore', $user), false)
        ->assertDontSee(route('users.edit', $user), false);

    erase($admin, $user)->assertRedirect(route('users.index'));

    expect(User::withTrashed()->find($user->id)->email)->toBeNull();
});
