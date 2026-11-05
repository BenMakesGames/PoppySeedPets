using System.ComponentModel;

namespace PoppySeedPets.Editor.Sections.GroupedStrings;

/// <summary>A group in the list. Mutable so that renaming a group doesn't replace its row, which would drop the selection.</summary>
public sealed class GroupRow(int index, string heading) : INotifyPropertyChanged
{
    public int Index { get; } = index;

    public string Heading
    {
        get => heading;
        set
        {
            if (heading == value) return;
            heading = value;
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(nameof(Heading)));
        }
    }

    public event PropertyChangedEventHandler? PropertyChanged;
}
