<?php

namespace App\Support;

use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\HtmlString;

class ChatThread
{
    public static function html(Conversation $conversation): HtmlString
    {
        $conversation->loadMissing(['messages.sender', 'booking.vendor.vendorType', 'vendor.vendorType', 'vendor.city', 'client']);

        $messages = $conversation->messages()->with('sender')->orderBy('id')->get();
        $rows = '';
        $lastDay = null;

        foreach ($messages as $message) {
            $dayKey = $message->created_at?->toDateString();
            if ($dayKey && $dayKey !== $lastDay) {
                $lastDay = $dayKey;
                $rows .= '<div class="lens-live-chat__day">'.e(self::dayLabel($message)).'</div>';
            }
            $rows .= self::bubble($conversation, $message);
        }

        if ($rows === '') {
            $rows = '<div class="lens-live-chat__empty"><span class="lens-live-chat__empty-icon">💬</span><p>No messages yet.</p><small>When the client or vendor writes, the thread appears here live.</small></div>';
        }

        return new HtmlString(
            '<div class="lens-live-chat">'.
                self::header($conversation, $messages->count()).
                '<div class="lens-live-chat__thread">'.$rows.'</div>'.
            '</div>'
        );
    }

    protected static function header(Conversation $conversation, int $count): string
    {
        $booking = $conversation->booking;
        $vendor = $conversation->vendor;
        $client = $conversation->client;
        $title = $booking?->project_name ?: (($vendor?->vendorType?->name_en ?: 'Creative').' session');
        $when = $booking?->scheduled_at?->format('l, j F Y · g:i A') ?: 'No session time yet';
        $where = $booking?->location_text ?: ($vendor?->address ?: ($vendor?->city?->name_en ?: 'Egypt'));
        $status = self::statusLabel($booking?->status);
        $reference = $booking?->reference ?: 'Unlinked';

        return '<div class="lens-live-chat__header">'.
            '<div class="lens-live-chat__people">'.
                self::avatar($client?->name, 'client').
                '<div class="lens-live-chat__swap" aria-hidden="true">↔</div>'.
                self::avatar($vendor?->display_name, 'vendor').
                '<div class="lens-live-chat__meta">'.
                    '<strong>'.e($client?->name ?: 'Client').' · '.e($vendor?->display_name ?: 'Vendor').'</strong>'.
                '</div>'.
            '</div>'.
            '<div class="lens-live-chat__chips">'.
                '<span class="lens-live-chat__chip lens-live-chat__chip--ref">'.e($reference).'</span>'.
                '<span class="lens-live-chat__chip lens-live-chat__chip--status">'.e($status).'</span>'.
                '<span class="lens-live-chat__chip">'.$count.' '.($count === 1 ? 'message' : 'messages').'</span>'.
            '</div>'.
            '<div class="lens-live-chat__session">'.
                '<b>'.e($title).'</b>'.
                '<span>'.e($when).'</span>'.
                '<span>'.e($where).'</span>'.
            '</div>'.
        '</div>';
    }

    protected static function bubble(Conversation $conversation, Message $message): string
    {
        $role = self::role($conversation, $message);
        $name = e($message->sender?->name ?? 'Unknown');
        $time = e($message->created_at?->format('g:i A') ?: '');
        $type = $message->type ?: 'text';
        $attachments = is_array($message->attachments) ? $message->attachments : [];
        $body = trim((string) $message->body);
        $media = self::media($type, $attachments);

        $hideBody = in_array($type, ['images', 'file', 'audio', 'location'], true) && $media !== '';
        $text = ($body !== '' && ! $hideBody)
            ? '<p class="lens-live-chat__text">'.nl2br(e($body)).'</p>'
            : '';

        return '<article class="lens-live-chat__row lens-live-chat__row--'.$role.'">'.
            self::avatar($message->sender?->name, $role).
            '<div class="lens-live-chat__stack">'.
                '<div class="lens-live-chat__who"><b>'.$name.'</b><em>'.e($role).'</em><time>'.$time.'</time></div>'.
                '<div class="lens-live-chat__bubble">'.$text.$media.'</div>'.
            '</div>'.
        '</article>';
    }

