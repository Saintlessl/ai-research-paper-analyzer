import hmac
import logging
from time import perf_counter
from typing import Annotated
from uuid import uuid4

from fastapi import APIRouter, Depends, FastAPI, Header, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse

from .schemas import AnalysisData, AnalyzeRequest, CompareData, CompareRequest, QAData, QARequest, ReviewData, TextRequest
from .services.gemini import GeminiProvider, Provider, ProviderError
from .services.prompts import build_prompt
from .services.structured_output import StructuredOutputError, generate_structured
from .settings import settings

logger = logging.getLogger(__name__)
app = FastAPI(title="Research Paper AI Service")
api = APIRouter(prefix="/api/v1")


@app.middleware("http")
async def request_context(request: Request, call_next):
    request.state.request_id = request.headers.get("X-Request-ID") or str(uuid4())
    started = perf_counter()
    response = await call_next(request)
    response.headers["X-Request-ID"] = request.state.request_id
    logger.info("request_completed request_id=%s method=%s path=%s status=%s duration_ms=%.2f", request.state.request_id, request.method, request.url.path, response.status_code, (perf_counter() - started) * 1000)
    return response


def error_response(request: Request, status_code: int, code: str, message: str) -> JSONResponse:
    return JSONResponse(status_code=status_code, content={"success": False, "request_id": request.state.request_id, "error": {"code": code, "message": message}})


def authorize(authorization: Annotated[str | None, Header()] = None) -> None:
    expected = "Bearer " + settings().internal_token.get_secret_value()
    if not authorization or not hmac.compare_digest(authorization, expected):
        raise HTTPException(401, "Invalid internal service token")


def get_provider() -> Provider:
    config = settings()
    api_key = config.gemini_api_key.get_secret_value() if config.gemini_api_key else None
    return GeminiProvider(api_key, config.gemini_model, config.gemini_timeout_seconds)


def execute(request_id, kind, text, model, provider, question=None):
    prompt = build_prompt(kind, text, model, question)
    try:
        result = generate_structured(provider, prompt, model, settings().repair_attempts)
        return {"success": True, "request_id": str(request_id), "data": result.model_dump(mode="json")}
    except (ProviderError, StructuredOutputError, RuntimeError) as exc:
        if isinstance(exc, ProviderError):
            classification = exc.code
        elif isinstance(exc, StructuredOutputError):
            classification = "PROVIDER_MALFORMED_OUTPUT"
        else:
            classification = "PROVIDER_UNAVAILABLE"
        logger.warning("ai_request_failed request_id=%s operation=%s classification=%s", request_id, kind, classification)
        raise HTTPException(502, detail={"code": "AI_PROCESSING_FAILED", "message": "Unable to process request"}) from exc


@app.exception_handler(HTTPException)
def http_errors(request: Request, exc: HTTPException):
    detail = exc.detail if isinstance(exc.detail, dict) else {"code": "UNAUTHORIZED" if exc.status_code == 401 else "REQUEST_FAILED", "message": str(exc.detail)}
    return error_response(request, exc.status_code, detail["code"], detail["message"])


@app.exception_handler(RequestValidationError)
def validation_errors(request: Request, exc: RequestValidationError):
    logger.info("request_validation_failed request_id=%s errors=%s", request.state.request_id, len(exc.errors()))
    return error_response(request, 422, "VALIDATION_ERROR", "Request validation failed")


@app.exception_handler(Exception)
def unexpected_errors(request: Request, exc: Exception):
    logger.exception("unexpected_error request_id=%s", request.state.request_id)
    return error_response(request, 500, "INTERNAL_ERROR", "Internal server error")


@app.get("/health")
def health():
    return {"status": "ok"}


@api.post("/analyze")
def analyze(body: AnalyzeRequest, provider: Provider = Depends(get_provider)):
    return execute(body.request_id, "analysis", body.text, AnalysisData, provider)


@api.post("/review")
def review(body: TextRequest, provider: Provider = Depends(get_provider)):
    return execute(body.request_id, "review", body.text, ReviewData, provider)


@api.post("/qa")
def qa(body: QARequest, provider: Provider = Depends(get_provider)):
    return execute(body.request_id, "qa", body.text, QAData, provider, body.question)


@api.post("/compare")
def compare(body: CompareRequest, provider: Provider = Depends(get_provider)):
    return execute(body.request_id, "comparison", f"PAPER A:\n{body.paper_a.text}\nPAPER B:\n{body.paper_b.text}", CompareData, provider)


app.include_router(api, dependencies=[Depends(authorize)])
