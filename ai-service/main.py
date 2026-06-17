from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn
from text_to_sql import process_nl_query
from market_basket import run_market_basket_analysis

app = FastAPI(title="Toserba Selamat AI Service", version="1.0.0")

# Allow CORS for local development
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

class QueryRequest(BaseModel):
    query: str

@app.get("/")
def read_root():
    return {"status": "AI Service is running"}

@app.post("/api/v1/ai/ask")
def ask_ai(request: QueryRequest):
    try:
        # text_to_sql engine converts natural language to sql, runs it, and returns a human readable answer
        response = process_nl_query(request.query)
        return {"success": True, "answer": response["answer"], "data": response.get("data", [])}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/v1/ai/train-market-basket")
def train_market_basket():
    try:
        rules = run_market_basket_analysis()
        return {"success": True, "message": "Market Basket rules generated successfully", "rules_count": len(rules)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
