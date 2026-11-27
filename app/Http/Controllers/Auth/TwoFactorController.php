<?php

namespace App\Http\Controllers\Auth;

use App\Features\TwoFactorAuthentication;
use App\Http\Controllers\Controller;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Pennant\Feature;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    public function edit(Request $request, Google2FA $google2fa): View
    {
        $user = $request->user();
        // Members who turned it on can still turn it off once the flag is off.
        abort_unless($user->hasTwoFactor() || Feature::active(TwoFactorAuthentication::class), 404);
        $secret = null;
        $qr_code = null;

        if (! $user->hasTwoFactor()) {
            $secret = $request->session()->get('two_factor.setup_secret') ?? $google2fa->generateSecretKey(32);
            $request->session()->put('two_factor.setup_secret', $secret);
            $url = $google2fa->getQRCodeUrl(config('app.name'), $user->email ?: $user->username, $secret);
            $qr_code = (new Writer(new ImageRenderer(new RendererStyle(192, 1), new SvgImageBackEnd)))->writeString($url);
        }

        return view('auth.two-factor', [
            'page_name' => __('Two-factor authentication'),
            'enabled' => $user->hasTwoFactor(),
            'secret' => $secret,
            'qr_code' => $qr_code,
            'recovery_codes' => $request->session()->get('two_factor.recovery_codes'),
        ]);
    }

    public function store(Request $request, Google2FA $google2fa): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $secret = $request->session()->get('two_factor.setup_secret');

        if (! $secret || ! $google2fa->verifyKey($secret, preg_replace('/\s+/', '', $request->code))) {
            throw ValidationException::withMessages(['code' => __('The code is incorrect.')]);
        }

        $recovery_codes = collect(range(1, 8))->map(fn () => Str::lower(Str::random(5).'-'.Str::random(5)))->all();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recovery_codes,
        ])->save();

        $request->session()->forget('two_factor.setup_secret');
        Log::notice('Two-factor authentication turned on', ['user_id' => $request->user()->id]);

        return to_route('two-factor.edit')
            ->with('status', __('Two-factor authentication is on.'))
            ->with('two_factor.recovery_codes', $recovery_codes);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        if (! $request->user()->verifyTwoFactorCode($request->code)) {
            throw ValidationException::withMessages(['code' => __('The code is incorrect.')]);
        }

        $request->user()->forceFill(['two_factor_secret' => null, 'two_factor_recovery_codes' => null])->save();
        Log::notice('Two-factor authentication turned off', ['user_id' => $request->user()->id]);

        return to_route('two-factor.edit')->with('status', __('Two-factor authentication is off.'));
    }

    public function challenge(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('two_factor.pending')) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        return view('auth.two-factor-challenge', ['page_name' => __('Two-factor authentication')]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        if (! $request->user()->verifyTwoFactorCode($request->code)) {
            Log::warning('Two-factor code was incorrect', ['user_id' => $request->user()->id]);

            throw ValidationException::withMessages(['code' => __('The code is incorrect.')]);
        }

        $request->session()->forget('two_factor.pending');
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
