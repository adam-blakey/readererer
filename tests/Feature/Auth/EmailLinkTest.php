<?php

use App\Enums\EmailLinkType;
use App\Models\User;
use App\Notifications\EmailLink;

test('each email link type links to its own page', function (EmailLinkType $type, string $route, string $subject) {
    $user = User::factory()->create();

    $mail = (new EmailLink($type, 'a-token'))->toMail($user);

    expect($mail->actionUrl)->toBe(route($route, ['token' => 'a-token', 'email' => $user->email]))
        ->and($mail->subject)->toBe(config('app.name').': '.$subject);
})->with([
    'reset password' => [EmailLinkType::ResetPassword, 'password.reset', 'Reset Password Request'],
    'login link' => [EmailLinkType::LoginLink, 'login-link.show', 'Your login link'],
]);

test('the email button shows the link type\'s own wording', function () {
    $user = User::factory()->create();

    $html = (string) (new EmailLink(EmailLinkType::LoginLink, 'a-token'))->toMail($user)->render();

    expect($html)->toContain('>Log in</span>')->not->toContain('Reset password');
});
