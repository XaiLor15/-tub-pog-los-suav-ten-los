<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/** Optional Google OAuth. New Google accounts are created as `pending`, like email sign-ups. */
class GoogleController extends Controller
{
    public static function enabled(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function redirect(): RedirectResponse
    {
        abort_unless(self::enabled(), 404);

        return Socialite::driver('google')->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        abort_unless(self::enabled(), 404);
        try {
            $g = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')->withErrors(['email' => 'ເຂົ້າສູ່ລະບົບດ້ວຍ Google ບໍ່ສຳເລັດ — ກະລຸນາລອງໃໝ່']);
        }
        $email = strtolower((string) $g->getEmail());
        if ($email === '') {
            return redirect()->route('login')->withErrors(['email' => 'ບັນຊີ Google ບໍ່ມີອີເມວ']);
        }

        $user = User::where('google_id', $g->getId())->first() ?? User::where('email', $email)->first();
        if (! $user) {
            $user = new User(['display_name' => $g->getName() ?: strtok($email, '@'), 'email' => $email]);
            $user->role = 'pending';
            $user->auth_provider = 'google';
        }
        $user->google_id ??= $g->getId();
        $user->save();

        if (! $user->isActive()) {
            return redirect()->route('login')->withErrors(['email' => 'ບັນຊີນີ້ຖືກປິດການໃຊ້ງານ ຫຼື ຖືກປະຕິເສດ.']);
        }
        LoginController::completeLogin($request, $user, true);

        return redirect()->intended(route($user->isPending() ? 'profile' : 'dashboard'));
    }
}
