# School Transportation & Bus Tracking — Implementation Plan

> Phase 1 (architecture analysis) is complete. This document is the contract for the
> new module, designed to blend into the existing system as if it were always there.

## 0. Existing architecture findings (Phase 1)

| Area | Finding |
|---|---|
| Backend | Laravel 11, Sanctum, 4 auth guards: `web` (`App\User` = school staff/admin), `teacher`, `student`, `parent` (`App\Models\My_Parent`) |
| Tenancy | Manual scoping via `App\Services\Scoping\SchoolContext` (`schoolIdFrom(actor)`, `scope($query, $schoolId)`). All school-scoped tables carry `school_id` |
| RBAC | School-scoped `roles` table + `role_user` pivot; permissions = JSON array of keys on the role. `App\Services\Roles\PermissionRegistry` is the single source of truth (backend middleware `EnsurePermission` + frontend tab filtering). Super admin / role-less users = full access (opt-in) |
| Notifications | `Notification` + `NotificationRecipient` (polymorphic) models, bilingual en/ar text, `App\Services\FcmNotificationService` (legacy FCM HTTP API, `fcm_tokens` table keyed by user_id + user_type) |
| Real-time | **None.** `BROADCAST_DRIVER=null`, no pusher/echo anywhere. Dashboard uses React Query; mobile has a foreground notification poller. FCM push is the only push channel |
| Maps | No map library installed anywhere (dashboard or mobile) |
| Admin dashboard | React 18 + Vite + TS, react-query, axios client in `src/api/client.ts`, types in `src/types/`, pages registered in `App.tsx` behind `RequirePermission`, sidebar in `components/Sidebar.tsx` (Arabic labels, lucide icons). Shared components: `DataTable`, `EditModal`, `ConfirmModal` |
| Mobile | Flutter, `go_router`, `UserRole { teacher, student, parent }` enum in `core/models/app_models.dart`, modules in `core/data/school_modules.dart`, features per role under `lib/features/`. Login via `POST /login`, role parsed from `type`/`guard` in profile. Admin excluded from mobile |
| Audit | `App\Services\AuditLogger` (best-effort, school_id resolved from actor) |
| Conventions | Migrations: anonymous-class, FK + indexes, timestamps; soft deletes where deletion is user-facing; JSON casts for arrays; bilingual fields via Spatie Translatable |

## 1. Components reused (not rebuilt)

- **Auth**: all 4 Sanctum guards + `/login` (driver logs in as a normal `App\User`).
- **Users & roles**: a Driver **is** an `App\User` + school-scoped `Role` named `driver` (`role_user` pivot). No parallel auth.
- **Students/parents**: existing `students` / `my__parents` rows only; transportation hangs off them.
- **Tenancy**: `SchoolContext` for every query.
- **Notifications**: `Notification` + `NotificationRecipient` + `FcmNotificationService` + `fcm_tokens`.
- **Audit**: `AuditLogger` for admin/config actions.
- **Permissions**: new keys appended to `PermissionRegistry` (same group/tab granularity).
- **Dashboard infra**: `apiClient`, `DataTable`/`EditModal`/`ConfirmModal`, `RequirePermission`, sidebar pattern, react-query.
- **Mobile infra**: session controller, app shell, module registry, notification service.

## 2. New backend components

```
app/Models/Transportation/        Bus, DriverProfile, RouteModel, RouteStop,
                                  StudentTransportProfile, Trip, TripStudent,
                                  LocationUpdate, TripEvent, TransportationSetting
app/Http/Controllers/Api/Transportation/
  Admin/  BusController, DriverController, RouteController, StudentTransportController,
          TripController, LiveTrackingController, SettingsController, GeocodeController
  Driver/ DriverTripController    (today / start / location / next-stop / pickup / dropoff / emergency / end)
  Parent/ ParentTransportController (children / active trip / bus location / status)
app/Services/Transportation/
  MapService (provider abstraction) + Providers/NominatimProvider (free OSM; geocode/reverse/haversine/approx ETA)
  GeofenceService                 (server-side proximity, fire-once per stop)
  TripLifecycleService            (start/end/cancel, idempotent pickup/dropoff, snapshots)
  TransportationNotifier          (bilingual Notification + recipients + FCM)
  LocationService                 (store update, latest, pruning)
```

## 3. Database entities (new tables, all `school_id`-scoped, following existing conventions)

