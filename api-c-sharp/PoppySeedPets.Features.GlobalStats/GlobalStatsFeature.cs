using Microsoft.EntityFrameworkCore;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;

namespace PoppySeedPets.Features.GlobalStats;

public static class GlobalStatsFeature
{
    public static IMvcBuilder AddGlobalStatsFeature(this IMvcBuilder mvc, IConfiguration config)
    {
        var connectionString = config.GetConnectionString("PoppySeedPets")
            ?? throw new InvalidOperationException("Missing ConnectionStrings:PoppySeedPets");

        mvc.Services.AddDbContext<GlobalStatsDbContext>(opt =>
            opt.UseMySql(connectionString, ServerVersion.AutoDetect(connectionString)));

        return mvc.AddApplicationPart(typeof(GlobalStatsFeature).Assembly);
    }
}
