<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AqiHistoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class HistoryExportController extends Controller
{
    public function __construct(
        private readonly AqiHistoryService $historyService,
    ) {}

    public function csv(Request $request): StreamedResponse
    {
        $readings = $this->historyService->exportableReadings($this->filters($request));

        return response()->streamDownload(function () use ($readings): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Type', 'Value', 'Recorded At']);

            foreach ($readings as $reading) {
                fputcsv($handle, [$reading->type, $reading->value, $reading->created_at->toIso8601String()]);
            }

            fclose($handle);
        }, 'airzen-history-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv']);
    }

    public function pdf(Request $request): Response
    {
        $filters = $this->filters($request);
        $readings = $this->historyService->exportableReadings($filters);

        $pdf = Pdf::loadView('exports.history-pdf', [
            'readings' => $readings,
            'filters' => $filters,
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download('airzen-history-'.now()->format('Y-m-d').'.pdf');
    }

    /**
     * @return array{type: ?string, from: ?string, to: ?string}
     */
    private function filters(Request $request): array
    {
        return [
            'type' => $request->query('type'),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];
    }
}
