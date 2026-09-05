<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);
        $loginInput = trim((string) $credentials['username']);
        $password = (string) $credentials['password'];

        // Find user by username, display name, or email
        $user = User::query()
            ->where('is_active', true)
            ->where(static function ($q) use ($loginInput): void {
                $q->where('username', $loginInput)
                    ->orWhere('name', $loginInput)
                    ->orWhere('email', $loginInput);
            })
            ->first();

        $authenticated = false;
        if ($user !== null) {
            $authenticated = Auth::attempt([
                'id' => $user->id,
                'password' => $password,
                'is_active' => true,
            ]);
        } else {
            $authenticated = Auth::attempt([
                'username' => $loginInput,
                'password' => $password,
                'is_active' => true,
            ]);
        }
        if (! $authenticated) {
            return back()
                ->withErrors(['login' => 'invalid'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();

        $request->user()->forceFill([
            'last_login_at' => now(),
        ])->save();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => [
                'required',
                'string',
                'confirmed',
            ],
        ], [
            'current_password.current_password' => __('crm.current_password_incorrect'),
            'current_password.required' => __('crm.current_password_required'),
            'password.required' => __('crm.new_password_required'),
            'password.confirmed' => __('crm.password_confirmation_mismatch'),
        ]);

        $user->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        return back()->with('password_success', __('crm.password_changed_success'));
    }
}
