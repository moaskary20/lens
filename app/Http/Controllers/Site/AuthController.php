<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\City;
use App\Models\User;
use App\Models\VendorType;
use App\Support\SiteAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    use InteractsWithSite;

    public function showLogin(): View
    {
        $this->shareShell('account');

        return view('site.auth.login', [
            'googleClientId' => config('services.google.client_id'),
        ]);
    }

    public function showRegister(Request $request): View
    {
        $this->shareShell('account');
        $role = $request->query('role') === 'vendor' ? 'vendor' : 'client';

        return view('site.auth.register', [
            'role' => $role,
            'cities' => City::query()->orderBy('name_en')->get(['id', 'name_en']),
            'vendorTypes' => VendorType::query()->orderBy('sort_order')->get(['id', 'slug', 'name_en', 'name_ar']),
            'googleClientId' => config('services.google.client_id'),
        ]);
    }

    public function showRoleChoice(): View
    {
        $this->shareShell('account');

        return view('site.auth.role');
    }

    public function login(Request $request): RedirectResponse
    {
        // Same credential check as POST /api/app/auth/login
        $response = app(ApiAuthController::class)->login($request);
        $payload = $this->jsonData($response);
        if ($response->getStatusCode() >= 400) {
            throw ValidationException::withMessages($payload['errors'] ?? [
                'email' => [$payload['message'] ?? 'Invalid email or password.'],
            ]);
        }

        $user = User::query()->with('vendor.vendorType')->findOrFail($payload['id'] ?? 0);
        if (! $user->canUseClientApp() && ! $user->isVendor()) {
            throw ValidationException::withMessages([
                'email' => 'This account cannot sign in on the website.',
            ]);
        }

        SiteAuth::login($user, $request);

        return redirect()->intended(route('site.account'));
    }

    public function register(Request $request): RedirectResponse
    {
        $api = app(ApiAuthController::class);
        $response = $api->register($request);
        if ($response->getStatusCode() >= 400) {
            $payload = $this->jsonData($response);
            throw ValidationException::withMessages($payload['errors'] ?? ['email' => [$payload['message'] ?? 'Registration failed.']]);
        }

        $payload = $this->jsonData($response);
        $user = User::query()->with('vendor.vendorType')->findOrFail($payload['id'] ?? 0);
        SiteAuth::login($user, $request);

        return redirect()->route('site.account')->with('status', 'Welcome to Lens.');
    }

    public function google(Request $request): RedirectResponse
    {
        $api = app(ApiAuthController::class);
        $response = $api->google($request);
        if ($response->getStatusCode() >= 400) {
            $payload = $this->jsonData($response);
            throw ValidationException::withMessages($payload['errors'] ?? ['id_token' => [$payload['message'] ?? 'Google sign-in failed.']]);
        }

        $payload = $this->jsonData($response);
        $user = User::query()->with('vendor.vendorType')->findOrFail($payload['id'] ?? 0);
        SiteAuth::login($user, $request);

        return redirect()->intended(route('site.account'));
    }

    public function logout(Request $request): RedirectResponse
    {
        SiteAuth::logout($request);

        return redirect()->route('site.home');
    }
}
