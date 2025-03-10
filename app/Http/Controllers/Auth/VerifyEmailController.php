<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Providers\AppServiceProvider;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

final class VerifyEmailController extends Controller
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        assert($user instanceof User, 'User must be an instance of '.User::class);

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route(AppServiceProvider::HOME, absolute: false).'?verified=1');
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return redirect()->intended(route(AppServiceProvider::HOME, absolute: false).'?verified=1');
    }
}
