# AirZen Webapp Buildout Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the scaffolded `my-app` Laravel + Inertia/Vue project into the working AirZen indoor-air-quality dashboard described in the project plan — deterministic EPA AQI scoring, a device ingestion API, a live dashboard, and a path to Railway deployment.

**Architecture:** Laravel 12 backend with two pure calculation services (`AqiCalculator`, `RecommendationService`) sitting between a device-authenticated ingestion endpoint and a `readings` table. The Inertia/Vue dashboard polls a session-authenticated `latest` endpoint every 8s and re-renders a CSS-driven AQI gauge, recommendation panel, and sensor strip. An ESP32 sketch and a Railway deployment checklist close the loop from physical sensor to public URL.

**Tech Stack:** Laravel 12, PHP 8.2+, Pest (via `tests/Pest.php`, PHPUnit runner), Inertia.js v2 + Vue 3, Tailwind CSS 3, shadcn-vue, PostgreSQL (Railway) / SQLite (local + test).

**Spec:** `../../../../AirZen-Webapp-Project-Plan.md` (repo root: `C:\Users\Rhuzz\orca\AirZen\AirZen-Webapp-Project-Plan.md`)

## Global Constraints

- PHP 8.2+ syntax: typed properties, `declare(strict_types=1)`, readonly where applicable (per laravel-specialist skill).
- All new Feature tests are Pest functions in `tests/Feature/`; `tests/Pest.php` already binds `Tests\TestCase` + `RefreshDatabase` to that directory — do not re-add `use RefreshDatabase` per file.
- All new Unit tests are Pest functions in `tests/Unit/`.
- No raw DB queries — Eloquent only.
- AQI breakpoint values and recommendation copy must match the spec verbatim (Section 6.3 / 6.4) — these are thesis-sourced constants, not free choices.
- Money/measurement columns are `float` in the DB (`decimal` is not required here — no arithmetic precision requirements beyond what the thesis prototype used).
- Existing scaffolding (Breeze-style auth controllers, shadcn-vue components, `AppLayout`, `AuthLayout`) is already in place — do not regenerate it; extend it.
- Frontend has no test runner installed (no Vitest/Jest/Dusk) — Vue/CSS work is verified by (a) a Pest feature test asserting the Inertia props contract, and (b) manual browser verification via the `run` skill/dev server. Do not invent a frontend test harness for this plan.

---

## Task 1: Verify scaffolding & wire PostgreSQL locally

**Files:**
- Modify: `.env` (not tracked; edit directly, do not commit)
- Read: `config/database.php`, `phpunit.xml` (no changes needed — confirms sqlite `:memory:` stays the test DB)

**Interfaces:**
- Consumes: nothing (first task)
- Produces: a booting app on `DB_CONNECTION=pgsql` that later tasks build on. Test suite continues to run against sqlite `:memory:` regardless (per `phpunit.xml:25-26`), so this task does not change how any later task's tests run.

- [ ] **Step 1: Confirm current baseline is green**

Run: `php artisan test`
Expected: all existing tests (Auth, Dashboard, Settings, Example) PASS. This is the safety net for every task after this one.

- [ ] **Step 2: Point local `.env` at PostgreSQL**

Edit `.env` (create a local Postgres database named `airzen` first, e.g. `createdb airzen`):

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=airzen
DB_USERNAME=postgres
DB_PASSWORD=
```

- [ ] **Step 3: Migrate and boot**

Run: `php artisan migrate:fresh`
Expected: `users`, `cache`, `jobs` tables created with no errors.

Run: `php artisan serve`
Expected: visiting `http://127.0.0.1:8000/register`, creating an account, and logging in reaches `/dashboard` with no errors.

- [ ] **Step 4: Commit**

Nothing to commit (`.env` is gitignored). No commit for this task — proceed to Task 2.

---

## Task 2: `readings` table + `Reading` model

**Files:**
- Create: `database/migrations/2026_09_13_000001_create_readings_table.php`
- Create: `app/Models/Reading.php`
- Create: `database/factories/ReadingFactory.php`
- Test: `tests/Feature/ReadingModelTest.php`

**Interfaces:**
- Consumes: nothing new.
- Produces: `Reading` model with fillable `temperature, humidity, co, nitrogen, pm25, aqi, status, color, device_id` (all cast to `float` except `status`, `color`, `device_id` which are `string`), used by Task 3-5's services and controller.

- [ ] **Step 1: Write the failing test**

