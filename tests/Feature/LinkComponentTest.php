<?php

use App\Enums\UserRole;
use Illuminate\Support\Facades\Blade;

test('a link does not render the models passed as its route parameters', function (string $attribute, UserRole $viewer) {
    $target = make_user(UserRole::Member, ['email' => 'private@example.com']);
    $this->actingAs(make_user($viewer));

    $html = Blade::render('<x-a class="btn" :route="\'users.show\'" :'.$attribute.'="$target">Name</x-a>', ['target' => $target]);

    expect($html)->not->toContain('private@example.com')
        ->toContain('class="btn')
        ->toContain($viewer === UserRole::Admin ? 'href="'.route('users.show', $target).'"' : 'aria-disabled="true"');
})->with(['user', 'model'])->with([UserRole::Admin, UserRole::Member]);
