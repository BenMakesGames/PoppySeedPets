---
name: process-retro
description: Holistic review of documentation, skills, and process health
---

# Process Retrospective

You are performing a holistic review of the project's documentation, skills, and process layer. The goal is to keep the system **accurate, useful, and flexible** -- guiding toward correctness without ossifying into over-specification.

**Mindset:** Be analytical, not mechanical. This is not a checklist to execute -- it's a set of questions to investigate. Use judgment. Not every question will surface something actionable every time, and that's fine.

## Key Principles

- **Confident-but-stale documentation is more dangerous than no documentation.** A missing doc is obviously missing; a wrong doc actively misleads. Prioritize finding drift between what the docs say and what the code actually does.
- **Question assumptions to avoid ossification.** Patterns that were correct 10 tickets ago may no longer be the best approach. Documentation should describe *what works now*, not enshrine past decisions as permanent law. Very few patterns are so sacred they can't be questioned.
- **Less is more.** If a documented pattern is obvious from the code itself, the documentation may be unnecessary weight. Docs should capture things that are *surprising*, *non-obvious*, or *easy to get wrong*.

## Areas to Investigate

### 1. Docs vs. Reality

Read actual code to verify that documented patterns still hold. Pay attention to:
- Are the documented file locations, class names, and method signatures still accurate?
- Do the documented conventions (one controller per endpoint, no repositories, etc.) still match what the code actually does?
- Are documented gotchas still relevant, or have they been fixed in code?
- Are there new gotchas the code reveals that aren't documented?

### 2. Cross-Ticket Synthesis

Review recently completed tickets (in `docs/tickets/complete/`) as a batch. Look for:
- Repeated learnings across multiple tickets that should be promoted to a reference doc
- Learnings that contradict each other or contradict the reference docs
- Patterns that emerged organically but haven't been named or documented
- Ticket learnings that were captured but are now outdated

### 3. Over-Specification Check

For each reference doc and `CLAUDE.md` file, ask: is this section **guiding** or **constraining**?
- Are there prescriptive rules that have already been broken (successfully) by later tickets? If so, the rule may need softening or removing.
- Are there sections that describe the *only* way to do something, when in practice there are valid alternatives?
- Is any documentation discouraging experimentation in areas where experimentation would be healthy?

### 4. Structural Health

Step back from content and look at the documentation structure itself:
- Is the root `CLAUDE.md` still the right entry point for a fresh session? Does it set the right context without overwhelming?
- Is the `docs/` directory organization still logical across the workspace and sub-projects?
- Are the skills (`/ticket`, `/process-retro`, `/architecture-retro`) still well-scoped, or do they need updating based on how work has actually gone?

### 5. Gaps

What's *not* documented that probably should be?
- Are there areas of the codebase that are tricky to work in but have no corresponding documentation?
- Are there recurring questions or confusions that keep coming up in tickets?

## Output

Present your findings as a conversation, organized by area. For each finding:
- **What you observed** (with specific file/line references where relevant)
- **Why it matters**
- **A suggested action** (update, remove, soften, restructure, etc.)

Do NOT auto-apply changes. The user decides what to act on. Some findings may spark discussion rather than immediate action -- that's the point.

After the user decides which changes to make, implement them together.
