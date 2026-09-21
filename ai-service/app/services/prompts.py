from pydantic import BaseModel

ACADEMIC_RULES = """You are an expert academic research analyst.
1. ABSOLUTE GROUNDING: Use ONLY the supplied paper context. Never invent facts, citations, evidence, or missing details.
2. EVIDENCE MAPPING: When providing evidence, you MUST cite the source exactly as provided in the context metadata tags (e.g., [page X | section: Y]). 
   - Extract the `page` number and `section` string directly from the context tags.
   - Set `chunk_id` if available.
   - Quote the exact text in `excerpt`.
3. If support is absent, state that it was not found and return empty/null evidence."""

SCORING_RUBRIC = """SCORING RUBRIC (0-100):
Evaluate objectively based on evidence quality, not arbitrary bounds.
- 90-100: Exceptional. Flawless methodology, highly reproducible, groundbreaking or definitive evidence.
- 75-89: Strong. Solid methodology with minor, non-critical gaps. Very reliable.
- 60-74: Adequate. Standard methodology but lacks depth, or has noticeable but acceptable limitations.
- 40-59: Weak. Noticeable flaws in design, missing crucial evidence, or unsupported claims.
- 0-39: Inadequate. Fundamentally flawed methodology, contradictory results, or severe lack of evidence."""

OPERATION_INSTRUCTIONS = {
    "analysis": """Analyze the classification, research structure, methodology, findings, limitations, strengths, weaknesses, keywords, and exactly seven quality scores (clarity, methodological_rigor, novelty, validity, reproducibility, significance, evidence_quality).
- Use `thought_process` to think step-by-step about the paper's overall quality and map out your findings BEFORE generating the structured lists and scores.
- Every claim and score MUST map evidence using the provided metadata. Preserve unavailable locator values as null.
- If methodology fields are missing, set them to null; never infer missing facts. Do not perform peer review, comparison, or QA.""",
    
    "review": """Produce a balanced peer review. Ground concerns and recommendations in the supplied paper context.
- Use `thought_process` to evaluate the paper's strengths vs weaknesses and decide on the final recommendation BEFORE filling out the rest of the fields.""",
    
    "qa": """Answer ONLY from the supplied paper context.
- Keep your answer direct and concise to ensure low latency. Do not over-explain.
- Set `found=false` and return an empty evidence list if the answer cannot be found in the context. Never hallucinate.""",
    
    "comparison": """Compare ONLY the supplied papers. Keep evidence attributable to the correct paper.
- Use `thought_process` to trace differences and similarities logically BEFORE generating the dimensions and conclusion.""",
    
    "recommendation": "Match the paper abstract and keywords against the provided list of reviewers. Recommend up to 3 best reviewers based on their expertise. Provide a clear reason for each recommendation.",
}


def build_prompt(operation: str, context: str, model: type[BaseModel], question: str | None = None) -> str:
    instruction = OPERATION_INSTRUCTIONS[operation]
    parts = [ACADEMIC_RULES, instruction]
    if operation == "analysis":
        parts.append(SCORING_RUBRIC)
    if question:
        parts.append(f"Question: {question}")
    parts.extend([f"JSON schema: {model.model_json_schema()}", f"Paper context:\n<context>{context}</context>"])
    return "\n\n".join(parts)