    /**
     * @param  array<string, mixed>  $attachments
     */
    protected static function media(string $type, array $attachments): string
    {
        $urls = collect($attachments['urls'] ?? [])
            ->map(fn ($url) => self::publicUrl($url))
            ->filter()
            ->values();
        if ($urls->isEmpty() && isset($attachments['paths']) && is_array($attachments['paths'])) {
            $urls = collect($attachments['paths'])->map(fn ($path) => self::publicUrl($path))->filter()->values();
        }

        $html = '';
        if ($urls->isNotEmpty() && in_array($type, ['images', 'text'], true)) {
            $images = $urls->map(function (string $url): string {
                $safe = e($url);

                return '<a href="'.$safe.'" target="_blank" rel="noopener" class="lens-live-chat__shot"><img src="'.$safe.'" alt=""></a>';
            })->implode('');
            $html .= '<div class="lens-live-chat__shots">'.$images.'</div>';
        }

        if ($type === 'file' || filled($attachments['file_name'] ?? null)) {
            $name = e($attachments['file_name'] ?? 'Shared file');
            $size = e($attachments['file_size'] ?? '');
            $href = $urls->first();
            $inner = '<span class="lens-live-chat__file-icon">PDF</span><span><b>'.$name.'</b><small>'.$size.'</small></span>';
            $html .= $href
                ? '<a class="lens-live-chat__file" href="'.e($href).'" target="_blank" rel="noopener">'.$inner.'</a>'
                : '<div class="lens-live-chat__file">'.$inner.'</div>';
        }

        if ($type === 'audio' || filled($attachments['duration'] ?? null)) {
            $html .= '<div class="lens-live-chat__voice"><span class="lens-live-chat__play"></span><span class="lens-live-chat__wave" aria-hidden="true"><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i></span><strong>'.e($attachments['duration'] ?? '0:00').'</strong></div>';
        }

        if ($type === 'location' || filled($attachments['location'] ?? null)) {
            $place = e($attachments['location'] ?? 'Shared location');
            $lat = $attachments['latitude'] ?? $attachments['lat'] ?? null;
            $lng = $attachments['longitude'] ?? $attachments['lng'] ?? null;
            $maps = ($lat !== null && $lng !== null)
                ? 'https://www.google.com/maps?q='.rawurlencode((string) $lat.','.$lng)
                : null;
            $inner = '<span>📍</span><span><b>Location</b><small>'.$place.'</small></span>';
            $html .= $maps
                ? '<a class="lens-live-chat__place" href="'.e($maps).'" target="_blank" rel="noopener">'.$inner.'</a>'
                : '<div class="lens-live-chat__place">'.$inner.'</div>';
        }

        return $html;
    }

    protected static function role(Conversation $conversation, Message $message): string
    {
        $senderId = (int) $message->sender_id;
        if ($senderId === (int) $conversation->client_id) {
            return 'client';
        }
        if ($senderId === (int) ($conversation->vendor?->user_id ?: 0)) {
            return 'vendor';
        }

        return 'staff';
    }

    protected static function avatar(?string $name, string $role): string
    {
        return '<div class="lens-live-chat__avatar lens-live-chat__avatar--'.$role.'" aria-hidden="true">'.e(self::initials($name)).'</div>';
    }

    protected static function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $letters = collect($parts)
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');

        return $letters !== '' ? $letters : 'L';
    }

    protected static function dayLabel(Message $message): string
    {
        $date = $message->created_at;
        if (! $date) {
            return 'Earlier';
        }
        if ($date->isToday()) {
            return 'Today';
        }
        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        return $date->format('l, j F Y');
    }

    protected static function statusLabel(?string $status): string
    {
        return match ($status) {
            'accepted', 'checked_in', 'in_progress' => 'Confirmed',
            'pending' => 'Pending',
            'delivered' => 'Delivered',
            'completed', 'approved' => 'Completed',
            'cancelled' => 'Cancelled',
            default => $status ? ucfirst(str_replace('_', ' ', $status)) : 'Open chat',
        };
    }

    protected static function publicUrl(mixed $path): ?string
    {
        if (! is_string($path) || trim($path) === '') {
            return null;
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset('storage/'.ltrim($path, '/'));
    }
}
