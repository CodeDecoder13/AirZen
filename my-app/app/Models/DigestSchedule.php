<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

final class DigestSchedule extends Model
{
    public const MIN_INTERVAL_MINUTES = 5;

    protected $fillable = ['enabled', 'mode', 'interval_minutes', 'send_at', 'last_sent_at'];

    protected $attributes = [
        'enabled' => true,
        'mode' => 'daily',
        'interval_minutes' => 60,
        'send_at' => '07:00',
    ];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'interval_minutes' => 'integer', 'last_sent_at' => 'datetime'];
    }

    public static function current(): self
    {
        return self::query()->first() ?? self::create([]);
    }

    public function isDue(?Carbon $now = null): bool
    {
        $now ??= now();
        $next = $this->nextRunAt($now);

        return $next !== null && $next->lte($now);
    }

    /** When the next digest should go out; null when the schedule is off. */
    public function nextRunAt(?Carbon $now = null): ?Carbon
    {
        if (! $this->enabled) {
            return null;
        }

        $now ??= now();

        if ($this->mode === 'interval') {
            return ($this->last_sent_at ?? $now)->copy()->addMinutes($this->interval_minutes);
        }

        // Daily: wall-clock time in the digest timezone, once per day.
        $slot = $now->copy()->setTimezone(config('app.digest_timezone'))->setTimeFromTimeString($this->send_at);

        if ($this->last_sent_at !== null && $this->last_sent_at->gte($slot)) {
            $slot->addDay();
        }

        return $slot;
    }
}
