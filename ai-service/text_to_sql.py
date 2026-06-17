import os
import re
import mysql.connector
import google.generativeai as genai
from dotenv import load_dotenv

load_dotenv()

# Configure Google Gemini API Key
genai.configure(api_key=os.getenv("GEMINI_API_KEY", ""))

# Initialize Gemini Model
model = genai.GenerativeModel('gemini-pro')

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "sm_inventory")
    )

def get_db_schema():
    """Fetch all tables and their columns to build context for the LLM."""
    try:
        conn = get_db_connection()
        cursor = conn.cursor()
        cursor.execute("SHOW TABLES")
        tables = cursor.fetchall()
        
        schema_text = ""
        for table in tables:
            table_name = table[0]
            cursor.execute(f"DESCRIBE {table_name}")
            columns = cursor.fetchall()
            col_details = ", ".join([f"{col[0]} ({col[1]})" for col in columns])
            schema_text += f"Table '{table_name}' has columns: {col_details}.\n"
            
        cursor.close()
        conn.close()
        return schema_text
    except Exception as e:
        print(f"Error fetching schema: {e}")
        return ""

def clean_sql(raw_sql: str):
    """Clean markdown backticks from LLM output."""
    raw_sql = raw_sql.strip()
    if raw_sql.startswith("```"):
        # Extract content between backticks
        match = re.search(r'```(?:sql)?\s*(.*?)\s*```', raw_sql, re.DOTALL | re.IGNORECASE)
        if match:
            raw_sql = match.group(1)
        else:
            raw_sql = raw_sql.replace("```sql", "").replace("```", "").strip()
    return raw_sql

def process_nl_query(query: str):
    try:
        print(f"Processing query via Gemini: {query}")
        
        # 1. Get database schema
        schema_context = get_db_schema()
        if not schema_context:
            return {"answer": "Maaf, tidak dapat terhubung ke database untuk membaca skema saat ini."}

        # 2. Ask Gemini to generate SQL
        prompt_sql = f"""
You are an expert MySQL database analyst. 
Here is the schema of the database:
{schema_context}

The user asked: "{query}"

Write a valid MySQL query to accurately answer the user's question. 
RULES:
1. ONLY return the raw SQL query. 
2. DO NOT wrap it in markdown backticks. 
3. DO NOT provide any explanation.
4. The query must be READ-ONLY (SELECT statements only).
5. Ensure the table and column names exactly match the schema provided.
"""
        sql_response = model.generate_content(prompt_sql)
        sql_query = clean_sql(sql_response.text)
        print(f"Generated SQL: {sql_query}")

        # Basic security check
        if not sql_query.upper().startswith("SELECT"):
            return {"answer": "Maaf, untuk alasan keamanan, AI hanya diizinkan untuk membaca data (SELECT)."}

        # 3. Execute SQL against database
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(sql_query)
        data = cursor.fetchall()
        cursor.close()
        conn.close()

        # 4. Ask Gemini to format the final answer
        prompt_answer = f"""
You are an intelligent, friendly Smart Assistant for a retail/inventory system (developed by Amnal).
The user asked: "{query}"
The database returned the following raw data: {data}

Please formulate a natural, easy-to-understand, and professional answer in Indonesian based on the data.
DO NOT mention the SQL query. If the data is empty ([]), inform the user nicely that there is no data matching their request.
Keep the answer concise but informative.
"""
        final_response = model.generate_content(prompt_answer)
        
        return {
            "answer": final_response.text,
            "data": data,
            "sql_executed": sql_query
        }
        
    except mysql.connector.Error as db_err:
        return {"answer": f"Terjadi kesalahan saat mencari data di database (Mungkin AI salah menulis query): {str(db_err)}"}
    except Exception as e:
        return {"answer": f"Maaf, terjadi kesalahan internal sistem AI: {str(e)}"}
