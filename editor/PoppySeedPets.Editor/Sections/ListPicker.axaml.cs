using System.Collections.ObjectModel;
using System.ComponentModel;
using Avalonia.Controls;

namespace PoppySeedPets.Editor.Sections;

/// <param name="Label">What the list shows.</param>
/// <param name="SearchText">What the filter looks through.</param>
public sealed record PickerItem(string Label, string SearchText);

/// <summary>A row in the list. Mutable so that editing an item doesn't replace its row, which would drop the selection.</summary>
public sealed class PickerRow(int index, string label) : INotifyPropertyChanged
{
    public int Index { get; } = index;

    public string Label
    {
        get => label;
        set
        {
            if (label == value) return;
            label = value;
            PropertyChanged?.Invoke(this, new PropertyChangedEventArgs(nameof(Label)));
        }
    }

    public event PropertyChangedEventHandler? PropertyChanged;
}

/// <summary>
/// A filterable list of a document's items, for picking which one to edit.
/// </summary>
/// <remarks>
/// The selection is held here, not by the list, as the filter can hide the selected item, and it follows whatever last
/// changed, so that undoing shows what was undone.
/// </remarks>
public sealed partial class ListPicker : UserControl
{
    private readonly ObservableCollection<PickerRow> _rows = [];
    private IReadOnlyList<PickerItem> _items = [];
    private int _rowsBuiltFor = -1;

    public ListPicker()
    {
        InitializeComponent();

        RowList.ItemsSource = _rows;
        FilterBox.OnTextSet(BuildRows);
        RowList.SelectionChanged += (_, _) =>
        {
            if (RowList.SelectedItem is not PickerRow row || row.Index == Selected) return;
            Selected = row.Index;
            Picked?.Invoke();
        };
    }

    /// <summary>The selected item's index in the document. Can be past the end, if there are no items.</summary>
    public int Selected { get; private set; }

    /// <summary>Raised when the user picks an item; not when <see cref="Show"/> moves the selection.</summary>
    public event Action? Picked;

    /// <summary>Shows the document's current items. Call after every change.</summary>
    public void Show(IReadOnlyList<PickerItem> items)
    {
        if (ListDifference.First(_items, items) is { } changed) Selected = Math.Max(0, Math.Min(changed, items.Count - 1));
        _items = items;

        // Edits leave the rows alone, and don't re-filter, or an item could vanish from the list mid-word.
        if (items.Count != _rowsBuiltFor || _rows.All(r => r.Index != Selected))
        {
            if (FilterBox.Text is { Length: > 0 }) FilterBox.Text = "";
            BuildRows();
        }
        else
        {
            foreach (var row in _rows) row.Label = items[row.Index].Label;
            SelectRow();
        }
    }

    private void BuildRows()
    {
        var filter = FilterBox.Text ?? "";
        _rows.Clear();
        foreach (var (index, item) in _items.Index())
            if (item.SearchText.Contains(filter, StringComparison.OrdinalIgnoreCase))
                _rows.Add(new PickerRow(index, item.Label));

        _rowsBuiltFor = _items.Count;
        SelectRow();
    }

    private void SelectRow() => RowList.SelectedItem = _rows.FirstOrDefault(r => r.Index == Selected);
}
