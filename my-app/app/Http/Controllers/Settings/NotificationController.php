<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Notifications', [
            'dailyDigestEnabled' => $request->user()->daily_digest_enabled,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'daily_digest_enabled' => ['required', 'boolean'],
        ]);

        $request->user()->update($validated);

        return to_route('notifications.edit');
    }
}
