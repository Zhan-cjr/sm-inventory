import os
import json
import re
import google.generativeai as genai
from dotenv import load_dotenv
from PIL import Image

load_dotenv()

# Initialize Gemini Model
api_key = os.getenv("GEMINI_API_KEY") or os.getenv("GOOGLE_API_KEY") or ""
if api_key:
    genai.configure(api_key=api_key)
model_name = os.getenv("GEMINI_MODEL", "gemini-3.1-flash-lite")
model = genai.GenerativeModel(model_name)

def extract_json_from_text(text: str) -> dict:
    text = text.strip()
    if text.startswith("```"):
        match = re.search(r'```(?:json)?\s*(.*?)\s*```', text, re.DOTALL | re.IGNORECASE)
        if match:
            text = match.group(1)
    
    try:
        return json.loads(text)
    except Exception as e:
        print(f"Error parsing JSON: {e}")
        return {"items": []}

def scan_listing_document(file_path: str) -> dict:
    try:
        print(f"Scanning product listing document: {file_path}", flush=True)
        with Image.open(file_path) as img:
            prompt = """
You are a professional AI Data Entry specialist for Indonesian retail inventory systems.
Analyze the provided document (Formulir Registrasi Produk / New Product Listing Form).

Extract:
1. Header metadata:
   - "supplier_name": String (Company / Distributor name, e.g. "PT SAYAP MAS UTAMA")
   - "listing_number": String (Document / Reference Number, e.g. "194 / DT / FL / 2026")
   - "listing_fee": Number (Total listing fee amount, 0 if free / dash)
   - "notes": String (Payment terms, trial notes)
2. Table rows under "PRODUK YANG DIUSULKAN" / product table:
   For EACH product row, extract:
   - "barcode": String (Digits of No. Barcode / EAN / PLU, e.g. "8998866205658")
   - "name": String (Exact product name, e.g. "GOLDA BTL 200ML AREN LATTE MT")
   - "unit_price": Number (HARGA SATUAN / DPP for single piece, e.g. 3050. CRITICAL: This must be the SINGLE unit price, NOT carton price)
   - "carton_price": Number or null (Harga per karton, e.g. 36600)
   - "pack_qty": Number or null (Isi per karton, e.g. 12)
   - "unit_of_measure": String (e.g. "pcs", "btl", "sct", "bag", default to "pcs")

CRITICAL RULES:
1. Return ONLY a valid JSON object matching the schema below without markdown backticks:
{
  "supplier_name": "...",
  "listing_number": "...",
  "listing_fee": 0,
  "notes": "...",
  "items": [
    {
      "barcode": "8998866205658",
      "name": "GOLDA BTL 200ML AREN LATTE MT",
      "unit_price": 3050,
      "carton_price": 36600,
      "pack_qty": 12,
      "unit_of_measure": "pcs"
    }
  ]
}
2. Ensure thousand separators (e.g. 3.050 or 36.600) are converted to standard numbers (3050, 36600).
"""
            response = model.generate_content([prompt, img])
            result_text = response.text
        
        data = extract_json_from_text(result_text)
        return data
    except Exception as e:
        print(f"Error in scan_listing_document: {e}", flush=True)
        return {"error": str(e), "items": []}

