<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Rules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Self-registration (handle_new_user): every new account starts as `pending`. */
class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.login', ['tab' => 'register', 'redirect' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')],
            'password' => Rules::password(),
        ], [
            'display_name.required' => 'ກະລຸນາໃສ່ຊື່',
            'email.unique' => 'ອີເມວນີ້ລົງທະບຽນແລ້ວ',
            'email.required' => 'ກະລຸນາໃສ່ອີເມວ',
            ...Rules::messages(),
        ]);

        $user = new User([
            'display_name' => trim($data['display_name']),
            'email' => strtolower(trim($data['email'])),
            'password' => $data['password'],
        ]);
        $user->role = 'pending';
        $user->auth_provider = 'email';
        $user->save();

        LoginController::completeLogin($request, $user);

        return redirect()->route('profile')->with('success', 'ລົງທະບຽນສຳເລັດ — ກຳລັງລໍຖ້າຜູ້ດູແລລະບົບອະນຸມັດ');
    }
}
