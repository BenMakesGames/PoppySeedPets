---
name: propose-ticket
description: Research and pressure-test a feature idea before it becomes a ticket — feasibility, implementation options, gotchas, and the hidden assumptions/contradictions behind the request. Discuss with the user until you both agree on the shape, then offer to hand off to /create-ticket. Never writes code or tickets; never invokes /create-ticket itself.
argument-hint: <feature idea or rough request>
---

# Propose Ticket: $ARGUMENTS

This is the thinking-partner phase that comes *before* a ticket exists. Take a rough request, investigate whether and how it can be done, read between the lines to surface what the user left unsaid, and discuss until you both agree on the shape. The deliverable is **shared, validated understanding** — not an artifact.

When aligned, you *offer* to create a ticket. You never write the ticket yourself and never invoke `/create-ticket` — that handoff is always the user's to make.

## What this is — and isn't

- **Is**: collaborative feasibility + design investigation. Map the area, find the gotchas, name the hidden assumptions, weigh implementation options, converge with the user.
- **Isn't**: writing the ticket (that's `/create-ticket`), writing code, or quietly deciding design-level forks on the user's behalf.

If the user's request is already crisp and uncontested and they just want the ticket, say so and point them at `/create-ticket` rather than manufacturing discussion.

## Phase 1: Research feasibility

The recommendation is only as good as the research behind it. Tickets that reference the wrong pattern or miss existing infrastructure cause downstream friction — catch that here.

1. **Fan out, then verify.** Delegate broad searching to an Explore/subagent to map the area fast — but re-read the actual source for any fact your recommendation rests on. A summary is a lead, not a citation; load-bearing claims get verified against the code.
2. **Read the priors.** Related completed tickets in `docs/tickets/complete/` and relevant `docs/` reference files. The *why* behind existing shapes is free context.
3. **Trace every consumer of what you'd touch.** The highest-value finds are integration points that would *silently* break — a list that another feature derives presentation order from, a default another path assumes. Grep the symbol; read each caller.
4. **Let tests tell you the invariants.** Tests that encode current assumptions reveal what must stay true and what your change would break. Their names are often the spec.

## Phase 2: Read between the lines

This is the part that distinguishes a proposal from a transcription. Take the literal request and interrogate it. For each item below, state it explicitly when found — don't bury it:

- **Hidden assumptions** — what the request implicitly treats as true, easy, or already-handled that may not be. (e.g. "pre-unlock these" assumes they're stored like normal unlocks — but one of them is special-cased and isn't stored at all.)
- **Contradictions** — where the request collides with documented design, existing intent, or itself. A contradiction is a signal, not noise. Surface it; don't silently pick a side.
- **Ambiguities** — phrasings with more than one reasonable reading. Name the readings.
- **Ripple effects & edge cases** the request doesn't mention — UI that keys off the thing being changed, persistence/save implications, balance, ordering, empty/boundary states.
- **Framing mismatches** — where the user's mental model of how it works diverges from how the code actually works. (e.g. items the user listed as a flat group that the code treats as three different *kinds*.)
- **Your own working assumptions** — not just the request's. The "it works like X" premises your *recommendation* leans on. Make each visible and tag it **verified-in-source** or **unverified**; verify the load-bearing ones now. An unverified premise stated as fact is the most dangerous thing in a proposal — it looks like a finding. Questioning these is generative: confirming or breaking one routinely uncovers adjacent bugs or cleanup the request never mentioned.

A request that looks contiguous/simple often isn't once you map it to the code. Your job is to find that gap before a ticket freezes the wrong assumption.

## Phase 3: Discuss and converge

Present, then iterate. A good first pass back to the user includes:

- **Feasibility verdict** — can it be done; how invasive.
- **Implementation options** — 1–3 viable shapes with tradeoffs, one-line each; recommend one and say why.
- **Gotchas** — the silent-break integration points, broken tests, save/compat notes, behavioral consequences.
- **Between-the-lines items** from Phase 2 — assumptions, contradictions, ambiguities — each as a concrete point to resolve.
- **Assumptions ledger** — the premises your recommendation rests on, each tagged verified / needs-confirm. Volunteer it; don't wait for the user to ask "what else are you assuming?"

Then **bucket the open questions**:

- **Ask the user** — design-level calls only they can make: which interpretation, scope in/out, naming/UX intent, whether a surfaced contradiction is intentional. Use `AskUserQuestion`; use option previews when comparing concrete shapes (code sketches, layouts). Keep it to one focused round at a time — don't dump every question at once.
- **Decide yourself by research** — anything the codebase can answer. Go read it; don't ask.
- **Park for the ticket** — local-taste / implementer-level choices (data structure, field names, easing) that don't need deciding now. Note them as eventual "Open Decisions" rather than litigating them here.
- **Spin off** — adjacent bugs or cleanup the investigation surfaced that are out of scope for this feature. Note as a *separate* follow-up ticket; don't bundle a discovered refactor into the feature ticket.

Re-research when an answer opens a new question. Loop until you and the user genuinely agree on the shape — feasibility, chosen approach, scope, and the resolved assumptions. Don't rush to the handoff while real forks are open.

## Phase 4: Offer the handoff — don't take it

Once you're both aligned:

1. Summarize the agreed shape concisely — the decided approach, scope boundaries, and the gotchas the ticket should carry.
2. **Ask** whether they'd like to turn it into a ticket.
3. Leave invoking `/create-ticket` to the user. Do **not** run it, do **not** write a ticket file, do **not** start implementing.

## Guardrails

- **No code changes. No ticket files. No `/create-ticket` invocation.** The output of this skill is alignment.
- **Verify before asserting.** Don't claim feasibility on a subagent summary alone — confirm the load-bearing facts in source.
- **Surface conflicts, don't paper over them.** When the request contradicts the code or itself, raising it is the whole point.
- **Don't over-question.** Ask only design-level forks, one focused round at a time; research everything else.
