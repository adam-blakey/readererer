<?php

use App\Features\ImageScaling;
use App\Models\Ensemble;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Pennant\Feature;

beforeEach(function () {
    Storage::fake('public');
});

function enable_image_scaling(bool $enabled = true): void
{
    config(['app.readererer_feature_flags' => [ImageScaling::class => ['enabled' => $enabled]]]);
    Feature::flushCache();
}

function stored_image_size(Ensemble $ensemble): array
{
    return array_slice(getimagesizefromstring(Storage::disk('public')->get($ensemble->image)), 0, 2);
}

test('the default upload limit is 10 MB', function () {
    expect(config('app.readererer_image_max_kilobytes'))->toBe(10240);
});

test('a large image is scaled down when the flag is on', function () {
    enable_image_scaling();
    $ensemble = Ensemble::factory()->create(['image' => null]);

    $ensemble->applyImageInput(UploadedFile::fake()->image('big.png', 3200, 1600));

    expect(stored_image_size($ensemble))->toBe([1600, 800]);
});

test('a large image is stored unchanged when the flag is off', function () {
    enable_image_scaling(false);
    $ensemble = Ensemble::factory()->create(['image' => null]);

    $ensemble->applyImageInput(UploadedFile::fake()->image('big.png', 3200, 1600));

    expect(stored_image_size($ensemble))->toBe([3200, 1600]);
});

test('a small image is not resized and a replaced scaled image is deleted', function () {
    enable_image_scaling();
    $ensemble = Ensemble::factory()->create(['image' => null]);
    $ensemble->applyImageInput(UploadedFile::fake()->image('small.png', 400, 300));
    $ensemble->save();
    $old = $ensemble->image;

    expect(stored_image_size($ensemble))->toBe([400, 300]);

    $ensemble->applyImageInput(UploadedFile::fake()->image('big.png', 2000, 3000));
    $ensemble->save();

    Storage::disk('public')->assertMissing($old);
    expect(stored_image_size($ensemble))->toBe([1067, 1600]);
});
