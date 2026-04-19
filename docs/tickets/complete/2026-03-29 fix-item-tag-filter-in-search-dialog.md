# Fix Item Tag Filter in Item Search Dialog

## Summary
The item group/tag filter in the "More..." search dialog (used by basement, market, encyclopedia, etc.) doesn't work — the user can type text but the value never reaches the API. Replace the broken `datalist` input with a typeahead modeled after the journal's `ActivityTagInputComponent`.

## Context
**Current behavior**: The item group `<input>` in `item-search.dialog.html` uses `[(ngModel)]` inside a `<form>` without a `name` attribute. Angular requires either `name` or `[ngModelOptions]="{standalone: true}"` for `ngModel` inside forms — without it, the model binding silently fails. The user sees text in the input, but `filter.itemGroup` stays `null`, so nothing is sent to the API. The backend filtering (`InventoryFilterService.filterItemGroup`) works correctly.

**New behavior**: The item group input should be a typeahead that shows matching suggestions from the already-fetched item groups list. The user must select a valid group from the dropdown. Once selected, it displays as a tag chip (existing behavior) that can be cleared. The pattern should follow the journal's `ActivityTagInputComponent` approach — native element refs with `fromEvent`, not `ngModel` inside the form.

## Implementation

### 1. Study the existing patterns
The journal's tag input (`webapp/src/app/module/pet-logs/component/activity-tag-input/`) is the closest analogue. Key differences from what we need:
- Journal supports **multiple** tags; the item search needs only **one** (single-select)
- Journal tags come from `PetActivityTagRepositoryService` (API-backed with caching); item groups are already fetched in `ngOnInit` from `/encyclopedia/item-groups` as a `string[]`
- Journal tags are objects `{ title, color, emoji }`; item groups are plain strings

The item search dialog already has the right structure — a tag chip display when selected (`@if(itemTag)`) and an input when not. The input just needs to work.

### 2. Replace the datalist input with a typeahead in the dialog
In `webapp/src/app/module/shared/dialog/item-search/item-search.dialog.html`, replace the `@else` block containing the `<input>` + `<datalist>` with a typeahead input pattern. The typeahead should:
- Use a `#tagSearch` ViewChild ref and `fromEvent` for keyup (like `ActivityTagInputComponent`)
- Filter the already-loaded `itemGroups` array locally (no API call needed)
- Show a dropdown of matching suggestions below the input
- Support keyboard navigation (ArrowUp/Down to move, Enter to select, Escape to close)
- On selection: set `filter.itemGroup` to the chosen string, set `itemTag` for display, clear the input, hide suggestions
- The existing `@if(itemTag)` block with the tag chip + clear button stays as-is

### 3. Update the dialog TypeScript
In `webapp/src/app/module/shared/dialog/item-search/item-search.dialog.ts`:
- Add a `ViewChild` for the typeahead input element
- Add `suggestions: string[] | null = null` and `selectedSuggestion: number` properties
- Add `fromEvent` subscriptions for keyup (filtering) and keydown (keyboard nav), following the `ActivityTagInputComponent` pattern
- Add a `doSelectGroup(group: string)` method that sets `filter.itemGroup`, builds `itemTag`, and clears the input
- Clean up subscriptions in `ngOnDestroy` (the dialog doesn't currently implement `OnDestroy` — add it)
- Remove `filterItemGroups()` and `filteredItemGroups` if they're fully replaced by the new suggestion logic, or repurpose them

### 4. Add dropdown styles
The dialog's SCSS file (`item-search.dialog.scss`) needs styles for the suggestions dropdown. Reference `activity-tag-input.component.scss` for the `.select` dropdown, `.selected` highlight, and button styling. Keep it simple — a positioned list below the input with a max-height and overflow scroll.

## Files
### Modify
- `webapp/src/app/module/shared/dialog/item-search/item-search.dialog.html` — replace datalist with typeahead dropdown
- `webapp/src/app/module/shared/dialog/item-search/item-search.dialog.ts` — add ViewChild, fromEvent subscriptions, keyboard nav, selection logic
- `webapp/src/app/module/shared/dialog/item-search/item-search.dialog.scss` — add dropdown styles

## Test Plan
- [ ] Open the "More..." dialog from basement (or market, or encyclopedia)
- [ ] Click into the Item Tag input — suggestions dropdown appears showing all item groups
- [ ] Type a partial name (e.g. "Fresh") — suggestions filter to matching groups
- [ ] Click a suggestion — input is replaced by the tag chip, suggestions close
- [ ] Click the tag chip — clears the tag, input reappears
- [ ] Use keyboard: type, arrow down/up to highlight, Enter to select — works correctly
- [ ] Press Escape — closes suggestions without selecting
- [ ] Select a tag and click Search — results are correctly filtered by that item group
- [ ] Clear the tag, click Search — results are unfiltered (no itemGroup param sent)
- [ ] Verify the filter count badge on the "More..." button increments when a tag is active

## Learnings

### Architectural decisions
- Used `@angular/cdk` `CdkConnectedOverlay` for the dropdown instead of a manually-positioned `<div>`. CDK overlay renders outside the dialog DOM, avoiding scroll-in-scroll issues. CDK is already installed as a transitive dependency of `@angular/material`.
- Used `STANDARD_DROPDOWN_BELOW_POSITIONS` for overlay positioning — CDK's built-in position set that handles viewport edge cases.
- Used simple template event bindings (`(input)`, `(focus)`, `(keydown)`) instead of `fromEvent` subscriptions. Since the `ngModel`-inside-form issue is avoided entirely (no `ngModel` at all), template events work cleanly and eliminate the need for manual subscription management and `OnDestroy`.
- Dropdown styles live in `controls.scss` (global) because CDK overlays render outside component scope in `.cdk-overlay-container`.

### Problems encountered
- Initial implementation used a custom dropdown `<div>` inside the dialog, which caused scroll-in-scroll UX issues. Switched to CDK overlay to render the dropdown outside the dialog entirely.
- `ActiveDescendantKeyManager` from CDK was considered for keyboard nav but requires each option to be a component implementing `Highlightable` (with `setActiveStyles()`/`setInactiveStyles()`). Too much ceremony for a flat string list — manual index tracking with `selectedSuggestion` is simpler and sufficient.
- Keyboard arrow navigation in a scrollable dropdown needs explicit `scrollIntoView({ block: 'nearest' })` on the selected element — the browser won't scroll a non-focused element into view automatically. Easy to miss during initial implementation.

### Interesting tidbits
- Angular silently drops `ngModel` bindings inside `<form>` when there's no `name` attribute — no console warning, no error. The input appears to work (text shows up) but the model is never updated. This is a known Angular gotcha.
- `@angular/cdk` is available in any project using `@angular/material` without adding it to `package.json` — it's a peer/transitive dependency. It provides overlay, a11y, and keyboard management primitives without any Material Design styling opinions.

### Rejected alternatives
- Simply adding `name="itemGroup"` to the existing `<input>` — would fix the binding but allows arbitrary text that doesn't match any item group, leading to empty results with no feedback to the user.
- Using Angular Material's `mat-autocomplete` — has full a11y and overlay support but brings opinionated Material Design styling that conflicts with the app's custom design and breaks on Material upgrades.
- Custom positioned `<div>` dropdown (first implementation attempt) — scroll-in-scroll inside the dialog was poor UX, especially on mobile.
