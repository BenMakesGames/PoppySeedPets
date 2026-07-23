# Auth Cutover Strategy

> **Status:** Options analysis / proposal for the .NET rewrite's authentication. Companion to
> `current-architecture-and-gotchas.md` (gotchas **G6** auth, **G9** status codes).
>
> **TL;DR:** preserving existing logins across the PHP→.NET cutover is a *nice-to-have that is
> nearly free*, because there is no server-side session state to migrate and the cookie is a
> raw opaque token, not a signed/encrypted framework cookie. The work is: read the same token,
> query the same table, and **match the cookie attributes exactly** (the one real footgun).

---

## 1. Why this is easy (the enabling facts) ✅ verified in code

| Fact | Evidence | Why it makes cutover trivial |
|---|---|---|
| No PHP native session; stateless firewall | `security.yaml` `stateless: true`; `framework.session` effectively unused by the API | Nothing to migrate out of a PHP/Redis session store |
| Token is an opaque 40-char `[A-Za-z0-9]` random string | `SessionService::generateSessionId()` → `generateSecureRandomString(40)` | It's a DB key — no claims, no signature, no secret to reproduce |
| Cookie value is the **raw token**, plain `setcookie` | `ResponseService.php:86-88` | **Not** a Symfony signed/encrypted cookie → .NET needs no shared signing key |
| All state lives in `user_session` (MySQL) | `SessionAuthenticator::authenticate()` `findOneBy(['sessionId' => …])` | .NET reads the same table; existing rows are already valid |
| Validation is a plain lookup + expiry check | `authenticate()` `:68-75` | `SELECT … WHERE session_id = ?; check expiration` — no crypto |

**The consequence:** for *existing* sessions, the browser already holds the cookie and will send
it to the .NET app unchanged. The .NET side doesn't even need to re-issue anything to *read* an
existing session — it just needs to accept the token and look it up. Cookie attributes only
matter for cookies the .NET app itself **sets** (new logins + sliding refresh).

---

## 2. Exactly what PHP does today

**Transport (dual)** — `SessionAuthenticator::supports()`/`getSessionIdOrThrow()`:
- Cookie `sessionId`, accepted only when **exactly 40 chars** (`supports():46`), **or**
- `Authorization: Bearer <token>` (`:49`); cookie takes precedence (`:54-56`).

**Validation** — `authenticate()`:
- `findOneBy(['sessionId' => $sessionId])`; if missing or `sessionExpiration < now` →
  `clearCookie()` + `PSPSessionExpired` (**401**, client auto-logs-out).
- If `user.isLocked` → `PSPAccountLocked` (**401** deliberately — G9).
- Else: **slide expiry** `setSessionExpiration(user.getDefaultSessionLengthInHours())`, stamp
  `user.setLastActivity()`, **`flush()`** — i.e. a **write on every authenticated request**.
- Password is never re-checked; possession of the token is proof
  (`SelfValidatingPassport(new UserBadge($user->getEmail()))`).

**Cookie** — `ResponseService.php:86-88` (prod branch):
```php
setcookie('sessionId', $token, time()+60*60*24*7, '/', 'poppyseedpets.com', true, true);
//         name         value    +7 days           path  domain             secure httponly
```
- **7-argument form → NO `SameSite` attribute is set**, so browsers apply their default
  (**`Lax`** in modern browsers). This is load-bearing (see §3).
- Domain `poppyseedpets.com` (no leading dot) scopes to the domain **and its subdomains** under
  RFC 6265, so `api.poppyseedpets.com` ↔ `www.poppyseedpets.com` are same-site.
- Set on **every response** via `ResponseService`, not just at login (a refresh-on-every-request).
- `clearCookie()` mirrors the same name/path/domain with an expired date
  (`SessionService.php:98-100`).

**Login** — `POST /account/logIn`: verify password (**argon2i**, `security.yaml`) → `SessionService::logIn()` creates the `UserSession` row and sets the cookie.

---

## 3. The exact-match checklist for .NET (get these right)

1. **Read both transports.** Accept cookie `sessionId` (40-char) **and**
   `Authorization: Bearer <token>` (cookie precedence). Don't drop bearer — non-browser clients
   rely on it.
2. **Custom `AuthenticationHandler<>`, not cookie-auth/JWT middleware.** The token carries no
   claims; the handler does the `user_session` lookup. Since PSP treats "current user" as the
   live DB entity (`UserAccessor`, used in 411 files), the handler / an `ICurrentUser` should
   make the `User` row loadable, not just expose claims.
3. **Match cookie attributes byte-for-byte when (re)issuing** — this is **the footgun**:
   - name `sessionId`, path `/`, Domain `poppyseedpets.com`, **Secure**, **HttpOnly**, Max-Age
     7 days.
   - **`SameSite=Lax`** — set it *explicitly*. PHP emitted no SameSite → browser default Lax;
     ASP.NET Core's default differs by version, so pin it to `Lax`. Do **not** emit
     `SameSite=None` (different consent/secure semantics) or `Strict` (breaks cross-subdomain
     XHR from the SPA). Getting this wrong = the browser stops sending the cookie = **mass
     logout**.
   - Keep the API host under `*.poppyseedpets.com` so Lax still sends the cookie on the SPA's
     fetch/XHR (same registrable domain).
4. **Generate the same token shape** on login: 40-char `[A-Za-z0-9]`, DB-unique (the 40-char
   `supports()` check and the column width depend on it).
5. **argon2i verification at login.** Verify PHP's `password_hash(PASSWORD_ARGON2I)` PHC strings
   (`$argon2i$v=19$m=…,t=…,p=…$salt$hash`) with e.g. `Isopoh.Cryptography.Argon2` or
   `Konscious.Security.Cryptography`. Needed only at login, not per request.
