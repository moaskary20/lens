<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\IssueReport;
use App\Support\AppClient;
use App\Support\Feature;
use App\Support\Roles;
use App\Support\LensNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IssueController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(Feature::enabled('issue_reports'), 422, 'Issue reports are disabled.');

        $data = $request->validate([
            'topic' => ['required', Rule::in(array_keys(IssueReport::TOPICS))],
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:2000'],
            'name' => ['nullable', 'string', 'max:80'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:20'],
            'booking_reference' => ['nullable', 'string', 'max:40'],
            'app_version' => ['nullable', 'string', 'max:20'],
            'platform' => ['nullable', 'string', 'max:20'],
            'guest' => ['nullable', 'boolean'],
        ]);

        $user = $request->boolean('guest') ? null : AppClient::user($request);
        if ($user && ! ($user->canUseClientApp() || $user->isVendor())) {
            $user = null;
        }
        if ($user) {
            Roles::abortUnlessCan($user, 'report_issues', 'Reporting issues is disabled for this role.');
        }

        $name = trim((string) ($data['name'] ?? $user?->name ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? $user?->email ?? '')));
        abort_if($name === '' || $email === '', 422, 'Name and email are required.');

        $issue = IssueReport::query()->create([
            'user_id' => $user?->id,
            'name' => $name,
            'email' => $email,
            'phone' => $data['phone'] ?? $user?->phone,
            'topic' => $data['topic'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'booking_reference' => $data['booking_reference'] ?? null,
            'app_version' => $data['app_version'] ?? null,
            'platform' => $data['platform'] ?? null,
            'status' => 'open',
        ]);

        $issue->refresh();

        LensNotifier::staff(
            LensNotifier::ISSUE_REPORTED,
            'New app issue',
            $issue->reference.' · '.$issue->topicLabel().' · '.$issue->subject,
            $user?->id,
        );

        if ($user) {
            LensNotifier::toUser(
                $user,
                LensNotifier::ISSUE_UPDATED,
                'We received '.$issue->reference,
                'Lens support will review your report and follow up here.',
            );
        }

        return response()->json([
            'ok' => true,
            'id' => $issue->id,
            'reference' => $issue->reference,
            'status' => $issue->status,
            'message' => 'Your report is with Lens support.',
        ], 201);
    }
}
