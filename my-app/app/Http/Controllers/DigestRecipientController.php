<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreDigestRecipientRequest;
use App\Models\DigestRecipient;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

final class DigestRecipientController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Notifications', [
            'recipients' => DigestRecipient::orderBy('email')->get(['id', 'email']),
        ]);
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
