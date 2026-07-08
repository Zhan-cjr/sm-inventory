import os
import json
import re
import mysql.connector
import google.generativeai as genai
from dotenv import load_dotenv
from PIL import Image

load_dotenv()

# Initialize Gemini Model
genai.configure(api_key=os.getenv("GEMINI_API_KEY", ""))
model = genai.GenerativeModel('gemini-3.1-flash-lite')

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "sm_inventory"),
        connection_timeout=10
    )

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

def scan_invoice(image_path: str, supplier_id: str) -> dict:
    try:
        print(f"Scanning invoice image: {image_path}", flush=True)
        with Image.open(image_path) as img:
            prompt = """
You are a professional Data Entry AI specializing in Indonesian retail invoices.
Please read the provided invoice image and extract the tabular data of the purchased items.

Return the result as a valid JSON object with the following schema:
{
  "supplier_name": "String",
  "invoice_number": "String",
  "invoice_date": "YYYY-MM-DD",
  "global_discount": Number,
  "tax": Number,
  "items": [
    {
      "raw_name": "String (exact product name as written on invoice)",
      "qty": Number (quantity),
      "unit_price": Number (price before discount),
      "discount_1": Number (first discount percentage, e.g. 5 for 5%),
      "discount_2": Number (second discount percentage, if any, else 0),
      "discount_3": Number (third discount percentage, if any, else 0),
      "subtotal": Number (total for this row)
    }
  ]
}

CRITICAL RULES:
1. ONLY return the JSON object. Do not add markdown backticks.
2. If there are no discounts, return 0 for discount_1, discount_2, discount_3.
3. Keep the exact raw_name so we can map it.
"""
            response = model.generate_content([prompt, img])
            result_text = response.text
        
        data = extract_json_from_text(result_text)
        
        # Now try to auto-map the items
        if supplier_id and "items" in data:
            conn = get_db_connection()
            cursor = conn.cursor(dictionary=True)
            
            for item in data["items"]:
                raw_name = item.get("raw_name")
                item["product_id"] = None # Default
                if raw_name:
                    # Query mappings table
                    cursor.execute(
                        "SELECT product_id FROM supplier_item_mappings WHERE supplier_id = %s AND raw_name = %s LIMIT 1",
                        (supplier_id, raw_name)
                    )
                    mapping = cursor.fetchone()
                    if mapping:
                        item["product_id"] = mapping["product_id"]
            
            cursor.close()
            conn.close()

        return data

    except Exception as e:
        print(f"Invoice Scan Error: {e}", flush=True)
        return {"error": str(e), "items": []}
