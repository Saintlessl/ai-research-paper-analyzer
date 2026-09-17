import json
from fastapi.testclient import TestClient
from app.main import app
from app.services.pipeline import clean_text, detect_sections, chunk_pages, analyze_citations, parse_validated
from app.schemas import AnalysisData

client = TestClient(app)
HEADERS = {"Authorization": "Bearer test-token"}

def request(operation, body):
    return client.post(f"/api/v1/{operation}", headers=HEADERS, json=body)

def test_health_is_public():
    assert client.get('/health').json() == {"status": "ok"}

def test_internal_endpoints_require_constant_time_bearer_auth():
    assert client.post('/api/v1/analyze', json={}).status_code == 401
    assert client.post('/api/v1/analyze', headers={"Authorization":"Bearer wrong"}, json={}).status_code == 401

def test_analyze_contract_and_validation(fake_provider):
    fake_provider.responses.append(json.dumps({"classification":{"paper_type":"Experimental","research_domain":"Computer Science"},"structure":{},"methodology":{},"scores":[],"findings":[],"keywords":["AI"],"citation_analysis":{}}))
    response=request('analyze', {"request_id":"123e4567-e89b-12d3-a456-426614174000","paper_id":1,"text":"Abstract\nA study.","metadata":{"title":"Paper","authors":[]}})
    assert response.status_code == 200
    assert response.json()["data"]["keywords"] == ["AI"]

def test_review_qa_compare_contracts(fake_provider):
    fake_provider.responses.extend([
      json.dumps({"summary":"s","strengths":[],"major_concerns":[],"minor_concerns":[],"methodology_review":"m","novelty_review":"n","results_review":"r","reproducibility_review":"x","recommendation":"ACCEPT","recommendation_reason":"ok","evidence":[]}),
      json.dumps({"answer":"Informasi tersebut tidak ditemukan dalam paper.","found":False,"evidence":[]}),
      json.dumps({"dimensions":[],"conclusion":"A and B differ","reasoning":"evidence","evidence":[]})])
    common={"request_id":"123e4567-e89b-12d3-a456-426614174000"}
    assert request('review', common|{"paper_id":1,"text":"x"}).status_code==200
    assert request('qa', common|{"paper_id":1,"text":"x","question":"dataset?"}).json()["data"]["found"] is False
    assert request('compare', common|{"paper_a":{"paper_id":1,"text":"a"},"paper_b":{"paper_id":2,"text":"b"}}).status_code==200

def test_invalid_json_is_repaired_once(fake_provider):
    fake_provider.responses.extend(['not json', json.dumps({"classification":{},"structure":{},"methodology":{},"scores":[],"findings":[],"keywords":[],"citation_analysis":{}})])
    response=request('analyze', {"request_id":"123e4567-e89b-12d3-a456-426614174000","paper_id":1,"text":"x","metadata":{"title":"x","authors":[]}})
    assert response.status_code==200 and fake_provider.calls==2

def test_pipeline_preserves_pages_sections_chunks_and_citations():
    pages=[" Abstract  text ","METHODS\nUsed data [1].\nReferences\n[1] Smith 2024."]
    assert clean_text(pages[0]) == "Abstract text"
    sections=detect_sections(pages)
    assert sections[-1].heading == "References"
    chunks=chunk_pages(pages, 30)
    assert chunks[0].page_start == 1 and chunks[-1].page_end == 2
    citations=analyze_citations("Used data [1].\nReferences\n[1] Smith 2024.")
    assert citations["total_references"] == 1

def test_schema_rejects_unexplained_or_out_of_range_scores():
    payload={"classification":{},"structure":{},"methodology":{},"scores":[{"criterion":"clarity","score":101,"reason":""}],"findings":[],"keywords":[],"citation_analysis":{}}
    try: parse_validated(json.dumps(payload), AnalysisData)
    except ValueError: pass
    else: raise AssertionError('invalid score accepted')
