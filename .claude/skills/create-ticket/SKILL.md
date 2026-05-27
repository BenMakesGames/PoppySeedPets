---
name: create-ticket
description: Create detailed implementation ticket(s) from a feature description
argument-hint: <feature description>
---

# Create Ticket: $ARGUMENTS

Create one or more implementation tickets from the user's feature description. Tickets must be detailed enough that `/implement-ticket` can implement without ambiguity. Workflow:

## Phase 1: Research

Thorough codebase research is critical — tickets referencing wrong patterns or missing existing infrastructure cause implementation friction.

1. Find and read related completed tickets in `docs/tickets/complete/`.
2. Read relevant `docs/` reference files for areas involved.
3. Identify existing codebase patterns relevant to the feature. User may reference analogous systems (e.g., "like the inventory system", "similar to how achievements work"). Read those systems thoroughly — modules, components, data structures, helpers — for context.

## Phase 2: Clarify

Before asking, sort each open question into one of three buckets. Only the first goes to the user.

**Ask the user — design-level or architecture-level decisions the implementer can't unilaterally make.**

- Requirements with multiple interpretations or that contradict documented design
- Domain-specific values, names, configs the codebase can't tell you (e.g., balance numbers, copy strings, design intent)
- Scope boundaries — what's in vs. out for each ticket
- Dialog vs. page, modal vs. inline, separate state vs. flag on existing state
- Public API surface, breaking changes, cross-module contracts

**Defer to the implementer — leave un-decided, don't ask the user.** Implementer is another instance of you running `/implement-ticket` with full research access; trust them with local taste:
- Local idiom (HashSet vs. Dictionary, list vs. array, struct vs. record)
- Easing curves, animation timing tweaks, exact pixel offsets
- Field names, variable names, method names within an established pattern
- Inline helper vs. extract class — when both reasonable
- Micro-style: (a) vs. (b) when both work and neither is clearly better

For defer-class questions, either omit or surface under "Open Decisions" (see template) — never pre-decide for the implementer, never bother the user.

**Don't ask — research first.** Anything answerable by reading code. If unsure, read before asking.

Keep questions focused, concrete, bucketed. Don't pad user's queue with implementer-class questions.

## Phase 3: Scope

Determine how many tickets are needed. User may describe one or several related features. Consider:

- **One ticket per deployable unit**: Each ticket implementable and testable on its own
- **Dependency ordering**: If B depends on A's artifacts, list in B's Prerequisites
- **User guidance**: User may suggest a split — follow their lead

Present proposed split to user; confirm before writing.

## Phase 4: Write Tickets

Write each ticket to `docs/tickets/<ticket-name>.md`. **No** date prefix (added on completion) or numeric index (e.g., `03-vn-system.md`) — Prerequisites carries ordering, more flexible than linear sequence. Descriptive names only.

### Ticket Shape

**Ticket shape varies by task type.** Match format to work, not the other way around:

- **Implementation tickets** — full template below (numbered steps, files, test plan). Flat `## Implementation` with numbered steps in execution order. No grouping subsections (Model / Service / UI) — if ticket needs them, split it or re-sequence steps.
- **Design tickets** (e.g., hero progression, naming decisions) — simpler format: Context, design-specific sections, minimal test plan.
- **Refactoring tickets** — flat `## Implementation` with numbered steps across the whole stack.

### Implementation Ticket Template

Sections ordered for top-down reading by LLM implementer: gate checks (Prerequisites), boundary + orientation (Scope, Relevant Docs, Constraints), decisions before steps (Open Decisions, Acceptance Criteria), then work (Implementation, Test Plan).

