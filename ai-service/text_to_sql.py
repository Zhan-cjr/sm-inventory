import os
import mysql.connector
import google.generativeai as genai
from dotenv import load_dotenv

load_dotenv()

# Configure Google Gemini API Key
genai.configure(api_key=os.getenv("GEMINI_API_KEY", "dummy-key-for-now"))

# Initialize Gemini Model
model = genai.GenerativeModel('gemini-1.5-flash')

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "sm_inventory")
    )

def process_nl_query(query: str):
    """
    1. Send NL query to LLM to generate SQL
    2. Run SQL safely against read-only MySQL replica
    3. Send results back to LLM to generate a human readable answer
    """
    try:
        # NOTE: This is a scaffold for Gemini. 
        # In a real environment, you provide the schema context to generate SQL.
        schema_context = "Table transactions(id, total_amount, transaction_date); Table transaction_items(id, transaction_id, product_id, quantity);"
        
        print(f"Processing query via Gemini: {query}")
        
        # In actual implementation:
        # 1. Ask Gemini to generate SQL based on schema_context + query
        # sql_response = model.generate_content(f"Convert this to SQL based on {schema_context}: {query}")
        # sql_query = extract_sql(sql_response.text)
        
        # 2. Execute SQL
        # data = execute_sql(sql_query)
        
        # 3. Ask Gemini to format the final answer based on data
        # final_response = model.generate_content(f"The user asked: {query}. The data is: {data}. Answer the user naturally in Indonesian.")
        
        return {
            "answer": "Berdasarkan data (Simulasi Gemini), tren penjualan mengalami kenaikan sebesar 15% pada bulan ini.",
            "data": []
        }
    except Exception as e:
        return {"answer": f"Maaf, terjadi kesalahan saat memproses data via Gemini: {str(e)}"}