| Table | Key columns |
|---|---|
| `transportation_buses` | school_id, branch_id?, bus_number, name, license_plate, capacity, status (AVAILABLE/ASSIGNED/ON_TRIP/OFFLINE/MAINTENANCE/INACTIVE), notes, is_active, softDeletes |
| `transportation_driver_profiles` | school_id, **user_id (unique → users)**, employee_no, phone, license_no, status |
| `transportation_routes` | school_id, branch_id?, name, code, direction (HOME_TO_SCHOOL / SCHOOL_TO_HOME), bus_id?, driver_user_id?, scheduled_start_time, active_days (json), is_active, softDeletes |
| `transportation_route_stops` | route_id, student_id, stop_order (order preserved here) |
| `transportation_student_profiles` | school_id, **student_id (unique)**, is_enabled, morning_route_id?, afternoon_route_id?, per-direction pickup/dropoff address+lat+lng+time, notes |
| `transportation_trips` | school_id, route_id, bus_id, driver_user_id, direction, trip_date, scheduled_start_time, started_at, ended_at, status (SCHEDULED/READY/IN_PROGRESS/COMPLETED/CANCELLED/EMERGENCY), emergency_at, settings snapshot |
| `transportation_trip_students` | trip_id, student_id, stop_order, pickup/dropoff lat+lng+address snapshot, status (WAITING/PICKED_UP/DROPPED_OFF/ABSENT/CANCELLED), approaching flags, picked_up_at/by(+gps), dropped_off_at/by(+gps), **client_event_id unique (idempotency)** |
| `transportation_location_updates` | trip_id, bus_id, driver_user_id, lat/lng (decimal 10,7), accuracy, speed, heading, recorded_at; index (trip_id, recorded_at) |
| `transportation_trip_events` | school_id, trip_id, student_id?, type, lat/lng?, meta json, actor (morph) — operational timeline |
| `transportation_settings` | school_id unique, geofence_radius_m (default 150), gps_interval_s (default 10), parent_live_tracking, approaching_notifications, notify_parents_on_emergency, map_provider, working days/schedule defaults |

## 4. APIs (`routes/api.php`, all under `auth:any`)

Admin (gated with `permission:` keys):
```
GET/POST/PUT/DELETE /transportation/buses...
GET/POST/PUT       /transportation/drivers...        (creates App\User + driver role)
GET/POST/PUT/DELETE /transportation/routes...  + POST /routes/{id}/stops/reorder
GET/PUT            /transportation/students/{student}/transport-profile
GET/POST/PUT       /transportation/trips...  GET /transportation/trips/history?filters
GET                /transportation/live   (active trips + latest GPS)
GET/PUT            /transportation/settings
GET                /transportation/geocode?q= /reverse-geocode
```
Driver (controller-enforced: driver → assigned trip → assigned students):
```
GET  /driver/trips/today        GET /driver/trips/{trip}
POST /driver/trips/{trip}/start POST /driver/trips/{trip}/end   POST .../cancel
POST /driver/trips/{trip}/location          (idempotent batching-friendly)
GET  /driver/trips/{trip}/next-stop
POST /driver/trips/{trip}/students/{ts}/picked-up   (client_event_id idempotent)
POST /driver/trips/{trip}/students/{ts}/dropped-off
POST /driver/trips/{trip}/emergency
```
Parent (backend verifies Parent → Child → Assignment → Active Trip on every call):
```
GET /parent/transportation/children
GET /parent/transportation/children/{student}/active-trip
GET /parent/transportation/trips/{trip}/bus-location   (child must be on trip)
```

## 5. Permissions (appended to PermissionRegistry, group `transportation`)

`transportation.view` (overview), `transportation.buses.manage`, `transportation.drivers.manage`,
`transportation.routes.manage`, `transportation.students.manage`, `transportation.trips.manage`,
`transportation.live_tracking.view`, `transportation.history.view`, `transportation.settings.manage`,
`transportation.emergency.manage` — bilingual labels; dashboard tabs mirror them.

## 6–8. Dashboard / Driver / Parent screens

- **Dashboard** (new sidebar section «المواصلات», routes behind `RequirePermission`):
  Overview · Buses · Drivers · Routes (ordered stops, map picker) · Students (transportation profiles + coordinates) · Trips · Live Tracking (Leaflet/OSM) · History (filters: date/driver/bus/route/branch/status) · Settings.
- **Driver mobile** (new `UserRole.driver` + `driverModules`): Driver Home (bus/route/next trip, big Start button), Active Trip (next stop card, giant PICKED UP / DROPPED OFF), Route Map (flutter_map), Emergency (double-confirm), Trip Complete. GPS via `geolocator` while trip active (configurable interval); offline queue with client_event_id idempotency.
- **Parent mobile**: «My Child's Bus» — child selector, trip status states (NOT_STARTED → BUS_ON_THE_WAY → APPROACHING → PICKED_UP → IN_TRANSIT → DROPPED_OFF → COMPLETED), live bus map (own child only), pickup/drop-off timeline, transportation notifications.

