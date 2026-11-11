<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class MorningAirQualityDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param array{
     *     date: string,
     *     readings: array{temperature: ?float, humidity: ?float, nitrogen: ?float, co: ?float, particulate_matter: ?float},
     *     aqi: ?float,
     *     status: ?string,
     *     color: ?string,
     *     recommendations: list<string>,
     * } $digest
     */
    public function __construct(
        public readonly array $digest,
    ) {}

    /**
     * Time-of-day label + greeting, so the same email reads right whether it
     * goes out on the 7 AM schedule or from the "Send now" button at midnight.
     *
     * @return array{label: string, greeting: string}
     */
    public function period(): array
    {
        $hour = (int) now(config('app.digest_timezone'))->format('G');

        return match (true) {
            $hour >= 4 && $hour < 6 => ['label' => 'dawn', 'greeting' => 'Rise and shine'],
            $hour >= 6 && $hour < 12 => ['label' => 'morning', 'greeting' => 'Good morning'],
            $hour >= 12 && $hour < 18 => ['label' => 'afternoon', 'greeting' => 'Good afternoon'],
            $hour >= 18 && $hour < 21 => ['label' => 'evening', 'greeting' => 'Good evening'],
            default => ['label' => 'night', 'greeting' => 'Hello, night owl'],
        };
    }

    public function envelope(): Envelope
    {
        $label = $this->period()['label'];

        $subject = $this->digest['status'] !== null
            ? "Your {$label} air quality: {$this->digest['status']}"
            : "Your {$label} air quality digest";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.morning-digest', with: ['period' => $this->period()]);
    }
}
