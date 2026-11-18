using Avalonia.Controls;
using Avalonia.Layout;
using Avalonia.Media;

namespace PoppySeedPets.Editor;

/// <summary>A modal question with a button per answer.</summary>
public static class ChoiceDialog
{
    /// <returns>The chosen button's text, or null if the dialog was closed some other way.</returns>
    public static Task<string?> Show(Window owner, string title, string message, params string[] buttons)
    {
        var dialog = new Window
        {
            Title = title,
            SizeToContent = SizeToContent.WidthAndHeight,
            CanResize = false,
            WindowStartupLocation = WindowStartupLocation.CenterOwner,
        };

        var row = new StackPanel { Orientation = Orientation.Horizontal, Spacing = 8, HorizontalAlignment = HorizontalAlignment.Right };
        foreach (var label in buttons)
        {
            var button = new Button { Content = label };
            button.Click += (_, _) => dialog.Close(label);
            row.Children.Add(button);
        }

        dialog.Content = new StackPanel
        {
            Margin = new(16),
            Spacing = 16,
            MaxWidth = 420,
            Children = { new TextBlock { Text = message, TextWrapping = TextWrapping.Wrap }, row },
        };

        return dialog.ShowDialog<string?>(owner);
    }
}
