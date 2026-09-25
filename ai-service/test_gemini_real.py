import asyncio
from app.services.gemini import GeminiProvider

from app.schemas import QAData, AnalysisData

import os
from dotenv import load_dotenv
import traceback
load_dotenv()

async def main():
    gemini = GeminiProvider(key=os.getenv("FASTAPI_GEMINI_API_KEY", "dummy"), model=os.getenv("FASTAPI_GEMINI_MODEL", "gemini-1.5-flash"))
    
    print("=== TEST 1: QA HALUSINASI ===")
    prompt = "Apa resep masakan nasi goreng yang enak?"
    context = "Paper ini membahas tentang penggunaan algoritma Random Forest untuk klasifikasi citra medis kanker paru-paru. Tingkat akurasi mencapai 95%."
    
    try:
        response = gemini.generate(
            prompt=f"Context:\n{context}\n\nQuestion:\n{prompt}",
            schema=QAData.model_json_schema()
        )
        print(response)
    except Exception as e:
        print("QA Error:")
        traceback.print_exc()

    print("\n=== TEST 2: SCORING ANCHOR ===")
    # Test scoring variations based on context
    
    # 1. Good paper
    good_prompt = "Lakukan analisis pada paper berikut."
    good_context = """
    Metodologi: Kami menggunakan dataset ImageNet dengan 1.2 juta gambar yang dibagi secara ketat (train/val/test 80/10/10).
    Eksperimen diulang 5 kali dengan seed berbeda untuk memastikan signifikansi statistik (p < 0.05).
    Akurasi model mencapai 98%, lebih baik 5% dari state-of-the-art.
    Source Code dan model weights tersedia di GitHub.
    """
    
    try:
        response1 = gemini.generate(
            prompt=f"Context:\n{good_context}\n\nInstruction:\n{good_prompt}",
            schema=AnalysisData.model_json_schema()
        )
        print("\n--- GOOD PAPER SCORE ---")
        print(response1)
    except Exception as e:
        print("Score 1 Error:")
        traceback.print_exc()
        
    # 2. Bad paper
    bad_prompt = "Lakukan analisis pada paper berikut."
    bad_context = """
    Metodologi: Kami mengumpulkan 15 gambar dari Google Images.
    Model dilatih selama 2 epoch. Tidak ada validasi dataset yang digunakan.
    Hasilnya bagus dan model kami berhasil mendeteksi objek dengan baik.
    Karena keterbatasan memori, kami tidak menyimpan kode eksperimennya.
    """
    
    try:
        response2 = gemini.generate(
            prompt=f"Context:\n{bad_context}\n\nInstruction:\n{bad_prompt}",
            schema=AnalysisData.model_json_schema()
        )
        print("\n--- WEAK PAPER SCORE ---")
        print(response2)
    except Exception as e:
        print("Score 2 Error:")
        traceback.print_exc()

if __name__ == "__main__":
    asyncio.run(main())
