import json
from unittest.mock import Mock

import pytest
from app.schemas import QAData
from app.services.gemini import GeminiProvider, ProviderError
from app.services.structured_output import StructuredOutputError, generate_structured


class StubProvider:
    def __init__(self, responses):
        self.responses = list(responses)
        self.requests = []

    def generate(self, prompt: str, schema: dict) -> str:
        self.requests.append((prompt, schema))
        value = self.responses.pop(0)
        if isinstance(value, Exception):
            raise value
        return value


def test_structured_generation_validates_response_against_pydantic_schema():
    provider = StubProvider([json.dumps({"answer": "Supported", "found": True, "evidence": []})])

    result = generate_structured(provider, "prompt", QAData, repair_attempts=1)

    assert result.answer == "Supported"
    assert provider.requests[0][1] == QAData.model_json_schema()


def test_invalid_output_repair_contains_output_and_validation_errors():
    provider = StubProvider([
        '{"answer": 3, "found": "maybe", "evidence": []}',
        json.dumps({"answer": "Not found", "found": False, "evidence": []}),
    ])

    result = generate_structured(provider, "original", QAData, repair_attempts=1)

    assert result.found is False
    repair_prompt = provider.requests[1][0]
    assert "original" in repair_prompt
    assert '"found": "maybe"' in repair_prompt
    assert "validation errors" in repair_prompt.lower()
    assert len(provider.requests) == 2


def test_repair_is_finite_and_reports_malformed_output_generically():
    provider = StubProvider(["not-json", "still-not-json"])

    with pytest.raises(StructuredOutputError, match="invalid structured response"):
        generate_structured(provider, "prompt", QAData, repair_attempts=1)

    assert len(provider.requests) == 2


def test_gemini_provider_requests_json_schema_without_live_call():
    response = Mock(text='{"answer":"x","found":true,"evidence":[]}')
    models = Mock()
    models.generate_content.return_value = response
    client = Mock(models=models)
    provider = GeminiProvider("secret", "gemini-test", timeout_seconds=12, client=client)

    raw = provider.generate("prompt", QAData.model_json_schema())

    assert raw == response.text
    call = models.generate_content.call_args
    assert call.kwargs["model"] == "gemini-test"
    assert call.kwargs["contents"] == "prompt"
    config = call.kwargs["config"]
    assert config.response_mime_type == "application/json"
    assert config.response_json_schema == QAData.model_json_schema()


@pytest.mark.parametrize(
    "upstream,code",
    [
        (TimeoutError("secret timeout detail"), "PROVIDER_TIMEOUT"),
        (Exception("429 quota secret"), "PROVIDER_QUOTA"),
        (Exception("401 invalid api key secret"), "PROVIDER_AUTH"),
    ],
)
def test_gemini_provider_classifies_errors_without_leaking_details(upstream, code):
    models = Mock()
    models.generate_content.side_effect = upstream
    provider = GeminiProvider("secret", "gemini-test", client=Mock(models=models))

    with pytest.raises(ProviderError) as caught:
        provider.generate("prompt", QAData.model_json_schema())

    assert caught.value.code == code
    assert "secret" not in str(caught.value)
