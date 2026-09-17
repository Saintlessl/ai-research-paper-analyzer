import hmac,json
from typing import Annotated,Any
from fastapi import Depends,FastAPI,Header,HTTPException,Request
from fastapi.responses import JSONResponse
from .settings import settings
from .schemas import *
from .services.gemini import GeminiProvider,Provider
from .services.pipeline import parse_validated
app=FastAPI(title='Research Paper AI Service')
def authorize(authorization:Annotated[str|None,Header()]=None):
 expected='Bearer '+settings().internal_token
 if not authorization or not hmac.compare_digest(authorization,expected): raise HTTPException(401,'Invalid internal service token')
def get_provider()->Provider: return GeminiProvider(settings().gemini_api_key,settings().gemini_model)
SYSTEM='You are an academic research analyst. Use only supplied context. Never invent missing information. Include evidence and return only JSON matching the requested schema.'
def execute(request_id,kind,text,model,provider):
 prompt=f'{SYSTEM}\nOperation: {kind}\nSchema: {json.dumps(model.model_json_schema())}\nContext: {text}'
 last=None
 for attempt in range(settings().repair_attempts+1):
  try: return {'success':True,'request_id':str(request_id),'data':parse_validated(provider.generate(prompt if attempt==0 else prompt+'\nRepair the prior invalid response.'),model).model_dump(mode='json')}
  except (ValueError,RuntimeError) as exc: last=exc
 raise HTTPException(502,detail={'code':'AI_PROCESSING_FAILED','message':str(last)})
@app.exception_handler(HTTPException)
def errors(request:Request,exc:HTTPException):
 detail=exc.detail if isinstance(exc.detail,dict) else {'code':'UNAUTHORIZED' if exc.status_code==401 else 'REQUEST_FAILED','message':str(exc.detail)}
 return JSONResponse(status_code=exc.status_code,content={'success':False,'request_id':request.headers.get('X-Request-ID'),'error':detail})
@app.get('/health')
def health(): return {'status':'ok'}
@app.post('/api/v1/analyze',dependencies=[Depends(authorize)])
def analyze(body:AnalyzeRequest,provider:Provider=Depends(get_provider)): return execute(body.request_id,'analysis',body.text,AnalysisData,provider)
@app.post('/api/v1/review',dependencies=[Depends(authorize)])
def review(body:TextRequest,provider:Provider=Depends(get_provider)): return execute(body.request_id,'review',body.text,ReviewData,provider)
@app.post('/api/v1/qa',dependencies=[Depends(authorize)])
def qa(body:QARequest,provider:Provider=Depends(get_provider)): return execute(body.request_id,'qa: '+body.question,body.text,QAData,provider)
@app.post('/api/v1/compare',dependencies=[Depends(authorize)])
def compare(body:CompareRequest,provider:Provider=Depends(get_provider)): return execute(body.request_id,'comparison',f'PAPER A:\n{body.paper_a.text}\nPAPER B:\n{body.paper_b.text}',CompareData,provider)
