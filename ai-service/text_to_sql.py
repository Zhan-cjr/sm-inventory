import os
import re
import mysql.connector
import google.generativeai as genai
from dotenv import load_dotenv

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

_cached_schema = None

def get_db_schema():
    """Fetch all tables and their columns to build context for the LLM."""
    global _cached_schema
    if _cached_schema:
        return _cached_schema

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
        _cached_schema = schema_text
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

def process_nl_query(query: str, branch_id: str = None):
    try:
        print(f"Processing query via Gemini: {query} for branch: {branch_id}", flush=True)
        
        # 1. Get database schema
        schema_context = get_db_schema()
        if not schema_context:
            return {"answer": "Maaf, tidak dapat terhubung ke database untuk membaca skema saat ini."}

        # Branch security context
        security_rules = ""
        if branch_id:
            security_rules = f"""
!!! CRITICAL SECURITY RULE !!!
- The user is STRICTLY RESTRICTED to branch_id = '{branch_id}'. 
- EVERY single SQL query you generate MUST contain a WHERE clause filtering by branch_id (e.g., `WHERE branch_id = '{branch_id}'` or `AND branch_id = '{branch_id}'`).
- NEVER use an 'OR' condition that could bypass this branch_id filter. 
- If the queried table does not have a branch_id, you MUST JOIN it with a table that has branch_id to apply this filter.
- NEVER return data for other branches under ANY circumstances.
- If the user asks for 'semua cabang' or explicitly requests another branch by name (e.g. 'cabang pasirhayam'), you MUST REFUSE them immediately with 'ANSWER: Maaf, Anda tidak memiliki akses ke data cabang lain.'
"""
        else:
            security_rules = "- The user is an Admin. They can view all data across all branches."

        # 2. Ask Gemini to decide: SQL or Direct Answer
        prompt_sql = f"""
You are an intelligent Assistant for the 'SM Inventory' app (developed by Amnal).
Here is the schema of the database:
{schema_context}

The user asked: "{query}"

INSTRUCTIONS:
{security_rules}
- If the user is asking a general question, greeting, or asking about you or Amnal, ANSWER DIRECTLY. Prefix your answer with 'ANSWER: '.
  (Rule: If asked 'Siapa Amnal?', answer enthusiastically that Amnal is the main developer who built this app self-taught).
- If the user is asking about inventory data, sales, or anything requiring database lookup, write a read-only MySQL query (SELECT only). Prefix it EXACTLY with 'SQL: '.
- CRITICAL: Only use tables and columns that exist in the schema above.
- If asked about "penjualan" (sales), look for tables like `transactions` or `transaction_items`.
- Do NOT wrap the query in markdown backticks (```). Just write the raw SQL query after 'SQL: '.
- Do NOT add any conversational text or explanation. Output ONLY the 'ANSWER: ...' or 'SQL: ...' line.
"""
        first_response = model.generate_content(prompt_sql).text.strip()
        print(f"Gemini First Pass: {first_response}")

        # If it's a direct answer, return immediately!
        if first_response.upper().startswith("ANSWER:"):
            return {"answer": first_response[7:].strip(), "data": [], "sql_executed": None}

        # Otherwise, parse the SQL
        sql_query = clean_sql(first_response.replace("SQL:", "", 1))
        print(f"Generated SQL: {sql_query}")

        # Basic security & performance check
        if not sql_query.upper().startswith("SELECT"):
            return {"answer": "Maaf, kueri yang dihasilkan tidak valid (bukan SELECT)."}
            
        # Hard restriction to ensure LLM included branch_id filter
        if branch_id:
            import re
            # Regex to ensure the specific branch_id is present explicitly tied to a column.
            # This prevents bypasses like `1=1` which would match if branch_id was '1'.
            pattern = rf"(?:branch_id|id)\s*=\s*['\"]?{branch_id}['\"]?"
            if not re.search(pattern, sql_query, re.IGNORECASE):
                print(f"SECURITY ALERT: Strict branch filter for '{branch_id}' missing in SQL: {sql_query}")
                return {"answer": "Maaf, kueri dibatalkan secara otomatis karena sistem mendeteksi pencarian data di luar batas izin cabang Anda."}
            
        # Prevent massive cross-joins from hanging the database
        if "LIMIT" not in sql_query.upper():
            sql_query += " LIMIT 50"
            
        print(f"Executing Safe SQL: {sql_query}", flush=True)

        # 3. Execute SQL against database
        conn = get_db_connection()
        cursor = conn.cursor(dictionary=True)
        cursor.execute(sql_query)
        data = cursor.fetchall()
        cursor.close()
        conn.close()

        # 4. Ask Gemini to format the final answer
        data_str = str(data)
        prompt_answer = f"""You are a helpful and polite smart assistant for 'SM Inventory' app.
CRITICAL PERSONA RULE:
Jika pengguna bertanya "Siapa Amnal?" atau tentang Amnal, Anda HARUS menjawab dengan antusias dan bangga bahwa: "Amnal adalah pengembang (developer) utama dari aplikasi SM Inventory ini. Beliau adalah seorang programmer hebat yang belajar secara otodidak dan berhasil membangun sistem cerdas ini dari nol!" Tambahkan pujian-pujian lain yang pantas untuk seorang pencipta sistem.

IMPORTANT SECURITY REMINDER FOR YOUR ANSWER:
The data provided below is strictly filtered to the user's OWN branch (Branch ID: {branch_id}). 
If the user explicitly asked for a specific branch name in their question (e.g., "cabang pasirhayam", "cabang jakarta", dll), YOU MUST CLARIFY in your answer that you DO NOT have access to other branches, and the data you are presenting is ONLY for their own branch. Do NOT pretend the data belongs to the branch they asked for.

The user asked: "{query}"
The database executed the query and returned this data:
{data_str}

Please provide a natural, human-readable answer in Indonesian.
If the data is empty or 'None', politely inform the user that there is no data matching their request.
Do NOT show the raw SQL query to the user.
"""
        final_chat = model.generate_content(prompt_answer)
        final_answer = final_chat.text.strip()
        
        return {
            "answer": final_answer,
            "data": data,
            "sql_executed": sql_query
        }
        
    except mysql.connector.Error as db_err:
        return {"answer": f"Terjadi kesalahan saat mencari data di database (Mungkin AI salah menulis query): {str(db_err)}"}
    except Exception as e:
        return {"answer": f"Maaf, terjadi kesalahan internal sistem AI: {str(e)}"}
