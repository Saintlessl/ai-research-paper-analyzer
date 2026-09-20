import hmac
import json
import logging
from time import perf_counter
from typing import Annotated
from uuid import uuid4

from fastapi import APIRouter, Depends, FastAPI, Header, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse

from .schemas import AnalysisData, AnalyzeRequest, CompareData, CompareRequest, QAData, QARequest, ReviewData, TextRequest
from .services.gemini import GeminiProvider, Provider
from .services.pipeline import parse_validated
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
    return GeminiProvider(api_key, config.gemini_model)


SYSTEM = "You are an academic research analyst. Use only supplied context. Never invent missing information. Include evidence and return only JSON matching the requested schema."


def execute(request_id, kind, text, model, provider):
    prompt = f"{SYSTEM}\nOperation: {kind}\nSchema: {json.dumps(model.model_json_schema())}\nContext: {text}"
    for attempt in range(settings().repair_attempts + 1):
        try:
            result = parse_validated(provider.generate(prompt if attempt == 0 else prompt + "\nRepair the prior invalid response."), model)
            return {"success": True, "request_id": str(request_id), "data": result.model_dump(mode="json")}
        except (ValueError, RuntimeError):
            logger.warning("ai_attempt_failed request_id=%s operation=%s attempt=%s", request_id, kind, attempt + 1, exc_info=True)
    raise HTTPException(502, detail={"code": "AI_PROCESSING_FAILED", "message": "Unable to process request"})


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
    return execute(body.request_id, "qa: " + body.question, body.text, QAData, provider)


@api.post("/compare")
def compare(body: CompareRequest, provider: Provider = Depends(get_provider)):
    return execute(body.request_id, "comparison", f"PAPER A:\n{body.paper_a.text}\nPAPER B:\n{body.paper_b.text}", CompareData, provider)


app.include_router(api, dependencies=[Depends(authorize)])
