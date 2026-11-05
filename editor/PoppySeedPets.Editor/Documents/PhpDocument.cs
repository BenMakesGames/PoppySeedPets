using System.Text;
using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Documents;

/// <summary>The non-visual half of a section: what <see cref="MainWindow"/> needs to save, undo and warn.</summary>
public interface ISectionDocument
{
    /// <summary>For messages.</summary>
    string FileName { get; }

    bool IsDirty { get; }

    /// <summary>The file no longer matches what was loaded or last saved.</summary>
    bool HasOutsideChanges { get; }

    /// <summary>Raised after any change to the value or dirty state.</summary>
    event Action? Changed;

    void Undo();

    /// <returns>Why it couldn't be saved, or null if it was.</returns>
    string? Save();

    /// <summary>Discards everything and re-reads the file.</summary>
    void Reload();

    /// <summary>Catches up with the file, unless that would lose unsaved changes.</summary>
    void Refresh();
}

/// <summary>Some data from a PHP file, being edited.</summary>
/// <typeparam name="T">Immutable.</typeparam>
public sealed class PhpDocument<T>(string path, IPhpCodec<T> codec, IEqualityComparer<T> comparer) : ISectionDocument where T : class
{
    // Strict, so a file that isn't valid UTF-8 is refused rather than saved with replacement characters.
    private static readonly UTF8Encoding Utf8 = new(encoderShouldEmitUTF8Identifier: false, throwOnInvalidBytes: true);

    private Undoable<T>? _content;
    private string? _lastKnownText;

    public string FileName => Path.GetFileName(path);

    /// <summary>Null until loaded, or if the file can't be: see <see cref="LoadError"/>.</summary>
    public T? Value => _content?.Value;

    public string? LoadError { get; private set; }

    public bool IsDirty => _content?.IsDirty ?? false;

    public bool HasOutsideChanges
    {
        get
        {
            try
            {
                return ReadText() != _lastKnownText;
            }
            catch (Exception e) when (IsFileProblem(e))
            {
                return true;
            }
        }
    }

    public event Action? Changed;

    /// <param name="mergeKey">Consecutive changes with the same key are one undo step: see <see cref="Undoable{T}.Change"/>.</param>
    public void Change(T after, string? mergeKey = null)
    {
        if (_content?.Change(after, mergeKey) == true) Changed?.Invoke();
    }

    public void Undo()
    {
        if (_content?.Undo() == true) Changed?.Invoke();
    }

    public string? Save()
    {
        if (_content is not { IsDirty: true } content) return null;
        if (codec.Problem(content.Value) is { } problem) return problem;

        try
        {
            // The file as it is now, so that outside changes to the rest of it survive.
            var current = ReadText();
            var updated = codec.Write(current, content.Value);
            if (updated != current) WriteText(updated);
            _lastKnownText = updated;
        }
        catch (Exception e) when (IsFileProblem(e))
        {
            return e.Message;
        }

        content.MarkSaved();
        Changed?.Invoke();
        return null;
    }

    public void Reload()
    {
        try
        {
            var text = ReadText();
            _content = new Undoable<T>(codec.Read(text), comparer);
            (_lastKnownText, LoadError) = (text, null);
        }
        catch (Exception e) when (IsFileProblem(e))
        {
            (_content, _lastKnownText, LoadError) = (null, null, e.Message);
        }

        Changed?.Invoke();
    }

    public void Refresh()
    {
        if (!IsDirty && (_content is null || HasOutsideChanges)) Reload();
    }

    private string ReadText() => Utf8.GetString(File.ReadAllBytes(path));

    // Via a temp file, so that a failure halfway can't leave half a file.
    private void WriteText(string text)
    {
        var temp = path + ".tmp";
        try
        {
            File.WriteAllBytes(temp, Utf8.GetBytes(text));
            File.Move(temp, path, overwrite: true);
        }
        finally
        {
            File.Delete(temp);
        }
    }

    private static bool IsFileProblem(Exception e) =>
        e is IOException or UnauthorizedAccessException or DecoderFallbackException or PhpSourceException;
}

/// <summary>Compares lists item by item.</summary>
public sealed class ItemsComparer<T> : IEqualityComparer<IReadOnlyList<T>>
{
    public static readonly ItemsComparer<T> Instance = new();

    public bool Equals(IReadOnlyList<T>? a, IReadOnlyList<T>? b) => a is not null && b is not null && a.SequenceEqual(b);

    public int GetHashCode(IReadOnlyList<T> items) => items.Count;
}
