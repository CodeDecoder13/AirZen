# Digest Recipients Table + History Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the per-user "daily digest" toggle with a standalone, sidebar-visible table of recipient email addresses (add/remove any email, not just registered users), and add a History page that tracks all indoor air quality readings with a customizable-range trend chart, a real AQI-band pie chart, and a filterable/paginated raw reading log.

**Architecture:** Two new top-level authenticated features, each following the existing app's pattern exactly (Inertia page + thin controller + a Service class holding the real query/aggregation logic, tested with PHPUnit-style Feature tests - this codebase does not use Pest despite `tests/Pest.php` existing as unused scaffold, a fact already established earlier in this project). The digest recipients feature drops `users.daily_digest_enabled` entirely in favor of its own `digest_recipients` table. The history feature extends the existing `AqiHistoryService` (adds `dailyAverages(int $days)`, generalizing the existing `weeklyAverageAqi()` which becomes a one-line wrapper, plus `bandDistribution(int $days)` and `paginatedReadings(array $filters, int $perPage)`) rather than creating a parallel service, since it is the exact same domain (PM2.5-driven AQI over a day range) just parameterized. History filtering uses a normal Inertia server visit (`router.get` with query params, one `Inertia::render` per request) rather than a separate JSON API, matching this Laravel+Inertia app's idiomatic pattern more closely than the polling composables used for the always-live dashboard.

**Tech Stack:** Laravel 12 (PHP 8.2+, typed, Eloquent-only), Vue 3 `<script setup>`, Tailwind arbitrary values, `lucide-vue-next` icons, native CSS `conic-gradient` for the pie chart (same technique already proven in `AqiGauge.vue` - no charting library). AirZen v2 brand tokens throughout the new pages (cream `#F7F8F1`, dark green `#1A3C30`, primary `#2A8362`, lime `#C6DF58`, Fraunces + DM Sans), matching `Dashboard.vue`/`Welcome.vue`.

