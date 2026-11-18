using Avalonia.Controls;
using Avalonia.Layout;
using Avalonia.Media;

namespace PoppySeedPets.Editor.Sections;

/// <summary>A string being edited, with a button to delete it.</summary>
public sealed class LineRow : DockPanel
{
    public TextBox Box { get; } = new() { TextWrapping = TextWrapping.Wrap };

    public Button DeleteButton { get; } = new() { Content = "✕", VerticalAlignment = VerticalAlignment.Top, Margin = new(6, 0, 0, 0) };

    public LineRow()
    {
        ToolTip.SetTip(DeleteButton, "Delete line");
        SetDock(DeleteButton, Dock.Right);
        Children.Add(DeleteButton);
        Children.Add(Box);
    }
}
