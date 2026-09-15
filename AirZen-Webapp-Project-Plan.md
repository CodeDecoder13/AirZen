# AirZen Webapp — Project Plan

**Project:** Rebuild of "AirZen: Indoor Air Quality Monitoring and Recommendation System" (FEU Tech thesis, 2023) as a standalone web application.

**Owner:** Rhuzzel Paramio

**Stack:** Laravel (Vue 3 + Inertia) · PostgreSQL · ESP32 (DHT11, MQ-7, MQ-135, GP2Y1014AU) · Railway hosting

---

## 1. Objective

Turn the original Arduino + KNN thesis prototype into a real, deployable webapp that:
- Receives live sensor data from the existing ESP32 device over WiFi
- Calculates AQI deterministically (EPA breakpoint formula — no training data required)
- Displays a live AQI indicator and matching health recommendations
- Is gated behind authentication for anyone managing/viewing the dashboard
- Is structured so a machine-learning layer (KNN, as in the original thesis) can be added later once enough historical data has accumulated

---

## 2. Phases & Milestones

### Phase 1 — Foundations (Week 1)
- [ ] Set up Laravel project (Breeze/Jetstream for auth scaffolding, or custom auth)
- [ ] Design database schema: `readings`, `users`
- [ ] Set up PostgreSQL on Railway
- [ ] Define `AqiCalculator` service (EPA breakpoint formula, per pollutant)
- [ ] Define `RecommendationService` (static band lookup from thesis Table 1)

**Deliverable:** Laravel app boots locally, auth works, AQI can be calculated from manually posted test data.

### Phase 2 — Device Integration (Week 2)
- [ ] Flash ESP32 firmware: WiFi connect + POST readings to `/api/readings`
- [ ] Confirm sensor calibration (raw ADC → ppm/µg per m³) using sensor datasheets from thesis hardware chapter
- [ ] Secure the ingestion endpoint (device token / API key)
- [ ] Verify readings land correctly in the database with correct AQI + status computed

**Deliverable:** Device reliably reports live readings into the database, end-to-end, with no manual intervention.

### Phase 3 — Dashboard (Weeks 3–4)
- [ ] Build landing/dashboard page:
  - Live AQI indicator (auto-refreshing or polling)
  - Current recommendation panel (from AQI band)
  - Sensor breakdown (temp, humidity, CO, NOx, PM2.5)
- [ ] Build reading history view (daily/weekly, matches original thesis feature)
- [ ] Login/auth screens
- [ ] Responsive layout (mobile + desktop)

**Deliverable:** Fully functional dashboard, live-updating, behind login.

### Phase 4 — Testing & Polish (Week 5)
- [ ] Validate AQI calculation against thesis Table 1/2 ranges
- [ ] Load-test the ingestion endpoint (simulate frequent device posts)
- [ ] Cross-browser / mobile QA
- [ ] Error handling: device offline, malformed payloads, DB downtime

**Deliverable:** Stable, demo-ready webapp.

### Phase 5 — Deployment (Week 5–6)
- [ ] Deploy Laravel app to Railway
- [ ] Point ESP32 firmware to production URL
- [ ] Set up scheduled backups for the readings database
- [ ] (Optional) Custom domain + HTTPS

**Deliverable:** Live, publicly accessible webapp with the physical device reporting to it.

### Phase 6 — Future Enhancement (Post-launch, ongoing)
- [ ] Accumulate real reading history (weeks/months)
- [ ] Once enough data exists: revisit KNN/cosine-similarity recommendation engine from the original thesis, trained on your own accumulated dataset instead of third-party devices
- [ ] Optional: trend forecasting, alerts/notifications when AQI crosses a threshold, multi-room/multi-device support

---

## 3. Data Flow (Reference)

```
ESP32 (sensors) → WiFi → Laravel API (/api/readings)
                              │
                              ▼
                     AqiCalculator (EPA formula)
                              │
                              ▼
                   RecommendationService (band lookup)
                              │
                              ▼
                     PostgreSQL (readings table)
                              │
                              ▼
                   Dashboard (live indicator + history)
```

