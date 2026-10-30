# Deployments

All three environments are hosted on Krystal and deployed by
`.github/workflows/deploy.yml`, the same pipeline as the website repo.

| Environment  | Trigger                         | Database                         |
| ------------ | ------------------------------- | -------------------------------- |
| `dev`        | Pull request opened / updated   | `migrate:fresh --seed` each time |
| `qa`         | Push to `main`                  | `migrate`                        |
| `production` | Push to `main`, after `qa`      | `migrate`                        |

## How it works

1. **`build_and_test`** runs the Pest suite (including browser tests), then
   reinstalls Composer dependencies without dev packages and uploads `vendor`
   and `public/build` as the `build` artifact. On `main` it also smoke-tests
   `php artisan optimize --env=production`.
2. **`deploy`** runs once per environment, one at a time (`qa` before
   `production`, so a `qa` failure skips production). For each domain it:
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

`dev` is shared: whichever pull request deployed last is what is on dev.

## Repository configuration

These settings live in GitHub, not in the repo, so they have to be applied by
hand. They are what the pipeline relies on, matching the website repo's setup.

### Variables (Settings → Secrets and variables → Actions → Variables)

Repository-level, because the deploy matrix reads the domains before any
environment is selected.

| Name              | Example                    | Description                                                                 |
| ----------------- | -------------------------- | --------------------------------------------------------------------------- |
| `DEV_DOMAIN`      | `dev.readererer.com`       | Dev domain; also the folder under `~/domains/` on the server.               |
| `QA_DOMAIN`       | `qa.readererer.com`        | QA domain.                                                                  |
| `PROD_DOMAIN`     | `readererer.com`           | Production domain.                                                          |
| `DEPLOY_SSH_HOST` | `ssh.example.krystal.uk`   | SSH host.                                                                   |
| `DEPLOY_SSH_PORT` | `722`                      | SSH port.                                                                   |
| `DEPLOY_SSH_USER` | `readererer`               | SSH user.                                                                   |
| `DEPLOY_GIT_KEY`  | `github_readererer`        | File name, in the server's `~/.ssh/`, of the key the server uses to fetch from GitHub. |

If an environment lives on a different Krystal account, set `DEPLOY_SSH_*`
on that environment instead; environment variables override repository ones.

### Secret

| Name              | Description                                                         |
| ----------------- | ------------------------------------------------------------------- |
| `SSH_PRIVATE_KEY` | Private key for `DEPLOY_SSH_USER`; its public key must be in the server's `~/.ssh/authorized_keys`. |

### Server, once per domain

- `~/domains/<domain>` is a git clone of this repo whose `origin` can be
  fetched with `~/.ssh/<DEPLOY_GIT_KEY>`, with a `.env` for that environment.
- The domain's document root points at `public/`.

### Environments (Settings → Environments)

- **`dev`** — no protection.
- **`qa`** — Deployment branches: `main` only.
- **`production`** — Deployment branches: `main` only, and a **Wait timer**
  (the batching window: runs that find a newer one in progress when the timer
  ends cancel themselves). Optionally add Required reviewers.

The old `production` environment variables (`UNIX_USERNAME`, `SERVER_DOMAIN`,
`SERVER_PORT`, `APP_DOMAIN`) and its `SSH_PRIVATE_KEY` are no longer read and
can be deleted.

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
