<#
.SYNOPSIS
  Build the API Docker image and deploy it to Lightsail container service.

.DESCRIPTION
  Steps:
    1. Load env vars from deploy/env.production.json (gitignored).
       If the file doesn't exist, reuse env vars from the previous deployment.
    2. docker build (multi-stage, produces runtime image)
    3. aws lightsail push-container-image (uploads image to the service's private registry)
    4. aws lightsail create-container-service-deployment (rolls out new version)

  Run this every time you want to deploy a new version. Env var changes also require
  redeploy (Lightsail has no separate "update env vars" call).

.PARAMETER ServiceName
  Lightsail container service name. Must already exist (run create-service.ps1 first).

.PARAMETER Region
  AWS region the service lives in.

.PARAMETER EnvFile
  Path to a JSON file containing { "KEY": "value", ... } pairs. Each pair becomes
  an env var inside the container. Defaults to deploy/env.production.json.

.PARAMETER Tag
  Optional override for the local Docker image tag. Defaults to git short SHA, or
  a UTC timestamp if not in a git repo.

.EXAMPLE
  ./deploy.ps1
  ./deploy.ps1 -EnvFile ./deploy/env.staging.json
#>
[CmdletBinding()]
param(
    [string]$ServiceName = 'psp-api',
    [string]$Region      = 'us-east-1',
    [string]$EnvFile     = '',
    [string]$Tag         = ''
)

$ErrorActionPreference = 'Stop'

$ScriptDir  = $PSScriptRoot
$ProjectDir = Split-Path -Parent $ScriptDir   # api-c-sharp/
if (-not $EnvFile) { $EnvFile = Join-Path $ScriptDir 'env.production.json' }

# ---- derive tag --------------------------------------------------------------
if (-not $Tag) {
    try {
        $Tag = (git -C $ProjectDir rev-parse --short HEAD 2>$null).Trim()
    } catch { }
    if (-not $Tag) { $Tag = (Get-Date -Format 'yyyyMMddHHmmss') }
}
$LocalImage = "psp-api:$Tag"
$Label      = 'psp-api'

# ---- load env vars -----------------------------------------------------------
$envVars = [ordered]@{
    'ASPNETCORE_ENVIRONMENT' = 'Production'
}

if (Test-Path $EnvFile) {
    Write-Host "==> Loading env vars from $EnvFile" -ForegroundColor Cyan
    $fromFile = Get-Content $EnvFile -Raw | ConvertFrom-Json
    foreach ($prop in $fromFile.PSObject.Properties) {
        $envVars[$prop.Name] = [string]$prop.Value
    }
} else {
    Write-Host "==> No $EnvFile found — reusing env vars from previous deployment" -ForegroundColor Cyan
    $prev = aws lightsail get-container-service-deployments `
        --service-name $ServiceName `
        --region $Region `
        --output json | ConvertFrom-Json
    $prevContainer = $prev.deployments | Select-Object -First 1 |
        ForEach-Object { $_.containers.api }
    if (-not $prevContainer) {
        throw "No previous deployment found and no $EnvFile to seed from."
    }
    foreach ($prop in $prevContainer.environment.PSObject.Properties) {
        $envVars[$prop.Name] = [string]$prop.Value
    }
}

Write-Host "    env keys: $($envVars.Keys -join ', ')"

# ---- build -------------------------------------------------------------------
Write-Host "==> Building $LocalImage" -ForegroundColor Cyan
docker build -t $LocalImage $ProjectDir
if ($LASTEXITCODE -ne 0) { throw "docker build failed" }

# ---- push to Lightsail -------------------------------------------------------
Write-Host "==> Pushing to Lightsail service '$ServiceName'" -ForegroundColor Cyan
$pushJson = aws lightsail push-container-image `
    --service-name $ServiceName `
    --label $Label `
    --image $LocalImage `
    --region $Region `
    --output json
if ($LASTEXITCODE -ne 0) { throw "push-container-image failed" }

$pushed = $pushJson | ConvertFrom-Json
$RemoteImage = $pushed.image
if (-not $RemoteImage) { throw "no image reference returned from push" }
Write-Host "    pushed as: $RemoteImage"

# ---- create deployment -------------------------------------------------------
$containers = @{
    api = @{
        image       = $RemoteImage
        ports       = @{ '8080' = 'HTTP' }
        environment = $envVars
    }
} | ConvertTo-Json -Depth 6 -Compress

$endpoint = @{
    containerName = 'api'
    containerPort = 8080
    healthCheck   = @{
        path               = '/'
        intervalSeconds    = 30
        timeoutSeconds     = 5
        healthyThreshold   = 2
        unhealthyThreshold = 3
        successCodes       = '200-499'
    }
} | ConvertTo-Json -Depth 6 -Compress

Write-Host "==> Creating deployment" -ForegroundColor Cyan
aws lightsail create-container-service-deployment `
    --service-name $ServiceName `
    --region $Region `
    --containers $containers `
    --public-endpoint $endpoint
if ($LASTEXITCODE -ne 0) { throw "create-container-service-deployment failed" }

Write-Host ""
Write-Host "Deployment started." -ForegroundColor Green
Write-Host "Status:"
Write-Host "  aws lightsail get-container-services --service-name $ServiceName --region $Region --query 'containerServices[0].{state:state,url:url,deployment:currentDeployment.state}'"
