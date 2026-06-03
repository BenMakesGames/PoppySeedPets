---
name: implement-ticket
description: Implement a ticket from docs/tickets/
argument-hint: <ticket-name>
---

# Implement Ticket: $ARGUMENTS

Implement a ticket from `docs/tickets/`. Tickets are written by `/create-ticket` and follow templates documented there — Implementation, Design, or Refactoring shape.

## Phase 1: Orient

1. Locate ticket matching "$ARGUMENTS" in `docs/tickets/` (skip `docs/tickets/complete/` — that's the archive).
2. Read ticket end-to-end. Note shape (Implementation / Design / Refactoring) and which sections are present.
3. Read every doc in **Relevant Docs & Anchors**.
4. Read every code symbol named in the ticket — Implementation steps, Acceptance Criteria, Constraints & Gotchas, Open Decisions. Don't paraphrase the ticket; ticket points at code, code is truth.
5. Read `CLAUDE.md` and any nested `CLAUDE.md` for areas you'll touch.
6. Internalize **Constraints & Gotchas** before writing code — cross-cutting hazards easy to miss inline.

## Phase 2: Check Prerequisites

If ticket has a **Prerequisites** section, verify each item concretely — grep entity, open file, confirm feature exists. If any prerequisite is unmet, stop and tell user which and why. Don't proceed; let user decide whether to continue.

## Phase 3: Resolve Open Questions

`/create-ticket` already triaged questions into three buckets — design-level went to user, defer-class to **Open Decisions**, research-class settled before writing. Don't re-ask settled items.

Your job:

- **Open Decisions** — make the call. Use listed default unless research surfaces clear reason to deviate. Escalate to user only if (a) you disagree with default after reading code, or (b) decision is larger than the ticket framed.
- **New ambiguities found while reading** — ask user only if design-level, contradicts ticket or existing code, or unanswerable by reading more code. Research first; ask second.
- **Local taste** (idiom, naming, micro-style) — decide. Don't pad user's queue.

If nothing blocking, go straight to implementation.

## Phase 4: Implement

1. Track work with todo tool — one todo per Implementation step is a reasonable default for Implementation tickets; group differently for Design/Refactoring shapes.
2. Follow Implementation steps in order written — they're sequenced for dependencies.
3. Mirror patterns named in the ticket (analogue tickets, code anchors); don't invent parallel structures.
4. Mark todos complete as you go — don't batch.

### Tests

Add tests for non-trivial logic, invariants, and regressions — be judicious. 100% coverage is **not** a goal. Every test is maintenance overhead; low-value tests bleed time without catching bugs. If ticket's Test Plan is purely manual, that's intentional — don't invent unit tests just to have them.

## Phase 5: Verify

1. Final `dotnet build -o /tmp/build-check` and `dotnet test` pass.
2. Walk **Acceptance Criteria** item by item — each is unambiguously pass/fail by code inspection or test. If any fails, fix before moving on.
3. Walk **Test Plan** item by item where you can; where you can't, ask user. Don't claim done on UI/feature work without exercising it.
4. If any Acceptance Criteria or Test Plan item can't be verified (e.g., requires hardware, external account), say so explicitly — don't claim success.
5. If ticket is missing **Acceptance Criteria** or **Test Plan** entirely, flag to user as a ticket-quality smell — those sections should exist on every ticket. Don't fabricate criteria post-hoc; note the gap and proceed with whatever verification you can.

## Phase 6: Self-review

Quick pass before declaring done — analogue to `/create-ticket`'s Phase 5:

- Every Implementation step actually addressed? No silent skips, no "I'll come back to this."
- Scope held? No drive-by refactors, extra abstractions, or features beyond the ticket. If you found something worth changing outside scope, note it for a follow-up ticket — don't roll it in.
- No dead code left behind — unused params, commented-out blocks, half-finished branches.
- Constraints & Gotchas all respected? Re-read that section against the diff.
- Open Decisions all resolved? Each has a chosen path reflected in code (worth capturing in Learnings).

Fix anything caught before moving on.

## Phase 7: Document

1. Move ticket from `docs/tickets/` to `docs/tickets/complete/`, prefixing filename with today's date in `yyyy-mm-dd` format (e.g., `2026-04-24 my-ticket.md`). Use `git mv` to preserve history as a rename.
2. Add a `## Learnings` section to the bottom of the completed ticket. Capture:
   - **Architectural decisions** — design choices made during implementation and why (incl. how Open Decisions resolved).
   - **Problems encountered** — bugs, gotchas, unexpected behavior, how resolved.
   - **Interesting tidbits** — non-obvious things learned about the codebase, frameworks, or domain.
   - **Workarounds / limitations** — framework constraints that forced workarounds; permanent vs. revisitable.
   - **Related areas affected** — other codebase parts touched or that may need future attention.
   - **Rejected alternatives** — approaches considered but not taken, and why — keeps future work from hitting the same dead ends.
3. If ticket implements or changes a game design feature, update `docs/game-design.md` (and sub-docs: `heroes.md`, `relics.md`, `stamps.md`, `bosses.md`, `sector-map.md`, `lock-resolution.md`, `aesthetic.md`, `colors.md`) to reflect new state. Design docs are a living reference — must match the shipped game.
4. Update relevant `docs/` reference file(s) with **reusable** patterns learned. Distill the generally-useful bits — new gotchas, confirmed patterns, API conventions — not ticket-specific narrative. Don't duplicate the ticket; reference it from the doc if useful.

## Phase 8: Post-feedback updates

If user reports bugs or issues after initial implementation and you fix them:

1. Append new problems/gotchas to completed ticket's **Learnings** section.
2. Distill reusable patterns into relevant `docs/` reference file(s).

Hard-won debugging knowledge from the bugfix pass is as valuable as from initial implementation — don't drop it on the floor.

## Important Notes

- Ask clarifying questions early if needed; also if new ones arise mid-implementation.
- Use todos to track progress; mark complete as you go.
- Keep user informed of progress — short updates at key moments (found, blocked, changed direction).
