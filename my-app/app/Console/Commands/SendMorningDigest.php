<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\MorningAirQualityDigest;
use App\Models\User;
use App\Services\MorningDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendMorningDigest extends Command
{
    protected $signature = 'digest:send-morning';

    protected $description = 'Email the daily morning air quality digest to users who have opted in';

    public function __construct(
        private readonly MorningDigestService $digestService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $digest = $this->digestService->build();

        if ($digest === null) {
            $this->info('No readings recorded yet today - skipping the morning digest.');

            return self::SUCCESS;
        }

        $recipients = User::where('daily_digest_enabled', true)->get();

        if ($recipients->isEmpty()) {
            $this->info('No users are opted in to the morning digest.');

            return self::SUCCESS;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->send(new MorningAirQualityDigest($digest));
        }

        $this->info("Sent the morning digest to {$recipients->count()} user(s).");

        return self::SUCCESS;
    }
}
