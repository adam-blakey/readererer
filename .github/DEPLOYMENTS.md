# Deployments

All three app environments and the static docs site are hosted on Krystal
and deployed by `.github/workflows/deploy.yml`, the same pipeline as the
website repo.

| Environment | Trigger                                           | Database                         |
| ----------- | ------------------------------------------------- | -------------------------------- |
| `dev`       | Pull request opened / updated, and push to `main` | `migrate:fresh --seed` each time |
| `qa`        | Push to `main`, after `dev`                       | `migrate`                        |
| `prod`      | Push to `main`, after `qa`                        | `migrate`                        |
| `docs`      | Push to `main`, after `prod`                      | None (static files from `site/`) |

The app's prod site is `readererer-prod.blakey.family`; the docs site is
`readererer.blakey.family`.

## How it works

1. **`build_and_test`** runs the Pest suite (including browser tests), then
   reinstalls Composer dependencies without dev packages and uploads `vendor`
   and `public/build` as the `build` artifact. On `main` it also smoke-tests
   `php artisan optimize --env=production`.
2. **`deploy`** runs once per environment, one at a time (on `main`: `dev`, then
   `qa`, then `prod`, so a failure skips the environments after it). For each domain it:
   - rsyncs `vendor` and `public/build` to `~/domains/<domain>/`;
   - fetches the exact commit on the server and `git reset --hard`s to it;
   - writes `version.json` (prod: the new release tag; dev/qa:
     `git describe`, plus `+pr<n>` on dev);
   - puts the app in maintenance mode, migrates, runs `optimize`,
     `event:cache` and `queue:restart`, and brings it back up.
3. **`prod` only:** before deploying it cancels itself if a newer `main`
   run is in progress, works out the next version from the Conventional
   Commits since the last tag, and after deploying publishes a GitHub release
   with that tag and generated notes.
4. **`docs`** runs after every environment has deployed. It rsyncs `site/`
   (following the logo symlink into `resources/images/`) to
   `~/domains/<domain>/public/`, deleting anything else in that folder. It
   refuses to run if `~/domains/<domain>/artisan` exists, so it cannot
   overwrite a folder that still holds the app.

Node and Composer are no longer needed on the server; only PHP and git.

Fork pull requests and Dependabot pull requests build and test but do not
deploy (they have no access to the secrets).

`dev` is shared: whichever pull request or `main` push deployed last is what
is on dev.

## Repository configuration

These settings live in GitHub, not in the repo, so they have to be applied by
hand. They are what the pipeline relies on, matching the website repo's setup.

### Environments (Settings → Environments)

Create `dev`, `qa` and `prod` (names must match exactly), with:

- **`dev`** — no protection.
- **`qa`** — Deployment branches: `main` only.
- **`prod`** — Deployment branches: `main` only, and a **Wait timer**
  (the batching window: runs that find a newer one in progress when the timer
  ends cancel themselves). Optionally add Required reviewers.
- **`docs`** — Deployment branches: `main` only.

### Variables and secret

Each deploy job runs in its environment, so every value below can be set on
the environment (Settings → Environments → *name*) or once on the repository
(Settings → Secrets and variables → Actions). An environment value overrides a
repository one. If all three sites share one cPanel account, set `APP_DOMAIN`
per environment and the rest once on the repository.

| Name                       | Example                         | Description                                                                           |
| -------------------------- | ------------------------------- | ------------------------------------------------------------------------------------- |
| `APP_DOMAIN` (per env)     | `readererer-prod.blakey.family` | The site's domain; also its folder under `~/domains/` on the server.                  |
| `DEPLOY_SSH_HOST`          | `ssh.example.krystal.uk`        | SSH host.                                                                             |
| `DEPLOY_SSH_PORT`          | `722`                           | SSH port (Krystal uses 722).                                                          |
| `DEPLOY_SSH_USER`          | `readererer`                    | cPanel username.                                                                      |
| `DEPLOY_GIT_KEY`           | `github_readererer`             | File name, in the server's `~/.ssh/`, of the key the server fetches from GitHub with. |
| `SSH_PRIVATE_KEY` (secret) |                                 | Private key GitHub logs in to the server with.                                        |

The live environment is now called `prod`. Recreate the old `production`
environment's `APP_DOMAIN`, any secrets and its protection rules on `prod`,
then delete `production`. Its `UNIX_USERNAME`, `SERVER_DOMAIN` and
`SERVER_PORT` variables are no longer read.

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

### Moving prod to `readererer-prod.blakey.family` and adding the docs site

The folder on the server is named after `APP_DOMAIN`, so changing prod's
domain moves the app to a new folder. If prod currently lives at
`readererer.blakey.family`:

1. In cPanel create the domain `readererer-prod.blakey.family` with document
   root `domains/readererer-prod.blakey.family/public` and run AutoSSL.
2. In **Terminal**, put prod in maintenance mode and move it:
   `cd ~/domains/readererer.blakey.family && php artisan down`, then
   `mv ~/domains/readererer.blakey.family ~/domains/readererer-prod.blakey.family`.
3. In the moved `.env` set `APP_URL=https://readererer-prod.blakey.family`, and
   if `DB_DATABASE` is an absolute SQLite path, update it to the new folder.
   Then `php artisan optimize && php artisan up`.
4. In GitHub, set the `prod` environment's `APP_DOMAIN` to
   `readererer-prod.blakey.family`.
5. Create the `docs` environment (Deployment branches: `main` only) with
   `APP_DOMAIN` = `readererer.blakey.family`. The SSH variables and secret are
   shared if they are set on the repository; if they are set per environment,
   copy them to `docs` too.
6. The `readererer.blakey.family` cPanel domain keeps its document root
   (`domains/readererer.blakey.family/public`), which after step 2 points at
   an empty folder until the next `main` deploy fills it. To fill it straight
   away, re-run the latest **Build, Test & Deploy** run on `main`.

If prod was somewhere else, skip steps 2–3 and set up the new prod domain as
in "once per site" below, and create `readererer.blakey.family` in cPanel with
document root `domains/readererer.blakey.family/public` before step 6.

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
   `APP_ENV` (`dev`, `qa`, or `production` on prod, which Laravel treats
   specially), `APP_DEBUG=false` (except dev),
   `APP_URL=https://<domain>`, the `DB_*` values from step 3 (SQLite needs the
   absolute path, `/home/<user>/domains/<domain>/database/database.sqlite`) and
   the mail settings. For `APP_KEY`, run
   `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"` and paste
   the output. Keep prod's existing key. Then re-run the deploy.

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
