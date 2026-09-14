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

    public function envelope(): Envelope
    {
        $subject = $this->digest['status'] !== null
            ? "Your morning air quality: {$this->digest['status']}"
            : 'Your morning air quality digest';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.morning-digest');
    }
}
