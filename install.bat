@echo off
REM ============================================================
REM install.bat — Installs dependencies for both apps.
REM   Runs `composer install` in api/, `dotnet restore` in
REM   api-c-sharp/, and `npm install` in webapp/.
REM ============================================================

where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP is not installed or not in PATH.
    exit /b 1
)

where dotnet >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: .NET SDK is not installed or not in PATH.
    exit /b 1
)

where npm >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: npm is not installed or not in PATH.
    exit /b 1
)

echo Installing PoppySeedPets dependencies...

echo.
echo === API (composer install) ===
cd /d %~dp0api && call composer install

echo.
echo === C# API (dotnet restore) ===
cd /d %~dp0api-c-sharp && call dotnet restore PoppySeedPets.slnx

echo.
echo === C# API (dotnet build) ===
cd /d %~dp0api-c-sharp && call dotnet build PoppySeedPets.slnx --no-restore

echo.
echo === SPA (npm install) ===
cd /d %~dp0webapp && call npm install

echo.
echo === Tests (npm install) ===
cd /d %~dp0tests && call npm install

echo.
echo Done!
pause
