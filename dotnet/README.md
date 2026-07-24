# Poppy Seed Pets — .NET API (rewrite)

The in-progress C#/.NET rewrite of the PHP/Symfony `api/`. See the decision docs in
[`docs/architecture/dotnet-rewrite/`](../docs/architecture/dotnet-rewrite/) for the *why* behind
the patterns here.

> **Status:** first vertical / skeleton. Compiles clean and has a passing test suite. Not yet
> wired to a live database or feature-complete.

## Layout

Single deployable API (mirrors today's `api/`), organized by **vertical slice**, not technical
layer — matching the project's existing convention.

```
PoppySeedPets.Api/
  Program.cs                       # host + DI wiring
  Features/                        # vertical slices — one endpoint per controller class
    Inventory/GetInventoryController.cs
  Infrastructure/
    Data/                          # EF Core: PspDbContext + entities
    ReferenceData/                 # in-memory version-locked definition store (FrozenDictionary)
    Auth/                          # session token, validator, custom AuthenticationHandler
    Http/                          # response envelope
PoppySeedPets.Api.Tests/           # xUnit; SQLite in-memory for DB-touching tests
```

## What this skeleton demonstrates (and the decisions behind it)

- **Reference-data strategy — Option D2** ([reference-data-and-caching.md](../docs/architecture/dotnet-rewrite/reference-data-and-caching.md)).
  `ItemDefinitionStore` is a `FrozenDictionary`-backed, load-once-at-startup store. Player rows
  (`Inventory`) carry a **bare `ItemId`, not an EF navigation property**, so the hot read path
  stitches definitions from memory and can never accidentally join. `Item` stays EF-mapped so
  search endpoints can still JOIN in SQL.
- **Auth — custom session handler** ([auth-cutover.md](../docs/architecture/dotnet-rewrite/auth-cutover.md)).
  `SessionAuthenticationHandler` reads the `sessionId` cookie (40-char) or a `Bearer` token and
  validates via `SessionValidator` (kept separate so it's unit-testable against an in-memory DB).
  Tokens are **hashed at rest** (SHA-256) per the §6.1 hardening; the sliding-expiry write matches
  PHP v1 behavior.
- **Determinism seam** — `TimeProvider` is injected (never `DateTimeOffset.UtcNow`), mirroring the
  PHP `Clock`. (`IRandom`/noise seam is a later addition; RNG need only be deterministic, not
  PHP-identical — see G3.)

## Build & test

```bash
dotnet build dotnet/PoppySeedPets.slnx
dotnet test  dotnet/PoppySeedPets.slnx
```

Requires the .NET 10 SDK. EF Core is pinned to 9.x to match `Pomelo.EntityFrameworkCore.MySql`
9.0.0 (no EF Core 10 Pomelo release yet; EF Core 9 runs fine on the .NET 10 runtime).

## Not done yet (next steps)

- Wire a real MySQL connection string (`ConnectionStrings:Psp`) and generate the initial EF model
  against the seed schema; the startup loader then populates the reference store for real.
- Login endpoint + argon2i verification.
- The full `ResponseService` envelope (user data, activity flash messages, reload flags).
- The request-gating pipeline (rate-limit, house-hours) as middleware/endpoint filters.
