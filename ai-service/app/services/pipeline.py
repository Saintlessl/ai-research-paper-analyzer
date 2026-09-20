import json
import re
from dataclasses import dataclass
from io import BytesIO
from typing import TypeVar

from pydantic import BaseModel, ValidationError
from pypdf import PdfReader
from pypdf.errors import PdfReadError

T = TypeVar("T", bound=BaseModel)


class DocumentProcessingError(ValueError):
    """PDF cannot be processed safely."""


@dataclass(frozen=True)
class Page:
    number: int
    text: str
    char_start: int
    char_end: int


@dataclass(frozen=True)
class Document:
    text: str
    pages: tuple[Page, ...]


@dataclass(frozen=True)
class Section:
    heading: str
    content: str
    page: int
    char_start: int = 0
    char_end: int = 0


@dataclass(frozen=True)
class Chunk:
    chunk_id: str
    text: str
    page_start: int
    page_end: int
    section: str | None = None
    char_start: int = 0
    char_end: int = 0


@dataclass(frozen=True)
class ChunkingConfig:
    max_chars: int = 6000
    overlap_chars: int = 300

    def __post_init__(self) -> None:
        if self.max_chars <= 0:
            raise ValueError("max_chars must be positive")
        if self.overlap_chars < 0 or self.overlap_chars >= self.max_chars:
            raise ValueError("overlap_chars must be non-negative and less than max_chars")


@dataclass(frozen=True)
class ProcessedDocument:
    text: str
    pages: tuple[Page, ...]
    sections: tuple[Section, ...]
    chunks: tuple[Chunk, ...]


HEADINGS = {
    "abstract", "introduction", "background", "related work", "literature review",
    "methods", "methodology", "materials and methods", "results", "discussion",
    "conclusion", "conclusions", "references", "bibliography", "limitations", "acknowledgements",
}
HEADING_RE = re.compile(r"^\s*(?:(?:\d+(?:\.\d+)*)[.)]?\s+)?(.+?)\s*:?[\s]*$", re.IGNORECASE)


def clean_text(text: str) -> str:
    """Normalize extraction noise while retaining meaningful line boundaries."""
    text = text.replace("\r\n", "\n").replace("\r", "\n").replace("\x00", "")
    lines = [re.sub(r"[\t \f\v]+", " ", line).strip() for line in text.split("\n")]
    output: list[str] = []
    blank = False
    for line in lines:
        if line:
            output.append(line)
            blank = False
        elif output and not blank:
            output.append("")
            blank = True
    return "\n".join(output).strip()


def _heading(line: str) -> str | None:
    match = HEADING_RE.fullmatch(line)
    if not match:
        return None
    candidate = match.group(1).strip().rstrip(":")
    return candidate if candidate.lower() in HEADINGS else None


def _document_from_pages(raw_pages: list[str]) -> Document:
    cleaned = [clean_text(page) for page in raw_pages]
    text = "\n\n".join(cleaned)
    pages: list[Page] = []
    offset = 0
    for number, page_text in enumerate(cleaned, 1):
        pages.append(Page(number, page_text, offset, offset + len(page_text)))
        offset += len(page_text) + (2 if number < len(cleaned) else 0)
    return Document(text, tuple(pages))


def extract_pdf(data: bytes) -> Document:
    if not data or not data.startswith(b"%PDF-"):
        raise DocumentProcessingError("PDF is malformed or unreadable")
    try:
        reader = PdfReader(BytesIO(data), strict=True)
        if reader.is_encrypted:
            raise DocumentProcessingError("encrypted PDFs are not supported")
        pages = [page.extract_text() or "" for page in reader.pages]
    except DocumentProcessingError:
        raise
    except (PdfReadError, OSError, ValueError, TypeError, KeyError) as exc:
        raise DocumentProcessingError("PDF is malformed or unreadable") from exc
    document = _document_from_pages(pages)
    if not document.text.strip():
        raise DocumentProcessingError("PDF contains no extractable text")
    return document


