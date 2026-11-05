using Avalonia.Controls;
using Avalonia.Headless;
using Avalonia.Headless.XUnit;
using Avalonia.Input;
using Avalonia.Interactivity;
using PoppySeedPets.Editor.Sections;
using PoppySeedPets.Editor.Sections.GroupedStrings;

namespace PoppySeedPets.Editor.Tests;

/// <summary>Drives the real window, headlessly, against a throwaway file.</summary>
public sealed class MainWindowTests : IDisposable
{
    private const string Text = """
        <?php
        const array Lyrics = [
            // One
            'one a',
            'one b',

            // Two
            'two a'
        ];

        """;

    private const string OtherText = "<?php\nconst array Other = [\n    // Alpha\n    'alpha',\n];\n";

    private readonly TempPhpFile _file = new(Text);
    private readonly TempPhpFile _otherFile = new(OtherText);

    public void Dispose()
    {
        _file.Dispose();
        _otherFile.Dispose();
    }

    private (MainWindow Window, GroupedStringsView View) Open()
    {
        var view = new GroupedStringsView("Lyrics", _file.Path, "Lyrics");
        var other = new GroupedStringsView("Other", _otherFile.Path, "Other");
        var window = new MainWindow([view, other]);
        window.Show();
        Ui.Settle();
        return (window, view);
    }

    private static TextBox LineBox(GroupedStringsView view, int line) =>
        ((DockPanel)view.Lines.Children[line]).Children.OfType<TextBox>().Single();

    private static void Press(Window window, PhysicalKey key) => window.KeyPressQwerty(key, RawInputModifiers.Control);

    private static void Click(Button button)
    {
        button.RaiseEvent(new RoutedEventArgs(Button.ClickEvent));
        Ui.Settle();
    }

    private static void Pick(GroupedStringsView view, int group) => view.Picker.RowList.SelectedIndex = group;

    [AvaloniaFact]
    public void ShowsTheSectionsAndGroups()
    {
        var (window, view) = Open();
        Assert.Equal(2, window.SectionList.ItemCount);
        Assert.Same(view, window.SectionHost.Content);
        Assert.Equal(2, view.Picker.RowList.ItemCount);
        Assert.Equal("One", view.HeadingBox.Text);
        Assert.Equal("one b", LineBox(view, 1).Text);
        Assert.Equal("2 groups, 3 lines", view.Summary.Text);
    }

    [AvaloniaFact]
    public void RealLyricsLookRight()
    {
        using var file = new TempPhpFile(File.ReadAllText(Repo.MusicPhp));
        var window = new MainWindow([new GroupedStringsView("Lyrics", file.Path, "Lyrics")]);
        window.Show();
        var view = (GroupedStringsView)window.SectionHost.Content!;
        Pick(view, 1);
        LineBox(view, 0).Text += " (edited)";
        Ui.Capture(window, "lyrics");
        Assert.EndsWith(" ●", window.Title);
    }

    [AvaloniaFact]
    public void TypingMakesItDirtyAndCtrlZUndoesTheWholeRun()
    {
        var (window, view) = Open();
        var box = LineBox(view, 0);
        box.Focus();
        box.CaretIndex = box.Text!.Length;

        window.KeyTextInput("!");
        window.KeyTextInput("?");
        Assert.Equal("one a!?", box.Text);
        Assert.True(view.Document.IsDirty);
        Assert.EndsWith(" ●", window.Title);

        Press(window, PhysicalKey.Z);

        Assert.Equal("one a", box.Text);
        Assert.False(view.Document.IsDirty);
        Assert.DoesNotContain("●", window.Title);
    }

    [AvaloniaFact]
    public void CtrlSSavesOnlyTheChange()
    {
        var (window, view) = Open();
        LineBox(view, 1).Text = "one B";

        Press(window, PhysicalKey.S);
        Ui.Settle();

        Assert.False(view.Document.IsDirty);
        Assert.DoesNotContain("●", window.Title);
        Assert.Equal(Text.Replace("'one b'", "'one B'"), _file.Text);
    }

    [AvaloniaFact]
    public void AnUnsavableValueSaysWhyAndStaysDirty()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "";

        Press(window, PhysicalKey.S);

