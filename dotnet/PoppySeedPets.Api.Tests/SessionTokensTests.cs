using PoppySeedPets.Api.Infrastructure.Auth;

namespace PoppySeedPets.Api.Tests;

public class SessionTokensTests
{
    [Fact]
    public void Generate_produces_a_40_char_alphanumeric_token()
    {
        var token = SessionTokens.Generate();

        Assert.Equal(40, token.Length);
        Assert.All(token, c => Assert.True(char.IsAsciiLetterOrDigit(c)));
    }

    [Fact]
    public void Generate_is_not_repetitive()
    {
        var tokens = Enumerable.Range(0, 100).Select(_ => SessionTokens.Generate()).ToHashSet();

        Assert.Equal(100, tokens.Count);
    }

    [Fact]
    public void Hash_is_deterministic_lowercase_hex_sha256()
    {
        var hash = SessionTokens.Hash("abc");

        // Known SHA-256("abc").
        Assert.Equal("ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad", hash);
    }

    [Fact]
    public void Hash_differs_per_token_and_never_echoes_the_token()
    {
        var token = SessionTokens.Generate();
        var hash = SessionTokens.Hash(token);

        Assert.NotEqual(token, hash);
        Assert.NotEqual(hash, SessionTokens.Hash(SessionTokens.Generate()));
        Assert.Equal(64, hash.Length);
    }
}
