from typing import Protocol
class Provider(Protocol):
 def generate(self,prompt:str)->str: ...
class GeminiProvider:
 def __init__(self,key:str|None,model:str): self.key=key; self.model=model
 def generate(self,prompt:str)->str:
  if not self.key: raise RuntimeError('Gemini is not configured')
  from google import genai
  return genai.Client(api_key=self.key).models.generate_content(model=self.model,contents=prompt).text
