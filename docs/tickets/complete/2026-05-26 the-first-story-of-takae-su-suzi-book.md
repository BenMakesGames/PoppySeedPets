# Add Book Item: "The First Story of Takae Su Suzi"

## Context
**Current behavior**: The Literature aisle in the bookstore offers two prose/poetry books — `⤮` (Poems) and `The Open Window` — both gated on the Library being unlocked. There is no in-game book containing the Takae Su Suzi creation myth.

**New behavior**: A new book item, `The First Story of Takae Su Suzi`, is available in the bookstore's Literature aisle for 20 moneys (same as `The Open Window`), gated on the Library being unlocked. Its `Read` action displays the full text of the story (sourced from `booktext.md` in the repo root) as markdown. No side effects on read — pure literature, matching the `Poems` / `The Open Window` pattern.

## Scope
### In scope
- A Doctrine migration that inserts the new `item`, its `item_grammar` row, and its `item_group_item` row (Book group, id 40).
- A new controller in `api/src/Controller/Item/Book/` exposing a `Read` action that returns the story text as markdown via `$responseService->itemActionSuccess(...)`.
- Adding the item to `BookstoreService::getAvailableLiterature` so it appears in the Literature aisle.
- Transcribing the story text from `booktext.md` into the controller's heredoc.
- Removing `booktext.md` from the repo root once its contents have been incorporated.

### Out of scope
- The item image asset itself. The artist will deliver `takae-su-suzi-1.svg`, to be dropped into the project's book-image directory at art-pass time. The migration in this ticket sets the `image` column to `book/takae-su-suzi-1` (extension dropped, matching the `book/poems` / `book/the-open-window` precedent), so the asset slots in once delivered. Do **not** create a placeholder image.
- Any unlock side effect, XP grant, or quest hook on read. (Confirmed pure-literature.)
- Updating `BookstoreService::QUEST_STEPS` or any other quest content — this book is a shop item, not a quest target.
- Adding the book to the Library starter set or any other auto-grant pathway.

## Relevant Docs & Anchors
- **Existing book controllers**: `api/src/Controller/Item/Book/PoemsController.php` and `api/src/Controller/Item/Book/TheOpenWindowController.php` are the closest analogues — pure-literature books with no unlock side effects. Mirror their shape.
- **Library-allowed validation**: `App\Controller\Item\ItemControllerHelpers::validateInventoryAllowingLibrary` — books use this (not `validateInventory`) so they can be read from the Library as well as House/Basement/Mantle.
- **Bookstore wiring**: `App\Service\BookstoreService::getAvailableLiterature` — see how `⤮` and `The Open Window` are added there, gated on `UnlockableFeatureEnum::Library`.
- **Migration analogue (book item inserts)**: `api/migrations/2026/03/Version20260311031129.php` is the canonical reference — it inserts both `⤮` and `The Open Window` with the exact column layout, `use_actions` JSON shape, item_grammar, and item_group_item (group 40 = Book) wiring this ticket needs.
- **Source text**: `booktext.md` at the repo root. Transcribe verbatim — the user has already corrected the known typos, so no further editing.

## Constraints & Gotchas
- **Item IDs are hand-picked**. Pick the next free `item.id` and `item_grammar.id`. As of the time this ticket was written, the highest `item.id` referenced in `api/migrations/**` is 1523 (Hoot Dog, `Version20260527015849`) and the highest `item_grammar.id` is 1603. Verify by grepping `api/migrations/**` for `INSERT INTO item \(` and `INSERT INTO item_grammar \(` before settling on numbers — another migration may have landed on `main` since.
- **`item_group_item` has no surrogate id** in this insert pattern (see the reference migration) — it's a composite `(item_group_id, item_id)` with `INSERT IGNORE`. Use group_id `40` (`Book`).
- **`use_actions` JSON shape**: literal `[["Read","theFirstStoryOfTakaeSuSuzi\/#\/read"]]` — the `\/` escaping matches existing rows in the seed and migrations; preserve it for grep-consistency.
- **`Item::hasUseAction` checks the action string** stored in `use_actions`. The validator call (`validateInventoryAllowingLibrary($user, $inventory, 'theFirstStoryOfTakaeSuSuzi/#/read')`) must pass the same route string used in `use_actions`. Mismatched strings = silent "item cannot be used in that way!" 500.
- **Article in `item_grammar`**: NULL. The title begins with "The", and existing book entries with leading articles (`The Open Window`, `The Umbra`) use NULL `article`. Don't insert `"the"`.
- **`down()` is empty in the analogue migration** — follow that convention for new-content migrations; we don't roll content back.
- **`booktext.md` is currently untracked** (see initial `git status`). The migration commit should both add the migration/controller/service edit AND `git rm` (or delete + commit) `booktext.md` so the source artifact doesn't linger in the repo root.

