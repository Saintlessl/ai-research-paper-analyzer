import pytest
from pydantic import ValidationError

from app.settings import Settings


def test_internal_token_must_not_be_blank():
    with pytest.raises(ValidationError):
        Settings(internal_token='   ')


def test_numeric_settings_reject_invalid_ranges():
    with pytest.raises(ValidationError):
        Settings(internal_token='token', repair_attempts=-1)
    with pytest.raises(ValidationError):
        Settings(internal_token='token', chunk_size=0)