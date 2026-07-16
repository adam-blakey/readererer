# Deployments

Readererer deploys through **GitHub Actions** using **GitHub Environments**. All
CI/CD lives in GitHub (the old GitLab pipeline has been removed).

| Environment  | Trigger                       | What it does                                              | Workflow                                  |
| ------------ | ----------------------------- | -------------------------------------------------------- | ----------------------------------------- |
| `dev`        | Pull request opened / updated | Build image, push to GHCR tagged `dev`                   | `.github/workflows/deploy-dev.yml`        |
| `qa`         | Push to `main`                | Build image, push to GHCR tagged `qa`                    | `.github/workflows/deploy-qa.yml`         |
| `production` | GitHub release published      | Deploy `main` over SSH with Laravel Envoy                | `.github/workflows/deploy-production.yml` |

## Dev and QA — container images

`dev` and `qa` **do not connect to any server.** They build the app image from
the root `Dockerfile` and push it to the **GitHub Container Registry (GHCR)**:

- Image: `ghcr.io/adam-blakey/readererer`
- `dev` pushes tags: `dev`, `pr-<number>`, `sha-<commit>`
- `qa` pushes tags: `qa`, `sha-<commit>`

Each environment's server is expected to **watch its tag (`dev` / `qa`) and pull
the new image itself** (e.g. via a watchtower-style poller or a scheduled
`docker pull && docker compose up -d`). GitHub's job finishes once the image is
pushed.

### Requirements

- No secrets or variables are needed — the built-in `GITHUB_TOKEN` authenticates
  to GHCR. The workflows request `packages: write` permission for this.
- **Server pull access.** If the GHCR package is **private**, the server needs
  credentials to pull it: create a Personal Access Token (or GitHub App token)
  with `read:packages` and `docker login ghcr.io` on the server. Making the
  package **public** avoids this.
- **Fork PRs are skipped** (they cannot push to the registry).

### The image

Multi-stage `Dockerfile`:

1. `node:24-alpine` builds the Vite assets (`npm ci && npm run build`).
2. `php:8.4-cli-bookworm` + Composer installs PHP dependencies (`--no-dev`).
3. `php:8.4-apache-bookworm` serves Laravel's `public/` directory.

Runtime configuration (`APP_KEY`, `DB_*`, etc.) is supplied by the server as
environment variables — **nothing sensitive is baked into the image** (`.env` and
the local SQLite database are excluded via `.dockerignore`).

### Running the image

The entrypoint (`docker/entrypoint.sh`) makes a bare `docker run` work out of
the box: it generates an ephemeral `APP_KEY` when none is supplied, creates
the SQLite database file if it's missing, and runs `php artisan migrate
--force` before starting Apache.

```bash
# Zero-config dev/QA run (ephemeral key + SQLite inside the container):
docker run --rm -p 8080:80 ghcr.io/adam-blakey/readererer:dev

# First run with sample data:
docker run --rm -p 8080:80 -e APP_SEED=true ghcr.io/adam-blakey/readererer:dev

# Durable setup: fixed key, database persisted on a volume:
docker run -d -p 8080:80 \
  -e APP_KEY="base64:..." \
  -e DB_DATABASE=/data/database.sqlite \
  -v readererer-data:/data \
  ghcr.io/adam-blakey/readererer:qa
```

Notes:

- Without `APP_KEY`, sessions and encrypted data do not survive a container
  restart. Generate a key with `php artisan key:generate --show` and pass it
  in for anything longer-lived than a throwaway run.
- `APP_SEED=true` runs the database seeders after migrating. The seeders are
  not idempotent — only set it on the first start against an empty database.
- Any other Laravel setting can be passed as an environment variable
  (`DB_CONNECTION=mysql`, `DB_HOST=...`, etc.). Migrations are retried for
  ~30 seconds at startup so a database container in the same compose stack
  has time to come up.
- Logs go to the container's stderr by default (`LOG_CHANNEL=stderr`), so
  application errors appear in `docker logs`.

### Footer version number

The version shown in the app footer comes from `version.json`, generated at
build time (read by `config/_version.php` — no git commands run inside the
app). The image workflows compute it with `git describe` and pass it in as
Docker build arguments; everywhere else (local installs, the production SSH
deploy) `php artisan app:generate-version` writes it from composer's
`post-autoload-dump` hook.

## Production — SSH deploy

`production` keeps the existing SSH deploy: it installs Laravel Envoy on the
runner and runs the `deploy` story in `Envoy.blade.php` (git pull, npm, composer,
migrate) against the production server. It deploys `main`, matching the previous
tag-triggered GitLab deploy; Envoy also fetches all tags.

### Required configuration on the `production` environment

Add these under **Settings → Environments → production**.

**Secret**

| Name              | Description                                                                                          |
| ----------------- | --------------------------------------------------------------------------------------------------- |
| `SSH_PRIVATE_KEY` | Private key for the SSH user. Its public key must be authorised on the server, and the key must be able to pull from the git remote (Envoy uses agent forwarding). |

**Variables**

| Name            | Example              | Description                                                            |
| --------------- | -------------------- | --------------------------------------------------------------------- |
| `UNIX_USERNAME` | `default`            | SSH user on the server.                                               |
| `SERVER_DOMAIN` | `ssh.readererer.com` | SSH host.                                                             |
| `SERVER_PORT`   | `22`                 | SSH port.                                                             |
| `APP_DOMAIN`    | `readererer.com`     | App domain — Envoy uses it to find the app dir; shown as deployment URL. |

## One-time setup

1. **Create the environments** in **Settings → Environments**: `dev`, `qa`,
   `production` (names must match exactly).
2. **Configure `production`** with the secret and variables above. `dev` and `qa`
   need no configuration.
3. **Recommended protection rules:**
   - `production` — **Required reviewers** so a release deploy waits for manual
     approval, and restrict **Deployment branches** to `main`.
   - `qa` — restrict **Deployment branches** to `main`.
   - `dev` — no restriction needed.

## Notes and limitations

- **Shared `dev` tag.** Every PR overwrites the `dev` image tag, so whichever PR
  built last is what the dev server pulls. Runs for the *same* PR are serialised
  (a newer push cancels the older).
- **Production ref.** Production deploys `main` (with all tags fetched). Ensure
  `main` points at the released commit when publishing the release. To deploy the
  exact tag, Envoy's `git reset --hard origin/<ref>` in `Envoy.blade.php` would
  need adjusting to handle tag refs.
