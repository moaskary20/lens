<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AccountController as ApiAccountController;
use App\Http\Controllers\Api\AuthController as ApiAuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\City;
use App\Models\User;
use App\Models\VendorType;
use App\Support\Egypt;
use App\Support\SiteAuth;
use App\Support\VendorRegisterCatalog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorOnboardingController extends Controller
{
    use InteractsWithSite;

    public function create(): View
    {
        $this->shareShell('account');

        return view('site.vendor.wizard', $this->wizardPayload(false, null));
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

        return view('site.vendor.wizard', $this->wizardPayload(true, $profile));
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

    /**
     * @param  array<string, mixed>|null  $profile
     * @return array<string, mixed>
     */
    protected function wizardPayload(bool $editing, ?array $profile): array
    {
        $vendor = is_array($profile['vendor'] ?? null) ? $profile['vendor'] : [];
        $extras = is_array($vendor['extras'] ?? null) ? $vendor['extras'] : [];
        $texts = [];
        $lists = [
            'specialties' => array_values($vendor['specialties'] ?? []),
            'delivery_formats' => array_values($vendor['delivery_formats'] ?? []),
        ];
        foreach ($vendor['filter_tags'] ?? [] as $slug) {
            // Group unknown; chips resolve against field options client-side.
            $lists['__filters'] = array_values(array_unique([
                ...($lists['__filters'] ?? []),
                (string) $slug,
            ]));
        }
        foreach ($extras as $key => $value) {
            if ($key === 'prices' && is_array($value)) {
                foreach ($value as $priceKey => $priceValue) {
                    $texts['extras.prices.'.$priceKey] = (string) $priceValue;
                }
                continue;
            }
            if (is_scalar($value) || $value === null) {
                $texts['extras.'.$key] = (string) ($value ?? '');
            }
        }
        foreach (['half_day_price', 'full_day_price', 'hourly_price', 'per_video_price', 'turnaround_hours'] as $column) {
            if (isset($vendor[$column]) && $vendor[$column] !== null && $vendor[$column] !== '') {
                $texts[$column] = (string) $vendor[$column];
            }
        }

        return [
            'editing' => $editing,
            'cities' => City::query()->orderBy('name_en')->get(['id', 'name_en']),
            'vendorTypes' => VendorType::query()->orderBy('sort_order')->get(['id', 'slug', 'name_en', 'name_ar']),
            'profile' => $profile,
            'registerCatalog' => VendorRegisterCatalog::types(),
            'banks' => Egypt::banks(),
            'telecomWallets' => Egypt::telecomWallets(),
            'joinState' => [
                'vendorType' => old('vendor_type', $vendor['vendor_type'] ?? ''),
                'pricingModelId' => (string) old('pricing_model_id', $extras['pricing_model_id'] ?? ''),
                'texts' => old('_texts', $texts) ?: $texts,
                'lists' => old('_lists', $lists) ?: $lists,
                'filterTags' => array_values($vendor['filter_tags'] ?? []),
                'payoutMethod' => old('payout_method', $vendor['payout_method'] ?? 'bank'),
                'walletNetwork' => old('wallet_network_type', $vendor['wallet_network_type'] ?? 'telecom'),
            ],
        ];
    }
}
