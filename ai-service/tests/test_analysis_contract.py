import json

import pytest
from pydantic import ValidationError

from app.schemas import AnalysisData
from app.services.prompts import build_prompt


CRITERIA = [
    "clarity", "methodological_rigor", "novelty", "validity",
    "reproducibility", "significance", "evidence_quality",
]


def evidence(**overrides):
    value = {
        "page": 2,
        "section": "Methods",
        "chunk_id": "chunk-0002",
        "excerpt": "We enrolled 120 participants.",
        "confidence": 0.91,
    }
    value.update(overrides)
    return value


def payload():
    return {
        "thought_process": "thinking",
        "classification": {
            "paper_type": "experimental",
            "research_domain": "medicine",
            "reason": "Reports a controlled experiment.",
            "evidence": [evidence()],
        },
        "structure": {
            "summary": "Standard empirical structure.",
            "sections": [{"heading": "Methods", "purpose": "Study design", "evidence": [evidence()]}],
            "evidence": [evidence()],
        },
        "methodology": {
            "research_problem": "Treatment effectiveness",
            "research_questions": ["Does treatment improve outcome?"],
            "research_objective": "Estimate treatment effect.",
            "hypothesis": None,
            "study_design": "Randomized trial",
            "methods": ["Random allocation"],
            "dataset": "Trial cohort",
            "sample_size": 120,
            "evidence": [evidence()],
        },
        "scores": [
            {"criterion": criterion, "score": 80, "reason": "Supported by reported methods.", "evidence": [evidence()]}
            for criterion in CRITERIA
        ],
        "findings": [{"kind": "finding", "severity": "LOW", "category": "results", "finding": "Treatment improved outcome.", "explanation": None, "evidence": [evidence()], "confidence": 0.8}],
        "limitations": [{"kind": "limitation", "severity": "MEDIUM", "category": "sampling", "finding": "Single-site sample.", "explanation": None, "evidence": [evidence()], "confidence": 0.9}],
        "strengths": [{"kind": "strength", "severity": "LOW", "category": "design", "finding": "Randomized design.", "explanation": None, "evidence": [evidence()], "confidence": 0.95}],
        "weaknesses": [{"kind": "weakness", "severity": "MEDIUM", "category": "generalisability", "finding": "Limited setting.", "explanation": None, "evidence": [evidence()], "confidence": 0.85}],
        "keywords": ["trial"],
    }


def test_analysis_contract_requires_exactly_seven_named_quality_scores():
    result = AnalysisData.model_validate(payload())
    assert [score.criterion for score in result.scores] == CRITERIA

    invalid = payload()
    invalid["scores"] = invalid["scores"][:-1]
    with pytest.raises(ValidationError):
        AnalysisData.model_validate(invalid)


def test_evidence_mapping_keeps_all_locator_fields_and_nullable_missing_values():
    value = payload()
    value["findings"][0]["evidence"] = [evidence(page=None, section=None, chunk_id=None, excerpt=None, confidence=None)]
    dumped = AnalysisData.model_validate(value).model_dump()
    assert dumped["findings"][0]["evidence"][0] == {
        "page": None, "section": None, "chunk_id": None, "excerpt": None, "confidence": None,
    }


def test_analysis_prompt_limits_scope_and_requires_not_found_nulls_and_evidence():
    prompt = build_prompt("analysis", "chunk context", AnalysisData)
    for phrase in ("exactly seven", "page", "section", "chunk_id", "excerpt", "confidence", "null", "not found"):
        assert phrase in prompt.lower()
    assert "do not perform peer review, comparison, or qa" in prompt.lower()
