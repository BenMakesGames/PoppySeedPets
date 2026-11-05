using Avalonia.Controls;
using Avalonia.Headless;
using Avalonia.Headless.XUnit;
using Avalonia.Input;
using Avalonia.Interactivity;
using Avalonia.VisualTree;
using PoppySeedPets.Editor.Sections.StringList;

namespace PoppySeedPets.Editor.Tests;

public sealed class StringListViewTests : IDisposable
{
    private const string Text = "<?php\nconst array Names = [\n    'Ann', 'Bob',\n    'Cy'\n];\n";

    private readonly TempPhpFile _file = new(Text);

    public void Dispose() => _file.Dispose();

    private (MainWindow Window, StringListView View) Open()
    {
        var view = new StringListView("Names", _file.Path, "Names");
        var window = new MainWindow([view]);
        window.Show();
        Ui.Settle();
        return (window, view);
    }

    /// <summary>The controls of a row. Only rows on screen have any.</summary>
    private static (TextBox Box, Button DeleteButton) Row(StringListView view, int line)
    {
        Ui.Render();
        var parts = view.Lines.ContainerFromIndex(line)!.GetVisualDescendants().ToList();
        return (parts.OfType<TextBox>().First(), parts.OfType<Button>().First());
    }

    private static IEnumerable<string?> VisibleLines(StringListView view) => view.Visible.Select(r => r.Text);

    private static void Click(Button button)
    {
        button.RaiseEvent(new RoutedEventArgs(Button.ClickEvent));
        Ui.Settle();
    }

    private static void Press(Window window, PhysicalKey key) => window.KeyPressQwerty(key, RawInputModifiers.Control);