```php
<?php
// tests/Feature/ReadingModelTest.php

use App\Models\Reading;

it('stores a reading with the expected columns and casts', function (): void {
    $reading = Reading::factory()->create([
        'temperature' => 27.5,
        'humidity' => 61.2,
        'co' => 3.1,
        'nitrogen' => 55.0,
        'pm25' => 12.4,
        'aqi' => 42.0,
        'status' => 'Good',
        'color' => '#22C55E',
        'device_id' => 'esp32-room-204',
    ]);

    expect($reading->temperature)->toBeFloat()->toBe(27.5)
        ->and($reading->aqi)->toBeFloat()->toBe(42.0)
        ->and($reading->status)->toBe('Good')
        ->and($reading->color)->toBe('#22C55E')
        ->and($reading->device_id)->toBe('esp32-room-204');

    $this->assertDatabaseHas('readings', ['device_id' => 'esp32-room-204']);
});

it('allows a null device_id', function (): void {
    $reading = Reading::factory()->create(['device_id' => null]);

    expect($reading->device_id)->toBeNull();
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ReadingModelTest`
Expected: FAIL — `Class "App\Models\Reading" not found` (model + migration + factory don't exist yet).

- [ ] **Step 3: Write the migration**

```php
<?php
// database/migrations/2026_09_13_000001_create_readings_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table): void {
            $table->id();
            $table->float('temperature');
            $table->float('humidity');
            $table->float('co');
            $table->float('nitrogen');
            $table->float('pm25');
            $table->float('aqi');
            $table->string('status');
            $table->string('color');
            $table->string('device_id')->nullable();
            $table->timestamps();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};
```

- [ ] **Step 4: Write the model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Reading extends Model
{
    /** @use HasFactory<ReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'temperature',
        'humidity',
        'co',
        'nitrogen',
        'pm25',
        'aqi',
        'status',
        'color',
        'device_id',
    ];

    protected $casts = [
        'temperature' => 'float',
        'humidity' => 'float',
        'co' => 'float',
        'nitrogen' => 'float',
        'pm25' => 'float',
        'aqi' => 'float',
    ];
}
```

- [ ] **Step 5: Write the factory**

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reading;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reading> */
final class ReadingFactory extends Factory
{
    protected $model = Reading::class;

    public function definition(): array
    {
        return [
            'temperature' => $this->faker->randomFloat(1, 18, 32),
            'humidity' => $this->faker->randomFloat(1, 30, 90),
            'co' => $this->faker->randomFloat(2, 0, 10),
            'nitrogen' => $this->faker->randomFloat(1, 0, 120),
            'pm25' => $this->faker->randomFloat(1, 0, 60),
            'aqi' => $this->faker->randomFloat(1, 0, 150),
            'status' => 'Good',
            'color' => '#22C55E',
            'device_id' => 'esp32-room-204',
        ];
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan migrate:status` (expect the new migration listed as `Ran`, since Pest's `RefreshDatabase` runs it against the test DB automatically) then `php artisan test --filter=ReadingModelTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_09_13_000001_create_readings_table.php app/Models/Reading.php database/factories/ReadingFactory.php tests/Feature/ReadingModelTest.php
git commit -m "feat: add readings table, Reading model, and factory"
```

---

## Task 3: `AqiCalculator` service (EPA breakpoint formula)

**Files:**
- Create: `app/Services/AqiCalculator.php`
- Test: `tests/Unit/AqiCalculatorTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Services\AqiCalculator::calculateSubIndex(string $pollutant, float $concentration): ?float` and `App\Services\AqiCalculator::calculateOverallAqi(array $readings): float`, where `$readings` is `['pm25' => float, 'co' => float, 'nitrogen' => float]`. Consumed by Task 5's `ReadingController`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Unit/AqiCalculatorTest.php

use App\Services\AqiCalculator;

beforeEach(function (): void {
    $this->calculator = new AqiCalculator();
});

it('calculates the pm25 sub-index at the low edge of a bracket', function (): void {
    // Bracket 0.0-15.4 -> AQI 0-50, concentration 0.0 -> AQI 0
    expect($this->calculator->calculateSubIndex('pm25', 0.0))->toBe(0.0);
});

it('calculates the pm25 sub-index at the midpoint of a bracket', function (): void {
    // Bracket 15.5-35.4 -> AQI 51-100, concentration 25.45 (midpoint) -> AQI ~75.5
    $result = $this->calculator->calculateSubIndex('pm25', 25.45);

    expect($result)->toBeGreaterThan(74.0)->toBeLessThan(77.0);
});

it('calculates the co sub-index', function (): void {
    // Bracket 4.5-9.4 -> AQI 51-100, concentration 4.5 -> AQI 51
    expect($this->calculator->calculateSubIndex('co', 4.5))->toBe(51.0);
});

it('calculates the nitrogen sub-index', function (): void {
    // Bracket 0-40 -> AQI 0-50, concentration 40 -> AQI 50
    expect($this->calculator->calculateSubIndex('nitrogen', 40.0))->toBe(50.0);
});

it('returns null for an unknown pollutant', function (): void {
    expect($this->calculator->calculateSubIndex('ozone', 10.0))->toBeNull();
});

it('returns null when concentration exceeds every bracket', function (): void {
    expect($this->calculator->calculateSubIndex('co', 999.0))->toBeNull();
});

it('returns the highest sub-index as the overall AQI', function (): void {
    // pm25=0 -> AQI 0 ; co=4.5 -> AQI 51 ; nitrogen=40 -> AQI 50
    // highest is co's 51
    $overall = $this->calculator->calculateOverallAqi([
        'pm25' => 0.0,
        'co' => 4.5,
        'nitrogen' => 40.0,
    ]);

    expect($overall)->toBe(51.0);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=AqiCalculatorTest`
Expected: FAIL — `Class "App\Services\AqiCalculator" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

final class AqiCalculator
{
    /**
     * @var array<string, list<array{bpLo: float, bpHi: float, iLo: float, iHi: float}>>
     */
    private const BREAKPOINTS = [
        'pm25' => [
            ['bpLo' => 0.0, 'bpHi' => 15.4, 'iLo' => 0.0, 'iHi' => 50.0],
            ['bpLo' => 15.5, 'bpHi' => 35.4, 'iLo' => 51.0, 'iHi' => 100.0],
            ['bpLo' => 35.5, 'bpHi' => 54.4, 'iLo' => 101.0, 'iHi' => 150.0],
            ['bpLo' => 54.5, 'bpHi' => 150.4, 'iLo' => 151.0, 'iHi' => 200.0],
            ['bpLo' => 150.5, 'bpHi' => 250.4, 'iLo' => 201.0, 'iHi' => 300.0],
        ],
        'co' => [
            ['bpLo' => 0.0, 'bpHi' => 4.4, 'iLo' => 0.0, 'iHi' => 50.0],
            ['bpLo' => 4.5, 'bpHi' => 9.4, 'iLo' => 51.0, 'iHi' => 100.0],
            ['bpLo' => 9.5, 'bpHi' => 12.4, 'iLo' => 101.0, 'iHi' => 150.0],
            ['bpLo' => 12.5, 'bpHi' => 15.4, 'iLo' => 151.0, 'iHi' => 200.0],
            ['bpLo' => 15.5, 'bpHi' => 30.4, 'iLo' => 201.0, 'iHi' => 300.0],
        ],
        'nitrogen' => [
            ['bpLo' => 0.0, 'bpHi' => 40.0, 'iLo' => 0.0, 'iHi' => 50.0],
            ['bpLo' => 41.0, 'bpHi' => 100.0, 'iLo' => 51.0, 'iHi' => 100.0],
            ['bpLo' => 101.0, 'bpHi' => 150.0, 'iLo' => 101.0, 'iHi' => 150.0],
            ['bpLo' => 150.0, 'bpHi' => 200.0, 'iLo' => 151.0, 'iHi' => 200.0],
            ['bpLo' => 201.0, 'bpHi' => 300.0, 'iLo' => 201.0, 'iHi' => 300.0],
        ],
    ];

    public function calculateSubIndex(string $pollutant, float $concentration): ?float
    {
        foreach (self::BREAKPOINTS[$pollutant] ?? [] as $bracket) {
            if ($concentration >= $bracket['bpLo'] && $concentration <= $bracket['bpHi']) {
                return (($bracket['iHi'] - $bracket['iLo']) / ($bracket['bpHi'] - $bracket['bpLo']))
                    * ($concentration - $bracket['bpLo'])
                    + $bracket['iLo'];
            }
        }

        return null;
    }

    /**
     * @param array{pm25: float, co: float, nitrogen: float} $readings
     */
    public function calculateOverallAqi(array $readings): float
    {
        $subIndices = array_filter([
            $this->calculateSubIndex('pm25', $readings['pm25']),
            $this->calculateSubIndex('co', $readings['co']),
            $this->calculateSubIndex('nitrogen', $readings['nitrogen']),
        ], static fn (?float $value): bool => $value !== null);

        return $subIndices === [] ? 0.0 : max($subIndices);
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=AqiCalculatorTest`
Expected: PASS (7 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/AqiCalculator.php tests/Unit/AqiCalculatorTest.php
git commit -m "feat: add AqiCalculator with EPA breakpoint interpolation"
```

---

## Task 4: `RecommendationService` (band lookup)

**Files:**
- Create: `app/Services/RecommendationService.php`
- Test: `tests/Unit/RecommendationServiceTest.php`

**Interfaces:**
- Consumes: nothing (takes a raw `float $aqi`, independent of `AqiCalculator`).
- Produces: `App\Services\RecommendationService::getRecommendation(float $aqi): array` returning `['status' => string, 'color' => string, 'recommendations' => array<string>]`. Consumed by Task 5's `ReadingController`.

- [ ] **Step 1: Write the failing tests**

```php
<?php
// tests/Unit/RecommendationServiceTest.php

use App\Services\RecommendationService;

beforeEach(function (): void {
    $this->service = new RecommendationService();
});

it('returns the Good band for aqi 0-50', function (): void {
    $result = $this->service->getRecommendation(30.0);

    expect($result['status'])->toBe('Good')
        ->and($result['color'])->toBe('#22C55E')
        ->and($result['recommendations'])->toHaveCount(3)
        ->and($result['recommendations'][0])->toBe('Keep windows and doors open to allow fresh air circulation.');
});

it('returns the Normal band for aqi 51-100', function (): void {
    $result = $this->service->getRecommendation(75.0);

    expect($result['status'])->toBe('Normal')->and($result['color'])->toBe('#EAB308');
});

it('returns the Unhealthy for Sensitive Groups band for aqi 101-150', function (): void {
    $result = $this->service->getRecommendation(120.0);

    expect($result['status'])->toBe('Unhealthy for Sensitive Groups')->and($result['color'])->toBe('#F59E0B');
});

it('returns the Unhealthy for All Groups band for aqi 151-200', function (): void {
    $result = $this->service->getRecommendation(175.0);

    expect($result['status'])->toBe('Unhealthy for All Groups')->and($result['color'])->toBe('#EF4444');
});

it('returns the Very Unhealthy band for aqi 201-300', function (): void {
    $result = $this->service->getRecommendation(250.0);

    expect($result['status'])->toBe('Very Unhealthy')->and($result['color'])->toBe('#A855F7');
});

it('falls back to the worst band when aqi exceeds 300', function (): void {
    $result = $this->service->getRecommendation(450.0);

    expect($result['status'])->toBe('Very Unhealthy')->and($result['color'])->toBe('#A855F7');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=RecommendationServiceTest`
Expected: FAIL — `Class "App\Services\RecommendationService" not found`.

- [ ] **Step 3: Write the implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

final class RecommendationService
{
    /**
     * @var list<array{max: float, status: string, color: string, recommendations: list<string>}>
     */
    private const BANDS = [
        [
            'max' => 50.0,
            'status' => 'Good',
            'color' => '#22C55E',
            'recommendations' => [
                'Keep windows and doors open to allow fresh air circulation.',
                'Regularly clean and dust the space to minimize allergens.',
                'Indoor plants like peace lilies or spider plants can help maintain this air quality.',
            ],
        ],
        [
            'max' => 100.0,
            'status' => 'Normal',
            'color' => '#EAB308',
            'recommendations' => [
                'Enhance ventilation by opening windows or using fans.',
                'Use natural cleaning products to reduce VOC release.',
                'Keep indoor humidity in check to prevent mold and mildew.',
            ],
        ],
        [
            'max' => 150.0,
            'status' => 'Unhealthy for Sensitive Groups',
            'color' => '#F59E0B',
            'recommendations' => [
                'Utilize air purifiers with HEPA filters where possible.',
                'Minimize activities in the room during peak hours.',
                'Sensitive individuals should consider a mask indoors.',
            ],
        ],
        [
            'max' => 200.0,
            'status' => 'Unhealthy for All Groups',
            'color' => '#EF4444',
            'recommendations' => [
                'Improve ventilation by opening windows or using exhaust fans.',
                'Run air purifiers with HEPA filters continuously.',
                'Avoid smoking or use of harsh chemicals in the room.',
            ],
        ],
        [
            'max' => 300.0,
            'status' => 'Very Unhealthy',
            'color' => '#A855F7',
            'recommendations' => [
                'Close windows and doors to limit outdoor pollutant exposure.',
                'Use additional air filtration if available.',
                'Follow local advisories on indoor air quality and health.',
            ],
        ],
    ];

    /**
     * @return array{status: string, color: string, recommendations: list<string>}
     */
    public function getRecommendation(float $aqi): array
    {
        foreach (self::BANDS as $band) {
            if ($aqi <= $band['max']) {
                return [
                    'status' => $band['status'],
                    'color' => $band['color'],
                    'recommendations' => $band['recommendations'],
                ];
            }
        }

        $worst = self::BANDS[count(self::BANDS) - 1];

        return [
            'status' => $worst['status'],
            'color' => $worst['color'],
            'recommendations' => $worst['recommendations'],
        ];
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=RecommendationServiceTest`
Expected: PASS (6 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Services/RecommendationService.php tests/Unit/RecommendationServiceTest.php
git commit -m "feat: add RecommendationService with thesis Table 1 bands"
```

---

## Task 5: Device ingestion API + session-authenticated latest endpoint

**Files:**
- Create: `app/Http/Middleware/VerifyDeviceApiKey.php`
- Create: `app/Http/Requests/StoreReadingRequest.php`
- Create: `app/Http/Resources/ReadingResource.php`
- Create: `app/Http/Controllers/Api/ReadingController.php`
- Create: `routes/api.php`
- Modify: `bootstrap/app.php` (register `routes/api.php`, register `device.key` middleware alias)
- Modify: `routes/web.php` (add session-authenticated `GET /api/readings/latest`)
- Modify: `.env.example` (document `DEVICE_API_KEY`)
- Test: `tests/Feature/ReadingIngestionTest.php`
- Test: `tests/Feature/LatestReadingTest.php`

**Interfaces:**
- Consumes: `App\Services\AqiCalculator::calculateOverallAqi()` (Task 3), `App\Services\RecommendationService::getRecommendation()` (Task 4), `App\Models\Reading` (Task 2).
- Produces: `POST /api/readings` (device-key protected, stateless) and `GET /api/readings/latest` (session-authenticated). Both return `{ "reading": {...}, "recommendation": {"status":..., "color":..., "recommendations":[...]} }`. This exact JSON shape is what Task 7's `useLatestReading` composable consumes.

- [ ] **Step 1: Write the failing ingestion tests**

```php
<?php
// tests/Feature/ReadingIngestionTest.php

use App\Models\Reading;

beforeEach(function (): void {
    config(['services.device.key' => 'test-device-key']);
});

it('rejects a request without the device key header', function (): void {
    $response = $this->postJson('/api/readings', [
        'temperature' => 25.0,
        'humidity' => 55.0,
        'co' => 2.0,
        'nitrogen' => 30.0,
        'pm25' => 10.0,
    ]);

    $response->assertStatus(401);
});

it('rejects a request with the wrong device key', function (): void {
    $response = $this->postJson('/api/readings', [
        'temperature' => 25.0,
        'humidity' => 55.0,
        'co' => 2.0,
        'nitrogen' => 30.0,
        'pm25' => 10.0,
    ], ['X-Device-Key' => 'wrong-key']);

    $response->assertStatus(401);
});

it('validates required numeric fields', function (): void {
    $response = $this->postJson('/api/readings', [
        'temperature' => 'not-a-number',
    ], ['X-Device-Key' => 'test-device-key']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['temperature', 'humidity', 'co', 'nitrogen', 'pm25']);
});

it('stores a reading with computed aqi, status, and color, and returns the recommendation', function (): void {
    $response = $this->postJson('/api/readings', [
        'temperature' => 25.0,
        'humidity' => 55.0,
        'co' => 4.5,   // sub-index 51
        'nitrogen' => 40.0, // sub-index 50
        'pm25' => 0.0, // sub-index 0
        'device_id' => 'esp32-room-204',
    ], ['X-Device-Key' => 'test-device-key']);

    $response->assertStatus(201)
        ->assertJsonPath('reading.aqi', 51)
        ->assertJsonPath('reading.status', 'Normal')
        ->assertJsonPath('reading.color', '#EAB308')
        ->assertJsonPath('recommendation.status', 'Normal')
        ->assertJsonCount(3, 'recommendation.recommendations');

    $this->assertDatabaseHas('readings', [
        'device_id' => 'esp32-room-204',
        'status' => 'Normal',
    ]);
});
```

- [ ] **Step 2: Write the failing latest-endpoint test**

```php
<?php
// tests/Feature/LatestReadingTest.php

use App\Models\Reading;
use App\Models\User;

it('requires authentication', function (): void {
    $response = $this->getJson('/api/readings/latest');

    $response->assertStatus(401);
});

it('returns the most recent reading for an authenticated user', function (): void {
    $user = User::factory()->create();
    Reading::factory()->create(['created_at' => now()->subMinutes(10), 'aqi' => 20.0]);
    $latest = Reading::factory()->create(['created_at' => now(), 'aqi' => 80.0, 'status' => 'Normal', 'color' => '#EAB308']);

    $response = $this->actingAs($user)->getJson('/api/readings/latest');

    $response->assertOk()
        ->assertJsonPath('reading.id', $latest->id)
        ->assertJsonPath('reading.aqi', 80)
        ->assertJsonPath('recommendation.status', 'Normal');
});

it('returns 404 when no readings exist yet', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson('/api/readings/latest');

    $response->assertStatus(404);
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --filter=ReadingIngestionTest`
Run: `php artisan test --filter=LatestReadingTest`
Expected: both FAIL — route `/api/readings` doesn't exist (404 instead of 401/422/201).

- [ ] **Step 4: Add `DEVICE_API_KEY` to env files**

Append to `.env` and `.env.example`:

```
DEVICE_API_KEY=change-me-to-a-long-random-string
```

Add to `config/services.php` inside the returned array:

```php
    'device' => [
        'key' => env('DEVICE_API_KEY'),
    ],
```

- [ ] **Step 5: Write the middleware**

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyDeviceApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.device.key');

        if (! $expected || ! hash_equals((string) $expected, (string) $request->header('X-Device-Key'))) {
            return response()->json(['message' => 'Invalid device key.'], 401);
        }

        return $next($request);
    }
}
```

- [ ] **Step 6: Write the form request**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization is handled by the VerifyDeviceApiKey middleware
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'temperature' => ['required', 'numeric'],
            'humidity' => ['required', 'numeric'],
            'co' => ['required', 'numeric'],
            'nitrogen' => ['required', 'numeric'],
            'pm25' => ['required', 'numeric'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 7: Write the API resource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'co' => $this->co,
            'nitrogen' => $this->nitrogen,
            'pm25' => $this->pm25,
            'aqi' => $this->aqi,
            'status' => $this->status,
            'color' => $this->color,
            'device_id' => $this->device_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 8: Write the controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReadingRequest;
use App\Http\Resources\ReadingResource;
use App\Models\Reading;
use App\Services\AqiCalculator;
use App\Services\RecommendationService;
use Illuminate\Http\JsonResponse;

final class ReadingController extends Controller
{
    public function __construct(
        private readonly AqiCalculator $aqiCalculator,
        private readonly RecommendationService $recommendationService,
    ) {}

    public function store(StoreReadingRequest $request): JsonResponse
    {
        $data = $request->validated();

        $aqi = $this->aqiCalculator->calculateOverallAqi([
            'pm25' => (float) $data['pm25'],
            'co' => (float) $data['co'],
            'nitrogen' => (float) $data['nitrogen'],
        ]);

        $recommendation = $this->recommendationService->getRecommendation($aqi);

        $reading = Reading::create([
            ...$data,
            'aqi' => $aqi,
            'status' => $recommendation['status'],
            'color' => $recommendation['color'],
        ]);

        return (new ReadingResource($reading))
            ->additional(['recommendation' => $recommendation])
            ->response()
            ->setStatusCode(201);
    }

    public function latest(): JsonResponse
    {
        $reading = Reading::latest()->first();

        if (! $reading) {
            return response()->json(['message' => 'No readings yet.'], 404);
        }

        $recommendation = $this->recommendationService->getRecommendation($reading->aqi);

        return (new ReadingResource($reading))
            ->additional(['recommendation' => $recommendation])
            ->response();
    }
}
```

Note: `ReadingResource` wraps its data under a top-level `reading` key automatically because Laravel wraps single resources in a key derived from the resource name (`Reading` -> `reading`) unless `JsonResource::withoutWrapping()` is called globally — confirm this is NOT called anywhere in `AppServiceProvider`. If it is, wrap explicitly with `['reading' => new ReadingResource($reading), 'recommendation' => $recommendation]` instead of `additional()`.

- [ ] **Step 9: Register routes**

Create `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\ReadingController;
use Illuminate\Support\Facades\Route;

Route::middleware('device.key')->post('/readings', [ReadingController::class, 'store']);
```

Modify `bootstrap/app.php`:

```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'device.key' => \App\Http\Middleware\VerifyDeviceApiKey::class,
        ]);
    })
```

Append to `routes/web.php` (session-authenticated, so the dashboard's `fetch()` calls ride the existing login cookie — no Sanctum needed):

```php
use App\Http\Controllers\Api\ReadingController;

Route::middleware(['auth', 'verified'])->prefix('api')->group(function () {
    Route::get('readings/latest', [ReadingController::class, 'latest'])->name('readings.latest');
});
```

- [ ] **Step 10: Run tests to verify they pass**

Run: `php artisan test --filter=ReadingIngestionTest`
Run: `php artisan test --filter=LatestReadingTest`
Expected: PASS (4 + 3 tests).

Run: `php artisan test` (full suite) and `php artisan route:list --path=api` to confirm both routes show with correct verbs and no regressions elsewhere.

- [ ] **Step 11: Commit**

```bash
git add app/Http/Middleware/VerifyDeviceApiKey.php app/Http/Requests/StoreReadingRequest.php app/Http/Resources/ReadingResource.php app/Http/Controllers/Api/ReadingController.php routes/api.php routes/web.php bootstrap/app.php config/services.php .env.example tests/Feature/ReadingIngestionTest.php tests/Feature/LatestReadingTest.php
git commit -m "feat: add device-authenticated ingestion API and session-authenticated latest endpoint"
```

---

## Task 6: ESP32 firmware sketch

**Files:**
- Create: `firmware/airzen_esp32/airzen_esp32.ino`

**Interfaces:**
- Consumes: `POST /api/readings` contract from Task 5 (`{temperature, humidity, co, nitrogen, pm25, device_id}` + `X-Device-Key` header).
- Produces: nothing consumed by later software tasks — this is the physical-device deliverable, verified by hardware, not Pest.

- [ ] **Step 1: Write the firmware**

```cpp
// firmware/airzen_esp32/airzen_esp32.ino
//
// AirZen sensor node. Reads DHT11 (temp/humidity), MQ-7 (CO), MQ-135 (NOx),
// and a Keyestudio GP2Y1014AU dust sensor (PM2.5), then POSTs a JSON payload
// to the AirZen ingestion API every 10 seconds.

#include <WiFi.h>
#include <HTTPClient.h>
#include <DHT.h>

// ---- Configuration (edit these before flashing) ----
const char* WIFI_SSID = "YOUR_WIFI_SSID";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";
const char* SERVER_URL = "http://YOUR_SERVER_HOST/api/readings"; // e.g. https://airzen.up.railway.app/api/readings
const char* DEVICE_KEY = "change-me-to-a-long-random-string"; // must match DEVICE_API_KEY on the server
const char* DEVICE_ID = "esp32-room-204";

// ---- Pins ----
#define DHTPIN 4
#define DHTTYPE DHT11
#define MQ7_PIN 34     // CO, analog
#define MQ135_PIN 35   // NOx, analog
#define DUST_PIN 32    // PM2.5, analog (GP2Y1014AU)

DHT dht(DHTPIN, DHTTYPE);

const unsigned long POST_INTERVAL_MS = 10000;
unsigned long lastPostAt = 0;

void connectWiFi() {
  Serial.print("Connecting to WiFi: ");
  Serial.println(WIFI_SSID);
  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);

  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }

  Serial.println();
  Serial.print("WiFi connected, IP: ");
  Serial.println(WiFi.localIP());
}

// Converts a raw MQ-7 analog reading to an approximate CO ppm value.
// Calibration curve derived from the MQ-7 datasheet Rs/Ro vs ppm chart;
// replace the constants below after calibrating against a known CO source.
float readCoPpm() {
  int raw = analogRead(MQ7_PIN);
  float voltage = raw * (3.3 / 4095.0);
  float ppm = voltage * 30.0; // placeholder linear approximation - calibrate per datasheet
  return ppm;
}

// Converts a raw MQ-135 analog reading to an approximate NOx index value.
// Same caveat as readCoPpm(): replace with the datasheet's Rs/Ro curve once
// calibrated against a reference gas source.
float readNitrogenIndex() {
  int raw = analogRead(MQ135_PIN);
  float voltage = raw * (3.3 / 4095.0);
  float index = voltage * 60.0; // placeholder linear approximation - calibrate per datasheet
  return index;
}

// Converts a raw GP2Y1014AU analog reading to an approximate PM2.5 ug/m3
// value, using the linear approximation from the Sharp/Keyestudio datasheet:
// Voltage (mV) = 0.17 * dust_density (ug/m3) + 0.6
float readPm25() {
  int raw = analogRead(DUST_PIN);
  float voltage = raw * (3300.0 / 4095.0); // millivolts
  float density = (voltage - 600.0) / 0.17;
  return density < 0 ? 0.0 : density;
}

void postReading(float temperature, float humidity, float co, float nitrogen, float pm25) {
  if (WiFi.status() != WL_CONNECTED) {
    Serial.println("WiFi dropped, reconnecting before POST...");
    connectWiFi();
  }

  HTTPClient http;
  http.begin(SERVER_URL);
  http.addHeader("Content-Type", "application/json");
  http.addHeader("X-Device-Key", DEVICE_KEY);

  String payload = String("{") +
    "\"temperature\":" + String(temperature, 1) + "," +
    "\"humidity\":" + String(humidity, 1) + "," +
    "\"co\":" + String(co, 2) + "," +
    "\"nitrogen\":" + String(nitrogen, 1) + "," +
    "\"pm25\":" + String(pm25, 1) + "," +
    "\"device_id\":\"" + String(DEVICE_ID) + "\"" +
    "}";

  Serial.print("POST payload: ");
  Serial.println(payload);

  int statusCode = http.POST(payload);
  Serial.print("Server responded with status: ");
  Serial.println(statusCode);

  if (statusCode > 0) {
    Serial.println(http.getString());
  }

  http.end();
}

void setup() {
  Serial.begin(115200);
  dht.begin();
  connectWiFi();
}

void loop() {
  if (millis() - lastPostAt >= POST_INTERVAL_MS) {
    lastPostAt = millis();

    float temperature = dht.readTemperature();
    float humidity = dht.readHumidity();

    if (isnan(temperature) || isnan(humidity)) {
      Serial.println("Failed to read from DHT11, skipping this cycle.");
      return;
    }

    float co = readCoPpm();
    float nitrogen = readNitrogenIndex();
    float pm25 = readPm25();

    postReading(temperature, humidity, co, nitrogen, pm25);
  }
}
```

- [ ] **Step 2: Verify (hardware, no automated test)**

1. Install the `DHT sensor library` (Adafruit) via Arduino IDE Library Manager.
2. Set `WIFI_SSID`, `WIFI_PASSWORD`, `SERVER_URL`, and `DEVICE_KEY` to match your `.env`'s `DEVICE_API_KEY` from Task 5.
3. Flash to the ESP32, open Serial Monitor at 115200 baud.
4. Confirm: `WiFi connected, IP: ...` prints, then every ~10s a `POST payload: {...}` line followed by `Server responded with status: 201`.
5. Confirm in Postgres/SQLite (`php artisan tinker` -> `App\Models\Reading::latest()->first()`) that a row landed with a non-zero `aqi`.

- [ ] **Step 3: Commit**

```bash
git add firmware/airzen_esp32/airzen_esp32.ino
git commit -m "feat: add ESP32 firmware sketch for AirZen sensor node"
```

---

## Task 7: Dashboard UI — gauge, recommendations, sensor strip, polling

**Files:**
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php` (swap the `dashboard` closure for `DashboardController@index`)
- Create: `resources/js/composables/useLatestReading.ts`
- Create: `resources/js/components/AqiGauge.vue`
- Modify: `resources/js/pages/Dashboard.vue`
- Modify: `tailwind.config.js` (brand colors + heading font)
- Modify: `resources/views/app.blade.php` (Google Fonts link)
- Test: `tests/Feature/DashboardPropsTest.php`

**Interfaces:**
- Consumes: `GET /api/readings/latest` response shape from Task 5 (`{reading: {...}, recommendation: {...}}`).
- Produces: `useLatestReading()` composable returning `{ reading: Ref<Reading|null>, recommendation: Ref<Recommendation|null> }`, consumed by Task 8's hero scene color sync.

- [ ] **Step 1: Write the failing backend contract test**

```php
<?php
// tests/Feature/DashboardPropsTest.php

use App\Models\Reading;
use App\Models\User;

it('passes the latest reading and recommendation as initial dashboard props', function (): void {
    $user = User::factory()->create();
    Reading::factory()->create(['aqi' => 30.0, 'status' => 'Good', 'color' => '#22C55E']);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('initialReading')
        ->where('initialReading.aqi', 30.0)
        ->has('initialRecommendation')
        ->where('initialRecommendation.status', 'Good'));
});

it('passes null props when no readings exist yet', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('initialReading', null)
        ->where('initialRecommendation', null));
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DashboardPropsTest`
Expected: FAIL — current `dashboard` route closure passes no `initialReading`/`initialRecommendation` props, so `has('initialReading')` fails.

- [ ] **Step 3: Write the controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\ReadingResource;
use App\Models\Reading;
use App\Services\RecommendationService;
use Inertia\Inertia;
use Inertia\Response;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly RecommendationService $recommendationService,
    ) {}

    public function index(): Response
    {
        $reading = Reading::latest()->first();

        return Inertia::render('Dashboard', [
            'initialReading' => $reading ? (new ReadingResource($reading))->resolve() : null,
            'initialRecommendation' => $reading ? $this->recommendationService->getRecommendation($reading->aqi) : null,
        ]);
    }
}
```

- [ ] **Step 4: Wire the route**

Replace in `routes/web.php`:

```php
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');
```

with:

```php
use App\Http\Controllers\DashboardController;

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=DashboardPropsTest`
Expected: PASS (2 tests).

- [ ] **Step 6: Add brand colors + heading font**

Modify `tailwind.config.js` `theme.extend`:

```js
        fontFamily: {
            sans: ['Instrument Sans', ...defaultTheme.fontFamily.sans],
            heading: ['Space Grotesk', ...defaultTheme.fontFamily.sans],
        },
        colors: {
            // ...existing tokens unchanged...
            airzen: {
                bg: '#F4FAF6',
                card: '#FFFFFF',
                green: '#12A454',
                'green-dark': '#0B7A3F',
                'green-light': '#E4F7EC',
                muted: '#5C7A67',
                good: '#22C55E',
                normal: '#EAB308',
                sensitive: '#F59E0B',
                unhealthy: '#EF4444',
                'very-unhealthy': '#A855F7',
            },
        },
```

Add Google Fonts to `resources/views/app.blade.php` `<head>` (alongside the existing font preconnects, if any):

```html
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Public+Sans:wght@400;500;600&display=swap" rel="stylesheet">
```

- [ ] **Step 7: Write the polling composable**

```ts
// resources/js/composables/useLatestReading.ts
import { onMounted, onUnmounted, ref, type Ref } from 'vue';

export interface Reading {
    id: number;
    temperature: number;
    humidity: number;
    co: number;
    nitrogen: number;
    pm25: number;
    aqi: number;
    status: string;
    color: string;
    device_id: string | null;
    created_at: string;
}

export interface Recommendation {
    status: string;
    color: string;
    recommendations: string[];
}

export function useLatestReading(
    initialReading: Reading | null,
    initialRecommendation: Recommendation | null,
    intervalMs = 8000,
): { reading: Ref<Reading | null>; recommendation: Ref<Recommendation | null> } {
    const reading = ref<Reading | null>(initialReading);
    const recommendation = ref<Recommendation | null>(initialRecommendation);
    let timer: ReturnType<typeof setInterval> | undefined;

    async function poll(): Promise<void> {
        try {
            const response = await fetch('/api/readings/latest', {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                return;
            }

            const body = await response.json();
            reading.value = body.reading;
            recommendation.value = body.recommendation;
        } catch {
            // network hiccup - keep showing the last known reading, try again next tick
        }
    }

    onMounted(() => {
        timer = setInterval(poll, intervalMs);
    });

    onUnmounted(() => {
        if (timer) clearInterval(timer);
    });

    return { reading, recommendation };
}
```

- [ ] **Step 8: Write the gauge component**

```vue
<!-- resources/js/components/AqiGauge.vue -->
<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    aqi: number;
    color: string;
}>();

const angle = computed(() => Math.min(props.aqi, 300) * 1.2);

const gaugeStyle = computed(() => ({
    background: `conic-gradient(
        #22C55E 0deg 60deg,
        #EAB308 60deg 120deg,
        #F59E0B 120deg 180deg,
        #EF4444 180deg 240deg,
        #A855F7 240deg 360deg
    )`,
}));

const markerStyle = computed(() => ({
    transform: `rotate(${angle.value}deg) translate(0, -110px) rotate(-${angle.value}deg)`,
    borderColor: props.color,
}));
</script>

<template>
    <div class="relative flex h-64 w-64 items-center justify-center rounded-full" :style="gaugeStyle">
        <div class="absolute inset-3 rounded-full bg-airzen-card"></div>
        <div class="absolute left-1/2 top-1/2 h-4 w-4 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white shadow" :style="markerStyle"></div>
        <div class="relative flex flex-col items-center font-heading">
            <span class="text-5xl font-bold" :style="{ color: props.color }">{{ Math.round(props.aqi) }}</span>
            <span class="text-sm font-medium text-airzen-muted">AQI</span>
        </div>
    </div>
</template>
```

- [ ] **Step 9: Rewrite the dashboard page**

```vue
<!-- resources/js/pages/Dashboard.vue -->
<script setup lang="ts">
import AqiGauge from '@/components/AqiGauge.vue';
import { useLatestReading, type Reading, type Recommendation } from '@/composables/useLatestReading';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    initialReading: Reading | null;
    initialRecommendation: Recommendation | null;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const { reading, recommendation } = useLatestReading(props.initialReading, props.initialRecommendation);

const sensorStrip = computed(() => [
    { label: 'Temperature', value: reading.value ? `${reading.value.temperature.toFixed(1)} \u00b0C` : '--' },
    { label: 'Humidity', value: reading.value ? `${reading.value.humidity.toFixed(1)} %` : '--' },
    { label: 'CO', value: reading.value ? `${reading.value.co.toFixed(2)} ppm` : '--' },
    { label: 'NOx', value: reading.value ? `${reading.value.nitrogen.toFixed(1)}` : '--' },
    { label: 'PM2.5', value: reading.value ? `${reading.value.pm25.toFixed(1)} \u00b5g/m\u00b3` : '--' },
]);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 rounded-xl bg-airzen-bg p-4">
            <div class="grid gap-6 md:grid-cols-2">
                <div class="flex flex-col items-center gap-4 rounded-[22px] bg-airzen-card p-8 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]">
                    <AqiGauge v-if="reading" :aqi="reading.aqi" :color="reading.color" />
                    <div v-if="recommendation" class="rounded-full px-4 py-1 text-sm font-medium" :style="{ backgroundColor: recommendation.color + '22', color: recommendation.color }">
                        {{ recommendation.status }}
                    </div>
                </div>

                <div class="rounded-[22px] bg-airzen-card p-6 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]">
                    <h2 class="mb-4 font-heading text-lg font-semibold">Recommendations</h2>
                    <ul class="flex flex-col gap-3">
                        <li
                            v-for="(item, index) in recommendation?.recommendations ?? []"
                            :key="index"
                            class="border-l-4 pl-3 text-sm text-airzen-muted"
                            :style="{ borderColor: recommendation?.color }"
                        >
                            {{ item }}
                        </li>
                    </ul>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
                <div v-for="sensor in sensorStrip" :key="sensor.label" class="rounded-[22px] bg-airzen-card p-4 text-center shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]">
                    <div class="text-xs uppercase tracking-wide text-airzen-muted">{{ sensor.label }}</div>
                    <div class="mt-1 font-heading text-xl font-semibold">{{ sensor.value }}</div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
```

- [ ] **Step 10: Run full backend suite + manual browser check**

Run: `php artisan test`
Expected: PASS, no regressions.

Manual (use the `run` skill or `npm run dev` + `php artisan serve`): log in, POST a test reading via `curl -X POST http://127.0.0.1:8000/api/readings -H "X-Device-Key: $DEVICE_API_KEY" -H "Content-Type: application/json" -d '{"temperature":25,"humidity":50,"co":4.5,"nitrogen":40,"pm25":10}'`, then load `/dashboard` and confirm the gauge, chip, recommendation list, and sensor strip render and update after ~8s when a second `curl` POST changes the AQI band.

- [ ] **Step 11: Commit**

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php resources/js/composables/useLatestReading.ts resources/js/components/AqiGauge.vue resources/js/pages/Dashboard.vue tailwind.config.js resources/views/app.blade.php tests/Feature/DashboardPropsTest.php
git commit -m "feat: build live AQI dashboard with gauge, recommendations, and sensor strip"
```

---

## Task 8: Animated hero scene (landing page)

**Files:**
- Create: `resources/js/components/HeroScene.vue`
- Modify: `resources/js/pages/Welcome.vue`

**Interfaces:**
- Consumes: nothing from the backend (static illustration); optionally accepts a `color` prop for the AQI-synced accent, defaulting to the "Good" green.
- Produces: nothing consumed elsewhere — purely presentational, verified manually.

- [ ] **Step 1: Write the scene component**

```vue
<!-- resources/js/components/HeroScene.vue -->
<script setup lang="ts">
withDefaults(defineProps<{ color?: string }>(), { color: '#22C55E' });
</script>

<template>
    <div class="rounded-[22px] bg-airzen-card p-6 shadow-[0_1px_2px_rgba(14,36,25,0.04),0_16px_32px_-20px_rgba(14,36,25,0.12)]">
        <svg viewBox="0 0 400 300" class="w-full" :style="{ '--device-color': color }">
            <!-- floor -->
            <polygon points="60,220 200,290 340,220 200,150" fill="#E3F6EA" stroke="#B7DFC5" stroke-width="2" />
            <!-- left wall (shadow) -->
            <polygon points="60,220 200,150 200,40 60,110" fill="#C7E7D4" stroke="#B7DFC5" stroke-width="2" />
            <!-- right wall (light) -->
            <polygon points="340,220 200,150 200,40 340,110" fill="#D8F0E1" stroke="#B7DFC5" stroke-width="2" />

            <!-- window on left wall -->
            <rect x="95" y="80" width="50" height="50" rx="4" fill="#FFF6D9" stroke="#E8D48A" stroke-width="2" />

            <!-- bookshelf on left wall -->
            <rect x="80" y="150" width="45" height="55" fill="#BFE3CE" stroke="#9FCBAF" stroke-width="1.5" />
            <line x1="80" y1="168" x2="125" y2="168" stroke="#9FCBAF" stroke-width="1.5" />
            <line x1="80" y1="186" x2="125" y2="186" stroke="#9FCBAF" stroke-width="1.5" />

            <!-- potted plant on floor -->
            <ellipse cx="270" cy="240" rx="14" ry="7" fill="#0B7A3F" opacity="0.5" />
            <rect x="262" y="225" width="16" height="15" rx="2" fill="#8A5A3B" />
            <circle cx="270" cy="215" r="12" fill="#2FAE6A" />

            <!-- sensor device on right wall -->
            <g transform="translate(255,90)">
                <rect x="0" y="0" width="40" height="26" rx="6" fill="#FFFFFF" stroke="var(--device-color)" stroke-width="2" />
                <circle class="airzen-led" cx="20" cy="13" r="4" fill="var(--device-color)" />

                <!-- ping rings -->
                <circle class="airzen-ping airzen-ping-1" cx="20" cy="13" r="6" fill="none" stroke="var(--device-color)" stroke-width="2" />
                <circle class="airzen-ping airzen-ping-2" cx="20" cy="13" r="6" fill="none" stroke="var(--device-color)" stroke-width="2" />

                <!-- wifi arcs -->
                <path class="airzen-wifi airzen-wifi-1" d="M 10 -6 A 14 14 0 0 1 30 -6" fill="none" stroke="var(--device-color)" stroke-width="2" stroke-linecap="round" />
                <path class="airzen-wifi airzen-wifi-2" d="M 4 -14 A 22 22 0 0 1 36 -14" fill="none" stroke="var(--device-color)" stroke-width="2" stroke-linecap="round" opacity="0.7" />
                <path class="airzen-wifi airzen-wifi-3" d="M -2 -22 A 30 30 0 0 1 42 -22" fill="none" stroke="var(--device-color)" stroke-width="2" stroke-linecap="round" opacity="0.5" />

                <!-- drifting particles -->
                <circle class="airzen-particle airzen-particle-1" cx="8" cy="30" r="2" fill="var(--device-color)" />
                <circle class="airzen-particle airzen-particle-2" cx="20" cy="34" r="2" fill="var(--device-color)" />
                <circle class="airzen-particle airzen-particle-3" cx="32" cy="30" r="2" fill="var(--device-color)" />
            </g>
        </svg>
        <p class="mt-3 text-center text-sm text-airzen-muted">Sensor mounted in Library Room 204 &middot; actively sampling</p>
    </div>
</template>

<style scoped>
.airzen-led {
    animation: airzen-pulse 1.8s ease-in-out infinite;
    transform-origin: center;
}

.airzen-ping {
    transform-origin: center;
    animation: airzen-ping-expand 2.4s ease-out infinite;
}
.airzen-ping-2 {
    animation-delay: 1.2s;
}

.airzen-wifi {
    animation: airzen-wifi-fade 2s ease-in-out infinite;
}
.airzen-wifi-2 {
    animation-delay: 0.25s;
}
.airzen-wifi-3 {
    animation-delay: 0.5s;
}

.airzen-particle {
    animation: airzen-drift 3s ease-in infinite;
}
.airzen-particle-2 {
    animation-delay: 1s;
}
.airzen-particle-3 {
    animation-delay: 2s;
}

@keyframes airzen-pulse {
    0%, 100% { opacity: 0.6; transform: scale(0.9); }
    50% { opacity: 1; transform: scale(1.15); }
}

@keyframes airzen-ping-expand {
    0% { r: 6; opacity: 0.8; }
    100% { r: 22; opacity: 0; }
}

@keyframes airzen-wifi-fade {
    0%, 100% { opacity: 0.15; }
    50% { opacity: 0.9; }
}

@keyframes airzen-drift {
    0% { transform: translateY(0); opacity: 0.8; }
    100% { transform: translateY(-24px); opacity: 0; }
}
</style>
```

- [ ] **Step 2: Wire it into the landing page**

Read `resources/js/pages/Welcome.vue` first to find the hero headline/CTA block, then add `HeroScene` as the second column of a two-column grid (stacked on mobile):

```vue
<script setup lang="ts">
import HeroScene from '@/components/HeroScene.vue';
// ...existing imports...
</script>
```

```html
<div class="grid gap-8 md:grid-cols-2 md:items-center">
    <div>
        <!-- existing headline / CTA markup stays here -->
    </div>
    <HeroScene />
</div>
```

- [ ] **Step 3: Manual verification**

Run `npm run dev` + `php artisan serve`, visit `/`, confirm: the LED pulses continuously, the 3 wifi arcs fade in a staggered sequence, the 2 ping rings expand and fade on a loop, and the 3 particles drift upward and fade. Resize to mobile width (~400px) and confirm the scene stacks below the headline instead of beside it.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/HeroScene.vue resources/js/pages/Welcome.vue
git commit -m "feat: add animated isometric hero scene to landing page"
```

---

## Task 9: Restyle the login page

**Files:**
- Modify: `resources/js/pages/auth/Login.vue`

**Interfaces:**
- Consumes: nothing new (same `form`/`route('login')` logic already present).
- Produces: nothing consumed elsewhere — presentational only.

- [ ] **Step 1: Apply the AirZen visual treatment**

Modify the `<AuthBase>` usage and inner classes in `resources/js/pages/auth/Login.vue`. Keep all existing `<script setup>` logic (form, submit, props) unchanged; only touch the template's classes:

```html
<AuthBase title="Log in to AirZen" description="Access the dashboard and reading history." class="bg-airzen-bg">
    <Head title="Log in" />

    <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="flex flex-col gap-6 rounded-[22px] bg-airzen-card p-8 shadow-[0_30px_60px_-20px_rgba(14,36,25,0.3)]">
        <h1 class="font-heading text-2xl font-semibold">Log in to AirZen</h1>
        <p class="-mt-4 text-sm text-airzen-muted">Access the dashboard and reading history.</p>

        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autofocus
                    tabindex="1"
                    autocomplete="email"
                    v-model="form.email"
                    placeholder="email@example.com"
                    class="rounded-[10px] border-transparent bg-[#EEF8F1] focus:border-airzen-green"
                />
                <InputError :message="form.errors.email" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between">
                    <Label for="password">Password</Label>
                    <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm" tabindex="5"> Forgot password? </TextLink>
                </div>
                <Input
                    id="password"
                    type="password"
                    required
                    tabindex="2"
                    autocomplete="current-password"
                    v-model="form.password"
                    placeholder="Password"
                    class="rounded-[10px] border-transparent bg-[#EEF8F1] focus:border-airzen-green"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div class="flex items-center justify-between" tabindex="3">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" v-model:checked="form.remember" tabindex="4" />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button type="submit" class="mt-4 w-full bg-airzen-green hover:bg-airzen-green-dark" tabindex="4" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Sign in
            </Button>
        </div>

        <div class="text-center text-sm text-airzen-muted">
            Don't have an account?
            <TextLink :href="route('register')" :tabindex="5">Sign up</TextLink>
        </div>
    </form>
</AuthBase>
```

Note: if `AuthLayout.vue` (aliased `AuthBase`) doesn't forward a `class` attribute to its root background element, open `resources/js/layouts/auth/AuthSimpleLayout.vue` (or whichever variant `AuthLayout.vue` delegates to) and add `bg-airzen-bg` directly to its outer wrapper `<div>` instead of relying on attribute fallthrough.

- [ ] **Step 2: Manual verification**

Run `npm run dev` + `php artisan serve`, visit `/login`. Confirm: mint background, white 22px-radius card with the heavier drop shadow, Space Grotesk heading, green-tinted inputs that switch border to `#12A454` on focus, full-width green "Sign in" button that darkens to `#0B7A3F` on hover. Confirm existing login flow still works (submits, shows validation errors, redirects to `/dashboard`).

Run: `php artisan test --filter=AuthenticationTest` to confirm no backend regression (this task only touches template markup, not form logic).
Expected: PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/auth/Login.vue
git commit -m "style: restyle login page to match AirZen mockup"
```

---

## Task 10: Deploy to Railway

**Files:**
- Create: `Procfile`
- Modify: `.env.example` (document production-only vars if new ones are needed)

**Interfaces:**
- Consumes: everything from Tasks 1-9 (this is the final integration step).
- Produces: a public URL used by Task 6's firmware `SERVER_URL` constant.

- [ ] **Step 1: Add a start command for Railway**

Create `Procfile`:

```
web: php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=${PORT:-8000}
```

- [ ] **Step 2: Provision Railway infrastructure (manual, via Railway dashboard/CLI)**

1. Create a new Railway project, add a **PostgreSQL** plugin to it.
2. Add a service from this GitHub repo (`CodeDecoder13/AirZen`, root directory `my-app`).
3. In the service's Variables tab, set:
   - `DB_CONNECTION=pgsql`
   - `DB_HOST=${{Postgres.PGHOST}}`
   - `DB_PORT=${{Postgres.PGPORT}}`
   - `DB_DATABASE=${{Postgres.PGDATABASE}}`
   - `DB_USERNAME=${{Postgres.PGUSER}}`
   - `DB_PASSWORD=${{Postgres.PGPASSWORD}}`
   - `APP_KEY` (generate locally with `php artisan key:generate --show` and paste the value)
   - `APP_ENV=production`
   - `APP_DEBUG=false`
   - `DEVICE_API_KEY=<a long random string, matching the ESP32 firmware's `DEVICE_KEY`>`
4. Deploy. Railway runs `composer install`/`npm run build` via Nixpacks auto-detection, then executes the `Procfile`'s `web` command, which runs migrations then serves the app.

- [ ] **Step 3: Verify the live deployment**

Run (replacing with the assigned Railway domain):

```bash
curl -i https://<your-app>.up.railway.app/up
```

Expected: `HTTP/1.1 200 OK` (Laravel's built-in health check route, registered via `health: '/up'` in `bootstrap/app.php`).

```bash
curl -X POST https://<your-app>.up.railway.app/api/readings \
  -H "X-Device-Key: <DEVICE_API_KEY>" \
  -H "Content-Type: application/json" \
  -d '{"temperature":25,"humidity":50,"co":4.5,"nitrogen":40,"pm25":10}'
```

Expected: `201` with a JSON body containing `reading.aqi` and `recommendation.status`.

- [ ] **Step 4: Point the ESP32 at production**

In `firmware/airzen_esp32/airzen_esp32.ino`, update:

```cpp
const char* SERVER_URL = "https://<your-app>.up.railway.app/api/readings";
```

Re-flash the device, confirm Serial output shows `Server responded with status: 201` against the production URL.

- [ ] **Step 5: Commit**

```bash
git add Procfile firmware/airzen_esp32/airzen_esp32.ino
git commit -m "chore: add Railway Procfile and point firmware at production URL"
```

---

## Self-Review Notes

- **Spec coverage:** Task 1 covers Phase 1's boot/auth check; Tasks 2-4 cover Phase 1's schema + services; Task 5 covers Phase 2's ingestion endpoint + security; Task 6 covers Phase 2's firmware; Tasks 7-9 cover Phase 3's dashboard, history-ready data model (`created_at` indexed for future range queries), auth screens, and responsive layout; Task 10 covers Phase 5's deployment. Phase 4 (load-testing, cross-browser QA, offline/malformed-payload handling) is intentionally **not** a separate task here — `StoreReadingRequest` validation (Task 5) already covers malformed payloads, and `VerifyDeviceApiKey` covers unauthorized posts; load-testing and cross-browser QA are manual/exploratory activities better run once Tasks 1-10 are live, not scriptable as plan steps. Phase 6 (KNN/ML) is explicitly future work per the spec and out of scope for this plan.
- **Placeholder scan:** every code step above is complete, runnable code; the two "placeholder linear approximation" comments in Task 6's firmware are flagged in-code as calibration TODOs because the spec itself (Section 4 risk table) says sensor calibration curves are an open item pending datasheet-based calibration — this is a spec-acknowledged gap, not a plan shortcut.
- **Type consistency:** `Reading` fillable/cast fields (Task 2) match `StoreReadingRequest` rules, `ReadingResource` fields, and the `useLatestReading.ts` `Reading` interface (Task 5, Task 7) exactly. `RecommendationService::getRecommendation()`'s return shape (`status`, `color`, `recommendations`) matches the `Recommendation` TS interface and every test's JSON path assertions.
