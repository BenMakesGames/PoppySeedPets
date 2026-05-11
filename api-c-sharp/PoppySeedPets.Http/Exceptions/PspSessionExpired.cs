using Microsoft.AspNetCore.Http;

namespace PoppySeedPets.Http.Exceptions;

public sealed class PspSessionExpired() : PspException("You have been logged out due to inactivity. Please log in again.")
{
    public override int StatusCode => StatusCodes.Status401Unauthorized;
}
