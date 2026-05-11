namespace PoppySeedPets.Http.Exceptions;

// Base for all client-facing app exceptions. Subclasses pick:
//   - HTTP status code via StatusCode override
//   - Client-safe message via constructor (message goes back to the browser as-is, so
//     don't put internal details in here)
//
// Throw from anywhere — services, controllers, helpers. PspExceptionHandler catches
// every subclass, maps StatusCode → response status, wraps message in ApiErrorResponse.
public abstract class PspException(string clientMessage) : Exception(clientMessage)
{
    public abstract int StatusCode { get; }
}
