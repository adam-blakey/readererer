# Deployments

Readererer deploys through **GitHub Actions** using **GitHub Environments**. Each
environment maps to a trigger:

| Environment  | Trigger                       | Deploys        | Seeds DB | Workflow                          |
| ------------ | ----------------------------- | -------------- | -------- | --------------------------------- |
| `dev`        | Pull request opened / updated | the PR branch  | yes      | `.github/workflows/deploy-dev.yml`        |
| `qa`         | Push to `main`                | `main`         | no       | `.github/workflows/deploy-qa.yml`         |
| `production` | GitHub release published      | `main`         | no       | `.github/workflows/deploy-production.yml` |

All three call the shared composite action `.github/actions/envoy-deploy`, which
runs the existing Laravel Envoy `deploy` story (`Envoy.blade.php`) over SSH — the
same mechanism the old GitLab pipeline used. The runner only installs Envoy and
opens the SSH connection; the actual build/migrate steps run on the server.

## One-time setup

### 1. Create the environments

In **Settings → Environments**, create three environments named exactly:

- `dev`
- `qa`
- `production`

### 2. Add secrets and variables to each environment

For **each** environment add the following.

**Secret**

| Name              | Description                                                                                    |
| ----------------- | ---------------------------------------------------------------------------------------------- |
| `SSH_PRIVATE_KEY` | Private key for the SSH user on that environment's server. Its public key must be authorised on the server, and the key must be able to pull from the app's git remote (Envoy uses agent forwarding). |

**Variables**

| Name            | Example              | Description                                                        |
| --------------- | -------------------- | ----------------------------------------------------------------- |
| `UNIX_USERNAME` | `default`            | SSH user on the server.                                            |
| `SERVER_DOMAIN` | `ssh.readererer.com` | SSH host.                                                          |
| `SERVER_PORT`   | `22`                 | SSH port.                                                          |
| `APP_DOMAIN`    | `readererer.com`     | App domain — Envoy uses it to find the app dir and is shown as the deployment URL. |

Point each environment's variables at that environment's own server/domain (dev,
QA, and production can be separate hosts or separate app directories on one host).

### 3. Recommended protection rules

- **`production`** — add **Required reviewers** so a release deploy waits for
  manual approval, and restrict **Deployment branches** to `main`.
- **`qa`** — restrict **Deployment branches** to `main`.
- **`dev`** — no branch restriction needed (deploys arbitrary PR branches).

## Notes and limitations

- **Seeding.** `dev` runs `php artisan db:seed --force` after migrating (matching
  the old "test" server); `qa` and `production` do not. Change the
  `seed-database` input in a workflow to adjust. Note the seeder appends rows, so
  repeated dev deploys without a fresh migrate can duplicate sample data.
- **Shared dev server.** PRs deploy to a single `dev` environment, so concurrent
  PRs will overwrite each other. Runs for the *same* PR are serialised (newer
  push cancels the older). The PR branch must exist on the server's git remote
  for Envoy's `git reset --hard origin/<branch>` to succeed.
- **Production ref.** Production deploys `main` (with all tags fetched), matching
  the previous GitLab tag-triggered deploy. Ensure `main` points at the released
  commit when you publish the release. To deploy the exact tag instead, Envoy's
  `git reset --hard origin/<ref>` in `Envoy.blade.php` would need adjusting to
  handle tag refs.
- **Fork PRs** are skipped because they cannot access environment secrets.

## Relationship to the old GitLab pipeline

`.gitlab-ci.yml` still exists and can be removed once these GitHub deployments are
verified. Mapping: GitLab `deploy_krystal_test` (develop branch, seed=1) → `dev`;
`deploy_krystal_demo` (`v*` tags → main, seed=0) → `production`. `qa` is new.
