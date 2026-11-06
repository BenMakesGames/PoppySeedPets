namespace PoppySeedPets.Editor.Php;

/// <summary>
/// How one kind of data lives in a PHP file. A new kind of PHP-backed section needs one of these and a view; files,
/// undo, dirty tracking and conflicts are <see cref="Documents.PhpDocument{T}"/>'s.
/// </summary>
/// <typeparam name="T">Immutable.</typeparam>
public interface IPhpCodec<T>
{
    /// <exception cref="PhpSourceException">The data isn't there, or isn't in a form this codec understands.</exception>
    T Read(string source);

    /// <summary>What's stopping <paramref name="value"/> from being saved, or null if nothing is.</summary>
    string? Problem(T value);

    /// <summary>
    /// <paramref name="source"/> with its data replaced by <paramref name="value"/>, leaving every byte that didn't
    /// have to change alone. Writing what <see cref="Read"/> returned must give back <paramref name="source"/> exactly.
    /// </summary>
    /// <exception cref="PhpSourceException"/>
    string Write(string source, T value);
}
