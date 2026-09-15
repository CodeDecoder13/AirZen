<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIotReadingRequest;
use App\Models\SensorReading;
use App\Services\LatestReadingSnapshot;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

final class IotIngestController extends Controller
{
    public function __construct(
        private readonly LatestReadingSnapshot $snapshot,
    ) {}

    public function store(Request $request): Response|JsonResponse|View
    {
        if (! $request->hasAny(['val1', 'val2', 'type1', 'type2'])) {
            return $this->status();
        }

        $validator = Validator::make($request->query(), (new StoreIotReadingRequest())->rules());

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        SensorReading::create(['type' => $data['type1'], 'value' => (float) $data['val1']]);
        SensorReading::create(['type' => $data['type2'], 'value' => (float) $data['val2']]);

        return response('OK', 200);
    }

    private function status(): View
    {
        return view('iot-status', $this->snapshot->get());
    }
}
