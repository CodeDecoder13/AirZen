<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreIotReadingRequest;
use App\Models\SensorReading;
use Illuminate\Http\Response;

final class IotIngestController extends Controller
{
    public function store(StoreIotReadingRequest $request): Response
    {
        $data = $request->validated();

        SensorReading::create(['type' => $data['type1'], 'value' => (float) $data['val1']]);
        SensorReading::create(['type' => $data['type2'], 'value' => (float) $data['val2']]);

        return response('OK', 200);
    }
}
