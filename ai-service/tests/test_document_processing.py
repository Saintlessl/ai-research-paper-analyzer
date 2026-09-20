from io import BytesIO

import pytest
from pypdf import PdfReader, PdfWriter
from pypdf.errors import FileNotDecryptedError
from reportlab.pdfgen import canvas

from app.services.pipeline import (
    ChunkingConfig,
    DocumentProcessingError,
    chunk_document,
    extract_pdf,
    process_pdf,
)


def make_pdf(*pages: str) -> bytes:
    output = BytesIO()
    pdf = canvas.Canvas(output)
    for text in pages:
        y = 800
        for line in text.splitlines():
            pdf.drawString(72, y, line)
            y -= 20
        pdf.showPage()
    pdf.save()
    return output.getvalue()


def test_extract_pdf_cleans_text_and_preserves_pages_and_boundaries():
    document = extract_pdf(make_pdf("Abstract\nA   useful study.", "Methods\nWe tested it."))

    assert [page.number for page in document.pages] == [1, 2]
    assert document.text == "Abstract\nA useful study.\n\nMethods\nWe tested it."
    assert document.text[document.pages[0].char_start:document.pages[0].char_end] == document.pages[0].text
    assert document.text[document.pages[1].char_start:document.pages[1].char_end] == document.pages[1].text


def test_process_pdf_detects_numbered_sections_and_assigns_chunks():
    result = process_pdf(
        make_pdf(
            "Paper title\nAbstract\nShort summary.",
            "1. Introduction\nAlpha beta gamma delta.\n2 METHODS\nOne two three four five.",
        ),
        ChunkingConfig(max_chars=24, overlap_chars=5),
    )

    assert [section.heading for section in result.sections] == ["Abstract", "Introduction", "METHODS"]
    assert all(chunk.section in {"Abstract", "Introduction", "METHODS"} for chunk in result.chunks)
    assert [chunk.chunk_id for chunk in result.chunks] == [f"chunk-{i:04d}" for i in range(1, len(result.chunks) + 1)]
    for chunk in result.chunks:
        assert result.text[chunk.char_start:chunk.char_end] == chunk.text
        assert chunk.page_start <= chunk.page_end
        assert len(chunk.text) <= 24


def test_chunking_is_deterministic_and_does_not_cross_sections():
    document = extract_pdf(make_pdf("Abstract\nAlpha beta gamma delta epsilon.\nMethods\nOne two three four five six."))
    config = ChunkingConfig(max_chars=20, overlap_chars=4)

    first = chunk_document(document, config)
    second = chunk_document(document, config)

    assert first == second
    assert all("Methods" not in chunk.text for chunk in first if chunk.section == "Abstract")


@pytest.mark.parametrize("payload", [b"not a pdf", b"%PDF-1.4\ntruncated"])
def test_extract_pdf_rejects_malformed_input(payload):
    with pytest.raises(DocumentProcessingError, match="malformed or unreadable"):
        extract_pdf(payload)


def test_extract_pdf_rejects_encrypted_input():
    source = PdfReader(BytesIO(make_pdf("secret")))
    writer = PdfWriter()
    for page in source.pages:
        writer.add_page(page)
    writer.encrypt("password")
    output = BytesIO()
    writer.write(output)

    with pytest.raises(DocumentProcessingError, match="encrypted"):
        extract_pdf(output.getvalue())


def test_extract_pdf_rejects_pdf_without_extractable_text():
    writer = PdfWriter()
    writer.add_blank_page(width=100, height=100)
    output = BytesIO()
    writer.write(output)

    with pytest.raises(DocumentProcessingError, match="no extractable text"):
        extract_pdf(output.getvalue())


def test_chunking_config_rejects_invalid_overlap():
    with pytest.raises(ValueError, match="overlap_chars"):
        ChunkingConfig(max_chars=20, overlap_chars=20)
