from typing import Literal
from uuid import UUID

from pydantic import BaseModel, Field, model_validator


QUALITY_CRITERIA = (
    "clarity",
    "methodological_rigor",
    "novelty",
    "validity",
    "reproducibility",
    "significance",
    "evidence_quality",
)


class Evidence(BaseModel):
    page: int | None = Field(default=None, ge=1)
    section: str | None = None
    chunk_id: str | None = None
    excerpt: str | None = Field(default=None, max_length=1000)
    confidence: float | None = Field(default=None, ge=0, le=1)


class Classification(BaseModel):
    paper_type: str | None = None
    research_domain: str | None = None
    reason: str | None = None
    evidence: list[Evidence] = Field(default_factory=list)


class StructureSection(BaseModel):
    heading: str
    purpose: str | None = None
    evidence: list[Evidence] = Field(default_factory=list)


class ResearchStructure(BaseModel):
    summary: str | None = None
    sections: list[StructureSection] = Field(default_factory=list)
    evidence: list[Evidence] = Field(default_factory=list)


class Methodology(BaseModel):
    research_problem: str | None = None
    research_questions: list[str] | None = None
    research_objective: str | None = None
    hypothesis: str | None = None
    study_design: str | None = None
    methods: list[str] | None = None
    dataset: str | None = None
    sample_size: int | None = Field(default=None, ge=0)
    evidence: list[Evidence] = Field(default_factory=list)


class Score(BaseModel):
    criterion: Literal[
        "clarity", "methodological_rigor", "novelty", "validity",
        "reproducibility", "significance", "evidence_quality",
    ]
    score: int = Field(ge=0, le=100)
    reason: str = Field(min_length=1)
    evidence: list[Evidence] = Field(default_factory=list)


class Finding(BaseModel):
    kind: Literal["finding", "limitation", "strength", "weakness"]
    severity: Literal["LOW", "MEDIUM", "HIGH", "CRITICAL"]
    category: str
    finding: str
    explanation: str | None = None
    evidence: list[Evidence] = Field(default_factory=list)
    confidence: float | None = Field(default=None, ge=0, le=1)


class CitationFinding(BaseModel):
    label: Literal["AI_SUSPECTED"] = "AI_SUSPECTED"
    finding: str
    reason: str
    evidence: list[Evidence] = Field(default_factory=list)
    confidence: float = Field(ge=0, le=1)


class AnalysisData(BaseModel):
    classification: Classification
    structure: ResearchStructure
    methodology: Methodology
    scores: list[Score]
    findings: list[Finding]
    limitations: list[Finding]
    strengths: list[Finding]
    weaknesses: list[Finding]
    keywords: list[str] = Field(default_factory=list)
    ai_suspected_citation_findings: list[CitationFinding] = Field(default_factory=list)

    @model_validator(mode="after")
    def exact_quality_scores(self):
        criteria = [score.criterion for score in self.scores]
        if len(criteria) != 7 or set(criteria) != set(QUALITY_CRITERIA):
            raise ValueError("scores must contain exactly seven unique quality criteria")
        for field, kind in (("findings", "finding"), ("limitations", "limitation"), ("strengths", "strength"), ("weaknesses", "weakness")):
            if any(item.kind != kind for item in getattr(self, field)):
                raise ValueError(f"{field} entries must use kind={kind}")
        return self


class ReviewData(BaseModel):
    summary: str
    strengths: list[str]
    major_concerns: list[str]
    minor_concerns: list[str]
    methodology_review: str
    novelty_review: str
    results_review: str
    reproducibility_review: str
    recommendation: Literal["ACCEPT", "MINOR_REVISION", "MAJOR_REVISION", "REJECT"]
    recommendation_reason: str
    evidence: list[Evidence]


class QAData(BaseModel):
    answer: str
    found: bool
    evidence: list[Evidence]


class CompareData(BaseModel):
    dimensions: list[dict]
    conclusion: str
    reasoning: str
    evidence: list[Evidence]


class Metadata(BaseModel):
    title: str
    authors: list[str]


class AnalyzeRequest(BaseModel):
    request_id: UUID
    paper_id: int
    text: str = Field(min_length=1)
    metadata: Metadata


class TextRequest(BaseModel):
    request_id: UUID
    paper_id: int
    text: str = Field(min_length=1)


class QARequest(TextRequest):
    question: str = Field(min_length=1)


class ComparePaper(BaseModel):
    paper_id: int
    text: str = Field(min_length=1)


class CompareRequest(BaseModel):
    request_id: UUID
    paper_a: ComparePaper
    paper_b: ComparePaper

class ReviewerCandidate(BaseModel):
    id: int
    name: str
    expertise: str | None = None

class RecommendRequest(BaseModel):
    request_id: UUID
    paper_title: str
    paper_abstract: str
    paper_keywords: list[str] = Field(default_factory=list)
    reviewers: list[ReviewerCandidate]

class Recommendation(BaseModel):
    reviewer_id: int
    reason: str
    confidence_score: float = Field(ge=0, le=1)

class RecommendResponse(BaseModel):
    recommendations: list[Recommendation]
