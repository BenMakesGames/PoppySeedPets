namespace PoppySeedPets.Editor.Php;

/// <summary>The PHP isn't something the editor can safely read or rewrite. The message is shown to the user.</summary>
public sealed class PhpSourceException(string message) : Exception(message);
