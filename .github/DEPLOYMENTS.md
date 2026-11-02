# Deployments

The app environments and the static docs site are hosted on Krystal
and deployed by GitHub Actions. `.github/workflows/deploy.yml` is a reusable
workflow that builds, tests and deploys to `dev`. `pull-request.yml` (**Build, Test &
Deploy (PR)**) calls it for pull requests, and `main.yml` (**Build, Test & Deploy**) calls it for
pushes to `main` and then runs the `qa`, production and docs jobs, so pull
requests show no skipped release jobs.

| Environment | Trigger                                           | Database                         |
| ----------- | ------------------------------------------------- | -------------------------------- |
| `dev`       | Pull request opened / updated, and push to `main` | `migrate:fresh --seed` each time |
| `qa`        | Push to `main`, after `dev`                       | `migrate`                        |
| `prod`      | Push to `main`, after `qa`                        | `migrate`                        |
| `nsw-beta`  | Push to `main`, after `qa`, alongside `prod`      | `migrate`                        |
| `docs`      | Push to `main`, after `prod` and `nsw-beta`       | None (static files from `site/`) |

`prod` and `nsw-beta` are both production sites (`readererer-prod.blakey.family`
and `attendance-beta.nsw.org.uk`); the docs site is
`readererer.blakey.family`.

## How it works

1. **`build_and_test`** checks code style with Pint and runs the Pest suite
   (Unit and Feature in parallel, then the browser tests), then
   reinstalls Composer dependencies without dev packages and uploads `vendor`
   and `public/build` as the `build` artifact. On `main` it also smoke-tests
   `php artisan optimize --env=production`.
2. **`deploy`** runs once per environment, one at a time (on `main`: `dev`, then
   `qa`, then `prod`, so a failure skips the environments after it). For each domain it:
   - rsyncs `vendor` and `public/build` to `~/domains/<domain>/`;
   - fetches the exact commit on the server and `git reset --hard`s to it;
   - writes `VERSION` (`git describe`, plus `+pr<n>` on dev);
   - puts the app in maintenance mode, migrates, runs `optimize`,
     `event:cache` and `queue:restart`, and brings it back up.

   The production sites (`prod`, `nsw-beta`) have no SSH, so they are
   deployed by cPanel's **Git™ Version Control**,
   driven from the runner through the cPanel API (`:2083/execute`) by
   `.github/actions/deploy-cpanel`:
   - `VersionControl/update` pulls `main` into the cPanel clone, which is
     the site's folder (`~/domains/<domain>`, passed to the action as
     `repository-root`), and `VersionControlDeployment/create`
     runs the tasks in `.cpanel.yml`. The job then polls
     `VersionControlDeployment/retrieve` until the deployment succeeds or
     fails (for up to 30 minutes).
   - `.cpanel.yml` works out the site from the clone it runs in, so the same
     file serves every site. It extracts the commit (`git archive`) into
     `~/readererer-build/<domain>` (which keeps
     `vendor` and `node_modules` between deploys), writes `VERSION`, runs
     `composer install --no-dev --no-scripts` (the host disables
     `proc_open`, which Composer scripts need), `npm ci` and `npm run build`.
     Only then does it put the app in maintenance mode (skipped on a site's
     first deploy, whose folder has no `vendor` for `artisan` to load yet). It rsyncs the app's
     folders (`app`, `bootstrap`, `config`, `database`, `lang`, `resources`,
     `routes`, `vendor`, `public/build`, `public/js`) into the site's folder
     with deletion, which also clears
     `bootstrap/cache`, and copies the root files and `public/index.php` and
     friends. `.env`, `storage`, SQLite databases and anything else outside
     those folders are left alone. Finally it runs `package:discover`,
     `migrate`, `optimize`, `event:cache` and `queue:restart`, and brings the
     app back up.
   - Everything the deploy adds to the site's folder (`vendor`,
     `public/build`, `bootstrap/cache`, `VERSION`) is git-ignored, so the
     clone stays free of local changes, which cPanel requires before it
     deploys.

   The pull updates the site's tracked files straight away, so until `down`
   the live site runs the new code with the previous `vendor`, assets, caches
   and database schema. A failing task stops the rest; after `down`, the site
   stays in maintenance mode. The job prints the path of the
   deployment log (`~/.cpanel/logs/vc_<id>_git_deploy.log`), which you read in
   **File Manager**. Fix the cause and re-run the deploy.
