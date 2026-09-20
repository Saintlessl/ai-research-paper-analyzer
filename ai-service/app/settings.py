from functools import lru_cache

from pydantic import Field, SecretStr, field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    internal_token: SecretStr
    gemini_api_key: SecretStr | None = None
    gemini_model: str = "gemini-2.5-flash"
    repair_attempts: int = Field(default=1, ge=0, le=5)
    chunk_size: int = Field(default=6000, gt=0)

    model_config = SettingsConfigDict(env_prefix="FASTAPI_", extra="ignore")

    @field_validator("internal_token")
    @classmethod
    def validate_internal_token(cls, value: SecretStr) -> SecretStr:
        if not value.get_secret_value().strip():
            raise ValueError("FASTAPI_INTERNAL_TOKEN must not be blank")
        return value


@lru_cache
def settings() -> Settings:
    return Settings()
