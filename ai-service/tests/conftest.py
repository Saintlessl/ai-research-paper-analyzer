import os, pytest
os.environ['FASTAPI_INTERNAL_TOKEN']='test-token'
from app.main import app, get_provider

class FakeProvider:
    def __init__(self): self.responses=[]; self.calls=0
    def generate(self, prompt: str) -> str:
        self.calls += 1
        value=self.responses.pop(0)
        if isinstance(value, Exception): raise value
        return value

@pytest.fixture
def fake_provider():
    provider=FakeProvider()
    app.dependency_overrides[get_provider]=lambda: provider
    yield provider
    app.dependency_overrides.clear()
