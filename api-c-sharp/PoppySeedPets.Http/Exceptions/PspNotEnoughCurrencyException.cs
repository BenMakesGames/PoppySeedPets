using Microsoft.AspNetCore.Http;

namespace PoppySeedPets.Http.Exceptions;

public sealed class PspNotEnoughCurrencyException(string need, string have)
    : PspException($"You need {need} to do that, but only have {have}...")
{
    public PspNotEnoughCurrencyException(string need, int have) : this(need, have.ToString()) { }

    public override int StatusCode => StatusCodes.Status422UnprocessableEntity;
}
