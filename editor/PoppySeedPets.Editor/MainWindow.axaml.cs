using Avalonia.Controls;
using Avalonia.Input;
using Avalonia.Interactivity;

namespace PoppySeedPets.Editor;

/// <summary>
/// The sections on the left, the open one on the right. Ctrl+S / Ctrl+Z go to the open section, ● marks unsaved
/// changes, and leaving a section (or the app) asks about them first. So only the open section is ever dirty.
/// </summary>
public sealed partial class MainWindow : Window
{
    private const string AppTitle = "Poppy Seed Pets Editor";

    private readonly IReadOnlyList<ISection> _sections;
    private readonly List<ListBoxItem> _items;
    private int _open;
    private bool _closeConfirmed;

    // For the XAML designer.
    public MainWindow() : this(Program.CreateSections(Program.RepoRoot)) { }

    public MainWindow(IReadOnlyList<ISection> sections)
    {
        InitializeComponent();

        _sections = sections;
        _items = sections.Select(s => new ListBoxItem { Content = s.Title }).ToList();
        SectionList.ItemsSource = _items;
        foreach (var section in sections) section.Document.Changed += UpdateDirtyMarks;

        Open(0);
        SectionList.SelectedIndex = 0;
        SectionList.SelectionChanged += OnSectionPicked;

        // Tunnel, so the shortcuts beat a focused TextBox's own Ctrl+Z: one undo history, the document's.
        AddHandler(KeyDownEvent, OnShortcut, RoutingStrategies.Tunnel);
        Activated += (_, _) => OpenSection.Document.Refresh();
        Closing += OnClosing;
    }

    internal ISection OpenSection => _sections[_open];

    private void Open(int index)
    {
        _open = index;
        OpenSection.Document.Refresh();
        SectionHost.Content = OpenSection.View;
        UpdateDirtyMarks();
    }

    private void UpdateDirtyMarks()
    {
        for (var i = 0; i < _sections.Count; i++)
            _items[i].Content = _sections[i].Title + (_sections[i].Document.IsDirty ? " ●" : "");

        Title = AppTitle + (OpenSection.Document.IsDirty ? " ●" : "");
    }

    private async void OnSectionPicked(object? sender, SelectionChangedEventArgs e)
    {
        var picked = SectionList.SelectedIndex;
        if (picked < 0 || picked == _open) return;

        if (await SettleUnsavedChanges()) Open(picked);
        else SectionList.SelectedIndex = _open;
    }

    private async void OnShortcut(object? sender, KeyEventArgs e)
    {
        if (e.KeyModifiers != KeyModifiers.Control) return;

        switch (e.Key)
        {
            case Key.Z:
                e.Handled = true;
                OpenSection.Document.Undo();
                break;
            case Key.S:
                e.Handled = true;
                await Save();
                break;
        }
    }

    /// <returns>True if nothing's left unsaved; on failure, says why.</returns>
    private async Task<bool> Save()
    {
        var document = OpenSection.Document;
        if (!document.IsDirty) return true;

        if (document.HasOutsideChanges)
        {
            var answer = await ChoiceDialog.Show(this, "Changed on disk",
                $"{document.FileName} has changed since it was opened. Saving replaces {OpenSection.Title} with what's here; the rest of the file is kept.",
                "Save anyway", "Cancel");
            if (answer != "Save anyway") return false;
        }

        if (document.Save() is not { } problem) return true;

        await ChoiceDialog.Show(this, "Couldn't save", problem, "OK");
        return false;
    }

    /// <returns>True if the open section can be left: it was clean, or was saved or discarded.</returns>
    private async Task<bool> SettleUnsavedChanges()
    {
        if (!OpenSection.Document.IsDirty) return true;

        var answer = await ChoiceDialog.Show(this, "Unsaved changes", $"Save changes to {OpenSection.Title}?", "Save", "Don't save", "Cancel");
        switch (answer)
        {
            case "Save":
                return await Save();
            case "Don't save":
                OpenSection.Document.Reload();
                return true;
            default:
                return false;
        }
    }

    private async void OnClosing(object? sender, WindowClosingEventArgs e)
    {
        if (_closeConfirmed || !OpenSection.Document.IsDirty) return;

        // Closing can't wait on a dialog, so cancel this close, and close again once it's settled.
        e.Cancel = true;
        if (!await SettleUnsavedChanges()) return;

        _closeConfirmed = true;
        Close();
    }
}
