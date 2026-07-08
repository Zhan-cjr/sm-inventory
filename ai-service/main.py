from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import uvicorn
from text_to_sql import process_nl_query
from market_basket import run_market_basket_analysis
from restock_predictor import predict_restock_needs
from pricing_engine import suggest_dynamic_pricing

app = FastAPI(title="Toserba Selamat AI Service", version="1.0.0")

# Allow CORS for local development
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

from typing import Optional

class QueryRequest(BaseModel):
    question: str
    branch_id: Optional[str] = None
    chat_history: Optional[list] = None

@app.get("/")
def read_root():
    return {"status": "AI Service is running"}

@app.post("/api/v1/ai/ask")
def ask_ai(request: QueryRequest):
    try:
        # text_to_sql engine converts natural language to sql, runs it, and returns a human readable answer
        response = process_nl_query(request.question, request.branch_id, request.chat_history)
        return {"response": response["answer"], "data": response.get("data", [])}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

from invoice_scanner import scan_invoice
import shutil
import os
from fastapi import UploadFile, File, Form

@app.post("/api/v1/ai/scan-invoice")
async def api_scan_invoice(
    file: UploadFile = File(...),
    supplier_id: Optional[str] = Form(None)
):
    try:
        # Save temp file
        temp_file_path = f"temp_{file.filename}"
        with open(temp_file_path, "wb") as buffer:
            shutil.copyfileobj(file.file, buffer)
            
        result = scan_invoice(temp_file_path, supplier_id)
        
        # Cleanup
        try:
            if os.path.exists(temp_file_path):
                os.remove(temp_file_path)
        except Exception as e:
            print(f"Warning: Failed to cleanup {temp_file_path}: {e}")
            
        return result
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/api/v1/ai/train-market-basket")
def train_market_basket():
    try:
        rules = run_market_basket_analysis()
        return {"success": True, "message": "Market Basket rules generated successfully", "rules_count": len(rules)}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/api/v1/ai/restock-suggestions")
def restock_suggestions(days_history: int = 30, target_days_supply: int = 30, branch_id: str = None):
    try:
        suggestions = predict_restock_needs(days_history=days_history, target_days_supply=target_days_supply, branch_id=branch_id)
        return {"success": True, "data": suggestions}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.get("/api/v1/ai/dynamic-pricing")
def dynamic_pricing(days_history: int = 30):
    try:
        suggestions = suggest_dynamic_pricing(days_history=days_history)
        return {"success": True, "data": suggestions}
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    uvicorn.run("main:app", host="0.0.0.0", port=8001, reload=True)
