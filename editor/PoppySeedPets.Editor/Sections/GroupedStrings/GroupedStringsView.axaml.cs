using System.Collections.ObjectModel;
using Avalonia.Controls;
using Avalonia.Layout;
using Avalonia.Media;
using PoppySeedPets.Editor.Documents;
using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Sections.GroupedStrings;

/// <summary>
/// Edits a <see cref="GroupedStringsCodec"/> const: pick a group on the left, edit it on the right.
/// </summary>
/// <remarks>
/// Controls sync <em>from</em> the document idempotently: setting a box to its current text is a no-op, and so is
/// changing the document to what it already holds, so no "am I refreshing?" flags are needed. The selection is held
/// here, not by the list, as the filter can hide the selected group, and it follows whatever last changed, so that
/// undoing shows what was undone.
/// </remarks>
public sealed partial class GroupedStringsView : UserControl, ISection
{
    private readonly PhpDocument<IReadOnlyList<StringGroup>> _document;
    private readonly ObservableCollection<GroupRow> _rows = [];
    private IReadOnlyList<StringGroup> _shown = [];
    private int _rowsBuiltFor = -1;
    private int _selected;

    // For the XAML designer.
    public GroupedStringsView() : this("Preview", "", "Preview") { }

    public GroupedStringsView(string title, string path, string constName)
    {
        InitializeComponent();

        Title = title;
        _document = new(path, new GroupedStringsCodec(constName), ItemsComparer<StringGroup>.Instance);
        GroupList.ItemsSource = _rows;

        _document.Changed += Sync;
        OnTextSet(FilterBox, BuildRows);
        GroupList.SelectionChanged += (_, _) =>
        {
            if (GroupList.SelectedItem is not GroupRow row || row.Index == _selected) return;
            _selected = row.Index;
            ShowSelected();
        };

        OnTextSet(HeadingBox, () =>
        {
            if (Selected is { } group)
                ChangeSelected(group with { Heading = HeadingBox.Text ?? "" }, $"heading {_selected}");
        });

        AddLineButton.Click += (_, _) =>
        {
            if (Selected is not { } group) return;
            ChangeSelected(group with { Strings = group.Strings.Add("") });
            Lines.Children.OfType<DockPanel>().LastOrDefault()?.Children.OfType<TextBox>().First().Focus();
        };

        AddGroupButton.Click += (_, _) =>
        {
            if (_document.Value is not { } groups) return;
            _document.Change([.. groups, new StringGroup("", [""])]);
            HeadingBox.Focus();
        };

        DeleteGroupButton.Click += (_, _) =>
        {
            if (_document.Value is { } groups && Selected is not null)
                _document.Change(groups.Where((_, i) => i != _selected).ToList());
        };
    }

    public string Title { get; }
    public Control View => this;
    public ISectionDocument Document => _document;

    private StringGroup? Selected => _document.Value is { } groups && _selected < groups.Count ? groups[_selected] : null;

    /// <summary>TextChanged is posted, so it lags the box: long enough for a save to miss what was just typed.</summary>
    private static void OnTextSet(TextBox box, Action handler) =>
        box.PropertyChanged += (_, e) =>
        {
            if (e.Property == TextBox.TextProperty) handler();
        };

    private void ChangeSelected(StringGroup after, string? mergeKey = null)
    {
        if (_document.Value is { } groups)
            _document.Change(groups.Select((group, i) => i == _selected ? after : group).ToList(), mergeKey);
    }

    private void Sync()
    {
        LoadError.Text = _document.LoadError;
        LoadError.IsVisible = _document.LoadError is not null;
        Editor.IsVisible = _document.Value is not null;

        var groups = _document.Value ?? [];
        if (FirstDifference(_shown, groups) is { } changed) _selected = Math.Max(0, Math.Min(changed, groups.Count - 1));
        _shown = groups;

        Summary.Text = $"{groups.Count} groups, {groups.Sum(g => g.Strings.Length)} lines";

        // Heading edits leave the rows alone, and don't re-filter, or a group could vanish from the list mid-word.
        if (groups.Count != _rowsBuiltFor || _rows.All(r => r.Index != _selected))
        {
            if (FilterBox.Text is { Length: > 0 }) FilterBox.Text = "";
            BuildRows();
        }
        else
        {
            foreach (var row in _rows) row.Heading = HeadingOf(groups[row.Index]);
            SelectRow();
        }

        ShowSelected();
    }

    private static int? FirstDifference(IReadOnlyList<StringGroup> a, IReadOnlyList<StringGroup> b)
    {
        for (var i = 0; i < Math.Min(a.Count, b.Count); i++)
            if (!a[i].Equals(b[i])) return i;

        return a.Count == b.Count ? null : Math.Min(a.Count, b.Count);
    }

    private static string HeadingOf(StringGroup group) => group.Heading.Length > 0 ? group.Heading : "(no heading)";

    private void BuildRows()
    {
        var filter = FilterBox.Text ?? "";
        _rows.Clear();
        foreach (var (index, group) in _shown.Index())
            if (group.Heading.Contains(filter, StringComparison.OrdinalIgnoreCase)
                || group.Strings.Any(s => s.Contains(filter, StringComparison.OrdinalIgnoreCase)))
                _rows.Add(new GroupRow(index, HeadingOf(group)));

        _rowsBuiltFor = _shown.Count;
        SelectRow();
    }

    private void SelectRow() => GroupList.SelectedItem = _rows.FirstOrDefault(r => r.Index == _selected);

    private void ShowSelected()
    {
        if (Selected is not { } group)
        {
            HeadingBox.IsEnabled = AddLineButton.IsEnabled = DeleteGroupButton.IsEnabled = false;
            HeadingBox.Text = "";
            Lines.Children.Clear();
            return;
        }

        HeadingBox.IsEnabled = AddLineButton.IsEnabled = DeleteGroupButton.IsEnabled = true;
        HeadingBox.Text = group.Heading;

        while (Lines.Children.Count > group.Strings.Length) Lines.Children.RemoveAt(Lines.Children.Count - 1);
        while (Lines.Children.Count < group.Strings.Length) Lines.Children.Add(CreateLine(Lines.Children.Count));

        foreach (var (line, row) in Lines.Children.OfType<DockPanel>().Index())
            row.Children.OfType<TextBox>().Single().Text = group.Strings[line];
    }

    /// <param name="line">The line this row shows. Rows are reused between groups, and only ever removed from the end.</param>
    private DockPanel CreateLine(int line)
    {
        var box = new TextBox { TextWrapping = TextWrapping.Wrap };
        OnTextSet(box, () =>
        {
            if (Selected is { } group && line < group.Strings.Length)
                ChangeSelected(group with { Strings = group.Strings.SetItem(line, box.Text ?? "") }, $"line {_selected} {line}");
        });

        var delete = new Button { Content = "✕", VerticalAlignment = VerticalAlignment.Top, Margin = new(6, 0, 0, 0) };
        ToolTip.SetTip(delete, "Delete line");
        delete.Click += (_, _) =>
        {
            if (Selected is { } group && line < group.Strings.Length)
                ChangeSelected(group with { Strings = group.Strings.RemoveAt(line) });
        };

        DockPanel.SetDock(delete, Dock.Right);
        return new DockPanel { Children = { delete, box } };
    }
}
