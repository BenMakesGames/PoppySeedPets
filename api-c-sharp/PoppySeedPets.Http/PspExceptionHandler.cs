using Microsoft.AspNetCore.Diagnostics;
using Microsoft.AspNetCore.Http;
using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;
using PoppySeedPets.Http.Exceptions;

namespace PoppySeedPets.Http;

// Catches every unhandled exception bubbling out of an endpoint and writes our standard
// ApiErrorResponse wire shape. Registered via app.UseExceptionHandler() in Program.cs.
//
// Known PspException subclasses → their StatusCode + their Message (already client-safe).
// Anything else → 500 + generic message in production; rethrow in Development so the
// developer exception page surfaces it.
public sealed class PspExceptionHandler(
    IHostEnvironment env,
    ILogger<PspExceptionHandler> logger
): IExceptionHandler
{
    private const string GenericErrorMessage =
        "A nasty error occurred! Don't worry: Ben has been e-mailed... he'll get it sorted. In the meanwhile, just try reloading and trying again!";

    public async ValueTask<bool> TryHandleAsync(HttpContext httpContext, Exception exception, CancellationToken cancellationToken)
    {
        if (exception is PspException psp)
        {
            httpContext.Response.StatusCode = psp.StatusCode;
            await httpContext.Response.WriteAsJsonAsync(new ApiErrorResponse(psp.Message), cancellationToken);
            return true;
        }

        logger.LogCritical(exception, "Unhandled exception: {Message}", exception.Message);

        // Let the developer exception page take over in dev — easier debugging than a
        // sanitized 500.
        if (env.IsDevelopment())
            return false;

        httpContext.Response.StatusCode = StatusCodes.Status500InternalServerError;
        await httpContext.Response.WriteAsJsonAsync(new ApiErrorResponse(GenericErrorMessage), cancellationToken);
        return true;
    }
}
