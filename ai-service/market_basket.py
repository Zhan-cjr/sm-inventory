import pandas as pd
from mlxtend.frequent_patterns import apriori, association_rules
import mysql.connector
import os
import uuid
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
        # Fetch actual transactions and product names
        query = """
            SELECT 
                ti.transaction_id, 
                ti.product_id, 
                p.name as product_name, 
                ti.quantity 
            FROM transaction_items ti
            JOIN transactions t ON t.id = ti.transaction_id
            JOIN products p ON p.id = ti.product_id
            WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        """
        
        df = pd.read_sql(query, conn)
        
        if df.empty:
            return []
            
        # Create a mapping for product_id to product_name to use later
        product_map = df[['product_id', 'product_name']].drop_duplicates().set_index('product_id')['product_name'].to_dict()

        # Group by transaction_id and product_id
        basket = (df.groupby(['transaction_id', 'product_id'])['quantity']
                  .sum().unstack().reset_index().fillna(0)
                  .set_index('transaction_id'))
                  
        # Convert quantities to booleans (True if > 0, else False)
        # This handles NaN safely and mlxtend requires boolean dataframes
        basket_sets = basket > 0
        
        # We need at least some transactions to find patterns
        if len(basket_sets) < 2:
            return []

        frequent_itemsets = apriori(basket_sets, min_support=0.01, use_colnames=True)
        
        if frequent_itemsets.empty:
            return []
            
        rules = association_rules(frequent_itemsets, metric="lift", min_threshold=1.0)
        
        # Sort by confidence and lift
        rules = rules.sort_values(['confidence', 'lift'], ascending=[False, False])
        
        # Take top 50 rules to avoid cluttering
        rules = rules.head(50)
        
        # Prepare for DB insert
        cursor = conn.cursor()
        
        # Truncate old rules
        cursor.execute("DELETE FROM market_basket_rules")
        
        # Insert new rules
        insert_query = """
            INSERT INTO market_basket_rules 
            (id, antecedent_id, consequent_id, antecedent_name, consequent_name, support, confidence, lift, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, NOW(), NOW())
        """
        
        rules_inserted = []
        for index, row in rules.iterrows():
            # We only handle 1-to-1 rules for simplicity in the UI
            antecedents = list(row['antecedents'])
            consequents = list(row['consequents'])
            
            if len(antecedents) == 1 and len(consequents) == 1:
                ant_id = antecedents[0]
                con_id = consequents[0]
                
                ant_name = product_map.get(ant_id, "Unknown")
                con_name = product_map.get(con_id, "Unknown")
                
                cursor.execute(insert_query, (
                    str(uuid.uuid4()),
                    ant_id,
                    con_id,
                    ant_name,
                    con_name,
                    float(row['support']),
                    float(row['confidence']),
                    float(row['lift'])
                ))
                
                rules_inserted.append({
                    "antecedents": ant_name,
                    "consequents": con_name,
                    "confidence": float(row['confidence'])
                })
        
        conn.commit()
        return rules_inserted
        
    except Exception as e:
        print(f"Error in MBA: {str(e)}")
        raise e
    finally:
        conn.close()
