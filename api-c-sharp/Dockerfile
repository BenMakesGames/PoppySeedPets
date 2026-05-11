# syntax=docker/dockerfile:1.7

ARG DOTNET_VERSION=10.0

FROM mcr.microsoft.com/dotnet/sdk:${DOTNET_VERSION} AS build
WORKDIR /src

COPY PoppySeedPets.Api/PoppySeedPets.Api.csproj PoppySeedPets.Api/
COPY PoppySeedPets.Http/PoppySeedPets.Http.csproj PoppySeedPets.Http/
COPY PoppySeedPets.Features.GlobalStats/PoppySeedPets.Features.GlobalStats.csproj PoppySeedPets.Features.GlobalStats/
RUN dotnet restore PoppySeedPets.Api/PoppySeedPets.Api.csproj

COPY . .
RUN dotnet publish PoppySeedPets.Api/PoppySeedPets.Api.csproj \
    -c Release \
    -o /app/publish \
    --no-restore \
    /p:UseAppHost=false

FROM mcr.microsoft.com/dotnet/aspnet:${DOTNET_VERSION} AS runtime
WORKDIR /app

ENV ASPNETCORE_URLS=http://+:8080 \
    ASPNETCORE_ENVIRONMENT=Production \
    DOTNET_RUNNING_IN_CONTAINER=true \
    DOTNET_NOLOGO=true

EXPOSE 8080

RUN groupadd --system --gid 1000 app \
 && useradd  --system --uid 1000 --gid app app
USER app:app

COPY --from=build --chown=app:app /app/publish .

ENTRYPOINT ["dotnet", "PoppySeedPets.Api.dll"]
