from functools import lru_cache
from pydantic_settings import BaseSettings, SettingsConfigDict
class Settings(BaseSettings):
    internal_token: str
    gemini_api_key: str|None=None
    gemini_model: str='gemini-2.5-flash'
    repair_attempts: int=1
    chunk_size: int=6000
    model_config=SettingsConfigDict(env_prefix='FASTAPI_', extra='ignore')
@lru_cache
def settings(): return Settings()
