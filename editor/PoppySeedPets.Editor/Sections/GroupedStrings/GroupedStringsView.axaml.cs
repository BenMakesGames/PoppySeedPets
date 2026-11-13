using Avalonia.Controls;
using PoppySeedPets.Editor.Documents;
using PoppySeedPets.Editor.Php;

namespace PoppySeedPets.Editor.Sections.GroupedStrings;

/// <summary>
/// Edits a <see cref="GroupedStringsCodec"/> const: pick a group on the left, edit it on the right.
/// </summary>
/// <remarks>
/// Controls sync <em>from</em> the document idempotently: setting a box to its current text is a no-op, and so is
/// changing the document to what it already holds, so no "am I refreshing?" flags are needed.
/// </remarks>
public sealed partial class GroupedStringsView : UserControl, ISection
{
    private readonly PhpDocument<IReadOnlyList<StringGroup>> _document;

    // For the XAML designer.
    public GroupedStringsView() : this("Preview", "", "Preview") { }

    public GroupedStringsView(string title, string path, string constName)
    {
        InitializeComponent();

        Title = title;
        _document = new(path, new GroupedStringsCodec(constName), ItemsComparer<StringGroup>.Instance);

        _document.Changed += Sync;
        Picker.Picked += ShowSelected;

        HeadingBox.OnTextSet(() =>
        {
            if (Selected is { } group)
                ChangeSelected(group with { Heading = HeadingBox.Text ?? "" }, $"heading {Picker.Selected}");
        });

        AddLineButton.Click += (_, _) =>
        {
            if (Selected is not { } group) return;
            ChangeSelected(group with { Strings = group.Strings.Add("") });
            Lines.Children.OfType<LineRow>().LastOrDefault()?.Box.Focus();
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
                _document.Change(groups.Where((_, i) => i != Picker.Selected).ToList());
        };
    }

    public string Title { get; }
    public Control View => this;
    public ISectionDocument Document => _document;

    private StringGroup? Selected => _document.Value is { } groups && Picker.Selected < groups.Count ? groups[Picker.Selected] : null;

    private void ChangeSelected(StringGroup after, string? mergeKey = null)
    {
        if (_document.Value is { } groups)
            _document.Change(groups.Select((group, i) => i == Picker.Selected ? after : group).ToList(), mergeKey);
    }

    private void Sync()
    {
        LoadError.Text = _document.LoadError;
        LoadError.IsVisible = _document.LoadError is not null;
        Editor.IsVisible = _document.Value is not null;

        var groups = _document.Value ?? [];
        Summary.Text = $"{groups.Count} groups, {groups.Sum(g => g.Strings.Length)} lines";

        Picker.Show(groups.Select(ToItem).ToList());
        ShowSelected();
    }

    private static PickerItem ToItem(StringGroup group) => new(
        group.Heading.Length > 0 ? group.Heading : "(no heading)",
        string.Join('\n', group.Strings.Prepend(group.Heading)));

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

        foreach (var (line, row) in Lines.Children.OfType<LineRow>().Index())
            row.Box.Text = group.Strings[line];
    }

    /// <param name="line">The line this row shows. Rows are reused between groups, and only ever removed from the end.</param>
    private LineRow CreateLine(int line)
    {
        var row = new LineRow();

        row.Box.OnTextSet(() =>
        {
            if (Selected is { } group && line < group.Strings.Length)
                ChangeSelected(group with { Strings = group.Strings.SetItem(line, row.Box.Text ?? "") }, $"line {Picker.Selected} {line}");
        });

        row.DeleteButton.Click += (_, _) =>
        {
            if (Selected is { } group && line < group.Strings.Length)
                ChangeSelected(group with { Strings = group.Strings.RemoveAt(line) });
        };

        return row;
    }
}
