<!--
Every commit in this PR must follow Conventional Commits (https://www.conventionalcommits.org), or the "Conventional Commits" check fails.

  type(optional scope): description

Types: build, chore, ci, docs, feat, fix, perf, refactor, revert, style, test
Start the description in lower case and keep the first line under 100 characters.

The release version is worked out from the commits:
  feat(register)!: drop old URLs    → major (a "!" or a "BREAKING CHANGE:" line in the body)
  fix(polls): validate dates        → patch (also perf; only when there's no feat)
  anything else                     → minor

Pull requests deploy to dev. Merges to main deploy to dev and QA straight away and to production after the production environment's wait timer. A run that finds a newer one still going when its wait ends cancels itself, so a burst of merges ships as one release.
-->