---

## 4. Risks / Open Items

| Risk | Notes |
|---|---|
| No labeled AQI dataset yet | Solved by using the EPA formula instead of ML for now (Phase 1) |
| Sensor calibration unclear | Need raw-ADC-to-concentration formulas from sensor datasheets (MQ-7, MQ-135, dust sensor) |
| Public endpoint security | Requires a device token/API key before going live |
| Single point of failure (one ESP32) | Fine for a demo/thesis-successor project; multi-device support is a future enhancement |

---

## 5. Success Criteria

- Device data flows into the database without manual steps
- AQI displayed matches expected EPA-standard ranges for known test conditions
- Recommendations correctly match the AQI band shown
- Dashboard is usable on both desktop and mobile
- App is live and reachable via a public URL on Railway

---

## 6. AI Build Prompts — Ready to Hand Off

Each block below is a self-contained prompt you can paste directly into an AI coding assistant (Claude Code, Cursor, etc.) to generate that piece of the project. They're written to include the decisions already made above, so the AI doesn't need to guess or ask you to re-explain context.

### 6.1 — Laravel project setup + auth

```
Set up a new Laravel project called "AirZen" using Laravel Breeze for authentication
(email/password login, no social auth needed). Use PostgreSQL as the database.
Scaffold it with Inertia.js + Vue 3 (not Blade-only, not React).
After scaffolding, confirm the login/register pages work and the app boots locally.
```

### 6.2 — Database schema

```
In this Laravel + PostgreSQL project, create a migration for a `readings` table with
these columns:
- id
- temperature (float)
- humidity (float)
- co (float)              -- carbon monoxide, ppm
- nitrogen (float)         -- NOx
- pm25 (float)             -- particulate matter, µg/m3
- aqi (float)              -- calculated overall AQI
- status (string)          -- e.g. "Good", "Normal", "Unhealthy for Sensitive Groups", etc.
- color (string)           -- hex color matching the status band
- device_id (string, nullable)
- created_at / updated_at

Also create an Eloquent model `Reading` for this table.
```

### 6.3 — AqiCalculator service (EPA breakpoint formula)

```
Create a Laravel service class `App\Services\AqiCalculator` that converts raw pollutant
readings into an AQI value using the EPA breakpoint linear interpolation formula:

    AQI = ((I_Hi - I_Lo) / (BP_Hi - BP_Lo)) * (C_p - BP_Lo) + I_Lo

It needs a method `calculateSubIndex(string $pollutant, float $concentration): ?float`
that looks up the correct breakpoint bracket for the pollutant and applies the formula,
and a method `calculateOverallAqi(array $readings): float` that computes the sub-index
for pm25, co, and nitrogen, and returns the highest one (that's the overall AQI, per EPA
convention).

Use these breakpoint tables:

PM2.5 (µg/m3):
  0.0–15.4   -> AQI 0–50
  15.5–35.4  -> AQI 51–100
  35.5–54.4  -> AQI 101–150
  54.5–150.4 -> AQI 151–200
  150.5–250.4 -> AQI 201–300

CO (ppm):
  0–4.4     -> AQI 0–50
  4.5–9.4   -> AQI 51–100
  9.5–12.4  -> AQI 101–150
  12.5–15.4 -> AQI 151–200
  15.5–30.4 -> AQI 201–300

Nitrogen/NOx:
  0–40    -> AQI 0–50
  41–100  -> AQI 51–100
  101–150 -> AQI 101–150
  150–200 -> AQI 151–200
  201–300 -> AQI 201–300

Write PHPUnit tests confirming a few known concentration values map to the expected AQI.
```

### 6.4 — RecommendationService (band lookup)