        Assert.Contains("blank", Ui.DialogMessage(window));
        Ui.Answer(window, "OK");
        Assert.True(view.Document.IsDirty);
        Assert.Equal(Text, _file.Text);
    }

    [AvaloniaFact]
    public void SavingOverOutsideChangesAsksFirst()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";
        _file.Text = Text.Replace("'two a'", "'theirs'");

        Press(window, PhysicalKey.S);
        Assert.Contains("changed since", Ui.DialogMessage(window));
        Ui.Answer(window, "Cancel");
        Assert.True(view.Document.IsDirty);
        Assert.Contains("theirs", _file.Text);

        Press(window, PhysicalKey.S);
        Ui.Answer(window, "Save anyway");
        Assert.False(view.Document.IsDirty);
        Assert.Contains("mine", _file.Text);
    }

    [AvaloniaFact]
    public void ClosingCleanJustCloses()
    {
        var (window, _) = Open();
        window.Close();
        Ui.Settle();
        Assert.False(window.IsVisible);
    }

    [AvaloniaFact]
    public void ClosingDirtyCanBeCancelled()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";

        window.Close();
        Assert.Contains("Save changes to Lyrics", Ui.DialogMessage(window));
        Ui.Answer(window, "Cancel");

        Assert.True(window.IsVisible);
        Assert.True(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void ClosingDirtyCanSave()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";

        window.Close();
        Ui.Answer(window, "Save");

        Assert.False(window.IsVisible);
        Assert.Contains("mine", _file.Text);
    }

    [AvaloniaFact]
    public void ClosingDirtyCanDiscard()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";

        window.Close();
        Ui.Answer(window, "Don't save");

        Assert.False(window.IsVisible);
        Assert.Equal(Text, _file.Text);
    }

    [AvaloniaFact]
    public void ClosingStaysOpenIfSavingFails()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "";

        window.Close();
        Ui.Answer(window, "Save");
        Ui.Answer(window, "OK");

        Assert.True(window.IsVisible);
    }

    [AvaloniaFact]
    public void SwitchingSectionsWhileCleanJustSwitches()
    {
        var (window, view) = Open();
        window.SectionList.SelectedIndex = 1;
        Ui.Settle();
        Assert.Empty(window.OwnedWindows);
        Assert.NotSame(view, window.SectionHost.Content);
    }

    [AvaloniaFact]
    public void SwitchingSectionsWhileDirtyCanBeCancelled()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";

        window.SectionList.SelectedIndex = 1;
        Assert.Contains("Save changes to Lyrics", Ui.DialogMessage(window));
        Ui.Answer(window, "Cancel");

        Assert.Same(view, window.SectionHost.Content);
        Assert.Equal(0, window.SectionList.SelectedIndex);
        Assert.Equal("mine", LineBox(view, 0).Text);
    }

    [AvaloniaFact]
    public void SwitchingSectionsWhileDirtyCanSave()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";

        window.SectionList.SelectedIndex = 1;
        Ui.Answer(window, "Save");

        Assert.NotSame(view, window.SectionHost.Content);
        Assert.Contains("mine", _file.Text);
    }

    [AvaloniaFact]
    public void SwitchingSectionsWhileDirtyCanDiscard()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";

        window.SectionList.SelectedIndex = 1;
        Ui.Answer(window, "Don't save");

        Assert.NotSame(view, window.SectionHost.Content);
        Assert.Equal(Text, _file.Text);
        Assert.False(view.Document.IsDirty);
        Assert.Equal("one a", LineBox(view, 0).Text);
    }

    [AvaloniaFact]
    public void SwitchingGroupsDoesntDirtyAnything()
    {
        var (_, view) = Open();
        Pick(view, 1);
        Assert.Equal("Two", view.HeadingBox.Text);
        Assert.Equal("two a", LineBox(view, 0).Text);
        Assert.Single(view.Lines.Children);
        Assert.False(view.Document.IsDirty);

        Pick(view, 0);
        Assert.Equal(2, view.Lines.Children.Count);
        Assert.False(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void RenamingAGroupUpdatesItsRowAndKeepsItSelected()
    {
        var (_, view) = Open();
        view.HeadingBox.Text = "Uno";
        Assert.Equal("Uno", Assert.IsType<PickerRow>(view.Picker.RowList.SelectedItem).Label);
    }

    [AvaloniaFact]
    public void AddingAGroupSelectsItAndSavesAtTheEnd()
    {
        var (window, view) = Open();
        Click(view.AddGroupButton);

        Assert.Equal(2, view.Picker.RowList.SelectedIndex);
        Assert.Equal("", view.HeadingBox.Text);

        view.HeadingBox.Text = "Three";
        LineBox(view, 0).Text = "three a";
        Press(window, PhysicalKey.S);
        Ui.Settle();

        Assert.False(view.Document.IsDirty);
        Assert.Equal(Text.Replace("'two a'", "'two a',\n\n    // Three\n    'three a',"), _file.Text);
    }

    [AvaloniaFact]
    public void AddingALineThenUndoingTakesItBackOut()
    {
        var (window, view) = Open();
        Pick(view, 1);
        Click(view.AddLineButton);
        Assert.Equal(2, view.Lines.Children.Count);

        LineBox(view, 1).Text = "two b";
        Press(window, PhysicalKey.Z);
        Assert.Equal("", LineBox(view, 1).Text);
        Press(window, PhysicalKey.Z);
        Assert.Single(view.Lines.Children);
        Assert.False(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void DeletingALine()
    {
        var (_, view) = Open();
        Click(((DockPanel)view.Lines.Children[0]).Children.OfType<Button>().Single());
        Assert.Single(view.Lines.Children);
        Assert.Equal("one b", LineBox(view, 0).Text);
    }

    [AvaloniaFact]
    public void DeletingAGroupThenUndoingShowsItAgain()
    {
        var (window, view) = Open();
        Pick(view, 1);
        Click(view.DeleteGroupButton);
        Assert.Equal(1, view.Picker.RowList.ItemCount);
        Assert.Equal("One", view.HeadingBox.Text);

        Press(window, PhysicalKey.Z);

        Assert.Equal(2, view.Picker.RowList.ItemCount);
        Assert.Equal("Two", view.HeadingBox.Text);
        Assert.Equal(1, view.Picker.RowList.SelectedIndex);
    }

    [AvaloniaFact]
    public void DeletingEveryGroupLeavesAWorkingPanel()
    {
        var (window, view) = Open();
        Click(view.DeleteGroupButton);
        Click(view.DeleteGroupButton);
        Assert.Equal(0, view.Picker.RowList.ItemCount);
        Assert.False(view.AddLineButton.IsEnabled);

        Click(view.AddGroupButton);
        Assert.Equal(1, view.Picker.RowList.ItemCount);
        Assert.True(view.AddLineButton.IsEnabled);
        view.HeadingBox.Text = "Fresh";
        LineBox(view, 0).Text = "start";
        Press(window, PhysicalKey.S);
        Ui.Settle();
        Assert.Contains("// Fresh", _file.Text);
    }

    [AvaloniaFact]
    public void UndoShowsTheGroupThatChanged()
    {
        var (window, view) = Open();
        LineBox(view, 0).Text = "mine";
        Pick(view, 1);

        Press(window, PhysicalKey.Z);

        Assert.Equal("One", view.HeadingBox.Text);
        Assert.Equal("one a", LineBox(view, 0).Text);
    }

    [AvaloniaFact]
    public void FilteringMatchesHeadingsAndText()
    {
        var (_, view) = Open();
        view.Picker.FilterBox.Text = "TWO A";
        Assert.Equal("Two", Assert.IsType<PickerRow>(Assert.Single(view.Picker.RowList.Items)).Label);

        view.Picker.FilterBox.Text = "one";
        Assert.Equal("One", Assert.IsType<PickerRow>(Assert.Single(view.Picker.RowList.Items)).Label);

        view.Picker.FilterBox.Text = "";
        Assert.Equal(2, view.Picker.RowList.ItemCount);
        Assert.False(view.Document.IsDirty);
    }

    [AvaloniaFact]
    public void EditingAFilteredGroupDoesntHideItMidWord()
    {
        var (_, view) = Open();
        view.Picker.FilterBox.Text = "Two";
        Pick(view, 0);
        view.HeadingBox.Text = "Tw";
        Assert.Equal("Tw", Assert.IsType<PickerRow>(Assert.Single(view.Picker.RowList.Items)).Label);
    }

    [AvaloniaFact]
    public void AddingAGroupClearsAFilterThatWouldHideIt()
    {
        var (_, view) = Open();
        view.Picker.FilterBox.Text = "Two";
        Click(view.AddGroupButton);
        Assert.Equal("", view.Picker.FilterBox.Text);
        Assert.Equal(3, view.Picker.RowList.ItemCount);
        Assert.Equal(2, view.Picker.RowList.SelectedIndex);
    }

    [AvaloniaFact]
    public void ABrokenFileShowsWhyInsteadOfTheEditor()
    {
        _file.Text = "<?php\nconst array Lyrics = [\n    'no heading',\n];\n";
        var (window, view) = Open();

        Assert.True(view.LoadError.IsVisible);
        Assert.Contains("Line 3", view.LoadError.Text);
        Assert.False(view.Editor.IsVisible);

        Press(window, PhysicalKey.S);
        Press(window, PhysicalKey.Z);
        Assert.Empty(window.OwnedWindows);

        _file.Text = Text;
        view.Document.Refresh();
        Assert.False(view.LoadError.IsVisible);
        Assert.True(view.Editor.IsVisible);
        Assert.Equal("One", view.HeadingBox.Text);
    }
}
