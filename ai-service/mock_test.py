import json
from unittest.mock import MagicMock
from app.services.vector_store import store_paper_chunks, search_relevant_chunks
from app.services.pipeline import Document, Chunk
from app.services.gemini import GeminiProvider
from app.schemas import QAData, Evidence
import uuid

provider = MagicMock(spec=GeminiProvider)
def fake_embed(texts): return [[0.1]*768 for _ in texts]
provider.embed_content.side_effect = fake_embed

chunks111 = [
    Chunk(chunk_id=str(uuid.uuid4()), page_start=1, page_end=1, section="", text="This is page 1."),
    Chunk(chunk_id=str(uuid.uuid4()), page_start=2, page_end=2, section="", text="...paper ini menggunakan dataset CIFAR-10 untuk eksperimen klasifikasi gambar..."),
    Chunk(chunk_id=str(uuid.uuid4()), page_start=3, page_end=3, section="", text="This is page 3.")
]
chunks222 = [
    Chunk(chunk_id=str(uuid.uuid4()), page_start=1, page_end=2, section="", text="This is page 1.\nThis is page 2."),
    Chunk(chunk_id=str(uuid.uuid4()), page_start=3, page_end=3, section="", text="...paper ini menggunakan dataset MNIST untuk deteksi tulisan tangan...")
]

store_paper_chunks(111, chunks111, provider)
store_paper_chunks(222, chunks222, provider)

def fake_generate(prompt, schema):
    if paper_id == 111 and "Dataset apa" in prompt:
        return QAData(
            answer="Dataset yang digunakan untuk eksperimen klasifikasi gambar adalah CIFAR-10.",
            found=True,
            evidence=[Evidence(page=2, section="unknown", chunk_id=chunks111[1].chunk_id, excerpt="paper ini menggunakan dataset CIFAR-10", confidence=0.99)]
        ).model_dump_json()
    elif paper_id == 111 and "Bagaimana metodologi" in prompt:
         return QAData(
            answer="Tidak ditemukan metodologi deteksi tulisan tangan dalam dokumen ini.",
            found=False,
            evidence=[]
        ).model_dump_json()
    return "{}"
provider.generate.side_effect = fake_generate

print("--- QUERY 1: Dataset di Paper 111 ---")
paper_id = 111
q1 = "Dataset apa yang dipakai di paper ini untuk eksperimen?"
context1 = search_relevant_chunks(111, q1, provider)
print("CONTEXT INJECTED TO PROMPT:")
print(context1)
print("\nJSON RESPONSE:")
from app.services.prompts import build_prompt
p1 = build_prompt("qa", context1, QAData, q1)
print(provider.generate(p1, QAData.model_json_schema()))

print("\n--- QUERY 2: Cross-contamination check (Paper 111 vs Paper 222) ---")
q2 = "Bagaimana metodologi deteksi tulisan tangannya?"
context2 = search_relevant_chunks(111, q2, provider)
print("CONTEXT INJECTED TO PROMPT:")
print(context2)
print("\nJSON RESPONSE:")
p2 = build_prompt("qa", context2, QAData, q2)
print(provider.generate(p2, QAData.model_json_schema()))

