from typing import Any, Literal
from uuid import UUID
from pydantic import BaseModel, Field
class Evidence(BaseModel): page:int|None=None; section:str|None=None; chunk_id:str|None=None; excerpt:str=Field(max_length=1000); confidence:float|None=Field(default=None,ge=0,le=1)
class Score(BaseModel): criterion:str; score:int=Field(ge=0,le=100); reason:str=Field(min_length=1); evidence:list[Evidence]=[]
class Finding(BaseModel): severity:Literal['LOW','MEDIUM','HIGH','CRITICAL']; category:str; finding:str; explanation:str|None=None; evidence:list[Evidence]=[]; confidence:float|None=Field(default=None,ge=0,le=1)
class AnalysisData(BaseModel): classification:dict[str,Any]; structure:dict[str,Any]; methodology:dict[str,Any]; scores:list[Score]; findings:list[Finding]; keywords:list[str]; citation_analysis:dict[str,Any]
class ReviewData(BaseModel): summary:str; strengths:list[str]; major_concerns:list[str]; minor_concerns:list[str]; methodology_review:str; novelty_review:str; results_review:str; reproducibility_review:str; recommendation:Literal['ACCEPT','MINOR_REVISION','MAJOR_REVISION','REJECT']; recommendation_reason:str; evidence:list[Evidence]
class QAData(BaseModel): answer:str; found:bool; evidence:list[Evidence]
class CompareData(BaseModel): dimensions:list[dict[str,Any]]; conclusion:str; reasoning:str; evidence:list[Evidence]
class Metadata(BaseModel): title:str; authors:list[str]
class AnalyzeRequest(BaseModel): request_id:UUID; paper_id:int; text:str=Field(min_length=1); metadata:Metadata
class TextRequest(BaseModel): request_id:UUID; paper_id:int; text:str=Field(min_length=1)
class QARequest(TextRequest): question:str=Field(min_length=1)
class ComparePaper(BaseModel): paper_id:int; text:str=Field(min_length=1)
class CompareRequest(BaseModel): request_id:UUID; paper_a:ComparePaper; paper_b:ComparePaper
