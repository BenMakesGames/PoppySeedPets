using Avalonia.Controls;
using PoppySeedPets.Editor.Documents;

namespace PoppySeedPets.Editor;

/// <summary>
/// One thing the editor edits: an entry in the left column, and the panel it opens on the right. Add one to
/// <see cref="Program.CreateSections"/>.
/// </summary>
public interface ISection
{
    string Title { get; }

    Control View { get; }

    ISectionDocument Document { get; }
}