6. **Preserve failure semantics (G9):** absent/expired → 401 (client auto-logs-out); locked →
   401 (deliberate); auth failure body `{success:false, errors:[…]}` at 403.
7. **`clearCookie` on logout/expiry** must use the identical name/path/domain, or a zombie
   cookie lingers.

If 1–7 hold, **every currently-logged-in browser and bearer client keeps working with zero
re-login.**

---

## 4. Genuine decisions (deliberate, but low-stakes)

1. **Keep the per-request write?** Today each authenticated request slides expiry + stamps
   `last_activity` + flushes — a DB write on *every* request. Options: (a) keep it (simplest,
   matches behavior; the token-bucket already throttles request rate); (b) **debounce** — only
   slide when expiry is within some window, only update `last_activity` every N minutes — to cut
   write volume; (c) rolling-expiry cookie without a per-request DB write. *Recommendation:* keep
   it for parity in v1, debounce later if write load shows up.
2. **Rehash to argon2id?** Keep argon2i (matches) vs. upgrade-on-login to argon2id (modern
   default). Optional; upgrade-on-next-login is a clean, no-migration option.
3. **Cutover style.** A hard switch (PHP off / .NET on, same DB + same domain) → existing
   sessions Just Work. Any brief blue/green overlap is also safe: both apps hitting the same
   `user_session` just means idempotent expiry-slides. (Full rewrite = hard switch = simplest.)
4. **Session-lookup caching (later, optional).** The per-request `SELECT … WHERE session_id` is
   cheap (unique index) but universal. A short-TTL in-process/HybridCache of session→user could
   shave it — but the sliding write still has to happen and logout must invalidate, so it's not
   worth it in v1. Note and revisit only if the auth path shows up in profiling.

---

## 5. Recommendation

**Preserve sessions — it's nearly free, so do it.** There is no migration, no re-login event,
and no crypto to reproduce; the cost is implementing the same dual-transport read against the
same `user_session` table plus a custom `AuthenticationHandler`, and matching the cookie
attributes (§3). Ship v1 at behavioral parity (keep the per-request slide, keep argon2i), and
treat the write-debounce and lookup-cache as later optimizations gated on real profiling.

The **only** thing that can turn this "easy" into "mass logout" is the cookie `SameSite`/domain
mismatch in §3.3 — so that's the item to get right and to test explicitly (log in on PHP, deploy
.NET, confirm the *existing* cookie authenticates without re-login) before cutover.

---

## 6. Security posture & hardening (from scratch)

**The scheme itself is sound — "old" here means canonical, not outdated.** An opaque, random,
server-side session id looked up in a table is the OWASP-recommended session-management
approach; it is *more* secure than a JWT-in-cookie for session purposes because it is instantly
revocable (delete the row), carries no tamperable/leakable payload, and keeps the server
authoritative. The security is in the handling, and PSP's is mostly good.

**Strengths already present (✅ verified):**
- Token is ~**238 bits** of CSPRNG entropy (40 chars over a 62-char alphabet via `random_int`,
  `CryptographicFunctions.php:39`) — unguessable.
- Cookie is **`Secure` + `HttpOnly`** → not sent over plaintext, not readable by JS (XSS can't
  exfiltrate `document.cookie`).
- **`SameSite=Lax` + POST-only mutations** → meaningful CSRF protection for free (there is *no*
  anti-CSRF token — the `csrf` hits in `config/reference.php` are Symfony's reference dump, not
  enabled — so SameSite + the "GET never mutates" rule is the CSRF defense).
- **Server-generated id on login** → session fixation not exploitable.
- Revocable + expiring.

**Hardening opportunities (cheap; the rewrite is the moment):**
1. **Hash the session id at rest — the biggest one.** Today `user_session.session_id` is stored
   **plaintext**, so a read-only DB leak (SQLi, leaked backup) yields *every live session token*
   → impersonate everyone until expiry. Store a **SHA-256 of the token**; the cookie holds the
   plaintext, the server hashes-then-looks-up (index on the hash). One line, and a DB leak
   yields nothing usable. (Note the asymmetry: passwords are argon2i'd; session tokens — equally
   sensitive bearer credentials — are not.)
2. **Set `SameSite=Lax` explicitly** (§3.3) rather than relying on the browser default.
3. **Add an absolute session lifetime cap** (expiry currently only *slides*, so an active or
   stolen-kept-active session never dies) + a **"log out all devices"** (delete the user's rows).
4. **Keep the token out of JS.** HttpOnly's XSS protection is void if the SPA stores the token in
   `localStorage` for the Bearer path — verify the browser client uses the cookie; reserve
   Bearer for non-browser clients.
5. **Defense-in-depth vs. XSS** (the dominant real threat): CSP + HSTS.

**Explicitly out of scope / accepted risk:** a malicious browser extension (or otherwise
compromised endpoint) is *inside* the browser trust boundary — it can act as the user or read
cookies via the extensions API regardless of HttpOnly, and regardless of opaque-token vs. JWT
vs. anything. No session scheme survives it; the only mitigations are exotic (token binding /
DPoP / mTLS-bound cookies) and are overkill here. This is **not** a reason to choose a different
scheme.

---

## 7. Open decision (to ratify)

1. **Preserve sessions across cutover?** (Recommended: yes.)
2. **v1 = behavioral parity** (keep per-request slide + argon2i), with debounce/rehash/cache as
   later options? (Recommended: yes.)
3. Confirm the deployment topology keeps the API under `*.poppyseedpets.com` (required for the
   Lax cookie to keep flowing to the SPA).
4. **Hash session ids at rest in the rewrite?** (Recommended: yes — cheapest high-value
   hardening; see §6.1.) And which §6 hardening items are in-scope for v1 vs. later.
