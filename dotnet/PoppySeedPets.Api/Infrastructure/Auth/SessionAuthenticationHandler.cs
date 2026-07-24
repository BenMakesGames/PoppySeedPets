using System.Security.Claims;
using System.Text.Encodings.Web;
using System.Text.Json;
using Microsoft.AspNetCore.Authentication;
using Microsoft.Extensions.Options;

namespace PoppySeedPets.Api.Infrastructure.Auth;

/// <summary>
/// Custom session authentication: read the opaque token from the <c>sessionId</c> cookie (or a
/// Bearer header), validate it against the DB, and build the principal. This is a thin adapter over
/// <see cref="ISessionValidator"/> — deliberately NOT ASP.NET's cookie or JWT handler, since the
/// token carries no claims and is validated by DB lookup. See auth-cutover.md §3.
/// </summary>
public sealed class SessionAuthenticationHandler : AuthenticationHandler<AuthenticationSchemeOptions>
{
    public const string SchemeName = "PspSession";
    public const string CookieName = "sessionId";

    private readonly ISessionValidator _validator;

    public SessionAuthenticationHandler(
        IOptionsMonitor<AuthenticationSchemeOptions> options,
        ILoggerFactory logger,
        UrlEncoder encoder,
        ISessionValidator validator)
        : base(options, logger, encoder)
    {
        _validator = validator;
    }

    protected override async Task<AuthenticateResult> HandleAuthenticateAsync()
    {
        if (!TryGetToken(out var token))
            return AuthenticateResult.NoResult();

        var result = await _validator.ValidateAsync(token, Context.RequestAborted);

        switch (result.Status)
        {
            case SessionStatus.Valid:
                var user = result.User!;
                var claims = new[]
                {
                    new Claim(ClaimTypes.NameIdentifier, user.Id.ToString()),
                    new Claim(ClaimTypes.Email, user.Email),
                };
                var identity = new ClaimsIdentity(claims, SchemeName);
                var ticket = new AuthenticationTicket(new ClaimsPrincipal(identity), SchemeName);
                return AuthenticateResult.Success(ticket);

            case SessionStatus.Locked:
                return AuthenticateResult.Fail("Account is locked.");

            default: // NotFound / Expired
                return AuthenticateResult.Fail("Session expired.");
        }
    }

    /// <summary>
    /// Emit the PSP error envelope at 401 (client auto-logs-out on 401 — G9), instead of the default
    /// empty challenge.
    /// </summary>
    protected override async Task HandleChallengeAsync(AuthenticationProperties properties)
    {
        Response.StatusCode = StatusCodes.Status401Unauthorized;
        Response.ContentType = "application/json; charset=utf-8";
        var body = JsonSerializer.Serialize(new { success = false, errors = new[] { "You must be logged in to do that!" } });
        await Response.WriteAsync(body);
    }

    private bool TryGetToken(out string token)
    {
        token = "";

        // Cookie takes precedence; the length check mirrors the PHP authenticator (auth-cutover.md §3).
        if (Request.Cookies.TryGetValue(CookieName, out var cookie)
            && cookie is { Length: SessionTokens.TokenLength })
        {
            token = cookie;
            return true;
        }

        var authorization = Request.Headers.Authorization.ToString();
        if (authorization.StartsWith("Bearer ", StringComparison.Ordinal))
        {
            token = authorization["Bearer ".Length..];
            return token.Length > 0;
        }

        return false;
    }
}
