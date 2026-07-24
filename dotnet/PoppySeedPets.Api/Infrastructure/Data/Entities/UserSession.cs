namespace PoppySeedPets.Api.Infrastructure.Data.Entities;

/// <summary>
/// A login session. The opaque token itself is never stored — we keep only its SHA-256
/// hash, so a read-only DB leak yields no usable sessions (auth-cutover.md §6.1).
///
/// Cutover note: existing PHP sessions are preserved because the cutover migration back-fills
/// <see cref="SessionIdHash"/> = SHA256(existing plaintext <c>session_id</c>) before dropping the
/// plaintext column. The browser keeps sending the plaintext cookie; the server hashes and matches.
/// </summary>
public class UserSession
{
    public int Id { get; set; }

    /// <summary>Lowercase hex SHA-256 of the 40-char session token.</summary>
    public required string SessionIdHash { get; set; }

    public int UserId { get; set; }

    public User User { get; set; } = null!;

    public DateTimeOffset SessionExpiration { get; set; }
}
