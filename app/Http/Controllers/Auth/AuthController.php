<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        if (!Auth::user()->canManageSchools()) {
            abort(403, 'Anda tidak memiliki hak akses untuk mereset kata sandi akun ini.');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update([
            'password' => $validated['password']
        ]);

        return back()->with('success', "Kata sandi untuk pengguna {$user->username} berhasil diperbarui.");
    }
}
