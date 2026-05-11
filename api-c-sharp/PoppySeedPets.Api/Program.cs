using PoppySeedPets.Features.GlobalStats;
using PoppySeedPets.Http;

const string CorsPolicyName = "PspCors";

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddExceptionHandler<PspExceptionHandler>();
builder.Services.AddProblemDetails();

var allowedOrigins = builder.Configuration
    .GetSection("Cors:AllowedOrigins")
    .Get<string[]>() ?? [];

builder.Services.AddCors(options =>
{
    options.AddPolicy(CorsPolicyName, policy =>
    {
        policy.WithOrigins(allowedOrigins)
            .AllowAnyHeader()
            .AllowAnyMethod()
            .AllowCredentials();
    });
});

builder.Services.AddControllers()
    .AddGlobalStatsFeature(builder.Configuration);

var app = builder.Build();

app.UseExceptionHandler();
app.UseCors(CorsPolicyName);
app.MapControllers();

app.Run();
