import requests
import json

base_url = "http://127.0.0.1:8001/api/v1"
headers = {"Authorization": "Bearer local-dev-token"}

# Paper 111
p111 = {
    "request_id": "00000000-0000-0000-0000-000000000111",
    "paper_id": 111,
    "text": "This is page 1.\n\f...paper ini menggunakan dataset CIFAR-10 untuk eksperimen klasifikasi gambar...\n\fThis is page 3."
}
print("Indexing Paper 111...")
res1 = requests.post(f"{base_url}/analyze", json=p111, headers=headers)
print("Paper 111 status:", res1.status_code)

# Paper 222
p222 = {
    "request_id": "00000000-0000-0000-0000-000000000222",
    "paper_id": 222,
    "text": "This is page 1.\n\fThis is page 2.\n\f...paper ini menggunakan dataset MNIST untuk deteksi tulisan tangan..."
}
print("Indexing Paper 222...")
res2 = requests.post(f"{base_url}/analyze", json=p222, headers=headers)
print("Paper 222 status:", res2.status_code)

# Query 1
print("\n--- QUERY 1: Dataset di Paper 111 ---")
q1 = {
    "request_id": "10000000-0000-0000-0000-000000000001",
    "paper_id": 111,
    "question": "Dataset apa yang dipakai di paper ini untuk eksperimen?"
}
res_q1 = requests.post(f"{base_url}/qa", json=q1, headers=headers)
data1 = res_q1.json().get("data", {})
print("Answer:", data1.get("answer"))
print("Evidence:", json.dumps(data1.get("evidence"), indent=2))

# Query 2
print("\n--- QUERY 2: Cross-contamination check ---")
q2 = {
    "request_id": "20000000-0000-0000-0000-000000000002",
    "paper_id": 111,
    "question": "Bagaimana metodologi deteksi tulisan tangannya?"
}
res_q2 = requests.post(f"{base_url}/qa", json=q2, headers=headers)
data2 = res_q2.json().get("data", {})
print("Answer:", data2.get("answer"))
print("Found:", data2.get("found"))
print("Evidence:", json.dumps(data2.get("evidence"), indent=2))

