<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\UpdateDigestScheduleRequest;
use App\Models\DigestSchedule;
use Illuminate\Http\RedirectResponse;

final class DigestScheduleController extends Controller
{
    public function update(UpdateDigestScheduleRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Restart the clock on every save so a new schedule never fires
        // instantly for a slot that has already passed.
        DigestSchedule::current()->update(array_filter($data, fn ($v) => $v !== null) + ['last_sent_at' => now()]);

        return back()->with('status', 'Schedule saved.');
    }
}
