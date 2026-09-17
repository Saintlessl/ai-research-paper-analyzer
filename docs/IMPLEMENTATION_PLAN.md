# Implementation Plan

Development follows vertical, tested slices and Conventional Commits.

1. **Foundation:** install Laravel 12 and the React starter stack; configure TypeScript, Tailwind, SQLite tests, database/Redis queue settings, and baseline builds.
2. **Identity:** retain starter authentication, add normalized roles, server-side middleware and policies, and test each role boundary.
3. **Paper ingestion:** normalized paper/author schema, private PDF storage, Form Request validation, ownership policies, asynchronous dispatch, audit entry, and Inertia pages.
4. **AI operations:** AI job/request/response schema, resilient queued job, authenticated Laravel HTTP client, retry/backoff, correlation IDs, and admin retry.
5. **AI service:** FastAPI settings, auth dependency, versioned contracts, PDF/page extraction, section-aware chunks, deterministic citation heuristics, Gemini adapter, Pydantic validation, finite repair.
6. **Research workflows:** normalized analyses, scores, findings and references; reviewer reports; paper-grounded Q&A; two-paper comparison; human review assignments/comments.
7. **Experience/admin:** role-aware dashboards using real aggregates, paper tabs and states, reviewer workspace, users/jobs/audits/statistics screens, accessible loading/error/empty states.
8. **Hardening:** authorization and secret scan, upload abuse controls, rate limits, failure-path tests, full Laravel/Python/type/build verification, setup and deployment documentation.

Each behavioral change uses RED → GREEN → REFACTOR. Before each commit: targeted tests, changed-file inspection, secret check, and explicit staging.