def detect_sections(pages: list[str] | Document) -> list[Section]:
    document = pages if isinstance(pages, Document) else _document_from_pages(pages)
    matches: list[tuple[str, int, int]] = []
    for page in document.pages:
        cursor = page.char_start
        for line in page.text.splitlines(keepends=True):
            value = line.rstrip("\n")
            heading = _heading(value)
            if heading:
                matches.append((heading, cursor, page.number))
            cursor += len(line)
    sections: list[Section] = []
    for index, (heading, start, page) in enumerate(matches):
        end = matches[index + 1][1] if index + 1 < len(matches) else len(document.text)
        sections.append(Section(heading, document.text[start:end].strip(), page, start, end))
    return sections


def _page_at(document: Document, position: int) -> int:
    for page in document.pages:
        if position <= page.char_end:
            return page.number
    return document.pages[-1].number


def _chunk_ranges(text: str, start: int, end: int, config: ChunkingConfig):
    cursor = start
    while cursor < end:
        proposed = min(cursor + config.max_chars, end)
        chunk_end = proposed
        if proposed < end:
            boundary = max(text.rfind("\n", cursor + 1, proposed + 1), text.rfind(" ", cursor + 1, proposed + 1))
            if boundary > cursor:
                chunk_end = boundary
        while cursor < chunk_end and text[cursor].isspace():
            cursor += 1
        while chunk_end > cursor and text[chunk_end - 1].isspace():
            chunk_end -= 1
        if chunk_end > cursor:
            yield cursor, chunk_end
        if proposed >= end:
            break
        next_cursor = max(chunk_end - config.overlap_chars, cursor + 1)
        while next_cursor < chunk_end and not text[next_cursor - 1].isspace():
            next_cursor += 1
        cursor = min(next_cursor, chunk_end)


def chunk_document(document: Document, config: ChunkingConfig = ChunkingConfig()) -> tuple[Chunk, ...]:
    sections = detect_sections(document)
    regions = [(section.char_start, section.char_end, section.heading) for section in sections]
    if not regions:
        regions = [(page.char_start, page.char_end, None) for page in document.pages if page.text]
    chunks: list[Chunk] = []
    for start, end, section in regions:
        for chunk_start, chunk_end in _chunk_ranges(document.text, start, end, config):
            chunks.append(Chunk(
                chunk_id=f"chunk-{len(chunks) + 1:04d}",
                text=document.text[chunk_start:chunk_end],
                page_start=_page_at(document, chunk_start),
                page_end=_page_at(document, max(chunk_start, chunk_end - 1)),
                section=section,
                char_start=chunk_start,
                char_end=chunk_end,
            ))
    return tuple(chunks)


def chunk_pages(pages: list[str], limit: int = 6000) -> list[Chunk]:
    document = _document_from_pages(pages)
    config = ChunkingConfig(limit, 0)
    chunks: list[Chunk] = []
    for page in document.pages:
        for start, end in _chunk_ranges(document.text, page.char_start, page.char_end, config):
            chunks.append(Chunk(
                f"p{page.number}-c{len(chunks) + 1}", document.text[start:end],
                page.number, page.number, None, start, end,
            ))
    return chunks


def process_pdf(data: bytes, config: ChunkingConfig = ChunkingConfig()) -> ProcessedDocument:
    document = extract_pdf(data)
    return ProcessedDocument(document.text, document.pages, tuple(detect_sections(document)), chunk_document(document, config))


def analyze_citations(text: str, current_year: int = 2026) -> dict:
    from .references import analyze_text_references

    return analyze_text_references(text, current_year)


def parse_validated(raw: str, model: type[T]) -> T:
    try:
        return model.model_validate(json.loads(raw.strip().removeprefix("```json").removesuffix("```").strip()))
    except (json.JSONDecodeError, ValidationError) as exc:
        raise ValueError("invalid structured AI response") from exc
