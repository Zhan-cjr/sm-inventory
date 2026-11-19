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

        # Pre-filter infrequent products BEFORE pivoting to avoid allocating a huge dense matrix (OOM risk)
        total_tx = df['transaction_id'].nunique()
        if total_tx < 3:
            return []

        min_support_val = max(3.0 / total_tx, 0.002)
        min_tx_count = max(3, int(total_tx * min_support_val))

        tx_per_product = df.groupby('product_id')['transaction_id'].nunique()
        frequent_products = tx_per_product[tx_per_product >= min_tx_count].index
        if len(frequent_products) < 2:
            return []

        df_filtered = df[df['product_id'].isin(frequent_products)]

        # Group by transaction_id and product_id
        basket_sets = (
            df_filtered.groupby(['transaction_id', 'product_id'])['quantity']
            .sum()
            .unstack(fill_value=0)
            .gt(0)
        )

        frequent_itemsets = apriori(
            basket_sets,
            min_support=min_support_val,
            use_colnames=True,
            low_memory=True,
            max_len=2,
        )
        
        if frequent_itemsets.empty:
            return []
            
        rules = association_rules(frequent_itemsets, metric="confidence", min_threshold=0.25)
        rules = rules[(rules['lift'] >= 1.15) & (rules['lift'] <= 50.0)]
        
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
