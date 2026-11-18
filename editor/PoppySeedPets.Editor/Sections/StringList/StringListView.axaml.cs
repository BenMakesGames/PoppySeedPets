using System.Collections.ObjectModel;
using System.ComponentModel;
using Avalonia.Controls;
using Avalonia.Input;
using Avalonia.Interactivity;
using Avalonia.Threading;
using Avalonia.VisualTree;
using PoppySeedPets.Editor.Documents;
using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Sections.StringList;

/// <summary>The entry at an index. Mutable, and positional: removing an entry shifts text between rows.</summary>
public sealed class EntryRow(int index, Action<EntryRow> edited) : INotifyPropertyChanged
{
    private string _text = "";

    public int Index { get; } = index;

    public string Text
    {
        get => _text;
        set
        {
            if (_text == value) return;
            _text = value;
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(nameof(Text)));
            edited(this);
        }
    }

    public event PropertyChangedEventHandler? PropertyChanged;
}

/// <summary>
/// Edits a <see cref="StringListCodec"/> const: every entry is a box, all in one list. Syncs from the document the same
/// way as <see cref="GroupedStrings.GroupedStringsView"/>, and shows whatever last changed, so that undoing shows what
/// was undone.
/// </summary>
public sealed partial class StringListView : UserControl, ISection
{
    private readonly PhpDocument<IReadOnlyList<string>> _document;
    private readonly List<EntryRow> _rows = [];
    private IReadOnlyList<string> _shown = [];

    // For the XAML designer.
    public StringListView() : this("Preview", "", "Preview") { }

    /// <param name="title">The section's name.</param>
    /// <param name="path">The PHP file.</param>
    /// <param name="constName">The const.</param>
    /// <param name="alphabetical">
    /// Keeps the list sorted: an entry moves to its place once it's finished with, never while it's being typed, and
    /// Enter finishes it. Otherwise, the list's order is the user's, and Enter adds a line below.
    /// </param>
    public StringListView(string title, string path, string constName, bool alphabetical = false)
    {
        InitializeComponent();

        Title = title;
        _document = new(path, new StringListCodec(constName), ItemsComparer<string>.Instance, alphabetical ? Alphabetical.Sorted : null);
        Hint.Text = alphabetical ? "Kept in alphabetical order" : "Enter adds a line below";

        Lines.ItemsSource = Visible;

        _document.Changed += Sync;
        FilterBox.OnTextSet(ApplyFilter);
        AddButton.Click += (_, _) =>
        {
            if (_document.Value is { } strings) InsertLine(strings.Count);
        };

        // Rows come and go as they scroll, so their controls' events are handled out here.
        Lines.AddHandler(Button.ClickEvent, (_, e) =>
        {
            if (e.Source is Control { DataContext: EntryRow row }) DeleteLine(row.Index);
        });

        Lines.AddHandler(KeyDownEvent, (_, e) =>
        {
            if (e.Key != Key.Enter || e.Source is not Control { DataContext: EntryRow row }) return;
            e.Handled = true;
            if (alphabetical) _document.Normalize();
            else InsertLine(row.Index + 1);
        });

        if (alphabetical) Lines.AddHandler(LostFocusEvent, (_, _) => _document.Normalize());
    }

    public string Title { get; }
    public Control View => this;
    public ISectionDocument Document => _document;

    /// <summary>The rows the filter lets through.</summary>
    internal ObservableCollection<EntryRow> Visible { get; } = [];

    private void InsertLine(int at)
    {
        if (_document.Value is not { } strings) return;

        _document.Change([.. strings.Take(at), "", .. strings.Skip(at)]);

        // Posted, as the row's controls don't exist until it's been scrolled to and laid out.
        var row = _rows[at];
        Dispatcher.UIThread.Post(() =>
        {
            if (Lines.ContainerFromIndex(Visible.IndexOf(row)) is { } container)
                container.GetVisualDescendants().OfType<TextBox>().FirstOrDefault()?.Focus();
        }, DispatcherPriority.Background);
    }

    private void DeleteLine(int line)
    {
        if (_document.Value is { } strings && line < strings.Count)
            _document.Change(strings.Where((_, i) => i != line).ToList());
    }

    private void Edited(EntryRow row)
    {
        if (_document.Value is { } strings && row.Index < strings.Count)
            _document.Change(strings.Select((s, i) => i == row.Index ? row.Text : s).ToList(), $"entry {row.Index}");
    }

    private void Sync()
    {
        LoadError.Text = _document.LoadError;
        LoadError.IsVisible = _document.LoadError is not null;
        Editor.IsVisible = _document.Value is not null;

        var strings = _document.Value ?? [];
        var changed = ListDifference.First(_shown, strings);
        var previous = _shown;
        var removed = strings.Count < previous.Count;
        var moved = strings.Count != previous.Count || strings.Zip(previous).Count(pair => pair.First != pair.Second) > 1;
        _shown = strings;

        Summary.Text = $"{strings.Count} entries";

        if (_rows.Count > strings.Count) _rows.RemoveRange(strings.Count, _rows.Count - strings.Count);
        while (_rows.Count < strings.Count) _rows.Add(new EntryRow(_rows.Count, Edited));

        foreach (var (line, row) in _rows.Index()) row.Text = strings[line];

        // Editing an entry doesn't re-filter, or it could vanish from the list mid-word.
        if (moved) ApplyFilter();

        // Not after removing: what's now there isn't what changed, and is no reason to drop a filter.
        if (changed is { } first && first < strings.Count && !removed) Reveal(_rows[Target(previous, strings, first)]);
    }

    /// <summary>
    /// Where what changed ended up, given where the lists first differ. That's the same place, unless an entry moved
    /// down the list, in which case the first difference is just what used to come after it.
    /// </summary>
    private static int Target(IReadOnlyList<string> before, IReadOnlyList<string> after, int first)
    {
        if (before.Count != after.Count) return first;

        var last = after.Count - 1;
        while (before[last] == after[last]) last--;
        return before[first] == after[last] ? last : first;
    }

    private void ApplyFilter()
    {
        var filter = FilterBox.Text ?? "";
        var wanted = _rows.Where(r => r.Text.Contains(filter, StringComparison.OrdinalIgnoreCase)).ToList();

        // From the end, and only what differs, so that adding a line doesn't reset the scroll position.
        var same = Visible.Zip(wanted).TakeWhile(pair => pair.First == pair.Second).Count();
        while (Visible.Count > same) Visible.RemoveAt(Visible.Count - 1);
        foreach (var row in wanted.Skip(same)) Visible.Add(row);
    }

    private void Reveal(EntryRow row)
    {
        if (!Visible.Contains(row)) FilterBox.Text = "";

        Dispatcher.UIThread.Post(() => Lines.ScrollIntoView(Visible.IndexOf(row)), DispatcherPriority.Background);
    }
}
