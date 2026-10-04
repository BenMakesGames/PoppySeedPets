# Local development with Docker (recommended)

This is the supported path for contributors. You only need [Docker Desktop](https://www.docker.com/products/docker-desktop/) on your machine — Docker provides PHP 8.4, Node 20, Composer, MySQL 8.0, and Redis 7 in containers.

## What you need to drop in by hand

Only one thing isn't bundled with the repo:

* `proprietary-assets/` at the repo root — pet images, fonts, icons. The app builds without it, but images will be missing.

(Game data — items, recipes, NPCs — lives in `db/seed/*.sql` and is auto-imported into MySQL the first time the `db` container starts. If you want to add additional seed files, drop them in `db/seed/` and `docker compose down -v && docker compose up` to re-seed.)

## Running

```
docker compose up
```

* API: `https://localhost:8000` (TLS via the checked-in `dev.pem` / `dev.key`)
* Web app: `https://localhost:4200`
* MySQL: `localhost:3306` (root / `b0ar!!` / `poppyseedpets`) — published for GUI clients
* Redis: internal only

## Port conflicts

If you already have something on port 3306 (a native MySQL/MariaDB install) or 4200 (another dev server), copy `.env.example` to `.env` at the repo root and override the relevant port. The `.env` file is gitignored, so each dev manages their own. The API port (8000) is currently not overridable — see the comment in `.env.example`.

First boot installs Composer and npm packages (a few minutes). Subsequent boots take ~15 s. The crunz scheduler (one tick per minute — `app:increase-time`, park events, etc.) runs inside the `api` container as a background loop; you'll see its output in `docker compose logs api`.

## What happens on every `docker compose up`

The `api` container's entrypoint:

1. `composer install` (no-op when dependencies haven't changed)
2. `php bin/console doctrine:migrations:migrate` (runs any new migrations since the last `git pull`)
3. Starts php-fpm, nginx, and the crunz loop

If you change `composer.json` or `package.json`, see the next section.

## Adding or updating dependencies

Both containers use lockfile-strict installs on boot — `npm ci` for the webapp, `composer install` for the api. Neither will silently mutate `package-lock.json` or `composer.lock`. To change dependencies intentionally, run the package-manager commands **inside the relevant container**. This guarantees the resulting lockfile is generated against the exact same Node/npm/PHP/Composer versions every other dev (and CI, and prod) uses — no more "works on my machine" lockfile churn.

### Angular (webapp)

```
# Add a runtime dependency
docker compose exec webapp npm install <package>

# Add a dev-only dependency
docker compose exec webapp npm install --save-dev <package>

# Update one package to a specific version
docker compose exec webapp npm install <package>@<version>

# Remove a dependency
docker compose exec webapp npm uninstall <package>
```

Then `docker compose restart webapp` and commit `webapp/package.json` + `webapp/package-lock.json` together.

### Symfony (api)

```
# Add a runtime dependency
docker compose exec api composer require <package>

# Add a dev-only dependency
docker compose exec api composer require --dev <package>

# Update one package
docker compose exec api composer update <package>

# Remove a dependency
docker compose exec api composer remove <package>
```

Then `docker compose restart api` and commit `api/composer.json` + `api/composer.lock` together.

### Don't run these natively

If you run `npm install` or `composer require` on your host machine, the lockfile will be generated against whatever Node/PHP/npm/Composer versions you happen to have, which probably differs from the container. That causes spurious lockfile diffs that look like "real" changes in PRs. Always go through the container.

## Resetting / wiping

* Restart with the same data: `docker compose restart`
* Wipe DB and re-seed from `db/seed/`: `docker compose down -v` then `docker compose up`

## Resetting accounts for local login

```sql
/* change all user email addresses to <id>@poppyseedpets.com */
UPDATE user SET email=CONCAT(id, '@poppyseedpets.com') WHERE email NOT LIKE '%@poppyseedpets.com';
```

---

# Native install (advanced / production)

The Docker setup above is what we support for local dev. The instructions below are what you'd use for a real production install, or if you want to run things directly on your host (in which case: you're on your own for keeping the various pieces in sync).

# API (server)

## Install & Configure

### PHP (Linux instructions)

Install the following linux packages:
* php8.3
* php8.3-bcmath
* php8.3-cli
* php8.3-common
* php8.3-devel
* php8.3-fpm
* php8.3-gd
* php8.3-gmp
* php8.3-intl
* php8.3-mbstring
* php8.3-mysqlnd
* php8.3-opcache
* php8.3-pdo
* php8.3-process
* php8.3-sodium
* php8.3-xml
* php8.3-zip
* php-pear
* composer
* redis6-devel
* lz4-devel

With pecl, install:
* igbinary
* msgpack
* zstd
* lzf
* redis, and answer "yes" to all questions about igbinary, msgpack, zstd, and lzf

Make sure to enable these various modules in a PHP ini file; examples (from https://github.com/amazonlinux/amazon-linux-2023/issues/328):
* `/etc/php.d/30-igbinary.ini` with `extension=igbinary.so`
    * Exact path may vary depending on your system
* 30-msgpack for `msgpack.so`
* 40-zstd for `zstd.so`
* 40-lzf for `lzf.so`
* 41-redis for `redis.so`

### Apache

Create a `.conf` file for Poppy Seed Pets, for example `/etc/httpd/conf.d/poppyseedpets.conf` (again, exact paths may vary depending on your system):

```apache
DocumentRoot "/var/www/html/PoppySeedPetsAPI/public"

<Directory "/var/www">
    AllowOverride None
    Require all granted
</Directory>

<Directory "/var/www/html/PoppySeedPetsAPI/public">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Poppy Seed Pets, itself

1. review `.env`; create `.env.local` containing overrides as needed
2. run `composer install`
3. run `php bin/console doctrine:migrations:migrate`
    * there are no fixtures; you'll need to get recipe, item, NPC data, etc, from somewhere...
4. add to crontab:<br>`* * * * * cd /PATH_TO_POPPY_SEED_PETS && vendor/bin/crunz schedule:run`

## Local Dev

### Running

1. run `symfony server:start` in root of this project
2. run `ng serve` in root of web app project
3. optionally, start local redis service

### Resetting Accounts for Local Login

```sql
/* change all user email addresses to <id>@poppyseedpets.com */
UPDATE user SET email=CONCAT(id, '@poppyseedpets.com') WHERE email NOT LIKE '%@poppyseedpets.com';
```

## Other Config Considerations

### Brotli

After the brotli mod is installed and enabled, use these rules to compress API responses (`application/json`):

```
<IfModule mod_brotli.c>
    AddOutputFilterByType BROTLI_COMPRESS application/json

    BrotliCompressionQuality 6
    BrotliCompressionWindow 19
    BrotliCompressionMaxInputBlock 18

    Header append Vary Accept-Encoding env=!dont-vary
</IfModule>
```

The largest API calls happen when the player views their house (pets & items). I was seeing requests that rarely even get to 200KB, but I'm sure some whacky user has many hundreds of items and maybe gets to 1MB.

* BrotliCompressionWindow of 19 = 512KB
* BrotliCompressionMaxInputBlock of 18 = 256KB

We could probably go even lower, but these values are already smaller (less RAM-using) than typical settings that "balance for performance", so I'm sure it's fine :P

The `Header append Vary Accept-Encoding` setting is because the API sits behind the AWS load balancer - a proxy - and we need to tell that proxy that the `Accept-Encoding` header is important information for us, and to please pass it along.

# Web App (front end)

## Install & Configure

### Prerequisites

Install the following:
* Node.js (v20+)
* npm (comes with Node.js)
* Angular CLI: `npm install -g @angular/cli`

### Proprietary Assets

The build expects a `proprietary-assets/` directory at the repo root. This contains images and other assets not included in the repo (gitignored). The app will build without it, but assets will be missing.

### SSL Certificates

`ng serve` is configured to run over HTTPS (required for secure cookies locally). It expects the following files in the repo root (one level above `webapp/`):
* `dev.key` — private key
* `dev.pem` — certificate

These are already checked into the repo and expire in **December 2033**. If they expire or you need to regenerate them:
```
openssl req -x509 -nodes -new -sha512 -days 3650 -newkey rsa:4096 -keyout dev.key -out dev.pem -subj "/C=US/CN=MY-CA"
```

### Poppy Seed Pets, itself

1. run `npm install`
2. review `src/environments/environment.ts`; the dev config points the API at `https://localhost:8000` by default

## Local Dev

### Running

1. run `ng serve` in the root of the `webapp/` directory
2. the app will be available at https://localhost:4200
3. make sure the API is also running (see API section above)