## 9. Notification events (reuse existing infra)

TRIP_STARTED, BUS_APPROACHING_PICKUP, STUDENT_PICKED_UP, BUS_APPROACHING_DROPOFF,
STUDENT_DROPPED_OFF, TRIP_COMPLETED, TRIP_CANCELLED, EMERGENCY — persisted as
`Notification` (bilingual) + `NotificationRecipient` per parent + FCM best-effort; mirrored in `transportation_trip_events`.

## 10. GPS tracking architecture

Driver app posts to `/driver/trips/{id}/location` every `gps_interval_s` (default 10s, server-configurable, battery-conscious: skip when stationary). Server validates driver↔trip↔bus, writes `transportation_location_updates`, runs `GeofenceService`, then:
- **Admin**: React Query poll (5s) of `/transportation/live`.
- **Parent**: poll (10s) of bus-location endpoint, gated by `parent_live_tracking` setting.
- No websockets introduced — matches the existing architecture; upgrade path = Laravel Reverb later without API changes. Old location rows pruned by scheduled cleanup (keep last N per trip + last 90 days).

## 11. Map integration

`MapService` abstraction (`geocodeAddress / reverseGeocode / calculateDistance / estimateEta / providerName`) with a free **Nominatim (OpenStreetMap)** provider as default. Dashboard: `leaflet` + `react-leaflet` (free OSM tiles). Mobile: `flutter_map`. Provider swap = config change + one new provider class.

## 12. Geofencing logic

Server-side haversine in `GeofenceService`: on each location update for an `IN_PROGRESS` trip,
check distance from bus to the **next incomplete stop** (morning: pickup points; afternoon: dropoff points).
On first entry into `geofence_radius_m` (default 150m, per-school setting): fire-once flag →
`BUS_APPROACHING_*` event + parent notification. Driver confirmation (PICKED UP / DROPPED OFF) is a
separate explicit, idempotent action.

## 13. Security

- Backend authorization on every endpoint; never frontend-only.
- Parent: verify `My_Parent → students → trip_students → trip` chain before returning any location.
- Driver: verify `User(driver role) → trip.driver_user_id`, students limited to trip roster.
- Admin: `SchoolContext::scope` on every transportation query; super admin may target via `X-School-Id` (existing convention).
- Idempotency: `client_event_id` unique key on pickup/dropoff; start/end guarded by status transitions (409 on invalid transition).
- Location updates only accepted for `IN_PROGRESS` (or `EMERGENCY`) trips of the authenticated driver.
- AuditLogger for bus/driver/route/assignment/settings changes + all emergency actions.

## 14. Testing strategy

