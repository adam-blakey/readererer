<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\SetupGroup;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

function update_user_payload(User $user, array $overrides = []): array
{
    $setup_group = SetupGroup::firstOrCreate(['name' => 'Group A'], ['week' => 1, 'color' => 'blue']);

    return array_merge([
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'role' => $user->role->value,
        'setup_group' => $setup_group->id,
    ], $overrides);
}

test('an admin can upload an ensemble image', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'slug' => 'strings', 'image' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), [
            'name' => 'Strings',
            'slug' => 'strings',
            'image' => UploadedFile::fake()->image('band.png'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('ensembles.show', $ensemble));

    $ensemble->refresh();
    expect($ensemble->image)->toStartWith('images/ensembles/');
    Storage::disk('public')->assertExists($ensemble->image);
    expect($ensemble->image_url)->toBe(Storage::disk('public')->url($ensemble->image));
});

test('an admin can upload a profile picture for a user', function () {
    $user = make_user(UserRole::Member, ['image' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $user), update_user_payload($user, [
            'image' => UploadedFile::fake()->image('face.jpg'),
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('users.show', $user));

    $user->refresh();
    expect($user->image)->toStartWith('images/users/');
    Storage::disk('public')->assertExists($user->image);
});

test('uploading a new image leaves the old file in place for the prune command', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'slug' => 'strings']);
    $ensemble->storeImage(UploadedFile::fake()->image('old.png'));
    $ensemble->save();
    $old = $ensemble->image;

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), [
            'name' => 'Strings',
            'slug' => 'strings',
            'image' => UploadedFile::fake()->image('new.png'),
        ])
        ->assertSessionHasNoErrors();

    $ensemble->refresh();
    expect($ensemble->image)->not->toBe($old);
    Storage::disk('public')->assertExists($old);
});

test('an image can be removed from an ensemble', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'slug' => 'strings']);
    $ensemble->storeImage(UploadedFile::fake()->image('old.png'));
    $ensemble->save();

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), [
            'name' => 'Strings',
            'slug' => 'strings',
            'remove_image' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect($ensemble->fresh()->image)->toBeNull();
});

test('an image can be removed from a user', function () {
    $user = make_user(UserRole::Member);
    $user->storeImage(UploadedFile::fake()->image('face.png'));
    $user->save();

    $this->actingAs(make_user(UserRole::Admin))
        ->patch(route('users.update', $user), update_user_payload($user, ['remove_image' => '1']))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->image)->toBeNull();
});

test('a new upload wins over the remove checkbox', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'slug' => 'strings', 'image' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), [
            'name' => 'Strings',
            'slug' => 'strings',
            'remove_image' => '1',
            'image' => UploadedFile::fake()->image('band.png'),
        ])
        ->assertSessionHasNoErrors();

    expect($ensemble->fresh()->image)->toStartWith('images/ensembles/');
});

test('a non-image upload is rejected', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'slug' => 'strings', 'image' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), [
            'name' => 'Strings',
            'slug' => 'strings',
            'image' => UploadedFile::fake()->create('setlist.pdf', 16, 'application/pdf'),
        ])
        ->assertSessionHasErrors('image');

    expect($ensemble->fresh()->image)->toBeNull();
    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

test('an oversized image is rejected', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'slug' => 'strings', 'image' => null]);

    $this->actingAs(make_user(UserRole::Admin))
        ->put(route('ensembles.update', $ensemble), [
            'name' => 'Strings',
            'slug' => 'strings',
            'image' => UploadedFile::fake()->image('huge.png')->size(4096),
        ])
        ->assertSessionHasErrors('image');

    expect($ensemble->fresh()->image)->toBeNull();
});

test('an external image URL is served unchanged', function () {
    $ensemble = Ensemble::factory()->create(['image' => 'https://placehold.co/640x480']);

    expect($ensemble->hasUploadedImage())->toBeFalse();
    expect($ensemble->image_url)->toBe('https://placehold.co/640x480');
});

test('a model without an image has no image URL', function () {
    $ensemble = Ensemble::factory()->create(['image' => null]);

    expect($ensemble->image_url)->toBeNull();
    expect($ensemble->hasUploadedImage())->toBeFalse();
});

test('the edit forms render the image upload field', function () {
    $admin = make_user(UserRole::Admin);
    $ensemble = Ensemble::factory()->create();

    $this->actingAs($admin)
        ->get(route('ensembles.edit', $ensemble))
        ->assertOk()
        ->assertSee('name="image"', false)
        ->assertSee('enctype="multipart/form-data"', false);

    $this->actingAs($admin)
        ->get(route('users.edit', make_user(UserRole::Member)))
        ->assertOk()
        ->assertSee('name="image"', false)
        ->assertSee('enctype="multipart/form-data"', false);
});
