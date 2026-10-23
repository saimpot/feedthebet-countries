# FeedTheBet: Countries and Flags

A small full-stack app for the FTB PHP Full Stack Developer task. A **Laravel 12** backend fetches country names and flags from [restcountries.com](https://restcountries.com) and exposes them as a JSON API. A **Vue 3** single-page app, available only to logged-in users, shows them as a searchable grid. Login is handled by **Auth0** (regular web application, server-side session). The country source sits behind an interface, so it can be swapped by changing one environment variable, and responses are cached in Redis with stale-while-revalidate. Everything runs in Docker.


## Contents

[Tech stack](#tech-stack) · [Architecture](#architecture) · [Quick start](#quick-start) · [Auth0 setup](#auth0-setup) · [Country data providers](#country-data-providers) · [Caching](#caching) · [API reference](#api-reference) · [Testing and quality](#testing-and-quality) · [Project structure](#project-structure) · [Design patterns](#design-patterns) · [Environment variables](#environment-variables) · [Troubleshooting](#troubleshooting) · [Production](#production)

## Tech stack

| Layer | Technology |
|---|---|
| Backend | PHP 8.3 (php-fpm), Laravel 12, `auth0/login` 7.22 (Auth0 PHP SDK), Pest 4, Laravel Pint, Larastan |
| Frontend | Vue 3.5 (`<script setup>`, TypeScript 6), Vite 8, Tailwind CSS 4, Vitest 4, ESLint 10 + oxlint, vue-tsc |
| Infrastructure | Docker Compose, nginx 1.27, Redis 7 (cache and sessions), Node 22 for the dev container |
| Data | restcountries.com v5 API, with a bundled static dataset as fallback. No SQL database. |

## Architecture

```
                      http://localhost:8080
 Browser ───────────────────────────────────────►  nginx (one origin: no CORS, cookies just work)
                                                     │
                      /  (everything else)           │        /api/*  /login  /callback  /logout
              ┌──────────────────────────────────────┴─────────────────────────────────┐
              ▼                                                                        ▼
   dev:  Vite dev server (node :5173, HMR)                              php-fpm :9000  (Laravel 12)
   prod: static build served by nginx                                       │            │
                                                                            ▼            ▼
                                                                    Redis (sessions   restcountries.com v5
                                                                     db 0, cache db 1)  (or bundled static data)
                                                                            ▲
                                           scheduler container: php artisan schedule:work (countries:warm daily)

   Browser ◄──► Auth0 (login page), redirected to and from by Laravel
```

**Request and auth flow** (Auth0 regular web application, session cookie, no tokens in the browser):

1. The Vue app boots and calls `GET /api/me`.
2. A guest gets `401 {"message":"Unauthenticated."}`. The frontend HTTP client then navigates to `/login`.
3. `/login` (Auth0 SDK controller) redirects to the Auth0 Universal Login page.
4. After login, Auth0 redirects to `/callback`. Laravel stores the session in Redis and redirects to `/`.
5. The app loads again, `/api/me` returns the user, and `/api/countries` returns the grid data.
6. `/logout` clears the session and the Auth0 session, then returns to `/`.

All `/api/*` routes except `/api/health` go through `throttle:60,1` first and then `auth` (guard `auth0-session`), so anonymous requests are rate-limited too. If `/api/me` fails for any reason other than 401, the app shows an error with a Retry button.

## Quick start

**Prerequisites:** Docker Desktop (Compose v2) and `make`. On Windows use **Git Bash** to run `make`; the raw `docker compose` equivalents are given below. You also need an Auth0 account for login (see [Auth0 setup](#auth0-setup)); there is no local PHP or Node requirement.

```bash
git clone <this repository> feedthebet
cd feedthebet

make install     # builds images, creates .env files, composer install, app key, npm install, starts the stack
```

`make install` copies `.env.example` to `.env` (root: just `APP_PORT`) and `backend/.env.example` to `backend/.env` if they do not exist yet. Then edit `backend/.env` with your Auth0 values, and open <http://localhost:8080>.

```bash
make up          # start (after the first install)
make down        # stop
make logs        # tail logs
```

<details>
<summary>Without make (raw docker compose equivalents)</summary>

```bash
cp .env.example .env
cp backend/.env.example backend/.env
docker compose build
docker compose run --rm --user app php composer install --no-interaction
docker compose run --rm --user app php php artisan key:generate --ansi   # only once; make install skips it when APP_KEY is set
docker compose run --rm node npm install
docker compose up -d

docker compose down                                                   # make down
docker compose exec -T --user app php php artisan countries:warm      # make warm
```

</details>

Services (see [docker-compose.yml](docker-compose.yml)): `nginx` (published on `APP_PORT`, default 8080), `php` (php-fpm), `scheduler` (`php artisan schedule:work`, runs as the `app` user like php-fpm), `redis`, `node` (Vite dev server with HMR). Source code is bind-mounted, so edits show up immediately.

Run `make help` to list all targets.

## Auth0 setup

1. In the [Auth0 dashboard](https://manage.auth0.com), go to **Applications → Applications → Create Application**.
2. Choose **Regular Web Applications** (not Single Page Application) and create it.
3. Open the application's **Settings** tab and set:
   - **Allowed Callback URLs:** `http://localhost:8080/callback`
   - **Allowed Logout URLs:** `http://localhost:8080`
4. Save changes. Copy **Domain**, **Client ID** and **Client Secret** from the same tab.
5. Go to **Applications → APIs → Create API**. Give it a name (for example "FeedTheBet API") and an identifier such as `https://feedthebet-api`, and keep the **RS256** signing algorithm. The identifier is only a label and is never called.
   This step is required. Without an audience, Auth0 returns an encrypted opaque access token. The Laravel SDK decodes the access token as a JWT, so the callback then fails with *"The JWT string must contain two dots"*.
   Then authorize the application to use this API for user logins. Open the API's **Application Access** tab (or the application's **APIs** tab) and allow **User Access** for your Regular Web Application. Otherwise the callback fails with *"Client … is not authorized to access resource server"*.
6. Put the values into `backend/.env`:

   ```dotenv
   AUTH0_DOMAIN=your-tenant.eu.auth0.com
   AUTH0_CLIENT_ID=...
   AUTH0_CLIENT_SECRET=...
   AUTH0_AUDIENCE=https://feedthebet-api   # the API identifier from step 5
   # optional: dedicated secret for encrypting the SDK session payload (defaults to APP_KEY)
   AUTH0_COOKIE_SECRET=   # e.g. output of: openssl rand -hex 32
   ```

   `APP_URL` must stay `http://localhost:8080` (or match the port you use): the callback URL is derived from it.
7. Clear the config cache so the container picks the values up:

   ```bash
   docker compose exec --user app php php artisan config:clear
   ```

8. Make sure a user exists (Auth0 dashboard → User Management → Users, or sign up on the login page if your tenant allows it), open <http://localhost:8080>, and log in.

The Auth0 routes (`/login`, `/callback`, `/logout`) are declared explicitly in [backend/routes/web.php](backend/routes/web.php) using the SDK controllers.

## Country data providers

The task points at `https://restcountries.com/v3.1/all`. **That API version is deprecated**: the URL now answers with a deprecation notice instead of data. This app therefore uses **v5** (`GET https://api.restcountries.com/countries/v5`), which:

- requires an API key (`Authorization: Bearer <key>`); a free key (1000 requests/month) is available at <https://restcountries.com/sign-up>;
- is paginated (the free plan allows `limit` up to 100), which `HttpCountryProvider` handles through its `nextPageQuery()` hook;
- returns rows with `names.common`, `codes.alpha_2` and `flag.url_svg|url_png|description`.

**Which provider is used** ([backend/config/countries.php](backend/config/countries.php)):

1. `COUNTRIES_DRIVER`, if set (`restcountries` or `static`). Leave it unset rather than empty.
2. Otherwise `restcountries` when `COUNTRIES_RESTCOUNTRIES_API_KEY` is set.
3. Otherwise `static`: a bundled dataset of 252 countries in [backend/resources/data/countries.json](backend/resources/data/countries.json) (flag images from flagcdn). The app therefore works out of the box without any key.

To use live data, set `COUNTRIES_RESTCOUNTRIES_API_KEY=...` in `backend/.env`, then `php artisan config:clear` and `make warm`. The live v5 mapping is covered by tests against a fixture ([backend/tests/Fixtures/restcountries_v5_list.json](backend/tests/Fixtures/restcountries_v5_list.json)); the public demo key (`rc_live_demo`) only returns one country, so a real key is needed to see the full list.

If the live provider fails and nothing is cached, `/api/countries` responds with `503`. There is no automatic runtime failover to the static dataset; the static driver is chosen by configuration.

### Adding a new provider in 3 steps


**1. Implement the interface**, or, for an HTTP API, extend `HttpCountryProvider` and fill in the provider-specific steps (it already handles requests, retries, error translation, sorting and events):

```php
namespace App\Countries\Providers;

final class AcmeCountriesProvider extends HttpCountryProvider
{
    public function name(): string { return 'acme'; }

    protected function endpoint(): string { return 'v1/countries'; }

    protected function headers(): array
    {
        return ['X-Api-Key' => (string) ($this->config['api_key'] ?? '')];   // optional hook
    }

    protected function map(array $payload): Collection
    {
        return collect($payload['items'])->map(fn (array $row) => new Country(
            $row['iso2'], $row['title'], $row['flag_svg'], $row['flag_alt'] ?? null,
        ));
    }
}
```

**2. Add a factory method** in [`CountryProviderManager`](backend/app/Countries/CountryProviderManager.php) (the method name is `create` + studly-cased driver name + `Driver`):

```php
protected function createAcmeDriver(): CountryProvider
{
    return new AcmeCountriesProvider(
        $this->container->make(Factory::class),
        $this->container->make(Dispatcher::class),
        $this->config->get('countries.drivers.acme'),
    );
}
```

**3. Add a config block** in `config/countries.php` under `drivers` (`'acme' => ['base_url' => ..., 'api_key' => env('COUNTRIES_ACME_API_KEY'), 'timeout' => 5]`) and select it with `COUNTRIES_DRIVER=acme`.

Caching, events, the controller and the frontend need no changes. Add a test next to [CountryProviderManagerTest](backend/tests/Feature/Countries/CountryProviderManagerTest.php).

## Caching

Country data barely changes, so it is cached in Redis by the [`CachedCountryProvider`](backend/app/Countries/Providers/CachedCountryProvider.php) decorator using Laravel's `Cache::flexible()` (stale-while-revalidate):

| Setting | Value | Meaning |
|---|---|---|
| `fresh_ttl` | 86400 s (1 day) | served directly, no upstream call |
| `stale_ttl` | 604800 s (7 days) | after the fresh window, stale data is served immediately and refreshed after the response is sent |
| key | `countries:v1:{driver}` | e.g. `countries:v1:static`; bump `v1` in `config/countries.php` when the payload shape changes. Laravel adds a companion key `illuminate:cache:flexible:created:<key>` with the creation timestamp. |

Rows are stored as plain arrays, not serialized objects, so a class change cannot break the cache. Configure with `COUNTRIES_CACHE_ENABLED` (default `true`) and `COUNTRIES_CACHE_STORE` (default: the default cache store, Redis).

The cache lives in **Redis database 1**. Inspect it with:

```bash
docker compose exec redis redis-cli -n 1 --scan --pattern '*countries*'
```

**Warming.** `php artisan countries:warm` fetches from the configured provider first and only then replaces the cached copy, so a failing provider leaves the previous copy untouched. When caching is disabled it does nothing and says so.

```bash
make warm
docker compose exec --user app php php artisan countries:warm
```

When caching is enabled, the command is scheduled **daily** in [backend/routes/console.php](backend/routes/console.php) and run by the `scheduler` container (`php artisan schedule:work`).

**Resilience.** The HTTP provider retries only connection errors and 5xx responses; a 401, 403 or 429 is not retried, so a bad key doesn't burn the request quota. Pagination stops at `countries.drivers.restcountries.max_pages` (default 10) and fails loudly rather than caching a truncated list.

## API reference

All responses are JSON. Errors from `/api/*` are always JSON, never redirects.

### `GET /api/health`

No authentication. Liveness check.

```json
{ "status": "ok" }
```

### `GET /api/me`

Authenticated user (profile fields from the Auth0 session).

`200`

```json
{ "data": { "name": "Jane Doe", "email": "jane@example.com", "picture": "https://s.gravatar.com/avatar/..." } }
```

`401` when not logged in:

```json
{ "message": "Unauthenticated." }
```

### `GET /api/countries`

Authenticated. All countries, sorted by name ignoring case and accents (so "Åland Islands" follows "Afghanistan"; this uses `ext-intl`'s `Collator`).

`200`

```json
{
  "data": [
    { "code": "AF", "name": "Afghanistan", "flagUrl": "https://flagcdn.com/af.svg", "flagAlt": "Flag of Afghanistan" },
    { "code": "AL", "name": "Albania", "flagUrl": "https://flagcdn.com/al.svg", "flagAlt": "Flag of Albania" }
  ]
}
```

`flagAlt` may be `null`. Status codes:

| Status | When | Body |
|---|---|---|
| 200 | OK | `{"data": [...]}` |
| 401 | no valid session | `{"message":"Unauthenticated."}` |
| 429 | more than 60 requests per minute | Laravel's throttle response |
| 503 | the provider failed and no cached copy exists | `{"message":"Country data is temporarily unavailable."}` |

The Auth0 routes `/login`, `/callback` and `/logout` are browser redirects, not JSON endpoints.

## Testing and quality

```bash
make test            # backend (Pest) + frontend (Vitest)
make backend-test    # docker compose run --rm --user app php ./vendor/bin/pest
make frontend-test   # docker compose run --rm node npm test
make lint            # Pint + Larastan (backend), oxlint + ESLint + vue-tsc (frontend)
```

Or run the backend tools individually inside the php container:

```bash
docker compose run --rm --user app php composer lint      # Pint --test (run ./vendor/bin/pint to fix)
docker compose run --rm --user app php composer analyse   # Larastan (config: backend/phpstan.neon, level 8)
```

CI ([.github/workflows/ci.yml](.github/workflows/ci.yml)) runs on pushes to `main` and on pull requests: Pint, Larastan and Pest for the backend; ESLint/oxlint, type-check, Vitest and a production build for the frontend; and a build of the production Docker images.

**What is covered**

- Backend (Pest, no network): the REST Countries adapter against a v5 fixture (mapping, pagination and its page cap, malformed rows, retry policy, failures), the static provider, accent-insensitive sorting, the cache decorator (fresh hits, stale-while-revalidate with deferred refresh, refresh and forget), the events and listeners, the manager resolving each driver, the controller JSON shape, the `countries:warm` command and its schedule, authentication behaviour (JSON 401 for guests, `/api/me`, middleware order), the 503 mapping and `/api/health`.
- Frontend (Vitest): the HTTP client (once-only 401 redirect, error messages, network errors), the API facades, `useAuth`, `useCountries` (state transitions, overlapping loads, accent-insensitive search), every component, and the `App` boot sequence (including the error and retry path).

## Project structure

```
.
├── docker-compose.yml          dev stack: nginx, php, scheduler, redis, node
├── docker-compose.prod.yml     production stack (self-contained images)
├── Makefile                    make help
├── docker/
│   ├── php/Dockerfile          dev php-fpm image (pool user "app", uid 1000)
│   ├── node/Dockerfile         dev node image (npm 11)
│   ├── nginx/                  default.conf (dev, proxies to Vite), prod.conf, laravel.conf (shared
│   │                           Laravel location), forwarded-maps.conf (X-Forwarded-* pass-through)
│   └── prod/                   production php/nginx Dockerfiles and entrypoint
├── .github/workflows/ci.yml    CI pipeline
├── backend/                    Laravel 12 (no database)
│   ├── app/Countries/          self-contained domain module
│   │   ├── Contracts/          CountryProvider (Strategy), RefreshableCountryProvider
│   │   ├── Data/Country.php    readonly DTO
│   │   ├── Providers/          HttpCountryProvider (Template Method), RestCountriesProvider (Adapter),
│   │   │                       StaticCountryProvider, CachedCountryProvider (Decorator)
│   │   ├── CountryProviderManager.php   Factory Method
│   │   ├── CountryNameSorter.php        accent-insensitive sort shared by all providers
│   │   ├── Events/, Listeners/ Observer
│   │   └── Exceptions/         CountryProviderException
│   ├── app/Providers/CountryServiceProvider.php   wiring: manager, decorator, listeners
│   ├── app/Http/               invokable controllers (Api/CountryController, Api/MeController), API resources
│   ├── app/Console/Commands/WarmCountriesCache.php
│   ├── config/countries.php    driver, per-driver settings, cache TTLs
│   ├── config/auth0.php        Auth0 SDK configuration
│   ├── resources/data/countries.json   bundled fallback dataset
│   ├── routes/web.php          Auth0 routes and the /api group
│   ├── routes/console.php      daily countries:warm schedule
│   ├── bootstrap/app.php       JSON 401 for the API, CountryProviderException → 503, trusted proxy
│   └── tests/                  Pest (Unit and Feature) and fixtures
├── frontend/                   Vue 3 + TypeScript + Vite
│   └── src/
│       ├── api/                httpClient (401 policy), countriesApi, authApi (Facade), routes
│       ├── composables/        useAuth, useCountries (state union and search)
│       ├── components/         AppHeader, SearchInput, CardGrid, CountryGrid, CountryCard, LoadingGrid,
│       │                       EmptyState, ErrorState
│       ├── types/              API and domain types
│       └── App.vue
```

## Design patterns


| Pattern | Where | Purpose |
|---|---|---|

## Environment variables

Backend variables go in `backend/.env` (template: [backend/.env.example](backend/.env.example)); `APP_PORT` goes in the root `.env`.

| Variable | Default | Purpose |
|---|---|---|
| `APP_PORT` (root `.env`) | `8080` | host port nginx is published on |
| `APP_URL` | `http://localhost:8080` | public URL; the Auth0 callback URL is derived from it |
| `APP_KEY` | generated by `make install` | Laravel encryption key (also the default Auth0 cookie secret) |
| `AUTH0_DOMAIN` | placeholder | Auth0 tenant domain |
| `AUTH0_CLIENT_ID` | placeholder | Auth0 application client ID |
| `AUTH0_CLIENT_SECRET` | placeholder | Auth0 application client secret |
| `AUTH0_AUDIENCE` | `https://feedthebet-api` | Identifier of the Auth0 API (required so the access token is a JWT) |
| `AUTH0_COOKIE_SECRET` | empty (uses `APP_KEY`) | encrypts the Auth0 SDK session payload |
| `COUNTRIES_DRIVER` | unset (auto) | `restcountries` or `static`; leave unset to choose by API key |
| `COUNTRIES_RESTCOUNTRIES_API_KEY` | empty | restcountries v5 bearer key; enables the live provider |
| `COUNTRIES_RESTCOUNTRIES_URL` | `https://api.restcountries.com` | upstream base URL |
| `COUNTRIES_CACHE_ENABLED` | `true` | toggle the caching decorator |
| `COUNTRIES_CACHE_STORE` | default store | cache store used for countries |
| `SESSION_DRIVER` / `CACHE_STORE` | `redis` | session and cache backends |
| `SESSION_SAME_SITE` | `lax` | must stay `lax` so the cookie survives the redirect back from Auth0 |
| `SESSION_SECURE_COOKIE` | `false` | set `true` when served over HTTPS |
| `REDIS_HOST` | `redis` | Redis host (the compose service name) |

## Troubleshooting

- **Windows: `make` not found.** Run it from Git Bash, or use the raw `docker compose` commands in [Quick start](#quick-start).
- **Windows: file changes not picked up / slow HMR.** Bind mounts on Windows do not deliver file events, so Vite is configured with `watch.usePolling` and `hmr.clientPort: 8080`. Keep the repository on the Windows drive that Docker shares, not inside WSL paths mixed with Windows paths.
- **Windows: `\r` / "bad interpreter" errors in scripts.** `.gitattributes` forces LF line endings. If a checkout was made with `core.autocrlf=true` before that file existed, re-clone or run `git add --renormalize .`.
- **IDE shows missing types in `frontend/`.** `frontend/node_modules` is a named Docker volume, so it is empty on the host. Run `npm install` in `frontend/` on the host as well if you want editor support (Node 22+).
- **Changed `.env` but nothing happens.** Run `docker compose exec --user app php php artisan config:clear`. Settings are read from the config cache if one exists.
- **Login loops or "Invalid state"/callback errors.** Check that the Auth0 Allowed Callback URL is exactly `http://localhost:8080/callback`, that `APP_URL` matches the URL in the browser, and that `SESSION_SAME_SITE=lax`. Clear cookies for `localhost`.
- **"The JWT string must contain two dots" on `/callback`.** `AUTH0_AUDIENCE` is missing, so Auth0 returned an encrypted opaque access token. Create an API in Auth0 (Applications → APIs), set its identifier as `AUTH0_AUDIENCE`, then run `config:clear` and log in again.
- **"Client … is not authorized to access resource server" on `/callback`.** The application is not allowed to request tokens for the API. In Auth0, open Applications → APIs → your API → Application Access and authorize your application for user access, then log in again.
- **Port 8080 is busy.** Set `APP_PORT=8081` in the root `.env`, `make up`, and update `APP_URL` and the Auth0 callback/logout URLs to the new port.
- **`/api/countries` returns 503.** The live provider failed and nothing is cached: check `COUNTRIES_RESTCOUNTRIES_API_KEY` and the logs (`make logs`; the failure is logged as "Country provider failed."), or unset the key / set `COUNTRIES_DRIVER=static`.
- **npm crashes with `Cannot read properties of null (reading 'edgesOut')`.** That is npm 10 on this dependency tree; the project's node image installs npm 11, so use `make` / the compose `node` service rather than a local Node's npm 10.

## Production

[docker-compose.prod.yml](docker-compose.prod.yml) builds self-contained images (no bind mounts): `docker/prod/php.Dockerfile` (php-fpm with `--no-dev`, optimized autoloader and opcache) and `docker/prod/nginx.Dockerfile` (builds the Vue app and serves the static files, proxying `/api`, `/login`, `/callback` and `/logout` to php-fpm). The PHP entrypoint caches config, routes and events and makes a best-effort `countries:warm` on start. Application code is root-owned in the image; only `storage` and `bootstrap/cache` are writable by `www-data`. The stack still uses Redis and a `scheduler` container.

To put it behind a TLS-terminating proxy, forward `X-Forwarded-Proto` and `X-Forwarded-Host`. nginx passes them on to Laravel, which trusts them because php-fpm is reachable only from nginx; keep it unpublished. Set `SESSION_SECURE_COOKIE=true` and an `https` `APP_URL` there.

```bash
make prod-build     # build images
make prod-up        # start in the background on APP_PORT (default 8080), project name feedthebet-prod
make prod-down      # stop and remove containers and volumes
```

It reads configuration from `backend/.env` and forces `APP_ENV=production`, `APP_DEBUG=false` and logging to stderr. Before a real deployment: set `APP_URL` to the public HTTPS URL, `SESSION_SECURE_COOKIE=true`, a dedicated `AUTH0_COOKIE_SECRET`, add the production URLs to the Auth0 callback/logout lists, and terminate TLS in front of nginx. Dev and prod both default to port 8080, so stop one before starting the other or set `APP_PORT`.
