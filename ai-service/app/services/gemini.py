from typing import Any, Protocol


class ProviderError(RuntimeError):
    """Sanitized provider failure safe for internal classification."""

    def __init__(self, code: str):
        self.code = code
        super().__init__("AI provider request failed")


class Provider(Protocol):
    def generate(self, prompt: str, schema: dict[str, Any]) -> str: ...


class GeminiProvider:
    def __init__(
        self,
        key: str | None,
        model: str,
        timeout_seconds: float = 30,
        client: Any | None = None,
    ):
        self.key = key
        self.model = model
        self.timeout_seconds = timeout_seconds
        self._client = client

    def _build_client(self):
        if not self.key:
            raise ProviderError("PROVIDER_NOT_CONFIGURED")
        from google import genai
        from google.genai import types

        return genai.Client(
            api_key=self.key,
            http_options=types.HttpOptions(timeout=int(self.timeout_seconds * 1000)),
        )

    @staticmethod
    def _classify(exc: Exception) -> str:
        text = str(exc).lower()
        status = getattr(exc, "status_code", None) or getattr(exc, "code", None)
        if isinstance(exc, TimeoutError) or "timeout" in text or "deadline" in text:
            return "PROVIDER_TIMEOUT"
        if status == 429 or "429" in text or "quota" in text or "resource_exhausted" in text:
            return "PROVIDER_QUOTA"
        if status in (401, 403) or any(token in text for token in ("401", "403", "api key", "unauthenticated", "permission_denied")):
            return "PROVIDER_AUTH"
        return "PROVIDER_UNAVAILABLE"

    def generate(self, prompt: str, schema: dict[str, Any]) -> str:
        try:
            client = self._client or self._build_client()
            from google.genai import types

            response = client.models.generate_content(
                model=self.model,
                contents=prompt,
                config=types.GenerateContentConfig(
                    response_mime_type="application/json",
                    response_json_schema=schema,
                ),
            )
            if not response.text:
                raise ProviderError("PROVIDER_MALFORMED_OUTPUT")
            return response.text
        except ProviderError:
            raise
        except Exception as exc:
            raise ProviderError(self._classify(exc)) from exc
