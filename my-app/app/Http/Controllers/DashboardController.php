<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\LatestReadingSnapshot;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly LatestReadingSnapshot $snapshot,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Dashboard', [
            'initialSnapshot' => $this->snapshot->get(),
        ]);
    }
}
