import re
from collections import Counter

from .pipeline import Chunk, Document, ProcessedDocument, detect_sections


YEAR_RE = re.compile(r"\b((?:19|20)\d{2})\b")
DOI_RE = re.compile(r"(?:https?://(?:dx\.)?doi\.org/|doi:\s*)(10\.\d{4,9}/[-._;()/:A-Z0-9]+)", re.I)
URL_RE = re.compile(r"https?://[^\s]+", re.I)
NUMERIC_CITATION_RE = re.compile(r"\[([\d\s,;\-–]+)\]")
AUTHOR_YEAR_RE = re.compile(r"(?:\b([A-Z][A-Za-z'’-]+)(?:\s+(?:&|and)\s+[A-Z][A-Za-z'’-]+)?\s*\(((?:19|20)\d{2})\)|\(([A-Z][A-Za-z'’-]+)(?:\s+(?:&|and)\s+[A-Z][A-Za-z'’-]+)?[,]?\s*((?:19|20)\d{2})\))")


def _numeric_keys(value: str) -> list[str]:
    keys: set[int] = set()
    for part in re.split(r"[,;]\s*", value):
        part = part.strip()
        range_match = re.fullmatch(r"(\d+)\s*[-–]\s*(\d+)", part)
        if range_match:
            start, end = map(int, range_match.groups())
            keys.update(range(min(start, end), max(start, end) + 1))
        elif part.isdigit():
            keys.add(int(part))
    return [str(key) for key in sorted(keys)]


def _locator(document: Document | ProcessedDocument, chunks: tuple[Chunk, ...], start: int, excerpt: str) -> dict:
    page = next((page.number for page in document.pages if page.char_start <= start <= page.char_end), None)
    chunk = next((chunk for chunk in chunks if chunk.char_start <= start < chunk.char_end), None)
    sections = document.sections if isinstance(document, ProcessedDocument) else detect_sections(document)
    section = next((item.heading for item in sections if item.char_start <= start < item.char_end), None)
    return {"page": page, "section": section, "chunk_id": chunk.chunk_id if chunk else None, "excerpt": excerpt[:1000], "confidence": 1.0}


def _entries(document: Document | ProcessedDocument) -> tuple[list[tuple[str, int]], int]:
    sections = document.sections if isinstance(document, ProcessedDocument) else detect_sections(document)
    reference = next((item for item in sections if item.heading.lower() in {"references", "bibliography"}), None)
    if not reference:
        return [], len(document.text)
    body = document.text[reference.char_start:reference.char_end]
    heading_end = body.find("\n") + 1
    content_start = reference.char_start + max(heading_end, 0)
    entries: list[tuple[str, int]] = []
    cursor = content_start
    current = ""
    current_start = cursor
    for line in document.text[content_start:reference.char_end].splitlines(keepends=True):
        stripped = line.strip()
        starts = bool(re.match(r"^(?:\[?\d+\]?[.)]?\s+|[A-Z][A-Za-z'’-]+,)", stripped))
        if starts and current:
            entries.append((current.strip(), current_start))
            current, current_start = stripped, cursor
        elif stripped:
            if not current:
                current_start = cursor
            current = f"{current} {stripped}".strip()
        cursor += len(line)
    if current:
        entries.append((current.strip(), current_start))
    return entries, reference.char_start


