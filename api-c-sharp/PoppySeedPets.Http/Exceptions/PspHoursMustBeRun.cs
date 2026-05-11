namespace PoppySeedPets.Http.Exceptions;

// 470 is a project-custom status code (signals "house hours must be run before this
// request can proceed"). Frontend reads it to trigger the run-hours flow.
public sealed class PspHoursMustBeRun() : PspException("House hours must be run.")
{
    public override int StatusCode => 470;
}