```
Create a Laravel service class `App\Services\RecommendationService` with a method
`getRecommendation(float $aqi): array` that returns the status label, hex color, and a
list of 3 recommendation strings for the AQI band the value falls into. Use these exact
bands (from the FEU Tech AirZen thesis, Table 1):

0-50   -> "Good" (#22C55E):
  - Keep windows and doors open to allow fresh air circulation.
  - Regularly clean and dust the space to minimize allergens.
  - Indoor plants like peace lilies or spider plants can help maintain this air quality.

51-100 -> "Normal" (#EAB308):
  - Enhance ventilation by opening windows or using fans.
  - Use natural cleaning products to reduce VOC release.
  - Keep indoor humidity in check to prevent mold and mildew.

101-150 -> "Unhealthy for Sensitive Groups" (#F59E0B):
  - Utilize air purifiers with HEPA filters where possible.
  - Minimize activities in the room during peak hours.
  - Sensitive individuals should consider a mask indoors.

151-200 -> "Unhealthy for All Groups" (#EF4444):
  - Improve ventilation by opening windows or using exhaust fans.
  - Run air purifiers with HEPA filters continuously.
  - Avoid smoking or use of harsh chemicals in the room.

201-300 -> "Very Unhealthy" (#A855F7):
  - Close windows and doors to limit outdoor pollutant exposure.
  - Use additional air filtration if available.
  - Follow local advisories on indoor air quality and health.

Fall back to the worst band if AQI exceeds 300.
```

### 6.5 — API endpoint to receive device readings

```
Create a Laravel API route POST /api/readings and a ReadingController@store method.
It should:
1. Validate incoming JSON: temperature, humidity, co, nitrogen, pm25 (all required, numeric)
2. Check a simple device API key sent via header "X-Device-Key" against a value stored
   in .env (DEVICE_API_KEY), reject with 401 if it doesn't match
3. Call App\Services\AqiCalculator::calculateOverallAqi() to get the AQI
4. Call App\Services\RecommendationService::getRecommendation() to get status/color
5. Save everything to the `readings` table
6. Return the created reading plus the recommendation list as JSON

Also add a GET /api/readings/latest endpoint that returns the most recent reading,
for the dashboard to poll.
```

### 6.6 — ESP32 firmware

```
Write Arduino/ESP32 firmware (C++) that:
1. Connects to WiFi (SSID/password as top-of-file constants)
2. Reads: DHT11 (temperature + humidity), MQ-7 (CO, analog pin), MQ-135 (NOx, analog pin),
   Keyestudio GP2Y1014AU dust sensor (PM2.5, analog pin)
3. Every 10 seconds, POSTs a JSON payload {temperature, humidity, co, nitrogen, pm25}
   to a configurable server URL (constant at top of file) using HTTPClient
4. Includes the header "X-Device-Key" with a hardcoded device key constant
5. Handles WiFi reconnection if the connection drops
6. Prints connection status and HTTP response codes to Serial for debugging

Keep it well-commented since this will be maintained by someone reviewing it later.
```

### 6.7 — Dashboard UI (matches the approved mockup)

```
Build a Vue 3 + Inertia dashboard page in this Laravel project called "Dashboard.vue"
that visually matches this design exactly:

- Light theme: page background #F4FAF6, white cards (#FFFFFF) with a soft shadow
  (0 1px 2px rgba(14,36,25,0.04), 0 16px 32px -20px rgba(14,36,25,0.12)) and
  22px border-radius
- Fonts: "Space Grotesk" (headings/numbers, weights 500-700) and "Public Sans"
  (body text), loaded from Google Fonts
- Brand green: #12A454 (primary buttons, accents), deep green #0B7A3F (hover states),
  light green #E4F7EC (chip backgrounds)
- A large circular AQI gauge: a ring built from a conic-gradient with 5 color segments
  (Good #22C55E 0-60deg, Normal #EAB308 60-120deg, Unhealthy-Sensitive #F59E0B 120-180deg,
  Unhealthy-All #EF4444 180-240deg, Very-Unhealthy #A855F7 240-360deg), a white circular
  mask cut out of the middle, a small white marker dot with a colored border that rotates
  around the ring to the current AQI's angle (angle = min(aqi,300) * 1.2 degrees), and the
  AQI number + "AQI" label centered inside the ring
- A status chip below the gauge (colored background/text matching the current band)
- A recommendations panel beside the gauge (white card, list of 3 recommendation items
  with a colored left accent bar per item, matching the current band's color)
- A 5-column sensor strip below (Temperature, Humidity, CO, NOx, PM2.5) as small white cards
- The dashboard should poll GET /api/readings/latest every 8 seconds and update the
  gauge, status chip, recommendation list, and sensor values reactively without a
  full page reload

I have an HTML/CSS/JS reference mockup (AirZen-Webapp-Mockup.html) with this exact
design if needed for pixel-matching — ask me to share the relevant CSS/markup if useful.
```

