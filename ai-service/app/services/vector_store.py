import logging
import uuid
import chromadb
from chromadb.config import Settings
from .pipeline import Chunk
from .gemini import GeminiProvider

logger = logging.getLogger(__name__)

# Initialize ChromaDB Persistent Client
# This saves the vector database to the local ./data folder
try:
    chroma_client = chromadb.PersistentClient(path="./data", settings=Settings(anonymized_telemetry=False))
    # We use a single collection for all papers, filtering by paper_id metadata.
    collection = chroma_client.get_or_create_collection(name="papers")
except Exception as exc:
    logger.error(f"Failed to initialize ChromaDB: {exc}")
    collection = None

def store_paper_chunks(paper_id: int, chunks: list[Chunk], provider: GeminiProvider) -> None:
    if not collection:
        logger.error("ChromaDB collection is not available.")
        return

    if not chunks:
        return
        
    texts = [chunk.text for chunk in chunks]
    
    # Generate embeddings using Gemini
    embeddings = provider.embed_content(texts)
    
    # Prepare IDs and Metadata
    ids = [f"{paper_id}_{chunk.chunk_id}" for chunk in chunks]
    metadatas = [
        {
            "paper_id": paper_id,
            "chunk_id": chunk.chunk_id,
            "page_start": chunk.page_start,
            "page_end": chunk.page_end,
            "section": chunk.section if chunk.section else "unknown",
        }
        for chunk in chunks
    ]
    
    # Store in ChromaDB
    collection.add(
        embeddings=embeddings,
        documents=texts,
        metadatas=metadatas,
        ids=ids
    )
    logger.info(f"Stored {len(chunks)} chunks in ChromaDB for paper_id={paper_id}")

def search_relevant_chunks(paper_id: int, query: str, provider: GeminiProvider, top_k: int = 5) -> str:
    if not collection:
        logger.error("ChromaDB collection is not available.")
        return ""
        
    # Check if paper exists first
    existing = collection.get(where={"paper_id": paper_id}, limit=1)
    if not existing or not existing.get("ids"):
        raise ValueError("PAPER_NOT_READY")

    # Generate embedding for the query
    query_embedding = provider.embed_content([query])[0]
    
    # Search ChromaDB with exact filter on paper_id
    results = collection.query(
        query_embeddings=[query_embedding],
        n_results=top_k,
        where={"paper_id": paper_id}
    )
    
    if not results or not results["documents"] or not results["documents"][0]:
        return ""
        
    # Reconstruct context
    context_chunks = []
    for doc, meta in zip(results["documents"][0], results["metadatas"][0]):
        context_chunks.append(f"[page {meta.get('page_start', '?')} | section: {meta.get('section', 'unknown')}]\n{doc}")
        
    return "\n\n".join(context_chunks)

def delete_paper_vectors(paper_id: int) -> None:
    if not collection:
        return
    try:
        collection.delete(where={"paper_id": paper_id})
        logger.info(f"Deleted vectors for paper_id={paper_id}")
    except Exception as exc:
        logger.error(f"Failed to delete vectors for paper_id={paper_id}: {exc}")
