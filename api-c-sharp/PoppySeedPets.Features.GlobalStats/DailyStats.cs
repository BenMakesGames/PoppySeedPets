using System.ComponentModel.DataAnnotations;
using System.ComponentModel.DataAnnotations.Schema;

namespace PoppySeedPets.Features.GlobalStats;

[Table("daily_stats")]
public sealed class DailyStats
{
    [Key, Column("id")] public int Id { get; set; }
    [Column("date")] public DateTime Date { get; set; }

    [Column("number_of_players1_day")] public int NumberOfPlayers1Day { get; set; }
    [Column("number_of_players3_day")] public int NumberOfPlayers3Day { get; set; }
    [Column("number_of_players7_day")] public int NumberOfPlayers7Day { get; set; }
    [Column("number_of_players28_day")] public int NumberOfPlayers28Day { get; set; }
    [Column("number_of_players_lifetime")] public int NumberOfPlayersLifetime { get; set; }

    [Column("total_moneys1_day")] public int TotalMoneys1Day { get; set; }
    [Column("total_moneys3_day")] public int TotalMoneys3Day { get; set; }
    [Column("total_moneys7_day")] public int TotalMoneys7Day { get; set; }
    [Column("total_moneys28_day")] public int TotalMoneys28Day { get; set; }
    [Column("total_moneys_lifetime")] public int TotalMoneysLifetime { get; set; }

    [Column("new_players1_day")] public int NewPlayers1Day { get; set; }
    [Column("new_players3_day")] public int NewPlayers3Day { get; set; }
    [Column("new_players7_day")] public int NewPlayers7Day { get; set; }
    [Column("new_players28_day")] public int NewPlayers28Day { get; set; }

    [Column("unlocked_trader1_day")] public int? UnlockedTrader1Day { get; set; }
    [Column("unlocked_trader3_day")] public int? UnlockedTrader3Day { get; set; }
    [Column("unlocked_trader7_day")] public int? UnlockedTrader7Day { get; set; }
    [Column("unlocked_trader28_day")] public int? UnlockedTrader28Day { get; set; }
    [Column("unlocked_trader_lifetime")] public int? UnlockedTraderLifetime { get; set; }

    [Column("unlocked_fireplace1_day")] public int? UnlockedFireplace1Day { get; set; }
    [Column("unlocked_fireplace3_day")] public int? UnlockedFireplace3Day { get; set; }
    [Column("unlocked_fireplace7_day")] public int? UnlockedFireplace7Day { get; set; }
    [Column("unlocked_fireplace28_day")] public int? UnlockedFireplace28Day { get; set; }
    [Column("unlocked_fireplace_lifetime")] public int? UnlockedFireplaceLifetime { get; set; }

    [Column("unlocked_greenhouse1_day")] public int? UnlockedGreenhouse1Day { get; set; }
    [Column("unlocked_greenhouse3_day")] public int? UnlockedGreenhouse3Day { get; set; }
    [Column("unlocked_greenhouse7_day")] public int? UnlockedGreenhouse7Day { get; set; }
    [Column("unlocked_greenhouse28_day")] public int? UnlockedGreenhouse28Day { get; set; }
    [Column("unlocked_greenhouse_lifetime")] public int? UnlockedGreenhouseLifetime { get; set; }

    [Column("unlocked_beehive1_day")] public int? UnlockedBeehive1Day { get; set; }
    [Column("unlocked_beehive3_day")] public int? UnlockedBeehive3Day { get; set; }
    [Column("unlocked_beehive7_day")] public int? UnlockedBeehive7Day { get; set; }
    [Column("unlocked_beehive28_day")] public int? UnlockedBeehive28Day { get; set; }
    [Column("unlocked_beehive_lifetime")] public int? UnlockedBeehiveLifetime { get; set; }

    [Column("unlocked_portal1_day")] public int? UnlockedPortal1Day { get; set; }
    [Column("unlocked_portal3_day")] public int? UnlockedPortal3Day { get; set; }
    [Column("unlocked_portal7_day")] public int? UnlockedPortal7Day { get; set; }
    [Column("unlocked_portal28_day")] public int? UnlockedPortal28Day { get; set; }
    [Column("unlocked_portal_lifetime")] public int? UnlockedPortalLifetime { get; set; }
}
