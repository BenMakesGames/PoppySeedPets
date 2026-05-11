<#
.SYNOPSIS
  One-time setup: create the Lightsail container service that will host the API.

.DESCRIPTION
  Creates a Lightsail container service. Idempotent — if a service with the same name
  already exists in the region, this script reports it and exits 0 without changing it.

  The container service is the *only* persistent AWS resource this stack creates.
  To tear everything down later: aws lightsail delete-container-service --service-name <name>

.PARAMETER ServiceName
  Lightsail container service name. Lowercase, hyphens allowed. Default: psp-api

.PARAMETER Region
  AWS region. Default: us-east-1

.PARAMETER Power
  Capacity tier. nano=$7, micro=$10, small=$20, medium=$40, large=$80, xlarge=$160 /mo.
  Default: micro

.PARAMETER Scale
  Number of container nodes (each costs Power price). Default: 1

.EXAMPLE
  ./create-service.ps1
  ./create-service.ps1 -ServiceName psp-api -Region us-east-1 -Power small -Scale 1
#>
[CmdletBinding()]
param(
    [string]$ServiceName = 'psp-api',
    [string]$Region      = 'us-east-1',
    [ValidateSet('nano','micro','small','medium','large','xlarge')]
    [string]$Power       = 'micro',
    [ValidateRange(1,20)]
    [int]$Scale          = 1
)

$ErrorActionPreference = 'Stop'

Write-Host "Checking for existing Lightsail container service '$ServiceName' in $Region..."
$existing = aws lightsail get-container-services `
    --service-name $ServiceName `
    --region $Region 2>$null

if ($LASTEXITCODE -eq 0) {
    Write-Host "Service '$ServiceName' already exists. No changes made." -ForegroundColor Yellow
    Write-Host $existing
    exit 0
}

Write-Host "Creating Lightsail container service '$ServiceName' (power=$Power, scale=$Scale)..."
aws lightsail create-container-service `
    --service-name $ServiceName `
    --power $Power `
    --scale $Scale `
    --region $Region

if ($LASTEXITCODE -ne 0) {
    Write-Error "create-container-service failed."
    exit 1
}

Write-Host ""
Write-Host "Service creation started. State goes PENDING -> READY in ~5-10 minutes." -ForegroundColor Green
Write-Host "Poll status with:"
Write-Host "  aws lightsail get-container-services --service-name $ServiceName --region $Region --query 'containerServices[0].state'"
Write-Host ""
Write-Host "Once READY, run ./deploy.ps1 to push your first image." -ForegroundColor Green