**Spec:** This plan is self-contained (direct implementation of the user's request). Prior art: `app/Services/AqiHistoryService.php`, `app/Services/RecommendationService.php`, `app/Mail/MorningAirQualityDigest.php`, `app/Console/Commands/SendMorningDigest.php`, `resources/js/pages/Dashboard.vue`, `resources/js/components/AqiSparkline.vue`, `resources/js/components/AppSidebar.vue`.

## Global Constraints

- Brand tokens (exact hex): background `#F7F8F1`, dark green `#1A3C30`, primary `#2A8362`, primary hover `#226b4f`, lime `#C6DF58`, body text `#1D352D`, muted `#6B8577`. Fonts: `.az2-display` (Fraunces) for headings, `.az2-sans` (DM Sans) for body - copy these two scoped classes verbatim into any new page's `<style scoped>` block exactly as they exist in `Dashboard.vue`, do not redefine them differently.
- AQI band colors (real, semantic, not brand decoration - do not restyle these to brand colors): Good `#22C55E`, Normal `#EAB308`, Unhealthy for Sensitive Groups `#F59E0B`, Unhealthy for All Groups `#EF4444`, Very Unhealthy `#A855F7` - these are `RecommendationService`'s actual band colors, reuse them exactly in the pie chart.
- Every reading-derived number must come from real `sensor_readings` data. No fabricated sample data anywhere in this plan (unlike `Welcome.vue`'s pre-auth marketing preview, these are all authenticated, real-data pages).
- Only animate `transform`/`opacity` (already-established constraint, twice enforced by the impeccable detector in this codebase).
- Existing tests that reference `users.daily_digest_enabled` (`tests/Feature/Settings/NotificationsUpdateTest.php`, `tests/Feature/SendMorningDigestCommandTest.php`) must be updated in the same task that removes the column - never leave a red test suite between tasks.
- Run `php artisan test` after every task. Run the impeccable detector (`node "C:\Users\Rhuzz\.agents\skills\impeccable\scripts\detect.mjs" --json <changed .vue files>`) after every frontend task.

---

### Task 1: `digest_recipients` table + model

**Files:**
- Create: `database/migrations/2026_09_15_100001_create_digest_recipients_table.php`
- Create: `app/Models/DigestRecipient.php`
- Create: `database/factories/DigestRecipientFactory.php`
- Test: `tests/Feature/DigestRecipientModelTest.php`

**Interfaces:**
- Produces: `DigestRecipient` Eloquent model, fillable `['email']`, table columns `id, email (unique string), timestamps`. Consumed by Task 2 (command) and Task 3 (controller).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\DigestRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigestRecipientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_an_email()
    {
        $recipient = DigestRecipient::factory()->create(['email' => 'ops@airzen.test']);

        $this->assertSame('ops@airzen.test', $recipient->email);
        $this->assertDatabaseHas('digest_recipients', ['email' => 'ops@airzen.test']);
    }

    public function test_email_must_be_unique()
    {
        DigestRecipient::factory()->create(['email' => 'dupe@airzen.test']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DigestRecipient::factory()->create(['email' => 'dupe@airzen.test']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DigestRecipientModelTest`
Expected: FAIL - `Class "App\Models\DigestRecipient" does not exist`.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digest_recipients', function (Blueprint $table): void {
            $table->id();
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('digest_recipients');
    }
};
```

- [ ] **Step 4: Write the model and factory**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DigestRecipientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class DigestRecipient extends Model
{
    /** @use HasFactory<DigestRecipientFactory> */
    use HasFactory;

    protected $fillable = ['email'];
}
```

```php
<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DigestRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<DigestRecipient> */
final class DigestRecipientFactory extends Factory
{
    protected $model = DigestRecipient::class;

    public function definition(): array
    {
        return ['email' => $this->faker->unique()->safeEmail()];
    }
}
```

- [ ] **Step 5: Run test to verify it passes, then migrate**

Run: `php artisan test --filter=DigestRecipientModelTest`
Expected: PASS (2 tests).

Run: `php artisan migrate`
Expected: `2026_09_15_100001_create_digest_recipients_table` shows `Ran`.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_09_15_100001_create_digest_recipients_table.php app/Models/DigestRecipient.php database/factories/DigestRecipientFactory.php tests/Feature/DigestRecipientModelTest.php
git commit -m "feat: add digest_recipients table and model"
```

---

### Task 2: Migrate `SendMorningDigest` off `users.daily_digest_enabled`, drop the column, retire the old Settings toggle

**Files:**
- Modify: `app/Console/Commands/SendMorningDigest.php`
- Modify: `tests/Feature/SendMorningDigestCommandTest.php`
- Create: `database/migrations/2026_09_15_100002_drop_daily_digest_enabled_from_users_table.php`
- Modify: `app/Models/User.php` (remove `daily_digest_enabled` from `$fillable` and `casts()`)
- Delete: `app/Http/Controllers/Settings/NotificationController.php`
- Delete: `resources/js/pages/settings/Notifications.vue`
- Delete: `tests/Feature/Settings/NotificationsUpdateTest.php`
- Modify: `routes/settings.php` (remove the two `notifications.*` routes)
- Modify: `resources/js/layouts/settings/Layout.vue` (remove the "Notifications" sidebar-settings nav item - it moves to the main app sidebar in Task 4)

**Interfaces:**
- Consumes: `DigestRecipient` from Task 1.
- Produces: `SendMorningDigest` now sends to every row in `digest_recipients`, no `User` dependency at all.

- [ ] **Step 1: Update the command's test first**

Replace `tests/Feature/SendMorningDigestCommandTest.php` in full:

```php
<?php

namespace Tests\Feature;

use App\Mail\MorningAirQualityDigest;
use App\Models\DigestRecipient;
use App\Models\SensorReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendMorningDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_to_every_recipient_when_data_exists()
    {
        Mail::fake();

        $recipient = DigestRecipient::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertQueued(MorningAirQualityDigest::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });
    }

    public function test_sends_nothing_when_no_readings_exist_today()
    {
        Mail::fake();

        DigestRecipient::factory()->create();

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }

    public function test_sends_nothing_when_there_are_no_recipients()
    {
        Mail::fake();

        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=SendMorningDigestCommandTest`
Expected: FAIL (command still queries `User`, `DigestRecipient` factory works but the command finds zero real recipients since it looks at the wrong table).

- [ ] **Step 3: Update the command**

```php
<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\MorningAirQualityDigest;
use App\Models\DigestRecipient;
use App\Services\MorningDigestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

final class SendMorningDigest extends Command
{
    protected $signature = 'digest:send-morning';

    protected $description = 'Email the daily morning air quality digest to every configured recipient';

    public function __construct(
        private readonly MorningDigestService $digestService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $digest = $this->digestService->build();

        if ($digest === null) {
            $this->info('No readings recorded yet today - skipping the morning digest.');

            return self::SUCCESS;
        }

        $recipients = DigestRecipient::all();

        if ($recipients->isEmpty()) {
            $this->info('No digest recipients configured.');

            return self::SUCCESS;
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->send(new MorningAirQualityDigest($digest));
        }

        $this->info("Sent the morning digest to {$recipients->count()} recipient(s).");

        return self::SUCCESS;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --filter=SendMorningDigestCommandTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Drop the column and delete the old toggle feature**

Create the drop migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('daily_digest_enabled');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('daily_digest_enabled')->default(false)->after('password');
        });
    }
};
```

In `app/Models/User.php`, remove `'daily_digest_enabled'` from `$fillable` and remove the `'daily_digest_enabled' => 'boolean'` line from `casts()`.

Delete these three files entirely (use `git rm`, not manual deletion, so the removal is tracked):
- `app/Http/Controllers/Settings/NotificationController.php`
- `resources/js/pages/settings/Notifications.vue`
- `tests/Feature/Settings/NotificationsUpdateTest.php`

In `routes/settings.php`, remove the `use App\Http\Controllers\Settings\NotificationController;` import and these two lines:

```php
Route::get('settings/notifications', [NotificationController::class, 'edit'])->name('notifications.edit');
Route::patch('settings/notifications', [NotificationController::class, 'update'])->name('notifications.update');
```

In `resources/js/layouts/settings/Layout.vue`, remove this entry from `sidebarNavItems`:

```ts
    {
        title: 'Notifications',
        href: '/settings/notifications',
    },
```

- [ ] **Step 6: Migrate and run the full suite**

Run: `php artisan migrate`
Expected: `2026_09_15_100002_drop_daily_digest_enabled_from_users_table` shows `Ran`.

Run: `php artisan test`
Expected: all green, no references to the deleted files remain (a leftover reference would show as a PHP fatal "class not found" or a Vue import error at build time, caught in Step 7).

- [ ] **Step 7: Build**

Run: `npm run build`
Expected: no errors (confirms no remaining import of the deleted `Notifications.vue`).

- [ ] **Step 8: Commit**

```bash
git add -A -- app database resources routes tests
git commit -m "refactor: replace per-user digest toggle with digest_recipients table"
```

---

### Task 3: Digest recipients management page (the table UI)

**Files:**
- Create: `app/Http/Controllers/DigestRecipientController.php`
- Create: `app/Http/Requests/StoreDigestRecipientRequest.php`
- Create: `resources/js/pages/Notifications.vue`
- Modify: `routes/web.php`
- Modify: `resources/js/components/AppSidebar.vue` (add the "Notifications" main-nav item)
- Test: `tests/Feature/DigestRecipientManagementTest.php`

**Interfaces:**
- Consumes: `DigestRecipient` (Task 1).
- Produces: `GET /notifications` (page, lists all recipients), `POST /notifications` (add one), `DELETE /notifications/{digestRecipient}` (remove one). All behind `auth`.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\DigestRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigestRecipientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login()
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_lists_all_recipients()
    {
        $user = User::factory()->create();
        DigestRecipient::factory()->create(['email' => 'a@airzen.test']);
        DigestRecipient::factory()->create(['email' => 'b@airzen.test']);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Notifications')->has('recipients', 2));
    }

    public function test_can_add_a_recipient()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notifications', ['email' => 'new@airzen.test']);

        $response->assertRedirect('/notifications');
        $this->assertDatabaseHas('digest_recipients', ['email' => 'new@airzen.test']);
    }

    public function test_cannot_add_a_duplicate_email()
    {
        $user = User::factory()->create();
        DigestRecipient::factory()->create(['email' => 'dupe@airzen.test']);

        $response = $this->actingAs($user)->post('/notifications', ['email' => 'dupe@airzen.test']);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('digest_recipients', 1);
    }

    public function test_rejects_an_invalid_email()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notifications', ['email' => 'not-an-email']);

        $response->assertSessionHasErrors('email');
    }

    public function test_can_remove_a_recipient()
    {
        $user = User::factory()->create();
        $recipient = DigestRecipient::factory()->create();

        $response = $this->actingAs($user)->delete("/notifications/{$recipient->id}");

        $response->assertRedirect('/notifications');
        $this->assertDatabaseMissing('digest_recipients', ['id' => $recipient->id]);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=DigestRecipientManagementTest`
Expected: FAIL (404s - route doesn't exist).

- [ ] **Step 3: Write the form request**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreDigestRecipientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', Rule::unique('digest_recipients', 'email')],
        ];
    }
}
```

- [ ] **Step 4: Write the controller**

```php
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
```

- [ ] **Step 5: Register the routes**

In `routes/web.php`, add (alongside the existing `dashboard` route, inside the same `auth`/`verified` area - check how `dashboard` is registered and match that middleware exactly):

```php
use App\Http\Controllers\DigestRecipientController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications', [DigestRecipientController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [DigestRecipientController::class, 'store'])->name('notifications.store');
    Route::delete('notifications/{digestRecipient}', [DigestRecipientController::class, 'destroy'])->name('notifications.destroy');
});
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=DigestRecipientManagementTest`
Expected: PASS (6 tests).

- [ ] **Step 7: Build the Vue page**

```vue
<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Mail, Trash2 } from 'lucide-vue-next';

interface Recipient {
    id: number;
    email: string;
}

defineProps<{
    recipients: Recipient[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/notifications' }];

const form = useForm({ email: '' });

function submit() {
    form.post(route('notifications.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('email'),
    });
}

function remove(id: number) {
    form.delete(route('notifications.destroy', id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="az2-sans flex h-full flex-1 flex-col gap-6 bg-[#F7F8F1] p-4 md:p-6">
            <div>
                <h1 class="az2-display text-2xl text-[#1D352D]">Notifications</h1>
                <p class="text-sm text-[#6B8577]">Everyone in this table gets the 7:00 AM morning air quality digest.</p>
            </div>

            <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                <form @submit.prevent="submit" class="flex flex-wrap items-start gap-3">
                    <div class="flex-1">
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="name@example.com"
                            class="w-full rounded-[10px] border border-[#E4EAE0] bg-[#F7F8F1] px-3 py-2 text-sm text-[#1D352D] focus:border-[#2A8362] focus:outline-none"
                        />
                        <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="flex items-center gap-1.5 rounded-full bg-[#2A8362] px-5 py-2 text-sm font-bold text-white hover:bg-[#226b4f] disabled:opacity-60"
                    >
                        <Mail :size="15" />
                        Add recipient
                    </button>
                </form>

                <table class="mt-6 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#EEF1E9] text-[11px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">
                            <th class="pb-2">Email</th>
                            <th class="w-16 pb-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEF1E9]">
                        <tr v-for="recipient in recipients" :key="recipient.id">
                            <td class="py-3 text-[#1D352D]">{{ recipient.email }}</td>
                            <td class="py-3 text-right">
                                <button type="button" class="text-[#6B8577] hover:text-red-600" @click="remove(recipient.id)">
                                    <Trash2 :size="16" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="recipients.length === 0">
                            <td colspan="2" class="py-6 text-center text-[#6B8577]">No recipients yet. Add one above.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.az2-display {
    font-family: 'Fraunces', ui-serif, Georgia, serif;
    letter-spacing: -0.02em;
    font-weight: 500;
}
.az2-sans {
    font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
}
</style>
```

- [ ] **Step 8: Add the sidebar nav entry**

In `resources/js/components/AppSidebar.vue`, add to `mainNavItems`:

```ts
import { LayoutGrid, Mail } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Notifications', href: '/notifications', icon: Mail },
];
```

- [ ] **Step 9: Build and verify in browser**

Run: `npm run build`
Then log in, click "Notifications" in the sidebar, add an email, confirm it appears in the table, remove it, confirm it disappears - all without a full page flash (Inertia `preserveScroll`).

- [ ] **Step 10: Run the impeccable detector**

Run: `node "C:\Users\Rhuzz\.agents\skills\impeccable\scripts\detect.mjs" --json resources/js/pages/Notifications.vue resources/js/components/AppSidebar.vue`
Expected: `[]`.

- [ ] **Step 11: Commit**

```bash
git add app/Http/Controllers/DigestRecipientController.php app/Http/Requests/StoreDigestRecipientRequest.php resources/js/pages/Notifications.vue routes/web.php resources/js/components/AppSidebar.vue tests/Feature/DigestRecipientManagementTest.php
git commit -m "feat: add digest recipients management table page, linked from the sidebar"
```

---

### Task 4: `AqiHistoryService` gains `dailyAverages(days)`, `bandDistribution(days)`, `paginatedReadings(filters, perPage)`

**Files:**
- Modify: `app/Services/AqiHistoryService.php`
- Test: `tests/Feature/AqiHistoryServiceTest.php` (extend, don't replace - existing tests for `pm25Trend`/`weeklyAverageAqi` must keep passing unchanged)

**Interfaces:**
- Consumes: `RecommendationService` (new constructor dependency), `AqiCalculator` (already a dependency).
- Produces: `dailyAverages(int $days): array` (same shape as the existing `weeklyAverageAqi()` entries, just N of them), `bandDistribution(int $days): array<string, int>` (status label -> day count), `paginatedReadings(array $filters, int $perPage = 20): \Illuminate\Pagination\LengthAwarePaginator` of raw `SensorReading` rows. Consumed by Task 5's `HistoryController`.

- [ ] **Step 1: Write the failing tests**

Add to `tests/Feature/AqiHistoryServiceTest.php` (keep every existing test method in the file untouched):

```php
    public function test_weekly_average_aqi_is_the_same_as_daily_averages_for_seven_days()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->assertSame($this->service->dailyAverages(7), $this->service->weeklyAverageAqi());
    }

    public function test_daily_averages_supports_a_longer_range()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()->subDays(20)]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 12.0, 'created_at' => now()]);

        $thirty = $this->service->dailyAverages(30);

        $this->assertCount(30, $thirty);
        $this->assertNotNull($thirty[9]['average_aqi']); // 20 days ago is index (30-1)-20 = 9
        $this->assertNotNull($thirty[29]['average_aqi']); // today
    }

    public function test_band_distribution_counts_days_by_status()
    {
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 5.0, 'created_at' => now()->subDay()]); // Good
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 25.0, 'created_at' => now()]); // Normal

        $distribution = $this->service->bandDistribution(7);

        $this->assertSame(1, $distribution['Good']);
        $this->assertSame(1, $distribution['Normal']);
        $this->assertArrayNotHasKey('Very Unhealthy', $distribution);
    }

    public function test_paginated_readings_filters_by_type()
    {
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 25.0]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0]);

        $page = $this->service->paginatedReadings(['type' => 'TEMPERATURE'], 20);

        $this->assertSame(1, $page->total());
        $this->assertSame('TEMPERATURE', $page->items()[0]->type);
    }

    public function test_paginated_readings_filters_by_date_range()
    {
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 20.0, 'created_at' => now()->subDays(10)]);
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 22.0, 'created_at' => now()]);

        $page = $this->service->paginatedReadings(['from' => now()->subDay()->toDateString()], 20);

        $this->assertSame(1, $page->total());
    }
```

Also add `use App\Services\RecommendationService;` isn't needed in the test (the service is resolved via `app(AqiHistoryService::class)`, which auto-wires the new constructor dependency automatically) - no test file changes beyond the new test methods above.

- [ ] **Step 2: Run tests to verify the new ones fail**

Run: `php artisan test --filter=AqiHistoryServiceTest`
Expected: the 5 new tests FAIL (methods don't exist yet), the pre-existing ones still PASS.

- [ ] **Step 3: Rewrite `AqiHistoryService`**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SensorReading;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

final class AqiHistoryService
{
    public function __construct(
        private readonly AqiCalculator $aqiCalculator,
        private readonly RecommendationService $recommendationService,
    ) {}

    /**
     * @return list<array{value: float, recorded_at: string}>
     */
    public function pm25Trend(int $limit = 20): array
    {
        return SensorReading::where('type', SensorReading::PARTICULATE_MATTER)
            ->latest()
            ->limit($limit)
            ->get(['value', 'created_at'])
            ->reverse()
            ->values()
            ->map(fn (SensorReading $reading): array => [
                'value' => $reading->value,
                'recorded_at' => $reading->created_at->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, date: string, average_aqi: ?float, is_today: bool}>
     */
    public function weeklyAverageAqi(): array
    {
        return $this->dailyAverages(7);
    }

    /**
     * $days days ending today. A day with no PM2.5 readings reports a null
     * average rather than a fabricated 0.
     *
     * @return list<array{label: string, date: string, average_aqi: ?float, is_today: bool}>
     */
    public function dailyAverages(int $days): array
    {
        $since = Carbon::now()->startOfDay()->subDays($days - 1);

        $readingsByDate = SensorReading::where('type', SensorReading::PARTICULATE_MATTER)
            ->where('created_at', '>=', $since)
            ->get(['value', 'created_at'])
            ->groupBy(fn (SensorReading $reading): string => $reading->created_at->toDateString());

        $result = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $dayReadings = $readingsByDate->get($date->toDateString());

            $result[] = [
                'label' => $date->format('D'),
                'date' => $date->toDateString(),
                'average_aqi' => $dayReadings && $dayReadings->isNotEmpty()
                    ? $this->aqiCalculator->calculateOverallAqi(['pm25' => $dayReadings->avg('value')])
                    : null,
                'is_today' => $date->isToday(),
            ];
        }

        return $result;
    }

    /**
     * How many days in the range fell into each AQI status band. Days with
     * no PM2.5 data are excluded entirely (not counted as any band).
     *
     * @return array<string, int>
     */
    public function bandDistribution(int $days): array
    {
        $counts = [];

        foreach ($this->dailyAverages($days) as $day) {
            if ($day['average_aqi'] === null) {
                continue;
            }

            $status = $this->recommendationService->getRecommendation($day['average_aqi'])['status'];
            $counts[$status] = ($counts[$status] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param array{type?: string, from?: string, to?: string} $filters
     */
    public function paginatedReadings(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $query = SensorReading::query()->latest();

        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
```

- [ ] **Step 4: Run all `AqiHistoryServiceTest` tests**

Run: `php artisan test --filter=AqiHistoryServiceTest`
Expected: PASS (all - the original 5 plus the 5 new ones = 10).

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: all green (this confirms `SensorReadingController::history()` and `DashboardController`, which both call `weeklyAverageAqi()`/`pm25Trend()`, still work unchanged).

- [ ] **Step 6: Commit**

```bash
git add app/Services/AqiHistoryService.php tests/Feature/AqiHistoryServiceTest.php
git commit -m "feat: generalize AqiHistoryService for a configurable history range, band distribution, and paginated raw readings"
```

---

### Task 5: History page - controller, pie chart component, page

**Files:**
- Create: `app/Http/Controllers/HistoryController.php`
- Create: `resources/js/components/BandDistributionChart.vue`
- Create: `resources/js/pages/History.vue`
- Modify: `routes/web.php`
- Modify: `resources/js/components/AppSidebar.vue`
- Test: `tests/Feature/HistoryPageTest.php`

**Interfaces:**
- Consumes: `AqiHistoryService::dailyAverages()`, `bandDistribution()`, `paginatedReadings()` (Task 4). `AqiSparkline.vue` (existing, reused for the trend chart - accepts `points: {value, recorded_at}[]`, and `dailyAverages()`'s `{average_aqi, date}` shape needs a tiny map to that interface, done inline in the controller or the Vue page - see Step 4).
- Produces: `GET /history` page; nothing else consumes it.

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login()
    {
        $this->get('/history')->assertRedirect('/login');
    }

    public function test_renders_with_default_30_day_range()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('History')
            ->where('days', 30)
            ->has('trend', 30)
            ->has('bandDistribution')
            ->has('readings.data', 1));
    }

    public function test_accepts_a_custom_day_range()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/history?days=90');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('days', 90)->has('trend', 90));
    }

    public function test_falls_back_to_30_days_for_an_unsupported_range()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/history?days=13');

        $response->assertInertia(fn ($page) => $page->where('days', 30));
    }

    public function test_filters_readings_by_type()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 25.0]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0]);

        $response = $this->actingAs($user)->get('/history?type=TEMPERATURE');

        $response->assertInertia(fn ($page) => $page->has('readings.data', 1));
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=HistoryPageTest`
Expected: FAIL (404 - route doesn't exist).

- [ ] **Step 3: Write the controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\SensorReadingResource;
use App\Services\AqiHistoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class HistoryController extends Controller
{
    private const ALLOWED_DAY_RANGES = [7, 30, 90];

    public function __construct(
        private readonly AqiHistoryService $historyService,
    ) {}

    public function index(Request $request): Response
    {
        $days = (int) $request->query('days', 30);
        if (! in_array($days, self::ALLOWED_DAY_RANGES, true)) {
            $days = 30;
        }

        $type = (string) $request->query('type', '');

        $dailyAverages = $this->historyService->dailyAverages($days);

        return Inertia::render('History', [
            'days' => $days,
            'filters' => [
                'type' => $type,
                'from' => $request->query('from', ''),
                'to' => $request->query('to', ''),
            ],
            'trend' => array_map(
                fn (array $day): array => ['value' => $day['average_aqi'] ?? 0.0, 'recorded_at' => $day['date']],
                array_values(array_filter($dailyAverages, fn (array $day): bool => $day['average_aqi'] !== null)),
            ),
            'bandDistribution' => $this->historyService->bandDistribution($days),
            'readings' => SensorReadingResource::collection(
                $this->historyService->paginatedReadings([
                    'type' => $type,
                    'from' => $request->query('from'),
                    'to' => $request->query('to'),
                ], 20),
            ),
        ]);
    }
}
```

- [ ] **Step 4: Write the tiny API resource used for the readings table**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class SensorReadingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'value' => $this->value,
            'recorded_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

- [ ] **Step 5: Register the route**

In `routes/web.php`, inside the same `auth`/`verified` group added in Task 3:

```php
use App\Http\Controllers\HistoryController;

Route::get('history', [HistoryController::class, 'index'])->name('history.index');
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test --filter=HistoryPageTest`
Expected: PASS (5 tests).

- [ ] **Step 7: Write `BandDistributionChart.vue` (the pie chart)**

```vue
<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    distribution: Record<string, number>;
}>();

const BAND_ORDER = ['Good', 'Normal', 'Unhealthy for Sensitive Groups', 'Unhealthy for All Groups', 'Very Unhealthy'];
const BAND_COLORS: Record<string, string> = {
    Good: '#22C55E',
    Normal: '#EAB308',
    'Unhealthy for Sensitive Groups': '#F59E0B',
    'Unhealthy for All Groups': '#EF4444',
    'Very Unhealthy': '#A855F7',
};

const total = computed(() => Object.values(props.distribution).reduce((sum, count) => sum + count, 0));

const segments = computed(() => {
    let cursor = 0;
    return BAND_ORDER.filter((band) => props.distribution[band] > 0).map((band) => {
        const count = props.distribution[band];
        const start = (cursor / total.value) * 360;
        cursor += count;
        const end = (cursor / total.value) * 360;
        return { band, count, color: BAND_COLORS[band], start, end };
    });
});

const gradient = computed(() => {
    if (total.value === 0) return '#EEF1E9';
    return `conic-gradient(${segments.value.map((s) => `${s.color} ${s.start}deg ${s.end}deg`).join(', ')})`;
});
</script>

<template>
    <div class="flex items-center gap-6">
        <div class="relative h-32 w-32 shrink-0 rounded-full" :style="{ background: gradient }">
            <div class="absolute inset-4 flex flex-col items-center justify-center rounded-full bg-white text-center">
                <span class="az2-display text-xl text-[#1D352D]">{{ total }}</span>
                <span class="text-[9px] uppercase tracking-[0.08em] text-[#6B8577]">days</span>
            </div>
        </div>
        <ul class="flex flex-1 flex-col gap-2 text-sm">
            <li v-for="segment in segments" :key="segment.band" class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-2 text-[#1D352D]">
                    <span class="h-2.5 w-2.5 shrink-0 rounded-full" :style="{ backgroundColor: segment.color }"></span>
                    {{ segment.band }}
                </span>
                <span class="font-medium text-[#6B8577]">{{ segment.count }}</span>
            </li>
            <li v-if="total === 0" class="text-[#6B8577]">No data in this range yet.</li>
        </ul>
    </div>
</template>

<style scoped>
.az2-display {
    font-family: 'Fraunces', ui-serif, Georgia, serif;
    letter-spacing: -0.02em;
    font-weight: 500;
}
</style>
```

- [ ] **Step 8: Write `History.vue`**

```vue
<script setup lang="ts">
import AqiSparkline from '@/components/AqiSparkline.vue';
import BandDistributionChart from '@/components/BandDistributionChart.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';

interface Reading {
    id: number;
    type: string;
    value: number;
    recorded_at: string;
}

interface Props {
    days: number;
    filters: { type: string; from: string; to: string };
    trend: { value: number; recorded_at: string }[];
    bandDistribution: Record<string, number>;
    readings: { data: Reading[]; links: { url: string | null; label: string; active: boolean }[] };
}

const props = defineProps<Props>();

const SENSOR_TYPES = ['TEMPERATURE', 'HUMIDITY', 'NITROGEN', 'C0', 'ParticulateMatter'];

function visit(overrides: Partial<{ days: number; type: string }>) {
    router.get(
        route('history.index'),
        { days: props.days, type: props.filters.type, ...overrides },
        { preserveState: true, preserveScroll: true },
    );
}

function goToPage(url: string | null) {
    if (!url) return;
    router.get(url, {}, { preserveState: true, preserveScroll: true });
}
</script>

<template>
    <Head title="History" />

    <AppLayout :breadcrumbs="[{ title: 'History', href: '/history' } as BreadcrumbItem]">
        <div class="az2-sans flex h-full flex-1 flex-col gap-6 bg-[#F7F8F1] p-4 md:p-6">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="az2-display text-2xl text-[#1D352D]">History</h1>
                    <p class="text-sm text-[#6B8577]">Every indoor air quality reading this device has ever sent.</p>
                </div>
                <div class="flex gap-1.5 rounded-full bg-white p-1 shadow-[0_1px_2px_rgba(20,35,25,0.04)]">
                    <button
                        v-for="range in [7, 30, 90]"
                        :key="range"
                        type="button"
                        class="rounded-full px-3 py-1.5 text-xs font-bold"
                        :class="days === range ? 'bg-[#2A8362] text-white' : 'text-[#6B8577] hover:text-[#1D352D]'"
                        @click="visit({ days: range })"
                    >
                        {{ range }}d
                    </button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-2">
                <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                    <h2 class="az2-display text-lg text-[#1D352D]">PM2.5 daily average</h2>
                    <p class="text-xs text-[#6B8577]">Last {{ days }} days.</p>
                    <div class="mt-4">
                        <AqiSparkline :points="trend" color="#2A8362" />
                    </div>
                </div>

                <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                    <h2 class="az2-display text-lg text-[#1D352D]">Days by air quality band</h2>
                    <p class="text-xs text-[#6B8577]">Last {{ days }} days.</p>
                    <div class="mt-4">
                        <BandDistributionChart :distribution="bandDistribution" />
                    </div>
                </div>
            </div>

            <div class="rounded-[28px] bg-white p-6 shadow-[0_1px_2px_rgba(20,35,25,0.04),0_24px_48px_-24px_rgba(20,35,25,0.25)]">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="az2-display text-lg text-[#1D352D]">Reading log</h2>
                    <select
                        :value="filters.type"
                        class="rounded-[10px] border border-[#E4EAE0] bg-[#F7F8F1] px-3 py-1.5 text-sm text-[#1D352D]"
                        @change="visit({ type: ($event.target as HTMLSelectElement).value })"
                    >
                        <option value="">All types</option>
                        <option v-for="type in SENSOR_TYPES" :key="type" :value="type">{{ type }}</option>
                    </select>
                </div>

                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-[#EEF1E9] text-[11px] font-bold uppercase tracking-[0.08em] text-[#6B8577]">
                            <th class="pb-2">Type</th>
                            <th class="pb-2">Value</th>
                            <th class="pb-2">Recorded</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#EEF1E9]">
                        <tr v-for="reading in readings.data" :key="reading.id">
                            <td class="py-2.5 text-[#1D352D]">{{ reading.type }}</td>
                            <td class="py-2.5 tabular-nums text-[#1D352D]">{{ reading.value }}</td>
                            <td class="py-2.5 text-[#6B8577]">{{ new Date(reading.recorded_at).toLocaleString() }}</td>
                        </tr>
                        <tr v-if="readings.data.length === 0">
                            <td colspan="3" class="py-6 text-center text-[#6B8577]">No readings in this range.</td>
                        </tr>
                    </tbody>
                </table>

                <div class="mt-4 flex flex-wrap justify-center gap-1">
                    <button
                        v-for="link in readings.links"
                        :key="link.label"
                        type="button"
                        class="rounded-full px-3 py-1 text-xs"
                        :class="link.active ? 'bg-[#2A8362] text-white' : 'text-[#6B8577] hover:text-[#1D352D]'"
                        :disabled="!link.url"
                        v-html="link.label"
                        @click="goToPage(link.url)"
                    ></button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.az2-display {
    font-family: 'Fraunces', ui-serif, Georgia, serif;
    letter-spacing: -0.02em;
    font-weight: 500;
}
.az2-sans {
    font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
}
</style>
```

Note: `v-html="link.label"` is safe here specifically because `link.label` comes from Laravel's own paginator (`&laquo; Previous`, page numbers, `Next &raquo;`) - it is never user input.

- [ ] **Step 9: Add the sidebar nav entry**

In `resources/js/components/AppSidebar.vue`, add to `mainNavItems` (alongside the "Notifications" entry from Task 3):

```ts
import { History as HistoryIcon, LayoutGrid, Mail } from 'lucide-vue-next';

const mainNavItems: NavItem[] = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'History', href: '/history', icon: HistoryIcon },
    { title: 'Notifications', href: '/notifications', icon: Mail },
];
```

(`History` is aliased to `HistoryIcon` on import because `History` collides with the DOM global of the same name.)

- [ ] **Step 10: Build and verify in browser with real data**

Run: `npm run build`
Log in, click "History". Confirm: the day-range pills (7d/30d/90d) each trigger a full data refresh without navigating away (`preserveState`), the trend sparkline shows real daily PM2.5 averages, the pie chart's total matches the number of days with data in range, the type filter dropdown re-queries the reading log, and pagination links work. POST a few readings via `curl` against `/iot.php` first if the local dev database is otherwise empty, so there is real data to look at.

- [ ] **Step 11: Run the impeccable detector**

Run: `node "C:\Users\Rhuzz\.agents\skills\impeccable\scripts\detect.mjs" --json resources/js/pages/History.vue resources/js/components/BandDistributionChart.vue resources/js/components/AppSidebar.vue`
Expected: `[]`.

- [ ] **Step 12: Commit**

```bash
git add app/Http/Controllers/HistoryController.php app/Http/Resources/SensorReadingResource.php resources/js/components/BandDistributionChart.vue resources/js/pages/History.vue routes/web.php resources/js/components/AppSidebar.vue tests/Feature/HistoryPageTest.php
git commit -m "feat: add History page with customizable-range trend, band-distribution pie chart, and filterable reading log"
```

---

## Self-Review Notes

- **Spec coverage:** "remove the github repo" - already done directly (not part of this plan, completed and committed before this plan was written). "where is the email notification feature, bring it there" - Task 3 moves it to a dedicated `/notifications` page linked directly from the main sidebar. "make it a table format... add email who can receive email" - Task 1-3 fully replace the boolean toggle with a real `digest_recipients` table + add/remove UI. "history management that tracks all indoor air quality" - Task 5's reading log paginates over every single `sensor_readings` row ever recorded, filterable by type and date. "live charts pie can be custom for better navigation" - Task 5's trend chart + pie chart both respond live to the 7d/30d/90d range selector without a page reload.
- **Placeholder scan:** every step has complete code, no TBDs.
- **Type consistency:** `DigestRecipient`'s `email` field matches across migration, model, factory, form request, controller, and the `Notifications.vue` `Recipient` interface. `AqiHistoryService::dailyAverages()`'s return shape (`label`, `date`, `average_aqi`, `is_today`) matches exactly what `weeklyAverageAqi()` already returns today (verified: it becomes a one-line delegate, so `Dashboard.vue`'s `WeeklyAqiBars` consumer needs zero changes). `HistoryController`'s `trend` prop shape (`{value, recorded_at}[]`) matches `AqiSparkline.vue`'s existing `TrendPoint` interface exactly, so it's reused with no modification.
