using Microsoft.AspNetCore.Http;

namespace PoppySeedPets.Http.Exceptions;

public sealed class PspNotUnlockedException(string featureName)
    : PspException($"You haven't unlocked the {featureName} yet!")
{
    public override int StatusCode => StatusCodes.Status403Forbidden;
}
