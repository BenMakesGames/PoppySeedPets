@echo off

docker --version >nul 2>&1
if errorlevel 1 (
    echo.
    echo Docker doesn't appear to be installed, or isn't on your PATH.
    echo Get Docker Desktop here: https://www.docker.com/products/docker-desktop/
    echo.
    pause
    exit /b 1
)

docker compose up --build
