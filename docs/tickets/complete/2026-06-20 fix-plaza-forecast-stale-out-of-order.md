# Fix: Plaza weather forecast shows stale / out-of-order days until manual refresh

## Context
**Current behavior**: On the Plaza page (the "Here's the current forecast" block in the Hello dialog), the forecast can render days that look out of order and with the current weekday missing — e.g. `Now, Thu, Fri, Sun, Mon, Tue, Wed` (Saturday absent). A manual page reload fixes it. No data is wrong on the server; the frontend is displaying a forecast window it fetched on an earlier day and never refreshed.

**New behavior**: The forecast always reflects the current UTC day. `Now` is today, followed by the next six days in chronological order, with no stale past-days and no scrambling — without needing a reload. Two independent defects are fixed: (1) the service's day-rollover check that fails to refetch across week boundaries, and (2) the component rendering past-dated entries from a stale array instead of dropping them.

## Root Cause
The API (`WeatherService::getWeatherForecast`, `api/src/Service/WeatherService.php`) always returns exactly 7 days in chronological order starting at the server's *current* day (`for($day = 0; $day <= 6; ...)`). It is never out of order. The bug is entirely client-side, in two places:

1. **Refresh gate** — `WeatherService` (`webapp/src/app/module/shared/service/weather.service.ts`) decides whether to refetch with `new Date().getUTCDay() > this.#lastUpdated.getUTCDay()`. `getUTCDay()` is a **day-of-week number (0–6)**, not a calendar date. The most severe consequence: once `#lastUpdated` lands on a **Saturday (6)**, no later day-of-week is ever `> 6`, so the page **stops refreshing the forecast for the rest of the session**. Backward-wrapping gaps (e.g. a tab suspended Fri→Sun) also skip the refetch. The window then drifts: each passing day, its first entries fall into the past.

2. **Stale-array rendering** — `WeatherForecastComponent` (`.../plaza/component/weather-forecast/weather-forecast.component.ts`) splits the array into `today = find(entry whose date is today)` and `forecast = filter(everything that isn't today)`, using the array verbatim. When the array's window starts in the past, the leading past-days render *before* `Now`, and today's entry — plucked into `Now` from the middle — looks "missing" from the list. With a window of `[Thu,Fri,Sat,Sun,Mon,Tue,Wed]` viewed on Saturday, this produces exactly `Now, Thu, Fri, Sun, Mon, Tue, Wed`.

A manual reload re-instantiates the root-provided `WeatherService` (`weather` BehaviorSubject resets to `null`, the `timer(0, 1000)` fires immediately and refetches), which is why refreshing "fixes itself."

## Scope
### In scope
- Fix the refresh gate in `WeatherService` to trigger on a change of **UTC calendar date**, not day-of-week.
- Make `WeatherForecastComponent` defensive: exclude any entry dated before today from the forecast list (and from the `today` match), so a momentarily-stale array degrades gracefully instead of rendering scrambled.

### Out of scope
- **Do not add `.sort()`** to the forecast — the array is already chronological; sorting leaves the stale past-days in place and does not fix the symptom. This is the wrong fix and must not be used.
- Any backend change (`WeatherService.php`, `GetForecastController`). The API contract is correct.
- **Live midnight rollover on an open page** (separate follow-up): `currentDate` (the Care-Package check) and the `today`/`forecast` split are computed once and don't re-derive at UTC midnight while the page stays open. Same family of bug; track separately, do not bundle here. Note the service fix already causes a refetch (and thus a fresh `weather` emission that re-runs the split) shortly after midnight, so this follow-up is a minor polish, not a correctness gap.

## Relevant Docs & Anchors
- **Code anchors (the fix)**:
  - `WeatherService` constructor timer + `updateWeather` (`webapp/src/app/module/shared/service/weather.service.ts`) — the `#lastUpdated` / `getUTCDay()` gate to replace.
  - `WeatherForecastComponent.ngOnInit` subscribe callback (`.../plaza/component/weather-forecast/weather-forecast.component.ts`) — the `today` find / `forecast` filter to harden.
  - `weather-forecast.component.html` — renders `Now` plus `@for(day of forecast …)` with `day.date|date:'EEE':'UTC'`. No template change is required, but read it to confirm the consumer expectations.
