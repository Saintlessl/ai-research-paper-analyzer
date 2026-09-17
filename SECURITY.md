# Security Policy

## Supported versions

Security fixes are applied to the current `main` branch until a tagged release policy is introduced.

## Reporting a vulnerability

Do not open a public issue containing credentials, private papers, authentication bypasses, or exploit details. Contact the repository owner privately with:

- affected revision and environment;
- reproducible steps with non-sensitive sample data;
- expected and actual behavior;
- impact and any proposed mitigation.

Never include a real Gemini key, internal service token, session cookie, uploaded paper, or production log in a report.

## Trust boundaries

```text
Browser --session + CSRF--> Laravel --queue--> Laravel worker
                                                |
                                     bearer token over HTTP(S)
                                                v
                                             FastAPI --server-side key--> Gemini
```

- Laravel is the only database owner and the only service that authorizes users.
- FastAPI has no Laravel database credentials and accepts privileged operations only with the internal bearer token.
- Gemini credentials remain in the AI-service environment and are never returned to Laravel or the browser.
- Uploaded papers are private by default; browser-supplied filenames are metadata only and never storage paths.

## Required production controls

1. Set `APP_ENV=production`, `APP_DEBUG=false`, a unique `APP_KEY`, secure session cookies, HTTPS, trusted proxy configuration, and restrictive CORS.
2. Use independent high-entropy values for `FASTAPI_INTERNAL_TOKEN`, database credentials, Redis credentials, and `GEMINI_API_KEY`; rotate them through a secret manager.
3. Expose FastAPI only on a private network or service mesh. `/health` may be public only inside that boundary.
4. Run web, queue worker, scheduler, database, Redis, and AI service with separate least-privilege identities.
5. Store private PDFs outside the public web root and apply encryption, retention, backup, and deletion policies appropriate to the institution.
6. Enforce upload and API rate limits at Laravel and the edge; cap PDF size, page count, extracted text, chunk count, and AI request duration.
7. Redact authorization headers, keys, cookies, raw prompts, and private paper content from application/APM logs.
8. Restrict admin, failed-job retry, reviewer assignment, export, and paper download operations with server-side policies.
9. Patch Composer, npm, Python, OS, PDF-parser, and image-processing dependencies routinely; fail CI on known high/critical vulnerabilities.
10. Monitor repeated auth failures, upload rejection, internal-token rejection, AI timeouts, repair exhaustion, queue failures, and admin actions.

## Abuse and failure cases

| Threat | Primary controls |
| --- | --- |
| Malicious/non-PDF upload | MIME and extension validation, generated name, private storage, size limit, parser limits |
| Paper ownership bypass | policies on every read/write/download/export operation |
| Reviewer data leakage | assignment-scoped policy and minimal Inertia props |
| Admin-route access | authenticated role middleware plus controller/policy checks |
| CSRF/session theft | Laravel CSRF, secure/HTTP-only/same-site cookies, session rotation |
| Laravel-to-FastAPI spoofing | constant-time bearer validation, private network, TLS, rotation |
| Prompt injection in a paper | treat paper text as untrusted data, fixed system rules, structured schema, evidence checks |
| Hallucinated/malformed AI result | Pydantic validation, finite repair, Laravel response validation, no persistence on failure |
| Resource exhaustion | asynchronous queue, request/worker timeout, chunk/page/size caps, retry backoff |
| Sensitive logging | allowlisted structured fields, safe generic errors, token/header redaction |
| Retry abuse | admin authorization, state validation, rate limiting, audit event |
| Dependency compromise | lockfiles, reviewable updates, CI tests/build/audits |

## Secret verification

Before release, confirm that no environment file or credential is tracked and scan tracked source for key-like values. Place placeholders only in `.env.example` files. If a secret is ever committed, removing the line is not sufficient: revoke/rotate it immediately and follow the repository owner's history-remediation process.
