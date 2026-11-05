<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AccountController as ApiAccountController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\City;
use App\Models\User;
use App\Models\VendorType;
use App\Support\SiteAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorOnboardingController extends Controller
{
    use InteractsWithSite;

    public function create(): View
    {
        $this->shareShell('account');

        return view('site.vendor.wizard', [
            'editing' => false,
            'cities' => City::query()->orderBy('name_en')->get(['id', 'name_en']),
            'vendorTypes' => VendorType::query()->orderBy('sort_order')->get(['id', 'slug', 'name_en', 'name_ar']),
            'profile' => null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge(['role' => 'vendor']);
        $response = app(ApiAuthController::class)->register($request);
        $payload = $this->jsonData($response);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($payload['errors'] ?? ['form' => $payload['message'] ?? 'Could not create vendor.'])->withInput();
        }

        $user = User::query()->with('vendor.vendorType')->findOrFail($payload['id'] ?? 0);
        SiteAuth::login($user, $request);

        return redirect()->route('site.account')->with('status', 'Vendor profile submitted for verification.');
    }

    public function edit(): View
    {
        SiteAuth::applyClientHeader(request());
        $user = SiteAuth::user();
        abort_unless($user?->isVendor(), 403);
        $profile = $this->jsonData(app(ApiAccountController::class)->profile());
        $this->shareShell('account');

        return view('site.vendor.wizard', [
            'editing' => true,
            'cities' => City::query()->orderBy('name_en')->get(['id', 'name_en']),
            'vendorTypes' => VendorType::query()->orderBy('sort_order')->get(['id', 'slug', 'name_en', 'name_ar']),
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = app(ApiAccountController::class)->updateProfile($request);
        $payload = $this->jsonData($response);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($payload['errors'] ?? ['form' => $payload['message'] ?? 'Could not update.'])->withInput();
        }

        return redirect()->route('site.account')->with('status', 'Vendor profile updated.');
    }
}
