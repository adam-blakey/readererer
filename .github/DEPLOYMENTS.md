# Deployments

All three environments are hosted on Krystal and deployed by
`.github/workflows/deploy.yml`, the same pipeline as the website repo.

| Environment  | Trigger                         | Database                         |
| ------------ | ------------------------------- | -------------------------------- |
| `dev`        | Pull request opened / updated, and push to `main` | `migrate:fresh --seed` each time |
| `qa`         | Push to `main`, after `dev`     | `migrate`                        |
| `production` | Push to `main`, after `qa`      | `migrate`                        |

## How it works

1. **`build_and_test`** runs the Pest suite (including browser tests), then
   reinstalls Composer dependencies without dev packages and uploads `vendor`
   and `public/build` as the `build` artifact. On `main` it also smoke-tests
   `php artisan optimize --env=production`.
2. **`deploy`** runs once per environment, one at a time (on `main`: `dev`, then
   `qa`, then `production`, so a failure skips the environments after it). For each domain it:
   - rsyncs `vendor` and `public/build` to `~/domains/<domain>/`;
   - fetches the exact commit on the server and `git reset --hard`s to it;
   - writes `version.json` (production: the new release tag; dev/qa:
     `git describe`, plus `+pr<n>` on dev);
   - puts the app in maintenance mode, migrates, runs `optimize`,
     `event:cache` and `queue:restart`, and brings it back up.
3. **Production only:** before deploying it cancels itself if a newer `main`
   run is in progress, works out the next version from the Conventional
   Commits since the last tag, and after deploying publishes a GitHub release
   with that tag and generated notes.

Node and Composer are no longer needed on the server; only PHP and git.

Fork pull requests and Dependabot pull requests build and test but do not
deploy (they have no access to the secrets).

`dev` is shared: whichever pull request or `main` push deployed last is what
is on dev.

## Repository configuration

These settings live in GitHub, not in the repo, so they have to be applied by
hand. They are what the pipeline relies on, matching the website repo's setup.

### Environments (Settings → Environments)

Create `dev`, `qa` and `production` (names must match exactly), with:

- **`dev`** — no protection.
- **`qa`** — Deployment branches: `main` only.
- **`production`** — Deployment branches: `main` only, and a **Wait timer**
  (the batching window: runs that find a newer one in progress when the timer
  ends cancel themselves). Optionally add Required reviewers.

### Variables and secret

Each deploy job runs in its environment, so every value below can be set on
the environment (Settings → Environments → *name*) or once on the repository
(Settings → Secrets and variables → Actions). An environment value overrides a
repository one. If all three sites share one cPanel account, set `APP_DOMAIN`
per environment and the rest once on the repository.

| Name                        | Example                  | Description                                                              |
| --------------------------- | ------------------------ | ------------------------------------------------------------------------ |
| `APP_DOMAIN` (per env)      | `dev.readererer.com`     | The site's domain; also its folder under `~/domains/` on the server.      |
| `DEPLOY_SSH_HOST`           | `ssh.example.krystal.uk` | SSH host.                                                                |
| `DEPLOY_SSH_PORT`           | `722`                    | SSH port (Krystal uses 722).                                             |
| `DEPLOY_SSH_USER`           | `readererer`             | cPanel username.                                                         |
| `DEPLOY_GIT_KEY`            | `github_readererer`      | File name, in the server's `~/.ssh/`, of the key the server fetches from GitHub with. |
| `SSH_PRIVATE_KEY` (secret)  |                          | Private key GitHub logs in to the server with.                           |

The old `production` variables `UNIX_USERNAME`, `SERVER_DOMAIN` and
`SERVER_PORT` are no longer read and can be deleted; `APP_DOMAIN` is kept.

### cPanel, once per account

1. **Login key for GitHub.** On your machine run
   `ssh-keygen -t ed25519 -N '' -C github-actions -f readererer-deploy`.
   In cPanel → **SSH Access** → **Manage SSH Keys** → **Import Key**, paste
   `readererer-deploy.pub` as the public key, then **Manage** → **Authorize**.
   Put the contents of `readererer-deploy` in the `SSH_PRIVATE_KEY` secret and
   delete both local files.
2. **Fetch key for the server.** In cPanel → **Terminal**, run
   `ssh-keygen -t ed25519 -N '' -f ~/.ssh/github_readererer` and
   `cat ~/.ssh/github_readererer.pub`. Add that as a read-only deploy key in
   GitHub → Settings → **Deploy keys**. `github_readererer` is the
   `DEPLOY_GIT_KEY` value.
3. **PHP.** In **Select PHP Version** (or **MultiPHP Manager**) choose 8.4 with
   `pdo_mysql`/`pdo_sqlite`, `mbstring`, `gd`, `zip`, `bcmath`, `exif` and
   `fileinfo` enabled. The deploy runs `php artisan` over SSH, so check
   `php -v` in **Terminal** reports 8.4 too.

### cPanel, once per site

1. **Domain.** In **Domains** → **Create A New Domain**, enter the domain,
   untick *Share document root*, and set the document root to
   `domains/<domain>/public`. Then run **SSL/TLS Status** → **Run AutoSSL**.
2. **First deploy.** Run a deploy to the environment. It turns the folder
   into a git checkout of the commit (no clone needed), then stops because
   there is no `.env` yet. Delete any placeholder page cPanel put in
   `public/` (such as `index.html`), as it would be served instead of the app.
3. **Database.** Each site needs its own database; dev's is wiped and reseeded
   on every deploy. For MySQL use **MySQL Database Wizard** to create a
   database and user with all privileges. For SQLite run
   `touch ~/domains/<domain>/database/database.sqlite`.
4. **`.env`.** `cp .env.example .env` in the site folder and set at least
   `APP_ENV` (`dev`/`qa`/`production`), `APP_DEBUG=false` (except dev),
   `APP_URL=https://<domain>`, the `DB_*` values from step 3 (SQLite needs the
   absolute path, `/home/<user>/domains/<domain>/database/database.sqlite`) and
   the mail settings. For `APP_KEY`, run
   `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"` and paste
   the output. Keep production's existing key. Then re-run the deploy.

No cron jobs are needed: the app has no scheduled tasks or queued jobs.

### Rules (Settings → Rules → Rulesets → `main`)

The existing `main` ruleset (restrict deletions, block force pushes, require
signed commits, CodeQL, code quality, coverage) stays. Add:

- **Require a pull request before merging.**
- **Require status checks to pass**, with:
  - `Pest test suite` (from Build, Test & Deploy)
  - `Pint code style` (from CI)
  - `commit_messages` (from Conventional Commits)

### Merging

The release version is derived from the commit messages that land on `main`,
so keep merge commits or rebase merging enabled. With squash merging, the PR
title becomes the commit message and must also be a Conventional Commit.

### Cleanup

Nothing pushes to `ghcr.io/adam-blakey/readererer` any more; the package can
be deleted under the profile's **Packages** tab once nothing pulls it.
