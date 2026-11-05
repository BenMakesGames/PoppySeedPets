namespace PoppySeedPets.Editor.Documents;

/// <summary>An immutable value with undo, which knows whether it differs from what was last saved.</summary>
public sealed class Undoable<T>(T value, IEqualityComparer<T> comparer)
{
    private readonly Stack<T> _undo = new();
    private T _saved = value;
    private string? _lastMergeKey;

    public T Value { get; private set; } = value;

    // Compared rather than flagged, so undoing or retyping back to what was saved is clean again.
    public bool IsDirty => !comparer.Equals(Value, _saved);

    /// <param name="mergeKey">Consecutive changes with the same key (typing in one field) are one undo step. Null never merges.</param>
    /// <returns>Whether the value changed.</returns>
    public bool Change(T after, string? mergeKey)
    {
        if (comparer.Equals(after, Value)) return false;

        if (mergeKey is null || mergeKey != _lastMergeKey) _undo.Push(Value);

        Value = after;
        _lastMergeKey = mergeKey;
        return true;
    }

    /// <returns>Whether there was anything to undo.</returns>
    public bool Undo()
    {
        if (!_undo.TryPop(out var before)) return false;

        Value = before;
        _lastMergeKey = null;
        return true;
    }

    public void MarkSaved()
    {
        _saved = Value;
        _lastMergeKey = null;
    }
}