- **Idiom to mirror**: `ClaimRewardsDialog` (`.../plaza/dialog/claim-rewards/claim-rewards.dialog.ts`) computes today's UTC date as `new Date().toISOString().substring(0, 10)` — reuse this exact idiom for the `YYYY-MM-DD` comparison key (string compare is also valid for `<`/`>=` on ISO dates).
- **Backend contract (read-only context)**: `WeatherService::getWeatherForecast` (`api/src/Service/WeatherService.php`) returns `today..+6` chronological; `GetForecastController` maps each to `{ date: 'Y-m-d', sky, holidays }`.

## Constraints & Gotchas
- **Stay UTC-consistent.** The whole feature is UTC: the template uses `date:'…':'UTC'`, the component uses `toISOString()`, and the server day boundary is UTC. "New day" and "before today" must both mean *UTC* day. Do not introduce local-time (`getDate()`, `getDay()`) comparisons.
- **`date` is a `YYYY-MM-DD` string** (`WeatherDataModel.date`). Today's key from `toISOString().substring(0, 10)` is the same shape, so `entry.date === todayKey` and `entry.date >= todayKey` are well-defined lexicographic comparisons — no `new Date()` round-trip needed per entry.
- **Preserve the existing self-heal/backoff in `updateWeather`.** On empty/`error` responses it sets `#lastUpdated = null` to force a retry next tick. Whatever type `#lastUpdated` becomes (date-string vs. `Date`), keep the "`null` ⇒ refetch" behavior intact.
- **`>6-days-stale` edge case.** If the array is so old that today isn't in it, `today` is `null` and the template shows the loading throbber indefinitely. The service fix prevents reaching that state; the component's defensive filter also makes it self-correct on the next refetch. Don't add separate handling for it.

