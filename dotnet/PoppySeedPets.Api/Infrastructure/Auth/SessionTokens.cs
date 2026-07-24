using System.Security.Cryptography;
using System.Text;

namespace PoppySeedPets.Api.Infrastructure.Auth;

/// <summary>
/// Session-token generation and hashing. The token is an opaque random string — the OWASP-preferred
/// session scheme (revocable, no payload). See auth-cutover.md.
/// </summary>
public static class SessionTokens
{
    private const string Alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";

    /// <summary>
    /// 40 chars — must stay 40 to remain byte-compatible with cookies already in users' browsers
    /// (the PHP authenticator only accepts a 40-char cookie). auth-cutover.md §3.
    /// </summary>
    public const int TokenLength = 40;

    /// <summary>Generate a fresh token using a CSPRNG (matches PHP's <c>random_int</c>). ~238 bits of entropy.</summary>
    public static string Generate()
    {
        Span<char> buffer = stackalloc char[TokenLength];
        for (var i = 0; i < TokenLength; i++)
            buffer[i] = Alphabet[RandomNumberGenerator.GetInt32(Alphabet.Length)];

        return new string(buffer);
    }

    /// <summary>
    /// Lowercase-hex SHA-256 of the token. This is what we persist, so a read-only DB leak yields no
    /// usable sessions (auth-cutover.md §6.1). The cookie holds the plaintext; we hash on the way in.
    /// </summary>
    public static string Hash(string token) =>
        Convert.ToHexStringLower(SHA256.HashData(Encoding.UTF8.GetBytes(token)));
}