```markdown
# Ticket Title

## Context
**Current behavior**: What exists today.
**New behavior**: What exists after implementation. Lead with user-visible / system-level change in one sentence, add nuance after. Serves as ticket summary — no separate Summary section.

## Prerequisites
- Tickets, features, or artifacts that must exist before implementation
- Omit entirely if none

## Scope
### In scope
Brief summary of goals + areas touched (e.g., "new data model + service layer, extends notification system, adds settings UI"). Blast-radius overview, not file manifest — Implementation lists files.

### Out of scope
Explicit non-goals: things a reader might assume are included but aren't, plus deferred follow-ups. Omit only if nothing to call out.

## Relevant Docs & Anchors
Pointers to read before coding:
- **Design docs**: e.g., `docs/heroes.md §Ansa`, `docs/relics.md §Boss modifiers`.
- **Analogue tickets**: completed tickets whose patterns this mirrors (e.g., `docs/tickets/complete/2026-04-19 meliono-hero.md`).
- **Code anchors**: key symbols/methods to read first (`HeroTypeInfo.Info`, `Encounter.SpawnNextPiece`, the stack-render loop in `Playing.Draw`).

Omit if Implementation steps already name everything needed.

## Constraints & Gotchas
Optional. Cross-cutting hazards affecting multiple steps, easy to miss if buried inline:
- Project reference rules (e.g., `Astromino.Data` cannot import `Astromino.UI`).
- Expected compiler warnings (e.g., CS8509 on hero switches).
- Save-format / serialization compatibility constraints.
- Framework quirks documented in earlier ticket Learnings.

Omit if none apply.

## Open Decisions
Optional. Defer-to-implementer decisions worth surfacing — local-taste questions the implementer should call during coding (optionally raising with user) rather than pre-deciding here. Omit if none. Each point: one line, question + default if known.

Example entries:
1. **Easing curve** — linear vs. smoothstep for the per-tile tween. Default: linear; eyeball during manual testing.
2. **Inline list vs. dedicated VFX class** — inline simpler; dedicated right if reuse is imminent. Default: inline.

## Acceptance Criteria
Verifiable assertions defining "done" — user-visible behavior, public API outcomes, test-observable invariants, and (when the ticket *is* about adding code shape) enum values, registry entries, or file existence. Each unambiguously pass/fail by code inspection or unit test. **Distinct from Test Plan**: *what must be true*, not *how to verify*. Don't restate Test Plan steps.

**Avoid in AC**: private field names, internal state transitions, statement ordering inside a method. Those lock the implementer into one shape when other shapes satisfy the same behavior — they belong in Constraints or Implementation.

- [ ] Example (registry-shape ticket — code shape *is* the deliverable): "`HeroTypeInfo.Info[HeroType.Ansa]` populated with starting bag, starting Coin, portrait, and flavor fields matching `docs/heroes.md §Ansa`."
- [ ] Example (behavioral ticket): "With `SoftDropDestroys` active, a soft-drop press destroys exactly one piece — the held key has no further effect until released and re-pressed."

## Implementation
Flat numbered list. **No grouping subsections** — if ticket needs Model / Service / UI sub-headers, split it or re-sequence into one linear flow.

### 1. Step Title
Why (intent) → where (anchor by symbol/pattern, never line number) → what (prose, not transcribed code).

### 2. Next Step Title
...

## Test Plan
Manual + integration verification of acceptance criteria: build/test commands, in-game steps, regression spot-checks. Action-oriented ("Open settings, change theme, reload — confirm theme persists"). **Distinct from Acceptance Criteria**: *how to verify*, not *what must be true*.

- [ ] Example: "`dotnet build -o /tmp/build-check` and `dotnet test` pass."
- [ ] Example: "Start an Ansa run; first piece drawn is a pentomino; Coin readout shows 4."
```

### Writing Guidelines

- **Lead with intent, follow with location**: State *why* before *where*. The why survives refactors; the where may drift.
- **Be specific about what, directional about how**: Name exact files, entities, enums. For signatures and types, point to the method/entity to mirror rather than spelling out the full signature — transcribed signatures go stale.
- **Don't transcribe method bodies**: Describe algorithm in prose, point to analogous method; don't paste full C# into the ticket. Transcribed bodies go stale on first refactor of surrounding conventions (field naming, helpers, idioms). Exception: tricky off-by-one math, non-obvious algorithm steps, or specific data shapes where prose is ambiguous — transcribe only the load-bearing fragment, not the whole method.
- **No line numbers**: Never cite as `File.cs:388-395`. Line numbers rot on first edit above the region. Anchor by method name, pattern, or grep-able landmark (e.g., "the stack-render loop in `Playing.Draw`", "the `new Encounter(...)` call site in `Playing`").
- **Mirror existing patterns by reference**: For analogous code, name the exemplar and call out only the *differences*. Don't transcribe the full structure — it may have changed by implementation time.
- **Keep scope tight**: One coherent unit of work per ticket. If it feels too large, split it.
- **Don't over-specify presentation**: Enough structure (component names, layout sketches, behavioral descriptions) to build it, but don't dictate every styling detail or exact position.
- **Defer local-taste decisions to the implementer**: For choices between reasonable local idioms (HashSet vs. Dictionary, inline list vs. dedicated class, easing curve, exact field name), don't pre-decide. Omit or surface under "Open Decisions" with a default.
- **Prefer symbols over paths**: Anchor by symbol name (`Encounter.SpawnNextPiece`, `BossModifierType` enum, `CoinCollectEffect` class) — durable across moves/renames, grep-able. Add a path only when the symbol is ambiguous or the implementer would have to hunt. Inline paths in the step that needs them, not in a separate sidebar.
- **Assume the implementer verifies**: Tickets read alongside current codebase. Provide enough to orient (files, patterns, analogues), don't be a frozen snapshot.

## Phase 5: Review

After writing, quick self-check on things Writing Guidelines don't catch:

- Does each ticket's Prerequisites accurately list what must exist first, and do tickets order so dependencies flow forward (no cycles)?
- Would an implementer running `/implement-ticket` have enough orientation (Relevant Docs & Anchors, named analogues) to start without a guess-and-grep pass?
- Are Acceptance Criteria unambiguously pass/fail by code inspection or unit test, and *disjoint* from Test Plan steps (no item restated as both)?
- Are cross-cutting hazards (project reference rules, expected warnings, save-format compat) hoisted to Constraints & Gotchas instead of buried in a single Implementation step?

Present completed tickets with brief summary of each.
