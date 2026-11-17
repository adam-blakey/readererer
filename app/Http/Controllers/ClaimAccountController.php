<?php

namespace App\Http\Controllers;

use App\Enums\EmailLinkType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ClaimAccountController extends Controller
{
    public function index(Request $request): View
    {
        return view('claim-account.index', [
            'page_name' => __('Set up your own account'),
            'users' => User::whereHas('ensembles', fn ($query) => $query->whereKey($request->user()->ensembles->modelKeys()))
                ->where('role', '!=', UserRole::Ensemble)
                ->autosort()
                ->get(),
        ]);
    }

    public function store(User $user): RedirectResponse
    {
        $status = EmailLinkType::LoginLink->send($user->email);

        if ($status !== Password::RESET_LINK_SENT) {
            Log::warning('Claim account login link not sent', ['user_id' => $user->id, 'status' => $status]);

            return back()->withErrors(['claim' => __('The login link could not be sent. Please wait a minute and try again.')]);
        }

        $user->forceFill(['must_change_password' => true])->save();
        Log::info('Claim account login link sent', ['user_id' => $user->id]);

        return back()->with('status', __('A login link has been emailed to :name. Open it to log in and choose a password.', ['name' => $user->name]));
    }
}
