using System.Security.Claims;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using PoppySeedPets.Api.Infrastructure.Data;
using PoppySeedPets.Api.Infrastructure.Http;
using PoppySeedPets.Api.Infrastructure.ReferenceData;

namespace PoppySeedPets.Api.Features.Inventory;

/// <summary>
/// GET /inventory — the first vertical slice. Demonstrates the reference-data pattern end to end:
/// authenticated, load the player's rows carrying bare item FK ids (no join), then stitch the item
/// definitions from the in-memory store. One endpoint per class; request/response DTOs live here.
/// </summary>
[ApiController]
[Route("inventory")]
[Authorize]
public sealed class GetInventoryController : ControllerBase
{
    private readonly PspDbContext _db;
    private readonly IItemDefinitions _items;

    public GetInventoryController(PspDbContext db, IItemDefinitions items)
    {
        _db = db;
        _items = items;
    }

    public sealed record InventoryLineResponse(int Id, int ItemId, string ItemName, bool IsFood, int Location);

    [HttpGet]
    public async Task<ActionResult<ApiResult<IReadOnlyList<InventoryLineResponse>>>> Handle(CancellationToken ct)
    {
        var userId = int.Parse(User.FindFirstValue(ClaimTypes.NameIdentifier)!);

        // Player rows only — project the bare FK id, no join to the item definition (D2).
        var rows = await _db.Inventory
            .AsNoTracking()
            .Where(i => i.OwnerId == userId)
            .Select(i => new { i.Id, i.ItemId, i.Location })
            .ToListAsync(ct);

        // Stitch definitions from the in-memory store: O(1) each, zero DB/network.
        var lines = rows
            .Select(r =>
            {
                var definition = _items[r.ItemId];
                return new InventoryLineResponse(r.Id, r.ItemId, definition.Name, definition.IsFood, r.Location);
            })
            .ToList();

        return Ok(ApiResult<IReadOnlyList<InventoryLineResponse>>.Ok(lines));
    }
}