## Open Decisions
1. **`#lastUpdated` representation** — store as the `YYYY-MM-DD` string (compare `!==` against today's key) vs. keep a `Date` and compare `toISOString().substring(0,10)` of each. Default: store the string; it's the smallest change and reads clearly. Keep the `null` sentinel either way.
2. **Where the component drops past-days** — filter inside the existing `subscribe` callback vs. a small derived getter. Default: filter in the callback, mirroring the current shape (compute `today` and `forecast` from a single `>= todayKey` pass).

## Acceptance Criteria
- [ ] `WeatherService` triggers a refetch when the current UTC date differs from the last-updated UTC date, and does **not** rely on `getUTCDay()` (or any day-of-week value) to detect a new day.
- [ ] After `#lastUpdated` falls on a Saturday, the service still refetches on the following Sunday (and every subsequent day) within the same session — i.e. there is no day-of-week value past which refreshing stops.
- [ ] The empty-response / error retry behavior is preserved: a forecast response with no days, or a failed request, still forces a refetch on a later tick.
- [ ] `WeatherForecastComponent.forecast` never contains an entry whose `date` is before the current UTC date; `today` matches only the entry whose `date` equals the current UTC date (or is `null` if none).
- [ ] Given a stale window `[Thu,Fri,Sat,Sun,Mon,Tue,Wed]` evaluated on Saturday, the rendered list is `Now(Sat)` followed by `Sun, Mon, Tue, Wed` in order — no `Thu`/`Fri` past-days, no scrambling.

## Implementation

### 1. Replace the day-of-week refresh gate with a UTC-date comparison
In `WeatherService` (`weather.service.ts`), change the rollover check so it refetches when today's UTC calendar date differs from the last successful update's date. Compute today's key with the same idiom as `ClaimRewardsDialog` (`new Date().toISOString().substring(0, 10)`). Per Open Decision 1, store `#lastUpdated` as that `YYYY-MM-DD` string (defaulting to `null`), set it when a refetch is initiated, and keep the `null`-forces-refetch paths in `updateWeather`'s empty/error branches. The guard condition becomes "no data yet, or `#lastUpdated` is `null`, or today's key differs from `#lastUpdated`." Leave the `timer(0, 1000)` cadence and the `#weatherAjax.closed` in-flight guard as they are.

### 2. Drop past-dated entries when splitting today vs. forecast
In `WeatherForecastComponent.ngOnInit` (`weather-forecast.component.ts`), compute today's UTC key once (same idiom). From the emitted array, set `today` to the entry whose `date` equals the key (or `null`), and `forecast` to entries whose `date` is **strictly after** the key — i.e. `entry.date > todayKey` (string comparison is correct for ISO dates), which both removes today and excludes any past-days. This replaces the current `find` / `filter(!startsWith(today))` pair, whose negation kept past-days. No template change is needed.

### 3. Sanity-check the template consumer
Confirm `weather-forecast.component.html` still works unchanged: `Now` renders from `today`, and `@for(day of forecast …)` now iterates only future days in chronological order. The `allowanceDayOfWeek` / Care-Package logic is untouched (its open follow-up is noted in Out of Scope).

## Test Plan
- [ ] Build/lint the webapp (the project's standard Angular build) and confirm no TypeScript errors in the two changed files.
- [ ] In the running app, open the Plaza Hello dialog and confirm the forecast shows `Now` = today and the next six days in order.
- [ ] **Stale-window regression (unit or manual harness):** feed `WeatherForecastComponent` a `weather` value whose first entries predate today (e.g. a window starting two days ago) and assert `forecast` contains no past-days and `today` is the current-day entry — reproducing the `Now, Thu, Fri, Sun, Mon, Tue, Wed` screenshot scenario and confirming it now renders correctly.
- [ ] **Refresh-gate check:** simulate the Saturday→Sunday rollover (set `#lastUpdated` to a Saturday date key, advance to Sunday) and confirm `updateWeather` is invoked — verifying the bug where day-of-week `0 > 6` previously blocked the refetch.
- [ ] Confirm a manual page reload still produces a correct forecast (existing self-heal path remains intact).

## Learnings

### Architectural decisions
- **Open Decision 1 (`#lastUpdated` representation)** — resolved to the default: store a `YYYY-MM-DD` string (`new Date().toISOString().substring(0, 10)`) and compare with `!==`, keeping the `null` sentinel. Smallest diff, and it makes the gate a plain string-equality check instead of two `getUTCDay()` calls.
- **Open Decision 2 (where the component drops past-days)** — resolved to the default: filter inside the existing `subscribe` callback. `today` = the entry whose `date === todayKey`; `forecast` = entries with `date > todayKey`. The single `>` pass both removes today and excludes past-days, replacing the old `find` / `filter(!startsWith)` pair whose negation was what kept the stale leading days.

### Interesting tidbits
- `WeatherDataModel.date` is a `YYYY-MM-DD` string and the server window is UTC-anchored, so lexicographic string comparison (`===`, `>`) is exactly chronological — no `new Date()` round-trip per entry. This is why the whole fix is pure string work and stays UTC-consistent by construction (no `getDate()`/`getDay()` introduced).
- The original gate's worst case was structural, not a typo: `getUTCDay()` returns 0–6, so once `#lastUpdated` landed on Saturday (6) **no** later day-of-week could ever be `> 6`, freezing the forecast for the rest of the session. The string-date compare has no such ceiling.
- The error/empty self-heal path needed no change: `updateWeather` still sets `#lastUpdated = null` on failure, and the gate's `=== null` clause re-triggers a refetch on the next tick. (That `=== null` clause is technically redundant now — `null !== today` is already true — but kept for parallelism with the ticket's stated guard and to make the self-heal intent explicit.)

### Tests
- The webapp ships **no `.spec.ts` files at all** — there is no unit-test suite or harness despite Karma/Jasmine being in `devDependencies`. Adding the first-ever spec for a small string-comparison change would fight the project's manual-testing convention, so the Test Plan's "unit or manual" items were verified by inspection/manual reasoning rather than an automated test. Verification was a `tsc --noEmit` type-check (exit 0); template was unchanged.

### Related areas affected
- **Live midnight rollover on an open page** remains an explicit follow-up (see Out of scope): `currentDate` and the `today`/`forecast` split don't re-derive at UTC midnight while the page stays open. The service fix now forces a refetch shortly after midnight, which re-runs the split, so this is polish rather than a correctness gap.