- Feature tests per role: admin CRUD + scoping isolation (school A cannot read school B), driver lifecycle (start→geofence→pickup→dropoff→end), parent authorization (cannot read other child's trip / trip history 403), idempotent pickup retries, invalid transitions, emergency.
- Seeders: demo school, buses, driver users, routes with stops, profiles with coordinates, scheduled trips.
- Manual: driver flow on device (permissions, offline queue), parent live map, admin live tracking.

## 15. Migration & rollout strategy

1. Migrations are additive only — zero changes to existing tables except none required (new tables only). Existing features untouched.
2. PermissionRegistry append → existing roles simply lack transportation keys until granted (opt-in, consistent with RBAC).
3. Mobile: `UserRole.driver` additive; existing roles unaffected. Backend login response gains `roles`/`type: driver` only for driver accounts.
4. Rollout order: backend → dashboard → driver app → parent app; each phase independently deployable behind permissions.

---

# IMPLEMENTATION STATUS (updated 2026-09-26)

## ✅ Completed

**Phase 1 — Analysis**: full architecture map (guards, RBAC registry, SchoolContext,
notification/FCM stack, no real-time infra, no maps) — see table above.

**Phase 2 — Design**: 11 new tables (all additive, `school_id`-scoped, no existing
table modified except additive relations), namespace `App\Models\Transportation`.

**Phase 3 — Backend** ✅ tested
- 11 migrations applied; 10 models; 6 services (MapService + Nominatim provider,
  GeofenceService, TripLifecycleService, TransportationNotifier, LocationService).
- 17 controllers (Admin / Driver / Parent), 36 routes registered under `auth.any`.
- 10 permission keys appended to PermissionRegistry (group `transportation`).
- Login returns `type: driver` for driver accounts (mobile detection).
- Pruning command `transportation:prune-locations` (scheduled daily 01:15).
- **14 new feature tests, all passing**; full suite 82 passed / 5 pre-existing
  AI-assistant failures (external Gemini API rate limits, unchanged by this work).

**Phase 4 — Admin Dashboard** ✅ builds (`vite build` clean; tsc clean for all new code)
- New «المواصلات» sidebar section with 9 permission-gated pages:
  Overview, Buses, Drivers, Routes (ordered stops + map), Students (profiles with
  draggable OSM map picker + geocoding), Trips, Live Tracking (5s polling, Leaflet/OSM,
  trail + roster states), History (6 filters), Settings.
- Shared `TransportMap` component (free OSM tiles, replaceable provider).

**Phase 5 — Driver mobile** ✅ `flutter analyze` clean (0 errors)
- `UserRole.driver` added; drivers log in with existing staff auth (email/password).
- Driver Home (bus, today's trips, giant START), Active Trip screen (next-stop card,
  huge PICKED UP/DROPPED OFF, distance/ETA, EMERGENCY with confirmation, END),
  offline-first GPS (10s interval, persisted retry queue, idempotent events via
  client_event_id), explain-then-request location permission.

**Phase 6 — Parent mobile** ✅
- «My Child's Bus» module: per-child status cards, live trip screen with OSM map
  (flutter_map), distance/ETA to own child's stop only, trip timeline, notifications
  deep-link to the bus screen.

**Phase 7 — Real-time**: driver → API → MySQL; admin dashboard polls /transportation/live
(5s); parent polls bus-location (10s); FCM push for events. No second real-time stack.

**Phase 8 — Geofencing** ✅ server-side, fire-once per stop, configurable radius
(default 150m), evaluated on every GPS update, triggers BUS_APPROACHING_* + parent
notification automatically (no driver action).

**Phase 9 — Testing**: 14 backend feature tests (admin CRUD, tenant isolation,
permission gating, driver HTTP lifecycle incl. idempotent pickup retry, cross-driver
403, parent authorization incl. other-child 404, emergency + admin live view,
confirmation requirement, login role types).

## 🔜 Remaining / follow-up recommendations
- On-device E2E walkthrough with real GPS hardware (driver app) — needs a physical
  device + running backend + FCM_SERVER_KEY for push.
- Background location on Android (foreground service) if trips must outlive the app
  being backgrounded — currently while-in-use, matching battery-conscious defaults.
- Optional: Laravel Reverb upgrade path for sub-second live tracking.
- Demo data: `php artisan db:seed --class="\TransportationDemoSeeder"` (after seeding a
  school + students; driver login driver.demo@school.local / driver123).

## ⚠️ Operational notes
- Running `php artisan test` executes `migrate:fresh` against the configured MySQL DB
  (Laravel 11 RefreshDatabase behavior on non-in-memory connections) — the dev database
  is rebuilt by the test suite. This is pre-existing project behavior.
- The 3 tsc errors in ProfitLossPage/SalariesPage/TimetableScenariosPage and the 4
  stale widget tests are pre-existing and unrelated to this module.

## ✅ Background services (added 2026-09-26, second pass)

Driver GPS tracking now survives the app being backgrounded (Android):

- **`DriverLocationForegroundService`** — a `location`-typed foreground service
  (reuses the dormant `flutter_background_service` dependency; no new packages).
  Headless `@pragma('vm:entry-point')` isolate reads token/base-url/trip-handle
  from SharedPreferences, posts GPS on the configurable interval, shows a
  persistent Arabic notification ("رحلة نشطة — آخر تحديث HH:MM:SS"), degrades to
  "لا يوجد اتصال — N نقطة بالانتظار" offline, and stops itself when the trip
  handle is cleared.
- **`GpsQueueStore`** — offline queue shared between the app isolate and the
  service isolate via SharedPreferences (bounded at 500 points, oldest-first
  flush, time-stamped so no duplicates); **`ActiveTripStore`** — persisted trip
  handle enabling resume after process death.
- **Manifest**: `FOREGROUND_SERVICE_LOCATION` permission + service declaration
  with `foregroundServiceType="location"` (Android 14+ requirement).
- **Lifecycle wiring**: initialized in `main()`; started on trip start; the
  active-trip screen re-attaches without restarting the isolate; logout clears
  the handle + stops the service (token safety); iOS keeps the in-app timer
  fallback (no always-on foreground service on iOS).
- iOS: `NSLocationWhenInUseUsageDescription` added earlier.
