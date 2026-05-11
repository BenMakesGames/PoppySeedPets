namespace PoppySeedPets.Http;

// Failure wire shape: { success: false, errors: [...] }.
// Mirrors PHP ResponseService::error(). Produced exclusively by PspExceptionHandler;
// endpoints throw PspException subclasses and never assemble this directly.
public sealed record ApiErrorResponse
{
    public bool Success => false;
    public IReadOnlyList<string> Errors { get; }

    public ApiErrorResponse(IReadOnlyList<string> errors) => Errors = errors;
    public ApiErrorResponse(string error) => Errors = [error];
}
