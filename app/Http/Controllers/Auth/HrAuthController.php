<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\HrUsers;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Lockout;

class HrAuthController extends Controller
{
    /**
     * Handle HR user registration.
     */
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'comp_code' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:App\Models\HrUsers,username'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:App\Models\HrUsers,email'],
            'contact_number' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed'],
        ]);

        $user = HrUsers::create([
            'comp_code' => $request->comp_code ?? '',
            'company_name' => $request->company_name,
            'username' => $request->username,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'contact_number' => $request->contact_number,
            'password' => Hash::make($request->string('password')),
        ]);

        // Auth::guard('hr_users')->login($user);

        // $request->session()->regenerate();

        return response()->json([
            'message' => 'Registration successful.',
            'user' => $user,
        ]);
    }

    /**
     * Handle HR user login.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited($request);

        if (! Auth::guard('hr_users')->attempt(
            $request->only('username', 'password'),
            $request->boolean('remember')
        )) {
            RateLimiter::hit($this->throttleKey($request));

            throw ValidationException::withMessages([
                'username' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey($request));

        $request->session()->regenerate();

        return response()->json([
            'message' => 'Login successful.',
            'user' => Auth::guard('hr_users')->user(),
        ]);
    }

    /**
     * Handle HR user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('hr_users')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function changePassword(Request $request){
        $request->validate([
            'current_password' => ['required'],
            'new_password' => ['required', 'min:8'],
            'confirm_password' => ['required', 'same:new_password'],
        ]);

        if(!Hash::check($request->current_password, $request->user()->password)){
            return response()->json([
                'message' => 'Current Password is incorrect'
            ]);
        }

        HrUsers::where('id', $request->user()->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);




    }

    /**
     * Get the authenticated HR user.
     */
    public function user(Request $request): JsonResponse
    {
        $user = Auth::guard('hr_users')->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return response()->json($user);
    }

    /**
     * Ensure the login request is not rate limited.
     */
    protected function ensureIsNotRateLimited(Request $request): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey($request), 5)) {
            return;
        }

        event(new Lockout($request));

        $seconds = RateLimiter::availableIn($this->throttleKey($request));

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    protected function throttleKey(Request $request): string
    {
        return Str::transliterate(Str::lower($request->string('username')) . '|' . $request->ip());
    }
}
