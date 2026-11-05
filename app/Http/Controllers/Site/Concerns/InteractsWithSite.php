<?php

namespace App\Http\Controllers\Site\Concerns;

use App\Http\Controllers\Api\AppController;
use App\Models\User;
use App\Support\SiteAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

trait InteractsWithSite
{
    protected function bootstrap(): array
    {
        return app(AppController::class)->bootstrap()->getData(true);
    }

    protected function webUser(?Request $request = null): ?User
    {
        return SiteAuth::user($request);
    }

    protected function shareShell(?string $active = null, array $extra = []): void
    {
        View::share(array_merge([
            'data' => $this->bootstrap(),
            'active' => $active,
            'authUser' => $this->webUser(),
        ], $extra));
    }

    /**
     * @return array<string, mixed>
     */
    protected function jsonData(JsonResponse $response): array
    {
        return json_decode($response->getContent() ?: '{}', true) ?: [];
    }
}
