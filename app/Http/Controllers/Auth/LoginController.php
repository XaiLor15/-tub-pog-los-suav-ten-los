<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const LOCK_MINUTES = 15;

    public function show(Request $request): View
    {
        return view('auth.login', ['tab' => 'login', 'redirect' => self::safeRedirect($request->query('redirect'))]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'string', 'max:200'],
        ], ['email.required' => 'ກະລຸນາໃສ່ອີເມວ', 'email.email' => 'ອີເມວບໍ່ຖືກຕ້ອງ', 'password.required' => 'ກະລຸນາໃສ່ລະຫັດຜ່ານ']);

        $user = User::where('email', strtolower(trim($data['email'])))->first();

        if ($user?->locked_until?->isFuture()) {
            throw ValidationException::withMessages(['email' => 'ບັນຊີຖືກລັອກຊົ່ວຄາວ ເນື່ອງຈາກໃສ່ລະຫັດຜິດຫຼາຍຄັ້ງ. ລອງໃໝ່ຫຼັງ '.$user->locked_until->format('H:i')]);
        }

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            if ($user) {
                $user->failed_login_count++;
                if ($user->failed_login_count >= self::MAX_ATTEMPTS) {
                    $user->locked_until = now()->addMinutes(self::LOCK_MINUTES);
                    $user->failed_login_count = 0;
                }
                $user->saveQuietly();
            }
            throw ValidationException::withMessages(['email' => 'ອີເມວ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ']);
        }

        if (! $user->isActive()) {
            throw ValidationException::withMessages(['email' => 'ບັນຊີນີ້ຖືກປິດການໃຊ້ງານ ຫຼື ຖືກປະຕິເສດ. ກະລຸນາຕິດຕໍ່ຜູ້ດູແລລະບົບ.']);
        }

        self::completeLogin($request, $user, $request->boolean('remember'));
        $target = self::safeRedirect($request->input('redirect'));

        return $target ? redirect($target) : redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'ອອກຈາກລະບົບແລ້ວ');
    }

    public static function completeLogin(Request $request, User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
        $request->session()->regenerate();
        $user->forceFill([
            'failed_login_count' => 0, 'locked_until' => null,
            'last_login_at' => now(), 'last_login_ip' => $request->ip(),
        ])->saveQuietly();
    }

    /** Only internal paths (prevents open redirects). */
    public static function safeRedirect(?string $path): ?string
    {
        if (! $path || ! str_starts_with($path, '/') || str_starts_with($path, '//') || str_contains($path, '\\')) {
            return null;
        }

        return $path;
    }
}
