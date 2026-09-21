import requests

# Hit Analyze
payload = {
    "request_id": "00000000-0000-0000-0000-000000000000",
    "paper_id": 999,
    "text": "This is a test document on page 1.\n\fThis is page 2 discussing AlphaTest framework."
}
res = requests.post("http://127.0.0.1:8001/api/v1/analyze", json=payload, headers={"Authorization": "Bearer service_secret_token_12345"})
print("Analyze:", res.status_code)

# Hit QA
qa_payload = {
    "request_id": "00000000-0000-0000-0000-000000000001",
    "paper_id": 999,
    "question": "What framework is discussed on page 2?"
}
res_qa = requests.post("http://127.0.0.1:8001/api/v1/qa", json=qa_payload, headers={"Authorization": "Bearer service_secret_token_12345"})
print("QA:", res_qa.status_code)
try:
    print("Answer:", res_qa.json()["data"]["answer"])
    print("Evidence:", res_qa.json()["data"].get("evidence"))
except Exception as e:
    print("Error:", e)

# Hit QA with Paper B to check isolation
qa_payload2 = {
    "request_id": "00000000-0000-0000-0000-000000000002",
    "paper_id": 888, 
    "question": "What framework?"
}
res_qa2 = requests.post("http://127.0.0.1:8001/api/v1/qa", json=qa_payload2, headers={"Authorization": "Bearer service_secret_token_12345"})
print("QA Paper 888:", res_qa2.status_code, res_qa2.text)
