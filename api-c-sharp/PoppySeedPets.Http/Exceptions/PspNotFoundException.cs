using Microsoft.AspNetCore.Http;

namespace PoppySeedPets.Http.Exceptions;

public class PspNotFoundException(string clientMessage) : PspException(clientMessage)
{
    public override int StatusCode => StatusCodes.Status404NotFound;
}