3. **Production releases** run as three jobs on `main`:
   - **`release_tag`** runs in the `release` environment, whose wait timer
     batches merges. It cancels the run if a newer `main` run is in
     progress, then works out the next version from the Conventional Commits
     since the last tag and pushes that tag, so that the servers'
     `git describe` names the release.
   - **`deploy_prod`** deploys every production site at once (a matrix over
     `prod` and `nsw-beta`); one site failing does not stop the others.
   - **`release`** publishes a GitHub release for the tag with generated
     notes once every site has deployed. If a site fails, the tag exists
     without a release until its job is re-run.
4. **`docs`** runs after every environment has deployed. It rsyncs `site/`
   (following the logo symlink into `resources/images/`) to
   `~/domains/<domain>/public/`, deleting anything else in that folder. It
   refuses to run if `~/domains/<domain>/artisan` exists, so it cannot
   overwrite a folder that still holds the app.

Dev and qa need only PHP and git on the server. Prod also builds there, with
Composer and CloudLinux's Node 24 (`/opt/alt/alt-nodejs24`, which
`.cpanel.yml` puts on `PATH`).

A new push to a pull request cancels that pull request's earlier run; runs on
`main` are never cancelled this way.

Fork pull requests and Dependabot pull requests build and test but do not
deploy (they have no access to the secrets).

`dev` is shared: whichever pull request or `main` push deployed last is what
is on dev.

## Repository configuration

These settings live in GitHub, not in the repo, so they have to be applied by
hand. They are what the pipeline relies on, matching the website repo's setup.

### Environments (Settings → Environments)

Create `dev`, `qa`, `release`, `prod`, `nsw-beta` and `docs` (names must
match exactly), with:

- **`dev`** — no protection.
- **`qa`** — Deployment branches: `main` only.
- **`release`** — Deployment branches: `main` only, and a **Wait timer**
  (the batching window: runs that find a newer one in progress when the timer
  ends cancel themselves). Optionally add Required reviewers. It gates every
  production site, so the wait timer formerly on `prod` moves here.
- **`prod`**, **`nsw-beta`** — Deployment branches: `main` only, and no wait
  timer (they deploy as soon as `release_tag` finishes).
- **`docs`** — Deployment branches: `main` only.

To add another production site, create its environment with the variables
below, set it up in cPanel as in "cPanel Git deployment, once per production
site", and add its name to the `deploy_prod` matrix in `main.yml`.

### Variables and secret

Each deploy job runs in its environment, so every value below can be set on
the environment (Settings → Environments → *name*) or once on the repository
(Settings → Secrets and variables → Actions). An environment value overrides a
repository one. If all three sites share one cPanel account, set `APP_DOMAIN`
per environment and the rest once on the repository.

| Name                                | Example                         | Description                                                                           |
| ----------------------------------- | ------------------------------- | ------------------------------------------------------------------------------------- |
| `APP_DOMAIN` (per env)              | `readererer-prod.blakey.family` | The site's domain; also its folder under `~/domains/` on the server.                  |
| `DEPLOY_SSH_HOST`                   | `ssh.example.krystal.uk`        | SSH host.                                                                             |
| `DEPLOY_SSH_PORT`                   | `722`                           | SSH port (Krystal uses 722).                                                          |
| `DEPLOY_SSH_USER`                   | `readererer`                    | cPanel username.                                                                      |
| `DEPLOY_GIT_KEY`                    | `github_readererer`             | File name, in the server's `~/.ssh/`, of the key the server fetches from GitHub with. |
| `SSH_PRIVATE_KEY` (secret)          |                                 | Private key GitHub logs in to the server with.                                        |
| `CPANEL_HOST` (production)          | `server.example.krystal.uk`     | cPanel host; the API is on port 2083.                                                 |
| `CPANEL_USER` (production)          | `readererer`                    | The site's cPanel username.                                                           |
| `CPANEL_TOKEN` (secret, production) |                                 | A cPanel API token for that account.                                                  |

