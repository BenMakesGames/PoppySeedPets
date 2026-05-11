using Microsoft.AspNetCore.Http;

namespace PoppySeedPets.Http.Exceptions;

// Technically forbidden (we know who the user is), but the client auto-logs-out on 401
// and there are legit Forbidden cases we DON'T want to log out for (e.g. accessing the
// Fireplace before unlocking it). So we return 401 here too. See the PHP equivalent.
public sealed class PspAccountLocked() : PspException("This account has been locked.")
{
    public override int StatusCode => StatusCodes.Status401Unauthorized;
}