    [AvaloniaFact]
    public void ShowsEveryEntry()
    {
        var (_, view) = Open();
        Assert.Equal(["Ann", "Bob", "Cy"], VisibleLines(view));
        Assert.Equal("3 entries", view.Summary.Text);
        Assert.False(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void EditingSavesOnlyThatEntry()
    {
        var (window, view) = Open();
        Row(view, 1).Box.Text = "Bobby";

        Press(window, PhysicalKey.S);
        Ui.Settle();

        Assert.False(view.Document.IsDirty);
        Assert.Equal(Text.Replace("'Bob'", "'Bobby'"), _file.Text);
    }

    [AvaloniaFact]
    public void TypingIsOneUndoStep()
    {
        var (window, view) = Open();
        var box = Row(view, 0).Box;
        box.Focus();
        box.CaretIndex = box.Text!.Length;

        window.KeyTextInput("!");
        window.KeyTextInput("?");
        Assert.Equal("Ann!?", box.Text);

        Press(window, PhysicalKey.Z);

        Assert.Equal("Ann", box.Text);
        Assert.False(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void EnterAddsALineBelowAndFocusesIt()
    {
        var (window, view) = Open();
        Row(view, 0).Box.Focus();

        window.KeyPressQwerty(PhysicalKey.Enter, RawInputModifiers.None);
        Ui.Settle();

        Assert.Equal(["Ann", "", "Bob", "Cy"], VisibleLines(view));
        Assert.True(Row(view, 1).Box.IsFocused);

        window.KeyTextInput("Abe");
        Press(window, PhysicalKey.S);
        Ui.Settle();

        Assert.Equal(Text.Replace("'Ann', 'Bob'", "'Ann', 'Abe', 'Bob'"), _file.Text);
    }

    [AvaloniaFact]
    public void AddLineGoesAtTheEnd()
    {
        var (_, view) = Open();
        Click(view.AddButton);

        Assert.Equal(["Ann", "Bob", "Cy", ""], VisibleLines(view));
        Assert.True(Row(view, 3).Box.IsFocused);
    }

    [AvaloniaFact]
    public void AddingToAFilteredListClearsTheFilter()
    {
        var (_, view) = Open();
        view.FilterBox.Text = "Ann";
        Click(view.AddButton);

        Assert.Equal("", view.FilterBox.Text);
        Assert.Equal(4, VisibleLines(view).Count());
    }

    [AvaloniaFact]
    public void ABlankEntryCantBeSaved()
    {
        var (window, view) = Open();
        Click(view.AddButton);

        Press(window, PhysicalKey.S);

        Assert.Contains("Entry 4 is blank", Ui.DialogMessage(window));
        Ui.Answer(window, "OK");
        Assert.Equal(Text, _file.Text);
    }

    [AvaloniaFact]
    public void DeletingThenUndoingBringsItBack()
    {
        var (window, view) = Open();
        Click(Row(view, 1).DeleteButton);
        Assert.Equal(["Ann", "Cy"], VisibleLines(view));

        Press(window, PhysicalKey.Z);

        Assert.Equal(["Ann", "Bob", "Cy"], VisibleLines(view));
        Assert.False(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void DeletingEverythingLeavesAWorkingPanel()
    {
        var (_, view) = Open();
        Click(Row(view, 0).DeleteButton);
        Click(Row(view, 0).DeleteButton);
        Click(Row(view, 0).DeleteButton);
        Assert.Empty(view.Visible);

        Click(view.AddButton);
        Assert.Single(view.Visible);
    }

    [AvaloniaFact]
    public void Filtering()
    {
        var (_, view) = Open();
        view.FilterBox.Text = "bo";
        Assert.Equal(["Bob"], VisibleLines(view));

        view.FilterBox.Text = "";
        Assert.Equal(3, VisibleLines(view).Count());
        Assert.False(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void EditingAFilteredEntryDoesntHideItMidWord()
    {
        var (_, view) = Open();
        view.FilterBox.Text = "Bob";
        Row(view, 0).Box.Text = "Rob";
        Assert.Equal(["Rob"], VisibleLines(view));
    }

    [AvaloniaFact]
    public void DeletingWhileFilteredKeepsTheFilterAccurate()
    {
        var (_, view) = Open();
        view.FilterBox.Text = "y";
        Click(view.AddButton); // clears it
        view.FilterBox.Text = "Bob";
        Click(Row(view, 0).DeleteButton);
        Assert.Empty(VisibleLines(view));
    }

    [AvaloniaFact]
    public void UndoRevealsAnEntryTheFilterIsHiding()
    {
        var (window, view) = Open();
        Row(view, 0).Box.Text = "Anne";
        view.FilterBox.Text = "Cy";

        Press(window, PhysicalKey.Z);

        Assert.Equal("", view.FilterBox.Text);
        Assert.Equal("Ann", Row(view, 0).Box.Text);
    }

    [AvaloniaFact]
    public void SectionsInTheSameFileDontClobberEachOther()
    {
        _file.Text = "<?php\nconst array A = [\n    'a',\n];\nconst array B = [\n    'b',\n];\n";
        var first = new StringListView("A", _file.Path, "A");
        var second = new StringListView("B", _file.Path, "B");
        var window = new MainWindow([first, second]);
        window.Show();
        Ui.Settle();

        Row(first, 0).Box.Text = "a2";
        Press(window, PhysicalKey.S);
        Ui.Settle();

        window.SectionList.SelectedIndex = 1;
        Ui.Settle();
        Row(second, 0).Box.Text = "b2";
        Press(window, PhysicalKey.S);
        Ui.Settle();

        Assert.Empty(window.OwnedWindows);
        Assert.Contains("'a2'", _file.Text);
        Assert.Contains("'b2'", _file.Text);
    }

    [AvaloniaFact]
    public void AddingToALongListScrollsToTheNewLine()
    {
        using var file = new TempPhpFile(File.ReadAllText(Path.Combine(Repo.Root, "api", "src", "Service", "BeehiveService.php")));
        var view = new StringListView("Bee queen names", file.Path, "QueenNames");
        var window = new MainWindow([view]);
        window.Show();
        Ui.Settle();
        Ui.Capture(window, "bee-names");

        Click(view.AddButton);
        Ui.Settle();
        Ui.Render();

        Assert.True(view.Scroller.Offset.Y > 0);
    }
}
