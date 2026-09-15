# Beehive Help Page: Hex Spaces & Who Collects What

## Context
**Current behavior**: Poppyopedia » Glossary » Beehive describes the hive as something that "produces several items passively" and explains Flower Power as fuel the bees "use up to produce more and faster." Nothing mentions spaces, terrains, or the different bee types.

**New behavior**: The page explains that the hive's spaces come in different terrain types with different stuff to collect, and how each bee type (and a helper pet) collects from a space - or collects something else instead. The Flower Power section is reworded so it no longer says the bees "produce" things. The once-per-space rule, full-clear reset, Basement bonus, and Gold Compass re-roll are deliberately left unexplained (they live under "More?!").

## Prerequisites
- `beehive-hex-spaces-and-per-bar-harvest` (so the copy describes shipped behavior; terrain names and bar names must match the UI).

## Scope
### In scope
- Copy edits to `beehive-help.component.html` only.

### Out of scope
- Any mention of the once-per-space rule, the full-clear reset, the +60 Basement bonus, or the Gold Compass re-roll.
- Enumerating which items each terrain yields.
- Changes to the beehive page itself, the Glossary index, or other help pages.

## Relevant Docs & Anchors
- **Style exemplars** (read all four before writing a word): `tools-and-hats.component.html`, `pet-level.component.html`, `lunchboxes.component.html`, and the existing `beehive-help.component.html` under `webapp/src/app/module/encyclopedia/page/help/`.
- **Behavior source of truth**: `HarvestController` and the terrain enum from the prerequisite ticket - the bar names (Royal Jelly, Honeycomb, Normal Bee Stuff, Helper) and terrain names (jungle, beach, grassy, rocky) in the copy must match what the beehive page renders.
- **Memory / house rules**: `feedback_docs_no_trigger_enumeration` (teach the mechanic, don't list every case), `feedback_text_style_ascii_american` (ASCII hyphens, American spelling), `project_pets_no_anatomy_assumptions`.

## Constraints & Gotchas
- **House style, observed**: short `<p>` paragraphs, one idea each; `<h4>` section headings; a light, chatty voice with the occasional parenthetical aside or one-line joke paragraph ("Arguably."); exclamation points used sparingly but sincerely; cross-links via `<app-help-link link="…" />` inline right after the term (see the existing `flavors` links on this page); a closing "More?!"-style tease rather than exhaustive detail. Reproduce that; don't drift into reference-manual tone.
- **Keep "More?!"** as the last section, unchanged in spirit - it's the stated cover for everything this ticket leaves out.
- The page must not claim the hive "produces" items; the new mental model is *bees go out and collect from a space*. Flower Power feeds the bees so they're ready to go out sooner.
- Pets' language is ambiguous and their anatomy varies; the helper sentence should say the pet hunts/gathers, nothing about talking to bees or carrying things in paws.

## Open Decisions
1. **Section layout** - fold the space/terrain explanation into the intro paragraph vs. a new `<h4>` ("The Hive's Spaces" or similar) between the intro and Flower Power. Default: new `<h4>` section; the intro stays one or two sentences.
2. **Flower Power rewrite** - direction is fixed (see Constraints); exact wording is the implementer's. Draft to start from: "Flower Power is a kind of fuel for your beehive. It's filled by feeding the beehive, and keeps the bees energetic - the more they have, the sooner they're ready to head out and collect. Some things (like Royal Jelly) only get made while the bees are well-fed! The more bees in your Beehive, the faster they work, but also the more items you'll have to feed them!"
3. **Terrain phrasing** - name all four terrains in one sentence ("jungle, beach, grassy, and rocky spaces") vs. "several kinds of terrain". Default: name them; the names are visible in the UI anyway.

## Acceptance Criteria
- [ ] The page states that the hive has spaces of different terrain types and that different terrains hold different stuff, without listing specific items.
- [ ] The page explains, in one short section, how each of the four collection types relates to a space's stuff: Normal Bee Stuff collects it; Honeycomb bees collect more of it; Royal Jelly bees bring back Royal Jelly instead; a helper pet hunts or gathers its own finds instead.
- [ ] The Flower Power section no longer contains the phrase "produce more and faster" and describes Flower Power as what gets the bees ready to go out and collect.
- [ ] The page contains no mention of spaces being spent/used up, the grid resetting, Basement size, or the Gold Compass.
- [ ] The "More?!" section remains and is the final section.
- [ ] Copy uses ASCII hyphens and American spelling; existing `<app-help-link link="flavors" />` links are preserved.
- [ ] `ng build` passes.

## Implementation

### 1. Study the style
Read the four exemplar pages listed above. Note sentence length, how parentheticals are used, and where the page jokes. Match it.

### 2. Rewrite the intro
In `beehive-help.component.html`, replace the "produces several items passively in real time" sentence with the collecting model: the hive is a house add-on; its bees head out to the hive's spaces to collect things, and they head out more often when fed floral / fruity / planty foods (keep the three `flavors` help links).

### 3. Add the spaces section
Per Open Decision 1, add an `<h4>` section explaining: the hive's spaces come in different terrains (Open Decision 3 for naming), each with different stuff; then one short paragraph (or a tight `<ul class="list">`, matching `tools-and-hats`'s "Related" list markup) covering the four collection types in the order the beehive page shows its bars. Keep it to the mechanic - no item names, no counts, no "double" arithmetic beyond "more".

### 4. Reword Flower Power
Rewrite the first two sentences per Open Decision 2's direction. Leave the "more bees, more food" sentence intact unless the rewrite reads better without it.

### 5. Leave "More?!" alone
Confirm it's still the last section and still promises unexplained powers.

## Test Plan
- [ ] `ng build` in `webapp/` passes.
- [ ] Open Poppyopedia » Glossary » Beehive: new section renders between the intro and Flower Power (or wherever Open Decision 1 landed); the three flavor help links still open the Flavors page.
- [ ] Read the page aloud next to `tools-and-hats` and `pet-level` - same voice, no reference-manual drift.
- [ ] Grep the file for "produce", "Basement", "Compass", "reset", "once": none present (a "produce"-free check catches the old Flower Power sentence).
- [ ] Compare terrain and bar names in the copy against the live beehive page: identical spelling and capitalization.

## Learnings

### Decisions
- Open Decision 1: new `<h4>The Hive's Spaces</h4>` between the intro and Flower Power; the intro is two sentences.
- Open Decision 2: used the draft wording, with "get made" -> "get collected" so the page never says the hive produces anything.
- Open Decision 3: the four terrains are named in one sentence.
- The four collection types are a `<ul class="list">` in bar order (Royal Jelly, Honeycomb, Normal Bee Stuff, helper), each one line, with one joke ("They're very focused." / "Normally.").

### Verification
- `grep -ci "produce|Basement|Compass|reset|once"` on the file returns 0; no em/en dashes; the three `flavors` help links are intact; `ng build` passes.
