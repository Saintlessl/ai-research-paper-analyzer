import os
from google import genai
from app.settings import settings

client = genai.Client(api_key=settings().gemini_api_key.get_secret_value())
for m in client.models.list():
    if "embedding" in m.name:
        print(m.name, m.supported_actions)
