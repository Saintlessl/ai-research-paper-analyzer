# Architecture

## System boundary

The application uses a strict service boundary:

```text
Inertia React UI -> Laravel -> Queue -> authenticated FastAPI -> Gemini
                              <- structured validated result <-
```

Laravel owns authentication, authorization, relational data, files, audit records, queue state, and all browser-facing routes. FastAPI has no database credentials and accepts paper content only over authenticated HTTP. Gemini credentials exist only in the AI service environment.

## Components

- **Web application:** Laravel 12, Inertia.js, React, TypeScript, Tailwind CSS.
- **Primary persistence:** MySQL/PostgreSQL in production; SQLite is supported for isolated automated tests.
- **Asynchronous work:** Laravel queue with Redis in production and database queue as a documented local fallback.
- **AI service:** Python 3.11+, FastAPI, Pydantic, PDF extraction, section-aware chunking, Gemini adapter.
- **Observability:** Correlation UUID shared by Laravel jobs, FastAPI calls, and AI requests; safe errors and durations stored without tokens.

## Trust model

1. Browser requests use Laravel sessions, CSRF protection, validation, policies, and role middleware.
2. Uploaded PDFs are private, MIME-validated, size-limited, and stored under generated names.
3. Laravel signs internal requests with a bearer service token.
4. FastAPI performs constant-time token comparison and schema validation.
5. AI output is parsed and validated before Laravel persists normalized results.
6. Failures preserve the paper and produce retryable, auditable AI job records.

## Core data domains

- Identity and access: users, roles, reviewer assignments.
- Papers: papers, authors, sections, references.
- Analysis: analyses, scores, findings, evidence metadata.
- Review: generated reports, human reviews, comments.
- Operations: AI jobs, requests, responses, audit logs.

## Processing sequence

1. Laravel validates and stores a PDF, paper metadata, authors, and a pending AI job in a transaction.
2. Laravel dispatches `ProcessUploadedPaper`; the upload response returns immediately.
3. The queued job sends the private PDF to FastAPI using a correlation ID and internal token.
4. FastAPI extracts pages, detects sections, creates bounded chunks, calls Gemini through an adapter, repairs malformed JSON within a finite retry budget, and validates the result with Pydantic.
5. Laravel validates the response again, persists normalized analysis records, and completes the AI job.
6. Terminal failures mark the job and paper failed without making the paper page unavailable.
