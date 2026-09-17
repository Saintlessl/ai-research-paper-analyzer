# Contributing

## Development contract

The mandatory request path is:

```text
Inertia React -> Laravel -> queued job -> authenticated FastAPI -> Gemini
```

Laravel remains the source of truth. FastAPI must not connect to Laravel's database, React must not call Gemini or privileged FastAPI endpoints, and uploads must not wait for AI completion.

## Workflow

1. Start from a clean `main` and create a narrowly named branch such as `feat/paper-upload` or `fix/ai-timeout`.
2. Implement one vertical behavior at a time with RED → GREEN → REFACTOR:
   - write a focused test;
   - run it and confirm the expected failure;
   - implement the minimum behavior;
   - run the focused test and relevant suite;
   - refactor only while green.
3. Inspect `git status` and `git diff`; never stage unrelated files or use blind `git add .`.
4. Run `git diff --check`, syntax/type checks, and a credential scan before committing.
5. Use Conventional Commits, for example `feat(papers): queue secure PDF analysis`.
6. Push the branch and open a pull request containing behavior, security impact, migrations, test evidence, and operational changes.

## Quality gates

Run the gates relevant to a change and the full set before merging:

```bash
composer.bat install
php artisan test
npm ci
npm run build

cd ai-service
uv sync --all-extras
uv run pytest
```

Production behavior that calls Gemini must be tested through an injected fake/mock in the normal suite. A paid/live Gemini request is never a prerequisite for deterministic CI.

## Database changes

- Prefer normalized tables, foreign keys, indexes, and unique constraints over opaque JSON.
- Keep migrations reversible and test a fresh migration.
- Treat uploaded papers and completed reviews as records requiring deliberate retention behavior; avoid broad cascades without justification.
- FastAPI code must not import a Laravel database driver or receive database credentials.

## Security checklist

- [ ] Every protected action has server-side middleware/policy enforcement.
- [ ] Inputs use Form Requests or Pydantic models with bounds.
- [ ] Files remain private and names are generated server-side.
- [ ] No token, key, `.env`, paper content, or sensitive log is staged.
- [ ] Errors are generic to clients but traceable via request ID.
- [ ] New retries are finite and idempotency/state behavior is tested.
- [ ] Dependency audit has no unreviewed high/critical finding.

## Pull request scope

Do not combine formatting, dependency upgrades, schema changes, and unrelated features. Generated build assets, dependencies, runtime logs, caches, local databases, credentials, and private sample papers must not be committed.
