using Avalonia.Controls;

namespace PoppySeedPets.Editor.Sections;

public static class TextBoxExtensions
{
    /// <summary>Use instead of TextChanged, which is posted, so it lags the box: long enough for a save to miss what was just typed.</summary>
    public static void OnTextSet(this TextBox box, Action handler) =>
        box.PropertyChanged += (_, e) =>
        {
            if (e.Property == TextBox.TextProperty) handler();
        };
}