def analyze_references(document: Document | ProcessedDocument, chunks: tuple[Chunk, ...], recent_year_cutoff: int) -> dict:
    entries, bibliography_start = _entries(document)
    body = document.text[:bibliography_start]
    numeric_matches = list(NUMERIC_CITATION_RE.finditer(body))
    numeric_keys = [key for match in numeric_matches for key in _numeric_keys(match.group(1))]
    author_matches = list(AUTHOR_YEAR_RE.finditer(body))
    author_keys = [f"{(match.group(1) or match.group(3)).lower()}-{match.group(2) or match.group(4)}" for match in author_matches]
    citations = Counter(numeric_keys + author_keys)
    references = []
    for index, (raw, start) in enumerate(entries, 1):
        number = re.match(r"^\[?(\d+)\]?[.)]?\s+", raw)
        year = YEAR_RE.search(raw)
        surname = re.match(r"^(?:\[?\d+\]?[.)]?\s+)?([A-Z][A-Za-z'’-]+)", raw)
        key = number.group(1) if number else (f"{surname.group(1).lower()}-{year.group(1)}" if surname and year else str(index))
        doi = DOI_RE.search(raw)
        url = URL_RE.search(raw)
        normalized = re.sub(r"^\[?\d+\]?[.)]?\s+", "", raw)
        title_match = re.search(r"\(?(?:19|20)\d{2}\)?\.\s+(.+?)\.\s+", normalized)
        title = title_match.group(1) if title_match else None
        author_match = re.match(r"([A-Z][A-Za-z'’-]+,\s*(?:[A-Z](?:\.|\b)))", normalized)
        references.append({
            "raw_text": raw, "citation_key": key, "title": title,
            "authors": [author_match.group(1).rstrip(".")] if author_match else ([surname.group(1)] if surname else []),
            "publication_year": int(year.group(1)) if year else None,
            "doi": doi.group(1).rstrip(".,") if doi else None,
            "url": url.group(0).rstrip(".,") if url else None,
            "citation_count": citations[key], "cited_in_text": citations[key] > 0,
            "issues": [], "evidence": [_locator(document, chunks, start, raw)],
        })
    bibliography_keys = {item["citation_key"] for item in references}
    cited_keys = set(citations)
    years = sorted(item["publication_year"] for item in references if item["publication_year"] is not None)
    irrelevant = [{"citation_key": item["citation_key"], "label": "AI_SUSPECTED", "reason": "potentially irrelevant title pattern; requires human review", "evidence": item["evidence"]} for item in references if "unrelated" in (item["title"] or "").lower()]
    return {
        "total_references": len(references), "publication_years": years,
        "recent_year_cutoff": recent_year_cutoff,
        "recent_count": sum(year >= recent_year_cutoff for year in years),
        "older_count": sum(year < recent_year_cutoff for year in years),
        "citation_patterns": {"numeric_bracket": len(numeric_matches), "author_year": len(author_matches)},
        "in_text_citations_missing_from_bibliography": sorted(cited_keys - bibliography_keys),
        "bibliography_entries_apparently_uncited": sorted(bibliography_keys - cited_keys),
        "potentially_irrelevant_patterns": irrelevant,
        "references": references, "method": "deterministic_heuristic",
    }


def analyze_text_references(text: str, current_year: int) -> dict:
    """Analyze plain/chunk-annotated text with stable API field names."""
    marker_re = re.compile(r"^\[(chunk-\d+); page (\d+); section ([^\]]+)\]\s*$", re.MULTILINE)
    markers = list(marker_re.finditer(text))
    cleaned = marker_re.sub("", text)
    from .pipeline import _document_from_pages, chunk_document

    document = _document_from_pages([cleaned])
    result = analyze_references(document, chunk_document(document), current_year - 5)
    for reference in result["references"]:
        evidence = reference["evidence"][0]
        raw_position = text.find(reference["raw_text"])
        marker = next((item for item in reversed(markers) if item.start() <= raw_position), None)
        evidence = {
            "page": int(marker.group(2)) if marker else None,
            "section": marker.group(3) if marker else "References",
            "chunk_id": marker.group(1) if marker else None,
            "excerpt": reference["raw_text"],
            "confidence": 1.0,
        }
        reference["evidence"] = [evidence]
    irrelevant = []
    for reference in result["references"]:
        if re.search(r"\b(?:personal blog|blogspot|recipes?)\b", reference["raw_text"], re.I):
            irrelevant.append({
                "citation_key": reference["citation_key"], "label": "AI_SUSPECTED",
                "reason": "non-scholarly source pattern", "evidence": reference["evidence"],
            })
    years = result["publication_years"]
    return result | {
        "publication_years": {str(year): years.count(year) for year in sorted(set(years))},
        "recent_reference_count": result["recent_count"],
        "older_reference_count": result["older_count"],
        "citation_patterns": {
            "numeric": sum(len(_numeric_keys(match.group(1))) for match in NUMERIC_CITATION_RE.finditer(cleaned.split("References", 1)[0])),
            "author_year": result["citation_patterns"]["author_year"],
        },
        "citations_missing_from_bibliography": result["in_text_citations_missing_from_bibliography"],
        "apparently_uncited_references": result["bibliography_entries_apparently_uncited"],
        "potentially_irrelevant_references": irrelevant,
    }
