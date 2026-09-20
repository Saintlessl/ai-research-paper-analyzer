from app.services.pipeline import _document_from_pages, chunk_document
from app.services.references import analyze_references


def test_numeric_references_are_normalized_and_grounded():
    document = _document_from_pages([
        "Introduction\nPrior work [1, 3] and [2-3] supports this.",
        "References\n[1] Smith, J. Useful Study. 2024. https://doi.org/10.1000/xyz\n"
        "[2] Doe, A. Older Study. 2018. https://example.org/paper\n"
        "[4] Roe, B. Unrelated title. 1999.",
    ])
    result = analyze_references(document, chunk_document(document), recent_year_cutoff=2021)

    assert result["total_references"] == 3
    assert result["publication_years"] == [1999, 2018, 2024]
    assert result["recent_count"] == 1
    assert result["older_count"] == 2
    assert result["citation_patterns"]["numeric_bracket"] == 2
    assert result["in_text_citations_missing_from_bibliography"] == ["3"]
    assert result["bibliography_entries_apparently_uncited"] == ["4"]
    assert result["references"][0]["doi"] == "10.1000/xyz"
    assert result["references"][0]["citation_count"] == 1
    assert result["references"][0]["evidence"][0]["page"] == 2
    assert result["references"][0]["evidence"][0]["section"].lower() == "references"
    assert result["method"] == "deterministic_heuristic"


def test_author_year_patterns_and_missing_reference_are_reported():
    document = _document_from_pages([
        "Introduction\nSmith (2020) agrees with (Jones & Lee, 2022).",
        "References\nSmith, J. (2020). Existing work.",
    ])
    result = analyze_references(document, chunk_document(document), recent_year_cutoff=2021)

    assert result["citation_patterns"]["author_year"] == 2
    assert result["in_text_citations_missing_from_bibliography"] == ["jones-2022"]
    assert result["references"][0]["citation_key"] == "smith-2020"


def test_bibliography_heading_is_supported():
    document = _document_from_pages(["Introduction\nEvidence [1].\nBibliography\n[1] Smith, J. Study. 2024."])
    result = analyze_references(document, chunk_document(document), recent_year_cutoff=2021)

    assert result["total_references"] == 1
    assert result["references"][0]["evidence"][0]["section"].lower() == "bibliography"