### 6.8 — Animated 3D hero scene (device mounted in a room)

```
Build an animated isometric "3D-style" hero illustration for the AirZen landing page,
showing the physical sensor device mounted on a wall inside a room. Use inline SVG +
CSS animations (no external 3D library needed). It should sit inside a white rounded
card (22px radius, same soft shadow as the other dashboard cards) at the top of the
landing page, next to the hero headline/CTA buttons (two-column layout on desktop,
stacked on mobile).

Scene requirements:
- An isometric "open box" room made of 3 flat polygons: a floor (lightest green,
  #E3F6EA), a left wall in shadow (#C7E7D4), and a right wall in light (#D8F0E1),
  all with a subtle darker green border stroke. Use the classic isometric diorama
  layout: floor as a diamond, the two walls rising straight up from its back edges.
- Add small room details for realism: a window with a soft warm glow on the left
  wall, a small bookshelf against the left wall, and a small potted plant illustration
  on the floor.
- The sensor device: a small white rounded rectangle mounted flush on the right wall,
  with a green border and a small pulsing LED dot inside it (opacity + scale pulse
  animation, continuous loop, ~1.8s).
- Above the device, 3 concentric arc "wifi signal" shapes that animate outward and
  fade in a staggered sequence, implying it's transmitting data.
- Around the device, 2-3 concentric "ping" rings that expand and fade continuously,
  implying it's actively sensing the room (like a radar sweep).
- 3-4 small particle dots near the device that drift upward and fade out on a loop,
  implying captured sensor data floating off to "the cloud"/dashboard.
- IMPORTANT: the LED, wifi arcs, and ping rings should all read their color from a
  single CSS custom property (e.g. --device-color) on the SVG's root element, NOT
  hardcoded colors — because this same scene needs to visually reflect the live AQI
  band shown in the dashboard's gauge (green for Good, yellow for Normal, orange/red/
  purple for worse air quality). When the dashboard polls a new reading and the AQI
  band changes, update this CSS variable via JS so the whole scene's accent color
  shifts in sync with the gauge below it.
- Caption under the scene card: "Sensor mounted in Library Room 204 · actively sampling"
```

### 6.9 — Login page (matches the approved mockup)

```
Style the Laravel Breeze login page (Login.vue) to match this design:
- Centered modal-style card, white background, 22px border-radius, soft shadow
  (0 30px 60px -20px rgba(14,36,25,0.3))
- Heading "Log in to AirZen" in Space Grotesk, subtext "Access the dashboard and
  reading history." in muted green-grey (#5C7A67)
- Email and password fields with light green-tinted backgrounds (#EEF8F1), 10px
  radius, border color changes to brand green (#12A454) on focus
- Full-width green submit button (#12A454, hover #0B7A3F) labeled "Sign in"
- Page background should be the same light mint (#F4FAF6) as the dashboard
```

### 6.10 — Deployment to Railway

```
Help me deploy this Laravel + PostgreSQL project to Railway:
1. Set up a Railway project with a PostgreSQL plugin
2. Configure the Laravel .env for Railway's DB connection variables
3. Set up the Procfile / start command for Laravel on Railway
4. Set DEVICE_API_KEY as a Railway environment variable
5. Run migrations on deploy
6. Give me the final public URL format so I can point my ESP32 firmware's serverUrl at it
```

---

**Tip:** Run these roughly in order (6.1 → 6.10) as separate sessions/tasks — each one builds on the previous, so keep the earlier code around for the AI to reference when running later prompts. Prompt 6.8 (hero scene) can be done in parallel with 6.7 (dashboard) since they're both frontend-only and don't depend on each other.