## Open Decisions
1. **Where to put the title heading inside the markdown returned by `Read`** — `Poems` opens with `# ⤮` (the item name as an H1) and then content. The implementer may use `# The First Story of Takae Su Suzi` as the leading H1, or omit it (the calling UI may already show the item name above the modal). Default: include `# The First Story of Takae Su Suzi` as the first line of the EOMD, matching `Poems`.
2. **Paragraph-break style inside the heredoc** — `Poems` uses blank lines between blocks. `booktext.md` is already blank-line-separated paragraphs; copy verbatim. No need to insert `<br />` or `<p>` tags — markdown handles it.
3. **`recycle_value` / `museum_points` / `fuel`** — `Poems` and `The Open Window` both use `fuel=90, recycle_value=0, museum_points=1`. Default: identical values; no design reason to deviate.

## Acceptance Criteria
- [ ] A new item row exists in `item` with `name = 'The First Story of Takae Su Suzi'`, `image = 'book/takae-su-suzi-1'` (corresponding to the artist-supplied `takae-su-suzi-1.svg`), `use_actions = '[["Read","theFirstStoryOfTakaeSuSuzi\\/#\\/read"]]'`, `description = NULL`, `fuel = 90`, `recycle_value = 0`, `museum_points = 1`, and `food_id`, `tool_id`, `hat_id`, etc. all NULL.
- [ ] A matching `item_grammar` row exists for this item with `article = NULL`.
- [ ] An `item_group_item` row joins this item to group id `40` (`Book`).
- [ ] A new controller class exists at `api/src/Controller/Item/Book/TheFirstStoryOfTakaeSuSuziController.php` with a single `read` action mounted at `POST /item/theFirstStoryOfTakaeSuSuzi/{inventory}/read`, gated on `IS_AUTHENTICATED_FULLY`, calling `ItemControllerHelpers::validateInventoryAllowingLibrary(...)`, and returning the story text via `$responseService->itemActionSuccess(<<<EOMD ... EOMD)`.
- [ ] `BookstoreService::getAvailableLiterature` includes `'The First Story of Takae Su Suzi' => 20` (alongside the existing `⤮` and `The Open Window` entries), inside the same `UnlockableFeatureEnum::Library` gate.
- [ ] The story text in the controller's heredoc matches `booktext.md` verbatim (modulo a leading H1 heading per Open Decision 1).
- [ ] `booktext.md` is removed from the repo root in the same commit/PR as the implementation.

## Implementation

### 1. Verify the next free item / grammar IDs
Before writing the migration, grep `api/migrations/**` for `INSERT INTO item \(` and `INSERT INTO item_grammar \(` and pick the next free integer for each. As of ticket-writing time the maxima were 1523 and 1603 respectively, but verify on the current branch — another content PR may have landed on `main` first.

### 2. Create the controller
Add `api/src/Controller/Item/Book/TheFirstStoryOfTakaeSuSuziController.php`. Mirror `PoemsController` exactly: same license header, same `#[Route("/item/theFirstStoryOfTakaeSuSuzi")]` class attribute, same `#[Route("/{inventory}/read", methods: ["POST"])]` + `#[IsGranted(...)]` on the `read` method, same `validateInventoryAllowingLibrary($user, $inventory, 'theFirstStoryOfTakaeSuSuzi/#/read')` call, same `$responseService->itemActionSuccess(<<<EOMD ... EOMD)` return. The only differences from `PoemsController` are the route segment, the validation action string, and the markdown payload.

Paste the contents of `booktext.md` into the heredoc body, preceded by a `# The First Story of Takae Su Suzi` H1 (Open Decision 1, default). Preserve paragraph breaks; do not add `<br />` tags.

### 3. Create the migration
Add `api/migrations/2026/05/Version<TIMESTAMP>.php` (use the current UTC timestamp; one migration file per timestamp, follow the existing year/month directory layout). Model it on `Version20260311031129` — `up()` contains a single `$this->addSql(<<<EOSQL ... EOSQL)` block that inserts:

1. The `item` row with the column list shown in the reference migration and `use_actions = '[["Read","theFirstStoryOfTakaeSuSuzi\\/#\\/read"]]'`.
2. The `item_grammar` row with `article = NULL`.
3. The `item_group_item` join row to group 40 (`Book`) via `INSERT IGNORE`.

`down()` stays empty (matches the analogue). `getDescription()` returns something like `'add The First Story of Takae Su Suzi'`.

