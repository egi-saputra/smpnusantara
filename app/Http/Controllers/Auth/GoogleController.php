<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;

class GoogleController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if (!$user) {
            // USER BARU → default role siswa
            $user = User::create([
                'name'      => $googleUser->getName(),
                'email'     => $googleUser->getEmail(),
                'password'  => null,
                'google_id' => $googleUser->getId(),
                'avatar'    => $googleUser->getAvatar(),
                'role'      => 'user', // ✅ DEFAULT ROLE
            ]);
        } else {
            // 🔁 USER LAMA → JANGAN UBAH ROLE
            $user->update([
                'avatar' => $googleUser->getAvatar(),
            ]);
        }

        Auth::login($user, true);

        $redirectUrl = match ($user->role) {
            'admin'   => '/admin/dashboard',
            'proktor' => '/proktor/dashboard',
            'guru'    => '/guru/dashboard',
            'siswa'   => '/siswa/dashboard',
            'user'   => '/user/dashboard',
            default   => '/user/dashboard',
        };

        return redirect()->intended($redirectUrl);
    }
}
