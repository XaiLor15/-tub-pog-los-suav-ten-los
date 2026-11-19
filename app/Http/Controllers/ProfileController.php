<?php

namespace App\Http\Controllers;

use App\Services\MediaService;
use App\Support\Rules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Profile (/profile): account, password, appearance (theme + font scale). Available to pending users. */
class ProfileController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function show(Request $request): View
    {
        return view('profile.show', ['user' => $request->user()->load('member.father')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $v = $request->validate([
            'display_name' => ['required', 'string', 'max:100'],
            'avatar' => Rules::image(),
        ], ['display_name.required' => 'ກະລຸນາໃສ່ຊື່ທີ່ສະແດງ', 'avatar.max' => 'ຮູບຕ້ອງບໍ່ເກີນ 10 MB', 'avatar.mimetypes' => 'ຮອງຮັບສະເພາະ JPG, PNG ຫຼື WebP']);
        $user->display_name = trim($v['display_name']);
        if ($request->hasFile('avatar')) {
            $old = $user->avatar_path;
            $user->avatar_path = $this->media->storeImage($request->file('avatar'), 'avatars')['path'];
            $this->media->delete($old);
        }
        $user->save();

        return back()->with('success', 'ບັນທຶກສຳເລັດ');
    }

    public function password(Request $request): RedirectResponse
    {
        $user = $request->user();
        $v = $request->validate([
            'current_password' => [$user->password ? 'required' : 'nullable', 'string'],
            'password' => Rules::password(),
        ], [...Rules::messages(), 'current_password.required' => 'ກະລຸນາໃສ່ລະຫັດຜ່ານປັດຈຸບັນ']);

        if ($user->password && ! Hash::check($v['current_password'], $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'ລະຫັດຜ່ານປັດຈຸບັນບໍ່ຖືກຕ້ອງ']);
        }
        $user->password = $v['password'];
        $user->save();
        // Sign out other devices.
        DB::table('sessions')->where('user_id', $user->id)->where('id', '!=', $request->session()->getId())->delete();

        return back()->with('success', 'ປ່ຽນລະຫັດຜ່ານສຳເລັດ');
    }

    public function preferences(Request $request): JsonResponse
    {
        $v = $request->validate([
            'theme' => ['nullable', Rule::in(['light', 'dark', 'system'])],
            'font_scale' => ['nullable', 'integer', Rule::in([100, 112, 125])],
        ]);
        $request->user()->forceFill(array_filter($v, fn ($x) => $x !== null))->save();

        return response()->json(['ok' => true]);
    }
}
