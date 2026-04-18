# Poppyopedia Groups Page: Type Icon Column

## Summary
Add a narrow, un-headed column on the left of the Poppyopedia "Pet Groups" results table that shows the icon for each group's type (mic for bands, telescope for astronomy, etc.).

## Context
**Current behavior**: The groups results table at `/poppyopedia/group` has three columns — "Name & Type", "Created", "Last Met". The type is communicated only as a small text label below the group name.

**New behavior**: A fourth column precedes "Name & Type" with no header text and an icon in each row representing the group's type, using the same SVG assets already used for group icons elsewhere in the app (e.g. on the Pet Friends panel).

## Acceptance Criteria
- [ ] The groups results table on `/poppyopedia/group` has a new leftmost column with an empty `<th>`
- [ ] Each row in that column shows the SVG icon matching the row's group type (band → `band.svg`, astronomy → `astronomy.svg`, gaming → `gaming.svg`, sportsball → `sportsball.svg`)
- [ ] Clicking the icon (or anywhere in the row) still navigates to the group detail page — row-level `routerLink` behavior is unchanged
- [ ] The icon does not introduce a duplicate accessible label — screen readers should announce the type once (via the existing small-text label in the next column), not twice (image is `alt=""` + `aria-hidden="true"`)
- [ ] The existing "Created" and "Last Met" columns are unchanged in content and order
- [ ] A new `petGroupIcon` pipe exists and is the only place that maps `PetGroupTypeEnum` → asset filename (i.e. the existing `GROUP_TYPE_IMAGES` constant in `pet-friends.component.ts` is removed and that component uses the pipe)

## Implementation

### 1. Create a `petGroupIcon` pipe as the single source of the type → image mapping
**Why**: The asset-path mapping currently lives as a local `GROUP_TYPE_IMAGES` array inside `pet-friends.component.ts:51-57`. Adding a second call site on the groups page would duplicate it. A pipe named alongside the existing `petGroupLabel` / `petGroupProductLabel` pipes keeps the naming convention and gives future callers one place to reach for.

**Where**: new file `webapp/src/app/module/shared/pipe/pet-group-icon.pipe.ts`

Mirror the structure of `pet-group-label.pipe.ts` — standalone pipe, `readonly` lookup array, type-guard returning `''` (or a sensible fallback) for out-of-range values. The pipe should take the numeric group type and return the **full asset URL** (e.g. `/assets/images/groups/band.svg`) so callers can bind it directly as `[src]="group.type|petGroupIcon"` without string concatenation. Lookup entries (in order, index 1-based to match `PetGroupTypeEnum`):
- 1 → `band`
- 2 → `astronomy`
- 3 → `gaming`
- 4 → `sportsball`

For an unknown / zero value, return `''` — a bound `<img src="">` is a cheap no-op and keeps the pipe side-effect free. (Callers who care can guard with `@if(group.type|petGroupIcon; as src)`.)

### 2. Switch the Pet Friends panel to the new pipe
**Why**: We're introducing the pipe specifically to be the single source of truth for this mapping — the existing call site should use it from day one, otherwise we've just moved duplication around.

**Where**:
- `webapp/src/app/module/shared/component/pet-friends/pet-friends.component.html` — replace the inline concatenation on line 27 with `[src]="group.type|petGroupIcon"`
- `webapp/src/app/module/shared/component/pet-friends/pet-friends.component.ts` — delete the `GROUP_TYPE_IMAGES` class field (lines 51-57), add `PetGroupIconPipe` to the `imports:` array on the `@Component` decorator

### 3. Add the type-icon column to the groups table
**Why**: The user wants a visual cue for group type alongside the existing text label.

**Where**: `webapp/src/app/module/encyclopedia/page/groups/groups.component.html`

- In `<thead>`, add a new empty `<th></th>` as the first cell (before "Name & Type").
- In each `<tr>` inside `<tbody>`, add a new first `<td>` containing an `<img>` with `[src]="group.type|petGroupIcon"`.
- Set `alt=""` **and** `aria-hidden="true"` on the `<img>` — the type is already announced by the existing `<small>{{ group.type|petGroupLabel }}</small>` in the next cell, so the icon is purely decorative and should be fully hidden from assistive tech.