### 4. Wire the book into the Literature aisle
In `App\Service\BookstoreService::getAvailableLiterature`, inside the existing `if($user->hasUnlockedFeature(UnlockableFeatureEnum::Library))` block, add `$prices['The First Story of Takae Su Suzi'] = 20;` alongside the two existing entries. The `ksort` at the end will reorder.

### 5. Remove booktext.md
Once the story text is incorporated into the controller, `git rm booktext.md` (or delete + stage) so the source file doesn't linger in the repo root.

### 6. Run the standard quality gates
From `api/`: `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan --configuration=phpstan.dist.neon` — fix anything new. (The controller mirrors an existing one almost verbatim, so neither should complain, but verify.)

## Test Plan
- [ ] `composer run php-cs-fixer-dry-run` and `vendor/bin/phpstan` pass with no new errors (run from `api/`).
- [ ] Run the migration locally (`docker compose up` or whatever the dev flow uses) and confirm the `item`, `item_grammar`, and `item_group_item` rows land with the expected values (`SELECT * FROM item WHERE name = 'The First Story of Takae Su Suzi'`).
- [ ] In a dev session with a user that has unlocked the Library: visit the bookstore, confirm `The First Story of Takae Su Suzi` appears in the Literature aisle at 20 moneys, buy it, and confirm it enters inventory.
- [ ] In a dev session with a user that has *not* unlocked the Library: confirm the book is *not* listed in the Literature aisle (the aisle should still show only when its `if` gate is satisfied, just as it does today).
- [ ] With the book in House, Basement, Mantle, or Library: click `Read` and confirm the full story text renders as a markdown modal. No flash messages, no inventory reload, no pet activity logs are created.
- [ ] Confirm `booktext.md` is no longer present in the repo root.
- [ ] Spot-check that `Poems` and `The Open Window` still work as before (no regression from the `getAvailableLiterature` edit).

## Learnings

### Architectural decisions
- **Open Decision 1 (H1 in markdown)** — resolved with default: included `# The First Story of Takae Su Suzi` as the leading line of the EOMD, matching `PoemsController`/`TheOpenWindowController`. Keeps the read-modal self-titled even if the calling UI changes.
- **Open Decision 2 (paragraph style)** — used blank-line-separated paragraphs verbatim from `booktext.md`; no `<br />` or `<p>` injected. Markdown handles it. (`Poems` only uses `<br />` for line-broken poetry where stanza breaks ≠ paragraph breaks; prose doesn't need it.)
- **Open Decision 3 (fuel/recycle/museum_points)** — kept identical to `Poems` / `The Open Window` (`fuel=90, recycle_value=0, museum_points=1`).
- **Next free IDs** — verified by grepping `INSERT INTO item \(` and `INSERT INTO item_grammar \(` separately across `api/migrations/**` (had to separate the two — a combined grep returns mixed IDs and overstates the item max). Maxima were 1523 / 1603 as the ticket predicted, so this book took 1524 / 1604.

### Interesting tidbits
- The validator action string (`theFirstStoryOfTakaeSuSuzi/#/read`) and the `use_actions` JSON entry (`[["Read","theFirstStoryOfTakaeSuSuzi\/#\/read"]]`) are matched **by string equality** inside `Item::hasUseAction`. The `\/` escaping in the SQL JSON is consumed by JSON decoding before comparison, so the runtime string is `theFirstStoryOfTakaeSuSuzi/#/read` on both sides — but the SQL itself must keep `\/` for consistency with the rest of the seed/migration corpus (grep-friendliness, not correctness).
- `git mv` on the source ticket failed with "not under version control" — the ticket and `booktext.md` had been added as untracked files (per the initial `git status` snapshot). For untracked-file relocations, plain `mv` is the right call; git will pick the new path up as a fresh untracked file.

### Workarounds / limitations
- No automated test was added. Book read-actions are a pure-data + I/O concern (DB row + literal markdown out); the existing `Poems`/`TheOpenWindow` controllers carry no tests either, and the Test Plan is deliberately manual. Mirroring that pattern.

### Related areas affected
- `BookstoreService::getAvailableLiterature` — added one line inside the existing Library-gated block. `ksort` at end keeps display order alphabetical, so the new entry slots in naturally.
- No frontend changes — bookstore UI renders aisles dynamically from the API response, and book-read modals are already wired to render markdown from `itemActionSuccess`.

### Rejected alternatives
- **Auto-grant via Library starter set** — explicitly out of scope per ticket. This stays a shop purchase.
- **Quest hook on read** — confirmed pure literature; no XP, no unlock, no flag flip.
- **Placeholder image** — explicitly forbidden. `book/takae-su-suzi-1` will resolve once the artist drops `takae-su-suzi-1.svg` into the proprietary-assets book directory.

