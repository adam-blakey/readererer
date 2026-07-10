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

1. `node:20-alpine` builds the Vite assets (`npm ci && npm run build`).
2. `composer:2` installs PHP dependencies (`--no-dev`).
3. `php:8.2-apache` serves Laravel's `public/` directory.

Runtime configuration (`APP_KEY`, `DB_*`, etc.) is supplied by the server as
environment variables — **nothing sensitive is baked into the image** (`.env` and
the local SQLite database are excluded via `.dockerignore`).

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
