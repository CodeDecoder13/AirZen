<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\MorningAirQualityDigest;
use App\Models\DigestRecipient;
use App\Services\MorningDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendMorningDigest extends Command
{
    protected $signature = 'digest:send-morning';

    protected $description = 'Email the daily morning air quality digest to every configured recipient';

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

        $recipients = DigestRecipient::all();

        if ($recipients->isEmpty()) {
            $this->info('No digest recipients configured.');

            return self::SUCCESS;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new MorningAirQualityDigest($digest));
        }

        $this->info("Sent the morning digest to {$recipients->count()} recipient(s).");

        return self::SUCCESS;
    }
}
