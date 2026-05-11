@echo off
REM ============================================================
REM run.bat — Starts all dev servers in separate windows.
REM   API (Symfony) at https://localhost:8000
REM   C# API        at https://localhost:7059
REM   SPA (Angular) at https://localhost:4200
REM ============================================================
echo Starting PoppySeedPets API and SPA...

REM Start the Symfony API server in a new window
start "PoppySeedPets API" cmd /k "cd /d %~dp0api && symfony serve"

REM Start the C# API server in a new window
start "PoppySeedPets C# API" cmd /k "cd /d %~dp0api-c-sharp\PoppySeedPets.Api && dotnet run --launch-profile https"

REM Start the Angular SPA dev server in a new window
start "PoppySeedPets SPA" cmd /k "cd /d %~dp0webapp && ng serve"

echo All servers are starting in separate windows.
echo   API:     https://localhost:8000
echo   C# API:  https://localhost:7059
echo   SPA:     https://localhost:4200
