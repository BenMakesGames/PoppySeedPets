using Microsoft.AspNetCore.Http;

namespace PoppySeedPets.Http.Exceptions;

public sealed class PspInvalidOperationException(string clientMessage) : PspException(clientMessage)
{
    public override int StatusCode => StatusCodes.Status422UnprocessableEntity;
}
