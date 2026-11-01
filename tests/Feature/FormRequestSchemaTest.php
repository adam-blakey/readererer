<?php

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

test('form requests require every fillable attribute whose column is NOT NULL', function () {
    $admin = make_user(UserRole::Admin);
    request()->setUserResolver(fn () => $admin);

    foreach (glob(app_path('Http/Requests/*Request.php')) as $file) {
        $request = 'App\\Http\\Requests\\'.basename($file, '.php');
        $model = new ('App\\Models\\'.preg_replace('/^(Store|Update)|Request$/', '', class_basename($request)));
        $rules = $request::createFrom(request())->rules();
        $columns = collect(Schema::getColumns($model->getTable()))->keyBy('name');

        foreach (array_intersect($model->getFillable(), array_keys($rules)) as $attribute) {
            $column = method_exists($model, $attribute) && $model->$attribute() instanceof BelongsTo
                ? $model->$attribute()->getForeignKeyName()
                : $attribute;

            if (isset($columns[$column]) && ! $columns[$column]['nullable']) {
                expect(rules_require_attribute($rules, $attribute))
                    ->toBeTrue("{$request} lets {$attribute} be empty, but {$column} is NOT NULL");
            }
        }
    }
});
