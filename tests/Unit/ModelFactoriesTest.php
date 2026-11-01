<?php

use Illuminate\Database\Eloquent\Factories\HasFactory;

test('every factory creates its model', function (string $factory) {
    $model = $factory::new()->create();

    expect($model)->toBeInstanceOf($factory::new()->modelName());
    expect($model->exists)->toBeTrue();
})->with('factories');

test('every model with the HasFactory trait has a factory', function (string $model) {
    expect($model::factory()->create()->exists)->toBeTrue();
})->with('models with factories');

dataset('factories', function () {
    return collect(glob(dirname(__DIR__, 2).'/database/factories/*Factory.php'))
        ->map(fn (string $path) => 'Database\\Factories\\'.basename($path, '.php'))
        ->all();
});

dataset('models with factories', function () {
    return collect(glob(dirname(__DIR__, 2).'/app/Models/*.php'))
        ->map(fn (string $path) => 'App\\Models\\'.basename($path, '.php'))
        ->filter(fn (string $model) => in_array(HasFactory::class, class_uses_recursive($model), true))
        ->values()
        ->all();
});
