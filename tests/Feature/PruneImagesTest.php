<?php

use App\Enums\UserRole;
use App\Models\Ensemble;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

/**
 * Put a file in a model's image directory and backdate it past the grace
 * period, since fakes are always written "now".
 */
function aged_image(string $directory, string $name, int $hours_old = 48): string
{
    $path = $directory.'/'.$name;
    Storage::disk('public')->put($path, 'fake image');
    touch(Storage::disk('public')->path($path), now()->subHours($hours_old)->getTimestamp());

    return $path;
}

test('an unreferenced image is deleted', function () {
    $orphan = aged_image(Ensemble::imageDirectory(), 'orphan.png');

    $this->artisan('images:prune')->assertSuccessful();

    Storage::disk('public')->assertMissing($orphan);
});

test('an image a record still points at is kept', function () {
    $ensemble = Ensemble::factory()->create();
    $ensemble->image = aged_image(Ensemble::imageDirectory(), 'in-use.png');
    $ensemble->save();

    $this->artisan('images:prune')->assertSuccessful();

    Storage::disk('public')->assertExists($ensemble->image);
});

test('an image belonging to a soft-deleted record is kept so a restore brings it back', function () {
    $ensemble = Ensemble::factory()->create();
    $ensemble->image = aged_image(Ensemble::imageDirectory(), 'trashed.png');
    $ensemble->save();
    $ensemble->delete();

    $this->artisan('images:prune')->assertSuccessful();

    Storage::disk('public')->assertExists($ensemble->image);
});

test('a freshly uploaded image is inside the grace period', function () {
    $ensemble = Ensemble::factory()->create(['name' => 'Strings', 'slug' => 'strings', 'image' => null]);
    $ensemble->storeImage(UploadedFile::fake()->image('just-uploaded.png'));
    $path = $ensemble->image;

    // The row is not saved, so nothing references the file; only its age saves it.
    $this->artisan('images:prune')->assertSuccessful();

    Storage::disk('public')->assertExists($path);
});

test('a shorter grace period sweeps a recent orphan', function () {
    $ensemble = Ensemble::factory()->create(['image' => null]);
    $ensemble->storeImage(UploadedFile::fake()->image('just-uploaded.png'));

    $this->artisan('images:prune', ['--hours' => 0])->assertSuccessful();

    Storage::disk('public')->assertMissing($ensemble->image);
});

test('a dry run reports without deleting', function () {
    $orphan = aged_image(Ensemble::imageDirectory(), 'orphan.png');

    $this->artisan('images:prune', ['--dry-run' => true])->assertSuccessful();

    Storage::disk('public')->assertExists($orphan);
});

test('user images are pruned too', function () {
    $orphan = aged_image(User::imageDirectory(), 'orphan.png');
    $keeper = make_user(UserRole::Member);
    $keeper->image = aged_image(User::imageDirectory(), 'keeper.png');
    $keeper->save();

    $this->artisan('images:prune')->assertSuccessful();

    Storage::disk('public')->assertMissing($orphan);
    Storage::disk('public')->assertExists($keeper->image);
});
