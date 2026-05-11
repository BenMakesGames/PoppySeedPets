# Lightsail Container Deploy

Deploys the C# API to a single AWS Lightsail container service. Flat monthly price,
one AWS resource, deletes cleanly.

## Prerequisites

Install on the deploy machine (once):

- **AWS CLI v2** — <https://aws.amazon.com/cli/>
- **Lightsail Control plugin** (for `push-container-image`):
  - `aws configure add-plugin lightsailctl` — see <https://lightsail.aws.amazon.com/ls/docs/en_us/articles/amazon-lightsail-install-software>
- **Docker Desktop** — must be running before you deploy
- **AWS credentials** with Lightsail permissions: `aws configure`

## One-time setup

```powershell
./deploy/create-service.ps1
```

Defaults: service name `psp-api`, region `us-east-1`, power `micro` ($10/mo), scale `1`.

Wait until state is `READY` (~5-10 min):

```powershell
aws lightsail get-container-services --service-name psp-api --region us-east-1 --query 'containerServices[0].state'
```

## Environment variables

Lightsail stores env vars per-deployment, so changing any var means a new deployment.
There is no separate "env vars" page in the API. The deploy script handles this:

- It looks for `deploy/env.production.json` (gitignored).
- If present: every key/value pair becomes an env var in the container.
- If absent: it reuses the env vars from the previous deployment.

To set up: copy the template and fill in real values.

```powershell
Copy-Item ./deploy/env.production.example.json ./deploy/env.production.json
# then edit env.production.json with real secrets
```

Adding a new env var later (e.g., a new Redis endpoint): add the key to
`env.production.json` and rerun `./deploy/deploy.ps1`. ASP.NET reads nested config
via the `__` convention — e.g. `Aws__Ses__FromAddress` maps to `Aws:Ses:FromAddress`
in `IConfiguration`.

### Why no IAM role?

Lightsail container services do NOT support IAM task roles (unlike ECS/Lambda).
For SES / S3 / any AWS SDK calls, create a dedicated IAM user with minimum
permissions, generate an access key, and put it in `env.production.json` as
`Aws__AccessKeyId` / `Aws__SecretAccessKey`. Rotate periodically.

### Future: Secrets Manager

When the secret count grows or rotation matters, switch to AWS Secrets Manager
or SSM Parameter Store. Pattern: deploy script fetches secrets at deploy time,
injects them into the container env. Optionally, the app fetches at startup
instead (needs IAM access keys baked into env to talk to Secrets Manager —
circular, so deploy-time injection is usually simpler).

## Deploy a new version

```powershell
./deploy/deploy.ps1
```

What it does:

1. Load env vars from `deploy/env.production.json` (or reuse from prior deploy)
2. `docker build` from `api-c-sharp/` using the `Dockerfile`
3. `aws lightsail push-container-image` uploads to the service's private registry
4. `aws lightsail create-container-service-deployment` rolls out the new image

Rollout is zero-downtime: Lightsail keeps the old version up until the new one
passes health checks on `/`.

## Check status

```powershell
aws lightsail get-container-services --service-name psp-api --region us-east-1 `
  --query 'containerServices[0].{state:state,url:url,deployment:currentDeployment.state}'
```

Public URL is in the `url` field. Wire DNS (Route 53 / etc.) to it.

## Logs

```powershell
aws lightsail get-container-log --service-name psp-api --container-name api --region us-east-1
```

## Update connection string later

```powershell
./deploy/deploy.ps1 -ConnectionString "Server=...new..."
```

## Tear everything down

```powershell
aws lightsail delete-container-service --service-name psp-api --region us-east-1
```

That single command removes the service, all deployments, all stored images, and
all logs. No orphaned resources. No surprise bills.

## Cost

Flat per month based on power and scale:

| Power  | Per node /mo |
|--------|--------------|
| nano   | $7           |
| micro  | $10          |
| small  | $20          |
| medium | $40          |
| large  | $80          |
| xlarge | $160         |

Bill = power-price × scale. No data-transfer charges up to generous limit
(500 GB/mo on micro). No per-request charges. No NAT, no LB, no surprises.

## Notes

- Container listens on port 8080 (set in `Dockerfile` via `ASPNETCORE_URLS`)
- Image runs as non-root user `app` (uid 1000)
- Health check hits `/` — change in `deploy.ps1` if you add a `/health` endpoint
- Database (RDS) and Redis (ElastiCache) live outside this service. Make sure
  their security groups allow inbound from the Lightsail container service's
  outbound IPs (Lightsail exposes these in the service detail page).
