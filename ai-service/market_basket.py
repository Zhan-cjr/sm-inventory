import pandas as pd
from mlxtend.frequent_patterns import apriori, association_rules
import mysql.connector
import os
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "sm_inventory")
    )

def run_market_basket_analysis():
    """
    1. Fetch recent transactions from MySQL
    2. Convert to basket format
    3. Run Apriori
    4. Save rules back to database
    """
    conn = get_db_connection()
    try:
        # Example Query (In real implementation you'd fetch real data)
        query = """
            SELECT transaction_id, product_id 
            FROM transaction_items 
            JOIN transactions t ON t.id = transaction_items.transaction_id
            WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        """
        
        # NOTE: This is a placeholder for the actual apriori execution.
        # df = pd.read_sql(query, conn)
        # 
        # basket = (df.groupby(['transaction_id', 'product_id'])['quantity']
        #           .sum().unstack().reset_index().fillna(0)
        #           .set_index('transaction_id'))
        # basket_sets = basket.applymap(lambda x: 1 if x > 0 else 0)
        # 
        # frequent_itemsets = apriori(basket_sets, min_support=0.01, use_colnames=True)
        # rules = association_rules(frequent_itemsets, metric="lift", min_threshold=1)
        
        # Placeholder rules returned
        return [
            {"antecedents": "Roti Tawar", "consequents": "Selai Kacang", "confidence": 0.85},
            {"antecedents": "Kopi", "consequents": "Gula", "confidence": 0.90}
        ]
        
    finally:
        conn.close()