The SSH values are not used by the production sites; `docs` still needs them. The old
`DEPLOY_FTP_HOST`, `DEPLOY_FTP_USER`, `DEPLOY_FTP_PASSWORD` and `DEPLOY_TOKEN`
settings are no longer read and can be deleted, along with `DEPLOY_TOKEN` in
prod's `.env`.

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
3. **PHP.** In **Select PHP Version** (or **MultiPHP Manager**) choose 8.5 with
   `pdo_mysql`/`pdo_sqlite`, `mbstring`, `gd`, `zip`, `bcmath`, `exif` and
   `fileinfo` enabled. The deploy runs `php artisan` over SSH, so check
   `php -v` in **Terminal** reports 8.5 too.

### cPanel Git deployment, once per production site

The production accounts have no SSH or Terminal, so each site is set up in
cPanel (shown for `prod`; for `nsw-beta` use `attendance-beta.nsw.org.uk`):

1. **PHP.** In **Select PHP Version** choose 8.5 with `pdo_sqlite`,
   `mbstring`, `openssl`, `fileinfo` and `zip` ticked. Deploy tasks run plain
   `php`, which follows this setting.
2. **Clone.** In **Git™ Version Control** → **Create**, turn on *Clone a
   Repository*, use `https://github.com/adam-blakey/readererer.git` (public, so
   no key is needed) with repository path
   `domains/readererer-prod.blakey.family`, the site's folder. It checks out
   `main`. Never edit files in the clone: cPanel will not deploy
   one with local changes.
3. **API token.** In **Security** → **Manage API Tokens** create a token (no
   expiry, or remember to renew it) and put it in the site's environment's
   `CPANEL_TOKEN` secret. Set `APP_DOMAIN`, `CPANEL_HOST` and `CPANEL_USER`
   there too.
4. Check that the site's folder has its `.env`. The first deploy
   stops before touching the site if that `.env` is missing.

`.cpanel.yml` tasks need `rsync` and `git` on the server. If a deploy fails at
`git archive` or the first `rsync`, one of them is missing (check the
deployment log). To see a clone's local changes without Terminal, add a
one-off cron job running
`cd ~/domains/readererer-prod.blakey.family && git status --porcelain > ~/git-status.txt`.

Dev and qa are deployed over SSH and do not use `.cpanel.yml`.

### Moving prod to `readererer-prod.blakey.family` and adding the docs site

The folder on the server is named after `APP_DOMAIN`, so changing prod's
domain moves the app to a new folder. If prod currently lives at
`readererer.blakey.family`:

1. In cPanel create the domain `readererer-prod.blakey.family` with document
   root `domains/readererer-prod.blakey.family/public` and run AutoSSL.
2. In **Terminal**, put prod in maintenance mode and move it:
   `cd ~/domains/readererer.blakey.family && php artisan down --retry=5 --refresh=5`, then
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
   away, re-run the latest **Build, Test & Deploy** run.

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
   the mail settings. On prod keep `APP_MAINTENANCE_DRIVER=file`. For `APP_KEY`, run
   `php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"` and paste
   the output. Keep prod's existing key. Then re-run the deploy.

No cron jobs are needed: the app has no scheduled tasks or queued jobs.

### Rules (Settings → Rules → Rulesets → `main`)

The existing `main` ruleset (restrict deletions, block force pushes, require
signed commits, CodeQL, code quality, coverage) stays. Add:

- **Require a pull request before merging.**
- **Require status checks to pass**, with:
  - `build / Pest test suite` (from Build, Test & Deploy (PR))
  - `commit_messages` (from Conventional Commits)

### Merging

The release version is derived from the commit messages that land on `main`,
so keep merge commits or rebase merging enabled. With squash merging, the PR
title becomes the commit message and must also be a Conventional Commit.

### Cleanup

Nothing pushes to `ghcr.io/adam-blakey/readererer` any more; the package can
be deleted under the profile's **Packages** tab once nothing pulls it.
