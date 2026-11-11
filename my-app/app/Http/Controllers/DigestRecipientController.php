<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDigestRecipientRequest;
use App\Mail\MorningAirQualityDigest;
use App\Models\DigestRecipient;
use App\Models\DigestSchedule;
use App\Services\MorningDigestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

final class DigestRecipientController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Notifications', [
            'recipients' => DigestRecipient::orderBy('email')->get(['id', 'email']),
            'status' => session('status'),
            'schedule' => (function () {
                $schedule = DigestSchedule::current();

                return [
                    'enabled' => $schedule->enabled,
                    'mode' => $schedule->mode,
                    'interval_minutes' => $schedule->interval_minutes,
                    'send_at' => $schedule->send_at,
                    'last_sent_at' => $schedule->last_sent_at?->toIso8601String(),
                    'next_run_at' => $schedule->nextRunAt()?->toIso8601String(),
                    'timezone' => config('app.digest_timezone'),
                    'min_interval' => DigestSchedule::MIN_INTERVAL_MINUTES,
                ];
            })(),
        ]);
    }

    public function send(MorningDigestService $digestService): RedirectResponse
    {
        $recipients = DigestRecipient::all();

        if ($recipients->isEmpty()) {
            return back()->withErrors(['send' => 'Add a recipient first.']);
        }

        $digest = $digestService->build();

        if ($digest === null) {
            return back()->withErrors(['send' => 'No sensor readings recorded today yet - nothing to send.']);
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new MorningAirQualityDigest($digest));
        }

        return back()->with('status', "Air quality email sent to {$recipients->count()} recipient(s).");
    }

    public function store(StoreDigestRecipientRequest $request): RedirectResponse
    {
        DigestRecipient::create($request->validated());

        return to_route('notifications.index');
    }

    public function destroy(DigestRecipient $digestRecipient): RedirectResponse
    {
        $digestRecipient->delete();

        return to_route('notifications.index');
    }
}
