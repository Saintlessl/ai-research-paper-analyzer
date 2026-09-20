# AI Research Paper Analyzer & Reviewer Assistant

A full-stack, production-quality web application that helps researchers, journal editors, and reviewers automatically analyze academic research papers using AI.

## Architecture

This application consists of two decoupled services:

1. **Laravel (12.x)**: The core system of record, handling user authentication, role-based access control (RBAC), secure PDF storage, background queues (Redis), relational data (MySQL), and the React/Inertia frontend.
2. **FastAPI (Python 3.11+)**: The stateless AI processing engine that interfaces with the Google Gemini API to chunk, process, and analyze academic papers.

## Features

- **Role-Aware Workspaces**: Distinct dashboards for Researchers, Reviewers, and Admins.
- **Secure Storage**: Uploaded papers are kept out of public web roots.
- **Background Processing**: AI analysis operations are queued asynchronously via Redis to prevent HTTP timeouts.
- **Structured Academic Analysis**: Validates AI responses against strict academic criteria (Clarity, Novelty, Methodology, etc.).
- **Grounded Q&A**: Ask questions directly against a paper's content, receiving answers with verifiable citations (page/section).
- **Paper Comparison**: Side-by-side comparative assessment of multiple papers.
- **AI & Human Reviewing**: Generate automated AI reviewer reports and assign human reviewers to submit manual assessments.
- **Admin Dashboard**: System-wide monitoring of AI jobs, audit logs, and user roles.

## Requirements

- **PHP**: 8.2+
- **Composer**: 2.x
- **Node.js**: 20+
- **MySQL**: 8.0+
- **Redis**: 5.0+
- **Python**: 3.11+
- **uv**: Python package manager

## Installation

### 1. Laravel Setup

```bash
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan storage:link
```

Ensure your `.env` contains the proper MySQL and Redis configuration:

```env
DB_CONNECTION=mysql
DB_DATABASE=ai_research_paper_analyzer
DB_USERNAME=root

QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
CACHE_STORE=redis
REDIS_CLIENT=predis
```

Run migrations and seed the system roles:

```bash
php artisan migrate:fresh --seed
```

### 2. FastAPI Setup

```bash
cd ai-service
uv venv
source .venv/bin/activate # or .venv\Scripts\activate on Windows
uv pip sync requirements.txt
```

Set up your environment variables for the FastAPI service:

```bash
export GEMINI_API_KEY="your-gemini-key"
export SERVICE_TOKEN="local-dev-token"
```

Start the FastAPI server:

```bash
uvicorn app.main:app --host 127.0.0.1 --port 8001
```

### 3. Run Queue Workers

```bash
php artisan queue:work --tries=3 --timeout=120
```

## Security & Architecture Rules

- The FastAPI service **does not** have direct access to the Laravel MySQL database. All communication goes through authenticated HTTP requests from Laravel.
- All endpoints are protected by `EnsureUserHasRole` middleware and strict Form Requests.
- Pydantic models in FastAPI guarantee structured JSON outputs for Laravel to deserialize safely.
- All destructive actions and AI requests trigger an `AuditLog` entry.
- File paths are randomized upon upload and mapped securely via `Storage::disk('papers')`.

## Development

Run tests:
```bash
php artisan test
```

## License

Proprietary
