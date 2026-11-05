<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Api\AccountController as ApiAccountController;
use App\Http\Controllers\Api\ChatController as ApiChatController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\InteractsWithSite;
use App\Models\Conversation;
use App\Models\Vendor;
use App\Support\SiteAuth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChatController extends Controller
{
    use InteractsWithSite;

    public function __construct(
        protected ApiChatController $api,
        protected ApiAccountController $account,
    ) {}

    public function index(): View
    {
        SiteAuth::applyClientHeader(request());
        $projects = $this->jsonData($this->account->projects());
        $this->shareShell('account');

        return view('site.chat.index', [
            'projects' => $projects['projects'] ?? $projects,
        ]);
    }

    public function open(Request $request): RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->open($request);
        $payload = $this->jsonData($response);
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($payload['errors'] ?? ['form' => $payload['message'] ?? 'Could not open chat.']);
        }

        $id = $payload['id'] ?? $payload['conversation']['id'] ?? null;

        return redirect()->route('site.messages.show', ['conversation' => $id]);
    }

    public function openVendor(Vendor $vendor): RedirectResponse
    {
        $request = request();
        SiteAuth::applyClientHeader($request);
        $request->merge([
            'vendor_id' => $vendor->id,
        ]);
        $response = $this->api->open($request);
        $payload = $this->jsonData($response);
        if ($response->getStatusCode() >= 400) {
            return redirect()->route('site.login')->withErrors($payload['errors'] ?? ['form' => 'Sign in to chat.']);
        }

        $id = $payload['id'] ?? $payload['conversation']['id'] ?? null;

        return redirect()->route('site.messages.show', ['conversation' => $id]);
    }

    public function show(Conversation $conversation): View
    {
        SiteAuth::applyClientHeader(request());
        $payload = $this->jsonData($this->api->show($conversation));
        $projects = $this->jsonData($this->account->projects());
        $this->shareShell('account');

        return view('site.chat.show', [
            'conversation' => $payload,
            'projects' => $projects['projects'] ?? $projects,
            'conversationId' => $conversation->id,
        ]);
    }

    public function poll(Conversation $conversation): JsonResponse
    {
        SiteAuth::applyClientHeader(request());

        return $this->api->show($conversation);
    }

    public function store(Request $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        SiteAuth::applyClientHeader($request);
        $response = $this->api->store($request, $conversation);
        if ($request->expectsJson()) {
            return $response;
        }
        if ($response->getStatusCode() >= 400) {
            return back()->withErrors($this->jsonData($response)['errors'] ?? ['form' => 'Could not send.']);
        }

        return back();
    }
}
