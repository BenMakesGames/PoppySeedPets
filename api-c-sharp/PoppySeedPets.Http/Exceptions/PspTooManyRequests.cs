namespace PoppySeedPets.Http.Exceptions;

// 420 is a project-custom status code (NOT the standard 429). Frontend's bot-detection
// flow keys off 420 specifically; don't substitute 429 without coordinating with the
// frontend.
public sealed class PspTooManyRequests() : PspException("Too many simultaneous requests. Please try again in a few seconds.")
{
    public override int StatusCode => 420;
}
