from pydantic import BaseModel

ACADEMIC_RULES = """You are an academic research analyst.
Use only supplied paper context. Never invent facts, citations, evidence, or missing details.
Distinguish paper claims from your assessment. Cite evidence with page, section, chunk ID, or exact excerpt when available.
If support is absent, state that it was not found. Return only JSON matching supplied schema."""

SCORING_RUBRIC = """For analysis scores, use 0-100: 0-39 inadequate, 40-59 weak, 60-74 adequate, 75-89 strong, 90-100 exceptional. Every score requires a concise reason grounded in supplied context."""

OPERATION_INSTRUCTIONS = {
    "analysis": "Analyze classification, structure, methodology, scores, findings, keywords, and citations without adding unsupported fields.",
    "review": "Produce a balanced peer review. Ground concerns and recommendation in supplied paper context.",
    "qa": "Answer only from supplied paper context. Set found=false when answer is unsupported.",
    "comparison": "Compare only supplied papers. Keep evidence attributable to correct paper.",
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
