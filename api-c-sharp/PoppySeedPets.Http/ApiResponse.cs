namespace PoppySeedPets.Http;

// Success wire shape: { success: true, data: ... }.
// Mirrors PHP ResponseService::success() (sans the user/activity injection — that's
// auth-scoped and layered on later for authenticated endpoints).
//
// `Success` is computed, not a constructor param — devs can't construct an error-shaped
// success. Error responses are produced exclusively by PspExceptionHandler from thrown
// PspException subclasses, never assembled by hand in endpoints.
public sealed record ApiResponse<T>
{
    public bool Success => true;
    public T Data { get; }

    public ApiResponse(T data) => Data = data;
}
