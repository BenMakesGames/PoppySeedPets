using Microsoft.AspNetCore.Authentication;
using Microsoft.EntityFrameworkCore;
using PoppySeedPets.Api.Infrastructure.Auth;
using PoppySeedPets.Api.Infrastructure.Data;
using PoppySeedPets.Api.Infrastructure.ReferenceData;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddControllers();

// EF Core + Pomelo MySQL. Fixed server version (no connect-at-startup); the connection string is
// supplied via config in real environments and may be absent when just building/booting locally.
var connectionString = builder.Configuration.GetConnectionString("Psp");
builder.Services.AddDbContext<PspDbContext>(options =>
{
    if (!string.IsNullOrWhiteSpace(connectionString))
        options.UseMySql(connectionString, new MySqlServerVersion(new Version(8, 0, 36)));
});

builder.Services.AddSingleton(TimeProvider.System);

// Reference data (Option D): one immutable in-memory store, loaded once at startup.
builder.Services.AddSingleton<ItemDefinitionStore>();
builder.Services.AddSingleton<IItemDefinitions>(sp => sp.GetRequiredService<ItemDefinitionStore>());

// Auth: the testable validator + the thin custom scheme handler.
builder.Services.AddScoped<ISessionValidator, SessionValidator>();
builder.Services
    .AddAuthentication(SessionAuthenticationHandler.SchemeName)
    .AddScheme<AuthenticationSchemeOptions, SessionAuthenticationHandler>(SessionAuthenticationHandler.SchemeName, null);
builder.Services.AddAuthorization();

var app = builder.Build();

// Load the version-locked reference data into memory before we start serving.
using (var scope = app.Services.CreateScope())
{
    var logger = app.Services.GetRequiredService<ILoggerFactory>().CreateLogger("ReferenceData");
    if (string.IsNullOrWhiteSpace(connectionString))
    {
        logger.LogWarning("No 'Psp' connection string configured — reference store is empty; DB-backed endpoints will not work.");
    }
    else
    {
        var db = scope.ServiceProvider.GetRequiredService<PspDbContext>();
        var store = app.Services.GetRequiredService<ItemDefinitionStore>();
        await store.LoadFromDatabaseAsync(db);
        logger.LogInformation("Loaded {Count} item definitions into memory.", store.Count);
    }
}

app.UseAuthentication();
app.UseAuthorization();
app.MapControllers();

app.Run();
