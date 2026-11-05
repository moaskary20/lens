<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Services\AiAssistant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiController extends Controller
{
    use InteractsWithSite;

    public function __construct(protected AiAssistant $assistant) {}

    public function show(Request $request): View
    {
        $this->shareShell('search');
        $q = trim((string) $request->query('q', ''));
        $summary = null;
        $vendors = [];

        if ($q !== '' && $this->assistant->enabled()) {
            $result = $this->assistant->assist($q);
            $summary = $result['summary'] ?? null;
            $vendors = array_map(
                fn (array $row) => app(\App\Http\Controllers\Api\AppController::class)->publicCard($row['vendor']),
                $result['results']['vendors'] ?? [],
            );
        }

        return view('site.ai', [
            'q' => $q,
            'summary' => $summary,
            'vendors' => $vendors,
            'enabled' => $this->assistant->enabled(),
        ]);
    }
}
