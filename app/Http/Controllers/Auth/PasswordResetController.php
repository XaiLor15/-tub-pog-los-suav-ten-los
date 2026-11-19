<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(Request $request): RedirectResponse
    {
        $email = strtolower($request->validate(['email' => ['required', 'email:rfc', 'max:150']], ['email.required' => 'ກະລຸນາໃສ່ອີເມວ'])['email']);
        $status = Password::sendResetLink(['email' => $email]);
        if ($status === Password::RESET_THROTTLED) {
            throw ValidationException::withMessages(['email' => 'ທ່ານຫາກໍ່ຂໍລິ້ງໄປ — ກະລຸນາລໍຖ້າ 1 ນາທີ ແລ້ວລອງໃໝ່.']);
        }

        // Same message for every case so the form does not reveal which emails exist.
        return back()->with('success', 'ຖ້າອີເມວນີ້ລົງທະບຽນໄວ້, ພວກເຮົາໄດ້ສົ່ງລິ້ງຕັ້ງລະຫັດຜ່ານໃໝ່ໄປແລ້ວ. ກະລຸນາກວດອີເມວ (ລວມທັງ Spam).');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => (string) $request->query('email', '')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc', 'max:150'],
            'password' => Rules::password(),
        ], Rules::messages());

        $status = Password::reset(
            ['email' => strtolower($data['email']), 'password' => $data['password'], 'token' => $data['token']],
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password, 'failed_login_count' => 0, 'locked_until' => null,
                    'remember_token' => Str::random(60),
                ])->save();
                DB::table('sessions')->where('user_id', $user->id)->delete();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'ລິ້ງນີ້ບໍ່ຖືກຕ້ອງ ຫຼື ໝົດອາຍຸແລ້ວ. ກະລຸນາຂໍລິ້ງໃໝ່.']);
        }

        return redirect()->route('login')->with('success', 'ຕັ້ງລະຫັດຜ່ານໃໝ່ສຳເລັດ — ກະລຸນາເຂົ້າສູ່ລະບົບ.');
    }
}
