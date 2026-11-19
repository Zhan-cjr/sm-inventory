import pandas as pd
import mysql.connector
import os
import requests
from dotenv import load_dotenv

load_dotenv()

def get_db_connection():
    return mysql.connector.connect(
        host=os.getenv("DB_HOST", "localhost"),
        user=os.getenv("DB_USER", "root"),
        password=os.getenv("DB_PASSWORD", ""),
        database=os.getenv("DB_NAME", "sm_inventory")
    )

def suggest_dynamic_pricing(days_history=30):
    """
    Analyzes products with high stock and low velocity to suggest flash sales.
    Phase 1 Rule: 
    - stock > (safety_stock * 3) OR stock > 100 if safety_stock is 0
    - sales in last 30 days is very low.
    """
    conn = get_db_connection()
    try:
        # Get sales data (safe integer days_history)
        days_int = max(1, min(365, int(days_history)))
        query_sales = f"""
            SELECT 
                ti.product_id,
                t.branch_id,
                SUM(ti.quantity) as sold_30d
            FROM transaction_items ti
            JOIN transactions t ON t.id = ti.transaction_id
            WHERE t.transaction_date >= DATE_SUB(NOW(), INTERVAL {days_int} DAY)
              AND t.is_voided = 0
            GROUP BY ti.product_id, t.branch_id
        """
        sales_df = pd.read_sql(query_sales, conn)

        # Get active stocks
        query_stocks = """
            SELECT 
                s.id as stock_id,
                s.branch_id,
                s.product_id,
                p.name as product_name,
                p.sku,
                s.quantity_on_hand,
                s.safety_stock,
                s.cost_price,
                s.selling_price
            FROM stocks s
            JOIN products p ON p.id = s.product_id
            WHERE s.is_active = 1 AND p.is_active = 1
        """
        stocks_df = pd.read_sql(query_stocks, conn)
        if stocks_df.empty:
            return []

        # Vectorized merge O(N + M) instead of O(N * M) iterrows
        merged = stocks_df.merge(sales_df, on=['product_id', 'branch_id'], how='left')
        merged['sold_30d'] = merged['sold_30d'].fillna(0.0).astype(float)
        merged['quantity_on_hand'] = merged['quantity_on_hand'].fillna(0.0).astype(float)
        merged['safety_stock'] = merged['safety_stock'].fillna(0.0).astype(float)
        merged['cost_price'] = merged['cost_price'].fillna(0.0).astype(float)
        merged['selling_price'] = merged['selling_price'].fillna(0.0).astype(float)

        daily_velocity = merged['sold_30d'] / float(days_int)
        overstock_threshold = (merged['safety_stock'] * 3.0).clip(lower=50.0)
        mask = (merged['quantity_on_hand'] > overstock_threshold) & (daily_velocity < (merged['quantity_on_hand'] / 120.0))

        candidates = merged.loc[mask]
        suggestions = []

        for row in candidates.itertuples(index=False):
            cost_price = float(row.cost_price)
            selling_price = float(row.selling_price)
            discount_amount = selling_price * 0.20
            final_price = selling_price - discount_amount

            if final_price < cost_price:
                final_price = cost_price * 1.05
                discount_amount = selling_price - final_price
                if discount_amount <= 0:
                    continue

            suggestions.append({
                "branch_id": row.branch_id,
                "product_id": row.product_id,
                "sku": row.sku,
                "product_name": row.product_name,
                "current_stock": float(row.quantity_on_hand),
                "sold_30d": float(row.sold_30d),
                "suggested_discount_amount": float(discount_amount),
                "original_price": selling_price,
                "final_price": float(final_price)
            })

        return suggestions

    except Exception as e:
        print(f"Error in dynamic pricing engine: {e}")
        return []
    finally:
        conn.close()

if __name__ == "__main__":
    print(suggest_dynamic_pricing())
