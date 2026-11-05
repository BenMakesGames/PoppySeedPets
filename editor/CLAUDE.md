# Editor

Avalonia desktop app for editing Poppy Seed Pets' game data in place, in the repo's own files.

- Run: `dotnet run --project editor/PoppySeedPets.Editor` (optionally followed by a repo path; defaults to this repo).
- Test: `dotnet test editor/PoppySeedPets.Editor.slnx`. Set `EDITOR_FRAMES=DIR` to save screenshots.

## Adding a section

A section is an `ISection`: one line in `Program.CreateSections`. More of a const that's already understood? Just the
line (`GroupedStringsView` or `StringListView`: a title, file and const name). A new shape of PHP data? Add an `IPhpCodec<T>` in `Php/`
and a view in `Sections/`, using `ListPicker` for a filterable list, and hold the data in a `PhpDocument<T>`, which does undo, dirty tracking, saving and noticing
outside changes.

## Rules that keep saving safe

- **PHP is spliced, never regenerated.** A codec's `Write` must return its input unchanged when nothing changed, and
  touch only the lines that did: stray whitespace and missing trailing commas aren't ours to tidy.
- **Refuse what isn't understood.** Reading throws `PhpSourceException` on anything unexpected, and the panel shows why.
  Skipping it instead would silently delete it on the next save. Extend the parser if a refusal gets in the way.
- **Sync controls from the document, idempotently.** See `GroupedStringsView`. Use `OnTextSet`, not `TextBox.TextChanged`: that's
  posted, so the document lags the box and a save can miss what was just typed.
- `MainWindow` doesn't know what a section edits. Only the open section can be dirty: leaving one settles it first.
