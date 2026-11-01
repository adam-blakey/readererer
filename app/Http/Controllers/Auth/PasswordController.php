<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(Request $request): View
    {
        return view('auth.change-password', [
            'page_name' => __('Change password'),
            'must_change_password' => $request->user()->must_change_password,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $was_forced = $user->must_change_password;

        // A forced change follows straight on from logging in with the temporary password.
        $validated = $request->validate(($was_forced ? [] : ['current_password' => ['required', 'current_password']]) + [
            'password' => ['required', Password::defaults(), 'confirmed', function (string $attribute, mixed $value, Closure $fail) use ($user) {
                if (Hash::check($value, $user->password)) {
                    $fail(__('Your new password must be different from your current one.'));
                }
            }],
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ])->save();

        $request->session()->regenerate();

        if ($was_forced) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return back()->with('status', __('Your password has been changed.'));
    }
}