### 4. Wire the pipe into the groups page module
**Why**: `GroupsComponent` is declared in the encyclopedia module (check `encyclopedia.module.ts` for whether it's a standalone component or NgModule-declared — the pattern used there dictates where `PetGroupIconPipe` needs to be imported). Follow whatever route the sibling `PetGroupLabelPipe` uses on the same template.

### 5. Style the icon cell
**Why**: Table cells default to text-line sizing, which will leave the icon oversized and the column too wide. The Pet Friends panel sizes group icons at `0.3in` square (`pet-friends.component.scss:63-69`), which is a good match in a table-row context.

**Where**: `webapp/src/app/module/encyclopedia/page/groups/groups.component.scss`

Add a rule that constrains the icon to ~0.3in square and keeps the cell tight (e.g. `width: 1%` on the new `<td>`/`<th>` so the column shrinks to the icon's width, with the "Name & Type" column absorbing the rest). Keep the rule scoped (e.g. via a class on the new `<td>`) so it doesn't affect other table cells on the page.

## Test Plan
- [ ] Navigate to `/poppyopedia/group` with the default empty search — confirm the results table renders with an un-headed leftmost column containing the correct icon for each group (band icon for bands, astronomy icon for astronomy labs, etc.)
- [ ] Click the icon in a row — confirm it navigates to `/poppyopedia/group/<id>` (same behavior as clicking the name)
- [ ] Resize the browser to a narrow width — confirm the icon column stays narrow and the "Name & Type" column still takes most of the horizontal space
- [ ] Inspect the rendered `<img>` in devtools — confirm `alt=""` and `aria-hidden="true"` so the icon is marked decorative
- [ ] Navigate to a pet's Friends panel (somewhere a pet belongs to a group) — confirm the group icons still render correctly after the switch to `petGroupIcon`
- [ ] With a screen reader (or by reading the DOM), confirm each row announces the group type once (from the `<small>` label) — not twice
- [ ] If a group of an unexpected type ever appears (e.g. a newly-introduced type that isn't in `GROUP_TYPE_IMAGES`), the image will 404 rather than break the row — acceptable for this ticket since the set of types is stable, but worth eyeballing the mapping matches the current `PetGroupTypeEnum` before merge

## Learnings

### Architectural decisions
- **Out-of-range returns `''`, not a fallback image.** The pipe deliberately returns an empty string for unknown group types, mirroring `pet-group-product-label.pipe.ts`'s "empty string for empty slot" convention. A bound `<img src="">` is a no-op and keeps the pipe side-effect free — no magic placeholder asset to maintain.
- **Pipe returns the full URL, not just the filename.** Callers bind `[src]="group.type|petGroupIcon"` directly with zero concatenation, which is the whole point of having the pipe be the single source of truth — if it returned just `"band"`, every caller would repeat the `/assets/images/groups/...svg` wrapping and the duplication would simply move one layer outward.

### Interesting tidbits
- `GroupsComponent` is still an NgModule-declared component (`standalone: false`), so its pipe dependencies are wired through `EncyclopediaModule`'s `imports:` array — not the component's own `imports:`. Sibling pipes on the same template (`PetGroupLabelPipe`) were already registered there, which made the pattern obvious.
- `PetFriendsComponent` is already a standalone component, so the same pipe had to be registered in two totally different places (module `imports:` vs component `imports:`) — a direct consequence of the project's ongoing standalone-migration mid-state.
- The Pet Friends panel uses `0.3in` for group icon squares (inches!), which matches the existing project convention — SCSS in this codebase uses `in` for small UI icons in multiple places. Worth matching rather than converting to px/rem for consistency.

### Reusable patterns
- Pipes named `petGroup*` (label, productLabel, icon) all follow the same shape: standalone pipe + `readonly` 1-indexed lookup array + bounds-checked `transform()`. The index-1 slot matching `PetGroupTypeEnum` makes the arrays line up with the enum without a `Map<enum, string>` indirection.
