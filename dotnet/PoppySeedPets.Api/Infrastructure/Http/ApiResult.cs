namespace PoppySeedPets.Api.Infrastructure.Http;

/// <summary>
/// Minimal response envelope, mirroring the PHP <c>{ success, data }</c> shape. The full PHP
/// ResponseService also injects user data, activity flash messages, and reload flags — those are a
/// later vertical (see current-architecture-and-gotchas.md G4); this skeleton keeps just the core.
/// </summary>
public sealed record ApiResult<T>(bool Success, T? Data)
{
    public static ApiResult<T> Ok(T data) => new(true, data);
}
