import os
import json
import uuid
import time
from app.services.vector_store import store_paper_chunks, search_relevant_chunks
from app.services.pipeline import Chunk
from app.services.gemini import GeminiProvider
from app.schemas import QAData
from app.settings import settings
from app.services.prompts import build_prompt

provider = GeminiProvider(settings().gemini_api_key.get_secret_value(), settings().gemini_model)

chunks111 = [
    Chunk(chunk_id=str(uuid.uuid4()), page_start=1, page_end=1, section="", text="This is page 1."),
    Chunk(chunk_id=str(uuid.uuid4()), page_start=2, page_end=2, section="", text="...paper ini menggunakan dataset CIFAR-10 untuk eksperimen klasifikasi gambar..."),
    Chunk(chunk_id=str(uuid.uuid4()), page_start=3, page_end=3, section="", text="This is page 3.")
]
chunks222 = [
    Chunk(chunk_id=str(uuid.uuid4()), page_start=1, page_end=2, section="", text="This is page 1.\nThis is page 2."),
    Chunk(chunk_id=str(uuid.uuid4()), page_start=3, page_end=3, section="", text="...paper ini menggunakan dataset MNIST untuk deteksi tulisan tangan...")
]

def safe_generate(prompt, schema):
    for i in range(5):
        try:
            return provider.generate(prompt, schema)
        except Exception as e:
            print("Retry", i+1, "due to", str(e))
            time.sleep(2)
    return None

store_paper_chunks(111, chunks111, provider)
store_paper_chunks(222, chunks222, provider)

print("--- QUERY 1: Dataset di Paper 111 ---")
q1 = "Dataset apa yang dipakai di paper ini untuk eksperimen?"
context1 = search_relevant_chunks(111, q1, provider)
p1 = build_prompt("qa", context1, QAData, q1)
result1 = safe_generate(p1, QAData.model_json_schema())
print("JSON RESPONSE:")
print(result1)

print("\n--- QUERY 2: Cross-contamination check (Paper 111 vs Paper 222) ---")
q2 = "Bagaimana metodologi deteksi tulisan tangannya?"
context2 = search_relevant_chunks(111, q2, provider)
p2 = build_prompt("qa", context2, QAData, q2)
result2 = safe_generate(p2, QAData.model_json_schema())
print("JSON RESPONSE:")
print(result2)

