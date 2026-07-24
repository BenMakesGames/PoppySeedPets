using PoppySeedPets.Api.Infrastructure.ReferenceData;

namespace PoppySeedPets.Api.Tests;

public class ItemDefinitionStoreTests
{
    private static ItemDefinitionStore StoreWith(params ItemDefinition[] items)
    {
        var store = new ItemDefinitionStore();
        store.Load(items);
        return store;
    }

    [Fact]
    public void Indexer_returns_the_definition()
    {
        var store = StoreWith(new ItemDefinition(1, "Apple", "apple.png", IsFood: true));

        Assert.Equal("Apple", store[1].Name);
        Assert.True(store[1].IsFood);
    }

    [Fact]
    public void Indexer_throws_a_helpful_error_for_an_unknown_id()
    {
        var store = StoreWith(new ItemDefinition(1, "Apple", null, true));

        var ex = Assert.Throws<KeyNotFoundException>(() => store[999]);
        Assert.Contains("999", ex.Message);
    }

    [Fact]
    public void TryGet_reports_presence()
    {
        var store = StoreWith(new ItemDefinition(1, "Apple", null, true));

        Assert.True(store.TryGet(1, out var found));
        Assert.Equal(1, found.Id);
        Assert.False(store.TryGet(2, out _));
    }

    [Fact]
    public void IdsWhere_filters_in_memory()
    {
        var store = StoreWith(
            new ItemDefinition(1, "Apple", null, IsFood: true),
            new ItemDefinition(2, "Sword", null, IsFood: false),
            new ItemDefinition(3, "Bread", null, IsFood: true));

        var foodIds = store.IdsWhere(i => i.IsFood);

        Assert.Equal([1, 3], foodIds.OrderBy(x => x));
    }

    [Fact]
    public void Load_replaces_contents_and_reports_count()
    {
        var store = StoreWith(new ItemDefinition(1, "Apple", null, true));
        Assert.Equal(1, store.Count);

        store.Load([new ItemDefinition(5, "Pear", null, true), new ItemDefinition(6, "Axe", null, false)]);

        Assert.Equal(2, store.Count);
        Assert.False(store.TryGet(1, out _));
        Assert.Equal("Pear", store[5].Name);
    }
}
