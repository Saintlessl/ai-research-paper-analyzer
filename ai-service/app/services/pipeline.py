import json,re
from dataclasses import dataclass
from typing import TypeVar
from pydantic import BaseModel,ValidationError
T=TypeVar('T',bound=BaseModel)
@dataclass
class Section: heading:str; content:str; page:int
@dataclass
class Chunk: chunk_id:str; text:str; page_start:int; page_end:int; section:str|None=None
HEADINGS={'abstract','introduction','methods','methodology','results','discussion','conclusion','references','limitations'}
def clean_text(text:str)->str: return re.sub(r'\s+',' ',text).strip()
def detect_sections(pages:list[str])->list[Section]:
 out=[]; current=None; content=[]; page=1
 for n,raw in enumerate(pages,1):
  for line in raw.splitlines():
   normalized=line.strip().lower().rstrip(':')
   if normalized in HEADINGS:
    if current: out.append(Section(current,' '.join(content).strip(),page))
    current=line.strip(); content=[]; page=n
   elif current: content.append(line.strip())
 if current: out.append(Section(current,' '.join(content).strip(),page))
 return out
def chunk_pages(pages:list[str],limit:int=6000)->list[Chunk]:
 out=[]
 for page,text in enumerate(pages,1):
  cleaned=clean_text(text)
  for start in range(0,len(cleaned),limit): out.append(Chunk(f'p{page}-c{start//limit+1}',cleaned[start:start+limit],page,page))
 return out
def extract_pdf(data:bytes)->list[str]:
 from io import BytesIO
 from pypdf import PdfReader
 pages=[p.extract_text() or '' for p in PdfReader(BytesIO(data)).pages]
 if not any(p.strip() for p in pages): raise ValueError('PDF contains no extractable text')
 return pages
def analyze_citations(text:str)->dict:
 bibliography=text.split('References',1)[-1] if 'References' in text else ''
 refs=[line for line in bibliography.splitlines() if re.match(r'^\s*\[?\d+\]?[.)]?',line) and line.strip()]
 cited=set(re.findall(r'\[(\d+)\]',text.split('References',1)[0]))
 keys={re.search(r'\d+',r).group() for r in refs if re.search(r'\d+',r)}
 years=[int(y) for y in re.findall(r'\b(?:19|20)\d{2}\b',bibliography)]
 return {'total_references':len(refs),'publication_years':years,'citations_missing_from_bibliography':sorted(cited-keys),'apparently_uncited_references':sorted(keys-cited),'method':'deterministic_heuristic'}
def parse_validated(raw:str,model:type[T])->T:
 try: return model.model_validate(json.loads(raw.strip().removeprefix('```json').removesuffix('```').strip()))
 except (json.JSONDecodeError,ValidationError) as exc: raise ValueError('invalid structured AI response') from exc
