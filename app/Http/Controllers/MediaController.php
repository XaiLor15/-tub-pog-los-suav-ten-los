<?php

namespace App\Http\Controllers;

use App\Models\DonationPayment;
use App\Models\Elder;
use App\Models\Event;
use App\Models\Leadership;
use App\Models\MediaItem;
use App\Models\Member;
use App\Models\News;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams files from the private disk after the same permission checks as the page that
 * shows them (replaces Supabase Storage bucket policies). Profile photos of members,
 * leaders and elders are public (approved for publication per the Handoff).
 */
class MediaController extends Controller
{
    public function member(Member $member): StreamedResponse
    {
        return $this->stream($member->photo_path);
    }

    public function leadership(Leadership $leadership): StreamedResponse
    {
        return $this->stream($leadership->photo_path);
    }

    public function elder(Elder $elder): StreamedResponse
    {
        return $this->stream($elder->photo_path);
    }

    public function avatar(User $user): StreamedResponse
    {
        abort_unless(auth()->check(), 404);

        return $this->stream($user->avatar_path);
    }

    public function eventCover(Event $event): StreamedResponse
    {
        abort_unless(Gate::allows('events.view', $event), 404);

        return $this->stream($event->cover_path);
    }

    public function item(MediaItem $mediaItem): StreamedResponse
    {
        $allowed = match ($mediaItem->module) {
            'event' => ($e = Event::find($mediaItem->record_id)) && Gate::allows('events.view', $e),
            'news' => ($n = News::find($mediaItem->record_id)) && Gate::allows('news.view', $n),
            default => false,
        };
        abort_unless($allowed, 404);

        return $this->stream($mediaItem->path, $mediaItem->mime);
    }

    public function slip(DonationPayment $donationPayment): StreamedResponse
    {
        return $this->stream($donationPayment->slip_path, null, false);
    }

    private function stream(?string $path, ?string $mime = null, bool $cache = true): StreamedResponse
    {
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, array_filter([
            'Content-Type' => $mime,
            'Cache-Control' => $cache ? 'private, max-age=86400' : 'no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]));
    }
}
