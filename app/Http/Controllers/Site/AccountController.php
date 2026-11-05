<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AccountController as ApiAccountController;
use App\Http\Controllers\Api\IssueController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\City;
use App\Support\SiteAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    use InteractsWithSite;

    public function __construct(protected ApiAccountController $api) {}

    public function index(): View
    {
        $user = SiteAuth::user();
        $this->shareShell('account');

        return view('site.account.index', [
            'user' => $user,
            'isVendor' => (bool) $user?->isVendor(),
        ]);
    }

    public function edit(): View
    {
        SiteAuth::applyClientHeader(request());
        $profile = $this->jsonData($this->api->profile());
        $this->shareShell('account');

        return view('site.account.edit', [
            'profile' => $profile,
            'cities' => City::query()->orderBy('name_en')->get(['id', 'name_en']),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->updateProfile($request);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not save profile.'])->withInput();
        }

        return redirect()->route('site.account')->with('status', 'Profile updated.');
    }

    public function wallet(): View
    {
        SiteAuth::applyClientHeader(request());
        $payload = $this->jsonData($this->api->wallet());
        $this->shareShell('account');

        return view('site.account.wallet', [
            'wallet' => array_merge($payload['wallet'] ?? [], [
                'currency' => $payload['currency'] ?? 'EGP',
                'transactions' => $payload['transactions'] ?? ($payload['wallet']['transactions'] ?? []),
            ]),
        ]);
    }

    public function paymentMethods(): View
    {
        SiteAuth::applyClientHeader(request());
        $methods = $this->jsonData($this->api->paymentMethods());
        $this->shareShell('account');

        return view('site.account.payment-methods', ['methods' => $methods['methods'] ?? $methods]);
    }

    public function storePaymentMethod(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->storePaymentMethod($request);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not save card.'])->withInput();
        }

        return back()->with('status', 'Payment method saved.');
    }

    public function destroyPaymentMethod(Request $request, \App\Models\UserPaymentMethod $paymentMethod): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $this->api->destroyPaymentMethod($paymentMethod);

        return back()->with('status', 'Payment method removed.');
    }

    public function addresses(): View
    {
        SiteAuth::applyClientHeader(request());
        $payload = $this->jsonData($this->api->addresses());
        $this->shareShell('account');

        return view('site.account.addresses', [
            'addresses' => $payload['addresses'] ?? $payload,
            'cities' => City::query()->orderBy('name_en')->get(['id', 'name_en']),
        ]);
    }

    public function storeAddress(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->storeAddress($request);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not save address.'])->withInput();
        }

        return back()->with('status', 'Address saved.');
    }

    public function destroyAddress(Request $request, \App\Models\UserAddress $address): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $this->api->destroyAddress($address);

        return back()->with('status', 'Address removed.');
    }

    public function settings(): View
    {
        SiteAuth::applyClientHeader(request());
        $settings = $this->jsonData($this->api->settings());
        $this->shareShell('account');

        return view('site.account.settings', ['settings' => $settings]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->updateSettings($request);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not save settings.'])->withInput();
        }

        return back()->with('status', 'Settings saved.');
    }

    public function report(): View
    {
        $this->shareShell('account');

        return view('site.account.report');
    }

    public function storeReport(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = app(IssueController::class)->store($request);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not send report.'])->withInput();
        }

        return redirect()->route('site.account')->with('status', 'Issue reported. Thanks.');
    }
}
