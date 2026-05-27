---
name: architecture-retro
description: Holistic review of code architecture, identifying tech debt and ergonomic improvements
---

# Architecture Retrospective

You are performing a holistic review of the project's code architecture. The goal is to identify **concrete, high-value improvements** that make daily development easier -- APIs that create pits of success, patterns that eliminate classes of bugs, and refactors that reduce cognitive load.

**Mindset:** You are looking for *friction* and *fragility*. Friction is anything that makes correct code harder to write than incorrect code. Fragility is anywhere a small mistake causes a subtle, hard-to-catch bug. The best architectural improvements flip these: correct becomes the default, mistakes become compile errors or test failures.

## Key Principles

- **Pits of success over documentation.** A gotcha that's documented is still a gotcha. If the same mistake keeps appearing in tickets, the fix isn't better docs -- it's changing the API so the mistake is impossible or at least harder to make.
- **Earned abstractions only.** Don't propose abstractions for problems that have only occurred once. Look for patterns that have *actually* repeated across multiple tickets, causing real friction or bugs each time.
- **Cost-aware.** Every refactor has a cost. Prefer changes that are localized (few files touched), low-risk (hard to break existing behavior), and high-leverage (prevent a broad class of problems). A small helper method that prevents a recurring off-by-one is better than a grand redesign.
- **Respect what works.** Not everything needs improvement. If a pattern is working well and not causing issues, leave it alone. Focus your energy on the actual pain points.

## Investigation Process

### 1. Mine Completed Tickets for Pain

Read recently completed tickets in `docs/tickets/complete/`. These are your primary evidence source. Look for:

- **Repeated gotchas**: The same type of mistake appearing across multiple tickets (e.g., forgetting reload flags, missing DTO fields, incorrect API paths)
- **Workarounds**: Places where the ticket author had to work around a limitation in the current API, especially if the same workaround appears more than once
- **"Bug found & fixed" entries**: These reveal APIs that made incorrect usage too easy
- **Rejected alternatives**: Sometimes the reason an approach was rejected reveals an API gap

### 2. Audit Documented Gotchas

Read the gotcha/known-issue sections in `docs/` directories and `CLAUDE.md` files. For each one, ask:
- Is this gotcha inherent to the domain, or is it an artifact of the current API design?
- Could a type change, method signature change, or new helper make this gotcha impossible?
- Has this gotcha actually bitten anyone (check tickets), or is it theoretical?

### 3. Examine High-Traffic Code

Identify files and methods that are modified in many tickets (the "hot spots"). Read these areas and look for:
- **Method signatures that are easy to misuse**: Wrong parameter order, stringly-typed values, boolean flags that are easy to mix up, missing context that callers need to thread through manually
- **Implicit contracts**: Things callers must "just know" to do (calling methods in a certain order, checking a condition before calling, manually keeping two things in sync)
- **Copy-paste patterns**: Code that's nearly identical across several places, suggesting a missing abstraction -- but only if the duplication has actually caused problems or drift

### 4. Look for Missing Type Safety

Scan for places where the type system could prevent bugs:
- Loose `any` types, missing interface definitions, stringly-typed values where unions or enums would help
- Places where invalid states are representable but shouldn't be

### 5. Check System Boundaries

Look at the interfaces between major systems:
- Are the boundaries clean, or do systems reach into each other's internals?
- Is the API contract between frontend and backend well-defined (matching DTOs, consistent response shapes)?
- Are there parameters being passed through layers just to reach a deeply nested consumer?
- Are there "god objects" accumulating unrelated responsibilities?

## Output

For each finding, present:
- **The evidence**: Specific tickets, code locations, and documented gotchas that demonstrate the problem. Don't propose improvements for theoretical problems -- show that it's actually caused friction.
- **The pain**: What goes wrong today. How much effort does this waste? How subtle are the bugs?
- **A proposed improvement**: A concrete, actionable change. Be specific enough that it could become a ticket. Include rough scope (small/medium/large) and which files would likely be affected.
- **What it prevents**: Which class of bugs or friction this eliminates going forward.

Organize findings by impact: most valuable improvements first.

Do NOT auto-apply changes. Present findings for discussion. The user decides which improvements are worth pursuing and in what order. Some findings may be deferred, combined, or rejected -- that's expected.

After discussion, create tickets in `docs/tickets/` for the agreed-upon improvements.
