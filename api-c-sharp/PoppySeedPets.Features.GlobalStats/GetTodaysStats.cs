using Microsoft.AspNetCore.Mvc;
using Microsoft.EntityFrameworkCore;
using PoppySeedPets.Http;

namespace PoppySeedPets.Features.GlobalStats;

// Mirrors api/src/Controller/GlobalStats/TodaysStatsController.php:
//   GET /globalStats/today  →  last 30 daily_stats rows, ordered by id desc.

[ApiController]
public class GetTodaysStats(GlobalStatsDbContext db)
{
    [HttpGet("globalStats/today")]
    public async Task<ApiResponse<IReadOnlyList<Stats>>> Handle(CancellationToken ct)
    {
        var rows = await db.DailyStats
            .AsNoTracking()
            .OrderByDescending(s => s.Id)
            .Take(30)
            .Select(s => new Stats(
                s.Date,
                s.NumberOfPlayers1Day, s.NumberOfPlayers3Day, s.NumberOfPlayers7Day, s.NumberOfPlayers28Day, s.NumberOfPlayersLifetime,
                s.TotalMoneys1Day, s.TotalMoneys3Day, s.TotalMoneys7Day, s.TotalMoneys28Day, s.TotalMoneysLifetime,
                s.NewPlayers1Day, s.NewPlayers3Day, s.NewPlayers7Day, s.NewPlayers28Day,
                s.UnlockedTrader1Day, s.UnlockedTrader3Day, s.UnlockedTrader7Day, s.UnlockedTrader28Day, s.UnlockedTraderLifetime,
                s.UnlockedFireplace1Day, s.UnlockedFireplace3Day, s.UnlockedFireplace7Day, s.UnlockedFireplace28Day, s.UnlockedFireplaceLifetime,
                s.UnlockedGreenhouse1Day, s.UnlockedGreenhouse3Day, s.UnlockedGreenhouse7Day, s.UnlockedGreenhouse28Day, s.UnlockedGreenhouseLifetime,
                s.UnlockedBeehive1Day, s.UnlockedBeehive3Day, s.UnlockedBeehive7Day, s.UnlockedBeehive28Day, s.UnlockedBeehiveLifetime,
                s.UnlockedPortal1Day, s.UnlockedPortal3Day, s.UnlockedPortal7Day, s.UnlockedPortal28Day, s.UnlockedPortalLifetime))
            .ToListAsync(ct);

        return new ApiResponse<IReadOnlyList<Stats>>(rows);
    }

    public sealed record Stats(
        DateTime Date,
        int NumberOfPlayers1Day, int NumberOfPlayers3Day, int NumberOfPlayers7Day, int NumberOfPlayers28Day, int NumberOfPlayersLifetime,
        int TotalMoneys1Day, int TotalMoneys3Day, int TotalMoneys7Day, int TotalMoneys28Day, int TotalMoneysLifetime,
        int NewPlayers1Day, int NewPlayers3Day, int NewPlayers7Day, int NewPlayers28Day,
        int? UnlockedTrader1Day, int? UnlockedTrader3Day, int? UnlockedTrader7Day, int? UnlockedTrader28Day, int? UnlockedTraderLifetime,
        int? UnlockedFireplace1Day, int? UnlockedFireplace3Day, int? UnlockedFireplace7Day, int? UnlockedFireplace28Day, int? UnlockedFireplaceLifetime,
        int? UnlockedGreenhouse1Day, int? UnlockedGreenhouse3Day, int? UnlockedGreenhouse7Day, int? UnlockedGreenhouse28Day, int? UnlockedGreenhouseLifetime,
        int? UnlockedBeehive1Day, int? UnlockedBeehive3Day, int? UnlockedBeehive7Day, int? UnlockedBeehive28Day, int? UnlockedBeehiveLifetime,
        int? UnlockedPortal1Day, int? UnlockedPortal3Day, int? UnlockedPortal7Day, int? UnlockedPortal28Day, int? UnlockedPortalLifetime);
}
